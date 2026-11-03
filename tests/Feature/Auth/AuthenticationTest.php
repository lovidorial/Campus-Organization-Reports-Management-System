<?php

namespace Tests\Feature\Auth;

use App\Models\Gpoa;
use App\Models\Organization;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $this->assertMatchesRegularExpression('/^[A-HJ-KM-NP-Z2-9]{5}$/', session('login_captcha'));
        $response->assertSee('Refresh code');
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $user = User::factory()->create();

        $response = $this->withSession(['login_captcha' => 'ABCDE'])->post('/login', [
            'email' => $user->email,
            'password' => 'password',
            'captcha' => ' abcde ',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_regular_user_login_discards_an_intended_admin_url(): void
    {
        $user = User::factory()->create([
            'role' => 'user',
            'terms_accepted_at' => now(),
        ]);

        $this->get('/admin/dashboard')->assertRedirect(route('login', absolute: false));

        $this->withSession(['login_captcha' => 'ABCDE'])
            ->post('/login', [
                'email' => $user->email,
                'password' => 'password',
                'captcha' => 'ABCDE',
            ])
            ->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create();

        $this->withSession(['login_captcha' => 'ABCDE'])->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
            'captcha' => 'ABCDE',
        ]);

        $this->assertGuest();
    }

    public function test_unknown_email_and_wrong_password_have_distinct_messages(): void
    {
        $this->withSession(['login_captcha' => 'ABCDE'])
            ->from('/login')
            ->post('/login', [
                'email' => 'missing@example.com',
                'password' => 'wrong-password',
                'captcha' => 'ABCDE',
            ])
            ->assertSessionHasErrors(['email' => 'No account found with this email.']);

        $user = User::factory()->create();
        $this->withSession(['login_captcha' => 'ABCDE'])
            ->from('/login')
            ->post('/login', [
                'email' => $user->email,
                'password' => 'wrong-password',
                'captcha' => 'ABCDE',
            ])
            ->assertSessionHasErrors(['password' => 'Incorrect password.']);
    }

    public function test_wrong_password_login_page_displays_the_error_only_once(): void
    {
        $user = User::factory()->create();

        $this->withSession(['login_captcha' => 'ABCDE'])
            ->from('/login')
            ->post('/login', [
                'email' => $user->email,
                'password' => 'wrong-password',
                'captcha' => 'ABCDE',
            ])
            ->assertRedirect('/login');

        $page = $this->get('/login')->assertOk()->assertSee('Incorrect password.');

        $this->assertSame(1, substr_count($page->getContent(), 'Incorrect password.'));
    }

    public function test_empty_and_invalid_email_fields_have_individual_validation_messages(): void
    {
        $this->from('/login')
            ->post('/login', [])
            ->assertSessionHasErrors([
                'email' => 'Please enter your email address.',
                'password' => 'Please enter your password.',
            ]);

        $this->withSession(['login_captcha' => 'ABCDE'])
            ->from('/login')
            ->post('/login', [
                'email' => 'not-an-email',
                'password' => 'some-password',
                'captcha' => 'ABCDE',
            ])
            ->assertSessionHasErrors(['email' => 'Please enter a valid email address.']);
    }

    public function test_inactive_user_has_a_specific_login_message(): void
    {
        $user = User::factory()->create(['officer_status' => 'inactive']);

        $this->withSession(['login_captcha' => 'ABCDE'])
            ->from('/login')
            ->post('/login', [
                'email' => $user->email,
                'password' => 'password',
                'captcha' => 'ABCDE',
            ])
            ->assertSessionHasErrors(['email' => 'This account is inactive. Please contact the OSDW office.']);

        $this->assertGuest();
    }

    public function test_sixth_failed_login_attempt_returns_friendly_lockout_validation_error(): void
    {
        $user = User::factory()->create();

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->withSession(['login_captcha' => 'ABCDE'])
                ->from('/login')
                ->post('/login', [
                    'email' => $user->email,
                    'password' => 'wrong-password',
                    'captcha' => 'ABCDE',
                ])
                ->assertRedirect('/login')
                ->assertSessionHasErrors($attempt === 4 ? 'email' : 'password');
        }

        $response = $this->withSession(['login_captcha' => 'ABCDE'])
            ->from('/login')
            ->post('/login', [
                'email' => $user->email,
                'password' => 'wrong-password',
                'captcha' => 'ABCDE',
            ]);

        $response->assertStatus(302)
            ->assertRedirect('/login')
            ->assertSessionHasErrors('email');
        $this->assertStringContainsString('Too many attempts', session('errors')->first('email'));

        $lockoutPage = $this->get('/login')->assertOk();
        $lockoutPage->assertSee('loginLockoutCountdown(60)', false)
            ->assertSee('Too many attempts. Try again in')
            ->assertSee('>1:00</span>', false);
        $this->assertSame(1, substr_count($lockoutPage->getContent(), 'Too many attempts.'));
    }

    public function test_wrong_captchas_count_toward_login_lockout(): void
    {
        $user = User::factory()->create();

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->withSession(['login_captcha' => 'ABCDE'])
                ->from('/login')
                ->post('/login', [
                    'email' => $user->email,
                    'password' => 'password',
                    'captcha' => 'ZZZZZ',
                ])
                ->assertRedirect('/login')
                ->assertSessionHasErrors($attempt === 4 ? 'email' : 'captcha');
        }

        $response = $this->withSession(['login_captcha' => 'ABCDE'])
            ->from('/login')
            ->post('/login', [
                'email' => $user->email,
                'password' => 'password',
                'captcha' => 'ABCDE',
            ]);

        $response->assertStatus(302)
            ->assertRedirect('/login')
            ->assertSessionHasErrors('email');
        $this->assertStringContainsString('Too many attempts', session('errors')->first('email'));
    }

    public function test_progressive_lockouts_escalate_and_reset_after_24_hours_idle(): void
    {
        Carbon::setTestNow('2026-10-19 12:00:00');

        try {
            $email = 'progressive-lock@example.com';
            foreach ([60, 300, 900, 3600, 3600] as $duration) {
                for ($attempt = 0; $attempt < 5; $attempt++) {
                    $response = $this->withSession(['login_captcha' => 'ABCDE'])
                        ->from('/login')
                        ->post('/login', [
                            'email' => $email,
                            'password' => 'wrong-password',
                            'captcha' => 'ABCDE',
                        ]);
                }

                $response->assertSessionHasErrors([
                    'email' => sprintf(
                        'Too many attempts. Try again in %d minutes %d seconds.',
                        intdiv($duration, 60),
                        $duration % 60,
                    ),
                ]);
                Carbon::setTestNow(now()->addSeconds($duration));
            }

            Carbon::setTestNow(now()->addDay()->addSecond());
            for ($attempt = 0; $attempt < 5; $attempt++) {
                $response = $this->withSession(['login_captcha' => 'ABCDE'])
                    ->from('/login')
                    ->post('/login', [
                        'email' => $email,
                        'password' => 'wrong-password',
                        'captcha' => 'ABCDE',
                    ]);
            }
            $response->assertSessionHasErrors(['email' => 'Too many attempts. Try again in 1 minutes 0 seconds.']);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_attempts_during_lockout_do_not_count_toward_the_next_lockout(): void
    {
        Carbon::setTestNow('2026-10-19 12:00:00');

        try {
            $email = User::factory()->create()->email;
            for ($attempt = 0; $attempt < 5; $attempt++) {
                $response = $this->withSession(['login_captcha' => 'ABCDE'])
                    ->from('/login')
                    ->post('/login', [
                        'email' => $email,
                        'password' => 'wrong-password',
                        'captcha' => 'ABCDE',
                    ]);
            }
            $response->assertSessionHasErrors(['email' => 'Too many attempts. Try again in 1 minutes 0 seconds.']);

            $this->withSession(['login_captcha' => 'ABCDE'])
                ->from('/login')
                ->post('/login', ['email' => $email, 'password' => 'anything', 'captcha' => 'ABCDE'])
                ->assertSessionHasErrors(['email' => 'Too many attempts. Try again in 1 minutes 0 seconds.']);

            Carbon::setTestNow(now()->addMinute());
            for ($attempt = 0; $attempt < 4; $attempt++) {
                $this->withSession(['login_captcha' => 'ABCDE'])
                    ->from('/login')
                    ->post('/login', [
                        'email' => $email,
                        'password' => 'wrong-password',
                        'captcha' => 'ABCDE',
                    ])
                    ->assertSessionHasErrors(['password' => 'Incorrect password.']);
            }

            $this->withSession(['login_captcha' => 'ABCDE'])
                ->from('/login')
                ->post('/login', [
                    'email' => $email,
                    'password' => 'wrong-password',
                    'captcha' => 'ABCDE',
                ])
                ->assertSessionHasErrors(['email' => 'Too many attempts. Try again in 5 minutes 0 seconds.']);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_login_rejects_an_incorrect_captcha_and_rotates_the_code(): void
    {
        $user = User::factory()->create();

        $response = $this->withSession(['login_captcha' => 'ABCDE'])
            ->from('/login')
            ->post('/login', [
                'email' => $user->email,
                'password' => 'password',
                'captcha' => 'ZZZZZ',
            ]);

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors(['captcha' => 'The captcha code is incorrect.']);
        $response->assertSessionHasInput('email', $user->email);
        $this->assertFalse(session()->has('_old_input.password'));
        $this->assertMatchesRegularExpression('/^[A-HJ-KM-NP-Z2-9]{5}$/', session('login_captcha'));
        $this->assertNotSame('ABCDE', session('login_captcha'));
        $this->assertGuest();
    }

    public function test_login_rejects_a_missing_captcha(): void
    {
        $user = User::factory()->create();

        $response = $this->withSession(['login_captcha' => 'ABCDE'])
            ->from('/login')
            ->post('/login', [
                'email' => $user->email,
                'password' => 'password',
            ]);

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors(['captcha' => 'The captcha code is incorrect.']);
        $this->assertFalse(session()->has('_old_input.password'));
        $this->assertNotSame('ABCDE', session('login_captcha'));
        $this->assertGuest();
    }

    public function test_captcha_refresh_returns_and_stores_a_new_code(): void
    {
        $response = $this->withSession(['login_captcha' => 'ABCDE'])
            ->getJson(route('login.captcha.refresh'));

        $response->assertOk();
        $response->assertJsonPath('captcha', session('login_captcha'));
        $this->assertNotSame('ABCDE', session('login_captcha'));
        $this->assertMatchesRegularExpression('/^[A-HJ-KM-NP-Z2-9]{5}$/', session('login_captcha'));
    }

    public function test_users_without_terms_acceptance_are_redirected_to_the_terms_page(): void
    {
        $user = User::factory()->create([
            'role' => 'user',
            'terms_accepted_at' => null,
        ]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertRedirect(route('terms.accept', absolute: false));
    }

    public function test_users_can_accept_terms_and_are_not_redirected_again(): void
    {
        $user = User::factory()->create([
            'role' => 'user',
            'terms_accepted_at' => null,
        ]);

        $response = $this->actingAs($user)->post('/terms/accept');

        $response->assertRedirect(route('dashboard', absolute: false));
        $this->assertNotNull($user->fresh()->terms_accepted_at);
    }

    public function test_regular_user_terms_acceptance_discards_an_intended_admin_url(): void
    {
        $user = User::factory()->create([
            'role' => 'user',
            'terms_accepted_at' => null,
        ]);

        $this->actingAs($user)
            ->withSession(['url.intended' => '/admin/dashboard'])
            ->post('/terms/accept')
            ->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/');
    }

    public function test_organization_deletion_preserves_linked_accounts(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $organization = Organization::create([
            'name' => 'CICS SC',
            'type' => 'Minor Student Organization',
            'college' => 'CICS',
            'is_active' => true,
        ]);
        $secretary = User::factory()->create([
            'role' => 'user',
            'organization_id' => $organization->id,
            'org_name' => $organization->name,
            'org_type' => $organization->type,
            'college' => $organization->college,
            'email' => 'cics-secretary@example.com',
        ]);
        Gpoa::create([
            'user_id' => $secretary->id,
            'term' => '1st Term',
            'school_year' => '2026-2027',
            'college' => 'CICS',
            'status' => 'approved',
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.organizations.destroy', $organization), [
                'confirm_name' => $organization->name,
            ])
            ->assertSessionHas('error');

        $this->assertDatabaseHas('users', ['id' => $secretary->id]);
        $this->assertDatabaseHas('organizations', ['id' => $organization->id]);
        $this->assertAuthenticatedAs($admin);
    }

    public function test_deleting_an_organization_requires_its_exact_name(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $organization = Organization::create([
            'name' => 'CICS SC',
            'type' => 'Minor Student Organization',
            'college' => 'CICS',
            'is_active' => true,
        ]);
        $secretary = User::factory()->create([
            'role' => 'user',
            'organization_id' => $organization->id,
            'org_name' => $organization->name,
            'org_type' => $organization->type,
            'college' => $organization->college,
        ]);

        $this->actingAs($admin)
            ->from(route('admin.organizations.show', $organization))
            ->delete(route('admin.organizations.destroy', $organization), [
                'confirm_name' => 'CICS Student Council',
            ])
            ->assertRedirect(route('admin.organizations.show', $organization))
            ->assertSessionHasErrors('confirm_name');

        $this->assertDatabaseHas('organizations', ['id' => $organization->id]);
        $this->assertDatabaseHas('users', ['id' => $secretary->id]);
    }

    public function test_organization_logo_is_used_as_user_avatar_when_present(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('organization-logos/test-logo.jpg', 'fake-logo-image');

        $organization = Organization::create([
            'name' => 'CTESC',
            'type' => 'Student Council',
            'college' => 'CTED',
            'logo_path' => 'organization-logos/test-logo.jpg',
            'is_active' => true,
        ]);

        $user = User::factory()->create([
            'organization_id' => $organization->id,
            'org_name' => $organization->name,
            'profile_photo_path' => null,
        ]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertOk();
        $response->assertSee('storage/organization-logos/test-logo.jpg');
    }

    public function test_missing_profile_photo_uses_the_default_avatar(): void
    {
        $user = User::factory()->create([
            'profile_photo_path' => 'profile-photos/missing-photo.jpg',
        ]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertOk();
        $response->assertSee(asset('images/osdw.logo.jpg'), false);
        $response->assertDontSee('storage/profile-photos/missing-photo.jpg');
    }

    public function test_public_storage_files_are_served_from_storage_route(): void
    {
        Storage::fake('public');
        $path = 'organization-logos/test-route-logo.jpg';
        $content = base64_decode('/9j/4AAQSkZJRgABAQAAAQABAAD/2wBDAP//////////////////////////////////////////////////////////////////////////////////////2wBDAf//////////////////////////////////////////////////////////////////////////////////////wAARCAABAAEDASIAAhEBAxEB/8QAFQABAQAAAAAAAAAAAAAAAAAAAAb/xAAUEAEAAAAAAAAAAAAAAAAAAAAA/9oADAMBAAIQAxAAAAF//8QAFBABAAAAAAAAAAAAAAAAAAAAAP/aAAgBAQABBQJ//8QAFBEBAAAAAAAAAAAAAAAAAAAAAP/aAgBAwEBPwF//8QAFBEBAAAAAAAAAAAAAAAAAAAAAP/aAgBAgEBPwF//8QAFBABAAAAAAAAAAAAAAAAAAAAAP/aAgBAQAGPwJ//8QAFBABAAAAAAAAAAAAAAAAAAAAAP/aAgBAQABPyF//9oADAMBAAIAAwAAABD/xAAUEAEAAAAAAAAAAAAAAAAAAAAA/9oACAEDAQE/EB//xAAUEAEAAAAAAAAAAAAAAAAAAAAA/9oACAECAQE/EB//xAAUEAEAAAAAAAAAAAAAAAAAAAAA/9oACAEBAAE/EB//2Q==', true);
        Storage::disk('public')->put($path, $content);

        $response = $this->get('/storage/'.$path);

        $response->assertOk();
        $response->assertHeader('content-type', 'image/jpeg');
    }
}
