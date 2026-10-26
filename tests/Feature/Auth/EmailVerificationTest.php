<?php

namespace Tests\Feature\Auth;

use App\Mail\ConfirmIdentityMail;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_email_verification_screen_can_be_rendered(): void
    {
        $user = User::factory()->unverified()->create();

        $response = $this->actingAs($user)->get('/verify-email');

        $response->assertStatus(200);
    }

    public function test_email_can_be_verified(): void
    {
        $user = User::factory()->unverified()->create();

        Event::fake();

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email)]
        );

        $response = $this->get($verificationUrl);

        Event::assertDispatched(Verified::class);
        $this->assertTrue($user->fresh()->hasVerifiedEmail());
        $response->assertOk()
            ->assertSee('Your identity is confirmed. You can now sign in to OrgTrack.')
            ->assertSee('Identity <span>confirmed</span>', false)
            ->assertSee('Go to login')
            ->assertSee(route('login'));

        $this->withSession(['login_captcha' => 'ABCDE'])->post('/login', [
            'email' => $user->email,
            'password' => 'password',
            'captcha' => 'ABCDE',
        ])->assertRedirect(route('dashboard', absolute: false));
        $this->assertAuthenticatedAs($user);
    }

    public function test_email_is_not_verified_with_invalid_hash(): void
    {
        $user = User::factory()->unverified()->create();

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1('wrong-email')]
        );

        $this->get($verificationUrl)
            ->assertOk()
            ->assertSee('This confirmation link is invalid or has expired.')
            ->assertSee('Link <span>expired</span>', false)
            ->assertSee('Resend confirmation email');

        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }

    public function test_expired_link_shows_resend_option_without_verifying_email(): void
    {
        $user = User::factory()->unverified()->create();
        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->subMinute(),
            ['id' => $user->id, 'hash' => sha1($user->email)],
        );

        $this->get($verificationUrl)
            ->assertOk()
            ->assertSee('This confirmation link is invalid or has expired.')
            ->assertSee('Link <span>expired</span>', false)
            ->assertSee('Resend confirmation email');

        $this->assertNull($user->fresh()->email_verified_at);
    }

    public function test_correct_password_for_unverified_user_sends_confirmation_without_logging_in(): void
    {
        Mail::fake();
        $user = User::factory()->unverified()->create();

        $this->withSession(['login_captcha' => 'ABCDE'])
            ->from('/login')
            ->post('/login', [
                'email' => $user->email,
                'password' => 'password',
                'captcha' => 'ABCDE',
            ])
            ->assertRedirect('/login')
            ->assertSessionHas('status', 'identity-verification-sent');

        $this->assertGuest();
        Mail::assertSent(ConfirmIdentityMail::class);
        $mail = Mail::sent(ConfirmIdentityMail::class)->first();
        $html = $mail->render();
        $this->assertTrue($mail->hasTo($user->email));
        $this->assertStringContainsString('expires=', $mail->verificationUrl);
        $this->assertStringContainsString('You need to confirm your identity to activate your OrgTrack account.', $html);
        $this->assertStringContainsString('This link expires in 60 minutes.', $html);
        $this->assertStringContainsString('Confirm my identity', $html);
        $this->assertStringContainsString('src="cid:osdw-logo@orgtrack"', $html);

        $this->get('/login')->assertSee(
            'We sent a confirmation link to your email. Please confirm your identity to continue.'
        );
    }

    public function test_admin_with_null_verification_timestamp_can_log_in(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        \Illuminate\Support\Facades\DB::table('users')
            ->where('id', $admin->id)
            ->update(['email_verified_at' => null]);
        $this->assertTrue($admin->fresh()->hasVerifiedEmail());

        $this->withSession(['login_captcha' => 'ABCDE'])->post('/login', [
            'email' => $admin->email,
            'password' => 'password',
            'captcha' => 'ABCDE',
        ])->assertRedirect(route('admin.dashboard', absolute: false));

        $this->assertAuthenticatedAs($admin);
    }

    public function test_admin_verification_migration_backfills_existing_admin_rows(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        \Illuminate\Support\Facades\DB::table('users')
            ->where('id', $admin->id)
            ->update(['email_verified_at' => null]);

        $migration = require database_path('migrations/2026_10_19_000001_verify_existing_admin_accounts.php');
        $migration->up();

        $this->assertNotNull($admin->fresh()->email_verified_at);
    }

    public function test_resend_verification_is_limited_to_three_per_hour_per_user(): void
    {
        Mail::fake();
        $user = User::factory()->unverified()->create();

        for ($attempt = 0; $attempt < 3; $attempt++) {
            $this->post(URL::temporarySignedRoute('verification.resend', now()->addMinutes(60), ['id' => $user->id]), ['email' => $user->email])
                ->assertSessionHas('verification-status');
        }

        $this->post(URL::temporarySignedRoute('verification.resend', now()->addMinutes(60), ['id' => $user->id]), ['email' => $user->email])
            ->assertSessionHas('verification-error');

        Mail::assertSent(ConfirmIdentityMail::class, 3);
    }

    public function test_resend_feedback_renders_the_check_email_result_state(): void
    {
        Mail::fake();
        $user = User::factory()->unverified()->create();
        $url = URL::temporarySignedRoute('verification.verify', now()->addMinutes(60), [
            'id' => $user->id,
            'hash' => sha1($user->email),
        ]);

        $resendUrl = URL::temporarySignedRoute('verification.resend', now()->addMinutes(60), ['id' => $user->id]);
        $this->from($url)->post($resendUrl, ['email' => $user->email])
            ->assertRedirect($url)
            ->assertSessionHas('verification-status');

        $this->get($url)
            ->assertOk()
            ->assertSee('Check your <span>email</span>', false)
            ->assertSee('The new link expires in 60 minutes.');
    }

    public function test_resend_does_not_send_to_a_mismatched_email(): void
    {
        Mail::fake();
        $user = User::factory()->unverified()->create();

        $this->post(URL::temporarySignedRoute('verification.resend', now()->addMinutes(60), ['id' => $user->id]), [
            'email' => 'someone-else@example.com',
        ])->assertSessionHas('verification-status');

        Mail::assertNothingOutgoing();
    }

    public function test_wrong_password_does_not_send_verification_email(): void
    {
        Mail::fake();
        $user = User::factory()->unverified()->create();

        $this->withSession(['login_captcha' => 'ABCDE'])
            ->post('/login', [
                'email' => $user->email,
                'password' => 'incorrect',
                'captcha' => 'ABCDE',
            ]);

        Mail::assertNothingOutgoing();
        $this->assertGuest();
    }
}
