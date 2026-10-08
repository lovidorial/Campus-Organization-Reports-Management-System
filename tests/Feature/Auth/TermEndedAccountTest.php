<?php

namespace Tests\Feature\Auth;

use App\Models\ActivityRequest;
use App\Models\Gpoa;
use App\Models\GpoaActivity;
use App\Models\Organization;
use App\Models\User;
use App\Exports\SummaryReportExport;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Spatie\Activitylog\Models\Activity as ActivityLog;
use Tests\TestCase;

class TermEndedAccountTest extends TestCase
{
    use RefreshDatabase;

    private const BLOCK_MESSAGE = 'This account is no longer active because the term has ended or a new officer has been assigned. Please contact the OSDW office.';

    public function test_archived_user_with_correct_credentials_is_blocked_and_logged(): void
    {
        $user = User::factory()->create(['officer_status' => 'archived']);

        $this->withSession(['login_captcha' => 'ABCDE'])
            ->from('/login')
            ->post('/login', [
                'email' => $user->email,
                'password' => 'password',
                'captcha' => 'ABCDE',
            ])
            ->assertRedirect('/login')
            ->assertSessionHasErrors(['email' => self::BLOCK_MESSAGE]);

        $this->assertGuest();
        $this->assertDatabaseHas('activity_log', [
            'subject_type' => User::class,
            'subject_id' => $user->id,
            'description' => 'login blocked: term ended',
        ]);
        $activity = ActivityLog::query()->latest('id')->first();
        $this->assertSame($user->id, $activity?->properties['user_id'] ?? null);
    }

    public function test_term_ended_login_message_appears_once_in_the_top_alert(): void
    {
        $user = User::factory()->create(['officer_status' => 'archived']);

        $this->withSession(['login_captcha' => 'ABCDE'])
            ->from('/login')
            ->post('/login', [
                'email' => $user->email,
                'password' => 'password',
                'captcha' => 'ABCDE',
            ])
            ->assertRedirect('/login');

        $response = $this->get('/login')->assertOk();
        $content = $response->getContent();

        $this->assertSame(1, substr_count($content, 'Account no longer active'));
        $this->assertStringContainsString('This account\'s term has ended.', $content);
        $this->assertStringContainsString('Questions? Contact osdwcsuaparri@gmail.com', $content);
        $this->assertStringNotContainsString('class="form-error"', $content);
    }

    public function test_archived_at_alone_blocks_an_otherwise_active_user(): void
    {
        $user = User::factory()->create([
            'officer_status' => 'active',
            'archived_at' => now(),
        ]);

        $this->withSession(['login_captcha' => 'ABCDE'])
            ->post('/login', [
                'email' => $user->email,
                'password' => 'password',
                'captcha' => 'ABCDE',
            ])
            ->assertSessionHasErrors(['email' => self::BLOCK_MESSAGE]);

        $this->assertGuest();
    }

    public function test_user_from_earlier_school_year_is_blocked_but_current_period_user_can_login(): void
    {
        $this->travelTo(now()->setDate(2026, 10, 8)->startOfDay());
        $ended = User::factory()->create([
            'term' => '2nd Term',
            'school_year' => '2025-2026',
        ]);
        $current = User::factory()->create([
            'term' => '1st Term',
            'school_year' => '2026-2027',
        ]);

        $this->withSession(['login_captcha' => 'ABCDE'])
            ->from('/login')
            ->post('/login', [
                'email' => $ended->email,
                'password' => 'password',
                'captcha' => 'ABCDE',
            ])
            ->assertSessionHasErrors(['email' => self::BLOCK_MESSAGE]);

        $this->assertGuest();

        $this->withSession(['login_captcha' => 'ABCDE'])
            ->post('/login', [
                'email' => $current->email,
                'password' => 'password',
                'captcha' => 'ABCDE',
            ])
            ->assertRedirect(route('dashboard', absolute: false));

        $this->assertAuthenticatedAs($current);
    }

    public function test_admin_with_old_period_is_never_blocked(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'officer_status' => 'archived',
            'archived_at' => now(),
            'term' => '1st Term',
            'school_year' => '2020-2021',
        ]);

        $this->withSession(['login_captcha' => 'ABCDE'])
            ->post('/login', [
                'email' => $admin->email,
                'password' => 'password',
                'captcha' => 'ABCDE',
            ])
            ->assertRedirect(route('admin.dashboard', absolute: false));

        $this->assertAuthenticatedAs($admin);
    }

    public function test_archiving_a_logged_in_user_ends_the_session_on_the_next_request(): void
    {
        $organization = $this->organization();
        $user = User::factory()->create([
            'organization_id' => $organization->id,
            'term' => $organization->term,
            'school_year' => $organization->school_year,
        ]);

        $this->actingAs($user)->get('/dashboard')->assertOk();
        $user->update(['officer_status' => 'archived', 'archived_at' => now()]);

        $this->get('/dashboard')
            ->assertRedirect(route('login', absolute: false))
            ->assertSessionHasErrors(['email' => self::BLOCK_MESSAGE]);

        $this->assertGuest();
        $this->assertNotNull($user->fresh());
    }

    public function test_restoring_an_officer_updates_the_period_and_allows_login_again(): void
    {
        $organization = $this->organization();
        $user = User::factory()->create([
            'organization_id' => $organization->id,
            'term' => '1st Term',
            'school_year' => '2025-2026',
            'officer_status' => 'archived',
            'archived_at' => now(),
        ]);
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->post(route('admin.officers.restore', $user))
            ->assertSessionHas('success');

        $this->assertSame('active', $user->fresh()->officer_status);
        $this->assertSame('1st Term', $user->fresh()->term);
        $this->assertSame('2026-2027', $user->fresh()->school_year);

        $this->post('/logout');
        $this->withSession(['login_captcha' => 'ABCDE'])
            ->post('/login', [
                'email' => $user->email,
                'password' => 'password',
                'captcha' => 'ABCDE',
            ])
            ->assertRedirect(route('dashboard', absolute: false));

        $this->assertAuthenticatedAs($user);
    }

    public function test_blocked_login_then_admin_restore_allows_a_fresh_login_without_419(): void
    {
        $organization = $this->organization();
        $user = User::factory()->create([
            'organization_id' => $organization->id,
            'term' => '1st Term',
            'school_year' => '2025-2026',
            'officer_status' => 'archived',
            'archived_at' => now(),
        ]);
        $admin = User::factory()->create(['role' => 'admin']);

        $this->withSession(['login_captcha' => 'ABCDE'])
            ->from('/login')
            ->post('/login', [
                'email' => $user->email,
                'password' => 'password',
                'captcha' => 'ABCDE',
            ])
            ->assertRedirect('/login')
            ->assertSessionHasErrors(['email' => self::BLOCK_MESSAGE]);

        $this->assertGuest();

        $this->actingAs($admin)
            ->post(route('admin.officers.restore', $user))
            ->assertSessionHas('success');

        $this->post('/logout')->assertRedirect('/');
        $this->get('/login')->assertOk()->assertHeader('Cache-Control', 'no-store');
        $captcha = session('login_captcha');

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
            'captcha' => $captcha,
        ])->assertRedirect(route('dashboard', absolute: false));

        $this->assertAuthenticatedAs($user);
    }

    public function test_replacing_a_secretary_archives_the_old_account_without_deleting_its_gpoa(): void
    {
        $organization = $this->organization();
        $oldSecretary = User::factory()->create([
            'name' => 'Outgoing Secretary',
            'position' => 'Secretary',
            'organization_id' => $organization->id,
            'term' => '2nd Term',
            'school_year' => '2025-2026',
        ]);
        $gpoa = Gpoa::create([
            'user_id' => $oldSecretary->id,
            'term' => '2nd Term',
            'school_year' => '2025-2026',
            'college' => 'CICS',
            'status' => 'approved',
        ]);
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->post(route('admin.officers.replacement.store'), [
                'name' => 'Incoming Secretary',
                'email' => 'incoming-secretary@example.test',
                'position' => 'Secretary',
                'term' => $organization->term,
                'school_year' => $organization->school_year,
                'organization_id' => $organization->id,
                'org_name' => $organization->name,
                'org_type' => $organization->type,
                'college' => $organization->college,
            ])
            ->assertRedirect(route('admin.officers.replacement.success', absolute: false));

        $this->assertSame('archived', $oldSecretary->fresh()->officer_status);
        $this->assertNotNull($oldSecretary->fresh()->archived_at);
        $this->assertDatabaseHas('gpoas', ['id' => $gpoa->id, 'user_id' => $oldSecretary->id]);
        $this->assertDatabaseHas('users', [
            'email' => 'incoming-secretary@example.test',
            'term' => $organization->term,
            'school_year' => $organization->school_year,
        ]);
    }

    public function test_updating_an_organization_period_does_not_overwrite_a_users_historical_period(): void
    {
        $organization = $this->organization();
        $user = User::factory()->create([
            'organization_id' => $organization->id,
            'term' => '2nd Term',
            'school_year' => '2025-2026',
        ]);
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->put(route('admin.organizations.update', $organization), [
                'name' => $organization->name,
                'type' => $organization->type,
                'college' => $organization->college,
                'term' => '1st Term',
                'school_year' => '2027-2028',
                'is_active' => '1',
            ])
            ->assertRedirect(route('admin.organizations.index'));

        $user->refresh();
        $this->assertSame('2nd Term', $user->term);
        $this->assertSame('2025-2026', $user->school_year);
        $this->assertTrue($user->isTermEnded());
    }

    public function test_period_ended_officer_is_in_history_not_the_current_officer_list(): void
    {
        $organization = $this->organization();
        $organization->update(['term' => '1st Term', 'school_year' => '2027-2028']);
        $officer = User::factory()->create([
            'name' => 'Expired Active Secretary',
            'position' => 'Secretary',
            'organization_id' => $organization->id,
            'term' => '2nd Term',
            'school_year' => '2025-2026',
            'officer_status' => 'active',
        ]);
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get(route('admin.officers.index'))
            ->assertOk()
            ->assertDontSee('Expired Active Secretary');

        $this->get(route('admin.officers.history'))
            ->assertOk()
            ->assertSee('Expired Active Secretary');

        $this->assertTrue($officer->isTermEnded());
    }

    public function test_blocked_users_records_remain_visible_in_admin_gpoa_and_monitoring_pages(): void
    {
        $organization = $this->organization();
        $submitter = User::factory()->create([
            'name' => 'Archived Submitter Name',
            'org_name' => 'CICS Student Council',
            'organization_id' => $organization->id,
            'officer_status' => 'archived',
            'archived_at' => now(),
        ]);
        $gpoa = Gpoa::create([
            'user_id' => $submitter->id,
            'term' => $organization->term,
            'school_year' => $organization->school_year,
            'college' => 'CICS',
            'status' => 'approved',
        ]);
        $activity = GpoaActivity::create([
            'gpoa_id' => $gpoa->id,
            'title' => 'Retained Submitted Activity',
            'date' => '2026-10-10',
            'venue' => 'Main Hall',
            'category' => 'Education',
        ]);
        $request = ActivityRequest::create([
            'user_id' => $submitter->id,
            'gpoa_id' => $gpoa->id,
            'gpoa_activity_id' => $activity->id,
            'title' => $activity->title,
            'date' => $activity->date,
            'venue' => $activity->venue,
            'category' => 'Education',
            'communication_letter' => 'letters/retained.pdf',
            'status' => 'pending',
        ]);
        $activity->update(['activity_request_id' => $request->id]);
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get(route('admin.gpoa.index'))
            ->assertOk()
            ->assertSee('CICS Student Council')
            ->assertSee('Submitted by Archived Submitter Name');

        $this->get(route('admin.activities'))
            ->assertOk()
            ->assertSee('Retained Submitted Activity')
            ->assertSee('Submitted by Archived Submitter Name');

        $this->get(route('admin.summary-report', ['term' => '1st Term', 'school_year' => '2026-2027']))
            ->assertOk()
            ->assertSee('Retained Submitted Activity')
            ->assertSee('Archived Submitter Name');

        $detailSheet = (new SummaryReportExport(collect([$activity]), collect(), collect(), collect()))->sheets()[0];
        $this->assertSame('Archived Submitter Name', $detailSheet->map($activity)[1]);

        $this->assertDatabaseHas('gpoas', ['id' => $gpoa->id]);
        $this->assertDatabaseHas('activity_requests', ['id' => $request->id]);
    }

    public function test_admin_user_delete_request_preserves_user_and_gpoa(): void
    {
        $submitter = User::factory()->create(['officer_status' => 'archived']);
        $gpoa = Gpoa::create([
            'user_id' => $submitter->id,
            'term' => '1st Term',
            'school_year' => '2026-2027',
            'college' => 'CICS',
            'status' => 'approved',
        ]);
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->delete(route('admin.users.destroy', $submitter))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('users', ['id' => $submitter->id]);
        $this->assertDatabaseHas('gpoas', ['id' => $gpoa->id, 'user_id' => $submitter->id]);
    }

    public function test_wrong_password_for_archived_user_shows_normal_credentials_error(): void
    {
        $user = User::factory()->create(['officer_status' => 'archived']);

        $this->withSession(['login_captcha' => 'ABCDE'])
            ->from('/login')
            ->post('/login', [
                'email' => $user->email,
                'password' => 'wrong-password',
                'captcha' => 'ABCDE',
            ])
            ->assertSessionHasErrors(['email' => trans('auth.failed')]);

        $this->assertGuest();
        $this->assertDatabaseMissing('activity_log', [
            'subject_id' => $user->id,
            'description' => 'login blocked: term ended',
        ]);
    }

    public function test_password_reset_can_complete_but_does_not_clear_the_term_ended_block(): void
    {
        Notification::fake();
        $user = User::factory()->create(['officer_status' => 'archived']);

        $this->post('/forgot-password', ['email' => $user->email])
            ->assertSessionHas('status', __(Password::RESET_LINK_SENT));

        Notification::assertSentTo($user, ResetPasswordNotification::class, function ($notification) use ($user): bool {
            $this->post('/reset-password', [
                'token' => $notification->token,
                'email' => $user->email,
                'password' => 'NewPassword123',
                'password_confirmation' => 'NewPassword123',
            ])->assertSessionHasNoErrors()->assertRedirect(route('login'));

            return true;
        });

        $this->withSession(['login_captcha' => 'ABCDE'])
            ->post('/login', [
                'email' => $user->email,
                'password' => 'NewPassword123',
                'captcha' => 'ABCDE',
            ])
            ->assertSessionHasErrors(['email' => self::BLOCK_MESSAGE]);

        $this->assertGuest();
    }

    private function organization(): Organization
    {
        return Organization::create([
            'name' => 'CICS Student Council',
            'type' => 'Student Council',
            'college' => 'CICS',
            'term' => '1st Term',
            'school_year' => '2026-2027',
            'is_active' => true,
        ]);
    }
}