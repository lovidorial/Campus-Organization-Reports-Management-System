<?php

namespace Tests\Feature;

use App\Models\ActivityReport;
use App\Models\ActivityRequest;
use App\Models\Gpoa;
use App\Models\GpoaActivity;
use App\Models\User;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class GpoaSubmissionRulesTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_gpoa_is_blocked_when_prior_plan_has_pending_or_ongoing_activities(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $gpoa = $this->createGpoa($user, '1st Term', '2025-2026');
        $gpoa->activities()->create(['title' => 'Letter not uploaded', 'date' => '2025-09-01']);
        $ongoing = $gpoa->activities()->create(['title' => 'Waiting for report', 'date' => '2025-09-02']);
        $request = ActivityRequest::create([
            'user_id' => $user->id,
            'gpoa_id' => $gpoa->id,
            'gpoa_activity_id' => $ongoing->id,
            'title' => $ongoing->title,
            'date' => $ongoing->date,
            'venue' => 'Main Hall',
            'category' => 'Symposium',
            'communication_letter' => 'letters/ongoing.pdf',
            'status' => ActivityRequest::STATUS_APPROVED,
        ]);
        $ongoing->update(['activity_request_id' => $request->id]);

        $this->actingAs($user)
            ->get(route('gpoa.create'))
            ->assertRedirect(route('gpoa.index'))
            ->assertSessionHas('error', fn (string $message) => str_contains($message, '1st Term SY 2025-2026') && str_contains($message, '2 remaining'));

        $this->post(route('gpoa.store'))
            ->assertSessionHasErrors('gpoa');

        $this->post(route('gpoa.import-preview'), [])->assertRedirect(route('gpoa.index'));
    }

    public function test_new_gpoa_is_allowed_when_previous_activities_are_completed_or_archived(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $gpoa = $this->createGpoa($user, '1st Term', '2025-2026');
        $completed = $gpoa->activities()->create(['title' => 'Finished activity', 'date' => '2025-09-01']);
        $request = ActivityRequest::create([
            'user_id' => $user->id,
            'gpoa_id' => $gpoa->id,
            'gpoa_activity_id' => $completed->id,
            'title' => $completed->title,
            'date' => $completed->date,
            'venue' => 'Main Hall',
            'category' => 'Symposium',
            'communication_letter' => 'letters/completed.pdf',
            'status' => ActivityRequest::STATUS_APPROVED,
        ]);
        $completed->update(['activity_request_id' => $request->id]);
        ActivityReport::create([
            'activity_request_id' => $request->id,
            'narrative_report' => 'reports/completed.pdf',
            'narrative_source' => 'uploaded',
            'submitted_at' => now(),
            'status' => 'approved',
        ]);
        $archived = $gpoa->activities()->create(['title' => 'Archived activity', 'date' => '2025-09-02', 'archived_at' => now()]);

        $this->assertTrue($gpoa->fresh()->isFinished());
        $this->actingAs($user)->get(route('gpoa.create'))->assertOk();
        $this->assertTrue($user->fresh()->gpoaSubmissionStatus()['allowed']);
        $this->assertSame('Archived', $archived->fresh()->monitoringStatus()['status']);
    }

    public function test_first_gpoa_is_allowed_and_officer_submit_links_are_hidden_when_blocked(): void
    {
        $user = User::factory()->create(['role' => 'user', 'term' => '1st Term', 'school_year' => '2026-2027']);
        $this->actingAs($user)->get(route('gpoa.create'))->assertOk();
        $this->actingAs($user)->get(route('gpoa.index'))->assertOk()->assertSee(route('gpoa.create'));

        $gpoa = $this->createGpoa($user, '1st Term', '2026-2027');
        $gpoa->activities()->create(['title' => 'Not finished', 'date' => '2026-10-01']);
        $index = $this->actingAs($user)->get(route('gpoa.index'));
        $index->assertOk()
            ->assertSee('Finish all activities in 1st Term SY 2026-2027 first. 1 remaining.')
            ->assertSee('Locked after submission')
            ->assertDontSee(route('gpoa.create'), false);

        $dashboard = $this->get(route('dashboard'));
        $dashboard->assertOk()
            ->assertSee('Finish all activities in 1st Term SY 2026-2027 first. 1 remaining.')
            ->assertDontSee(route('gpoa.create'), false);
    }

    public function test_admin_is_exempt_from_the_previous_gpoa_completion_guard(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $gpoa = $this->createGpoa($admin, '1st Term', '2025-2026');
        $gpoa->activities()->create(['title' => 'Admin pending activity', 'date' => '2025-09-01']);

        $this->actingAs($admin)->get(route('gpoa.create'))->assertOk();
        $this->assertTrue($admin->fresh()->gpoaSubmissionStatus()['allowed']);
    }

    public function test_duplicate_term_and_school_year_is_rejected_and_profile_period_overrides_posted_values(): void
    {
        Storage::fake('private');
        $user = User::factory()->create(['role' => 'user', 'term' => '2nd Term', 'school_year' => '2026-2027']);
        $this->createGpoa($user, '2nd Term', '2026-2027');

        $duplicate = $this->actingAs($user)->post(route('gpoa.store'), $this->payload('1st Term', '2030-2031'));
        $duplicate->assertSessionHasErrors('term');
        $this->assertDatabaseCount('gpoas', 1);

        $this->assertDatabaseMissing('gpoas', ['user_id' => $user->id, 'term' => '1st Term', 'school_year' => '2030-2031']);

        $otherUser = User::factory()->create(['role' => 'user', 'term' => '1st Term', 'school_year' => '2027-2028']);
        $this->actingAs($otherUser)->post(route('gpoa.store'), $this->payload('2nd Term', '2035-2036'))
            ->assertRedirect(route('dashboard', absolute: false));
        $this->assertDatabaseHas('gpoas', ['user_id' => $otherUser->id, 'term' => '1st Term', 'school_year' => '2027-2028']);
    }

    public function test_second_submission_for_the_same_period_returns_validation_error_and_keeps_one_gpoa(): void
    {
        Storage::fake('private');
        $user = User::factory()->create(['role' => 'user', 'term' => '1st Term', 'school_year' => '2032-2033']);
        $payload = $this->payload('1st Term', '2032-2033');

        $this->actingAs($user)->post(route('gpoa.store'), $payload)
            ->assertRedirect(route('dashboard', absolute: false));

        $user->gpoas()->first()->activities()->update(['archived_at' => now()]);

        $this->actingAs($user)->from(route('gpoa.create'))
            ->post(route('gpoa.store'), $this->payload('1st Term', '2032-2033'))
            ->assertSessionHasErrors([
                'term' => 'A GPOA for 1st Term / SY 2032-2033 has already been submitted.',
            ]);

        $this->assertDatabaseCount('gpoas', 1);
        $this->assertCount(1, Storage::disk('private')->allFiles('uploads/gpoa'));
    }

    public function test_planned_activity_sync_failure_rolls_back_gpoa_and_uploaded_pdf(): void
    {
        Storage::fake('private');
        $user = User::factory()->create(['role' => 'user', 'term' => '1st Term', 'school_year' => '2033-2034']);
        $activityInsertFailed = false;

        DB::listen(function (QueryExecuted $query) use (&$activityInsertFailed): void {
            if (! $activityInsertFailed && str_contains(strtolower($query->sql), 'insert into "gpoa_activities"')) {
                $activityInsertFailed = true;
                throw new \RuntimeException('Simulated planned activity sync failure.');
            }
        });

        $this->actingAs($user)->from(route('gpoa.create'))
            ->post(route('gpoa.store'), $this->payload('1st Term', '2033-2034'))
            ->assertSessionHasErrors([
                'term' => 'A GPOA for 1st Term / SY 2033-2034 has already been submitted.',
            ]);

        $this->assertTrue($activityInsertFailed);
        $this->assertDatabaseCount('gpoas', 0);
        $this->assertDatabaseCount('gpoa_activities', 0);
        $this->assertSame([], Storage::disk('private')->allFiles('uploads/gpoa'));
    }

    public function test_duplicate_index_migration_reports_records_without_deleting_them(): void
    {
        $user = User::factory()->create();
        $first = $this->createGpoa($user, '1st Term', '2026-2027');

        Schema::table('gpoas', fn ($table) => $table->dropUnique('gpoas_user_term_school_year_unique'));
        $second = $this->createGpoa($user, '1st Term', '2026-2027');
        $migration = require database_path('migrations/2026_10_02_000001_add_unique_user_term_school_year_to_gpoas_table.php');

        try {
            $migration->up();
            $this->fail('Expected the migration to report duplicate GPOAs.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString("gpoa_ids={$first->id},{$second->id}", $exception->getMessage());
        }

        $this->assertDatabaseCount('gpoas', 2);
    }

    private function createGpoa(User $user, string $term, string $schoolYear): Gpoa
    {
        return Gpoa::create([
            'user_id' => $user->id,
            'term' => $term,
            'school_year' => $schoolYear,
            'college' => 'CICS',
            'status' => 'approved',
        ]);
    }

    private function payload(string $term, string $schoolYear): array
    {
        return [
            'colleges' => 'CICS',
            'term' => $term,
            'school_year' => $schoolYear,
            'prepared_by' => 'Test Officer',
            'document_path' => UploadedFile::fake()->create('approved.pdf', 10, 'application/pdf'),
            'planned_activities' => [[
                'title' => 'New activity',
                'time_frame' => 'exact_date',
                'date' => today()->addDays(5)->toDateString(),
                'venue' => 'Main Hall',
                'category' => 'Symposium',
                'sdgs' => [4],
                'objectives' => 'Provide student development activities.',
                'expected_outcome' => 'Participants build useful skills.',
                'plan_key_strategy' => 'Facilitated workshop.',
                'target_participants' => 'Students',
                'person_in_charge' => 'Organization officers',
                'estimated_budget' => 2500,
            ]],
            'approved_confirmation' => '1',
            'verify' => '1',
        ];
    }
}
