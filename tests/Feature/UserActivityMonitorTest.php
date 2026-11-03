<?php

namespace Tests\Feature;

use App\Models\ActivityReport;
use App\Models\ActivityRequest;
use App\Models\Gpoa;
use App\Models\GpoaActivity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserActivityMonitorTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_activity_monitor_handles_empty_narrative_collection(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get(route('activity-monitor.index'))
            ->assertOk();
    }

    public function test_unrequested_planned_activity_has_request_action_without_narrative_pending_badge(): void
    {
        $user = User::factory()->create(['term' => '1st Term', 'school_year' => '2026-2027']);
        $gpoa = Gpoa::create([
            'user_id' => $user->id,
            'term' => '1st Term',
            'school_year' => '2026-2027',
            'college' => 'CICS',
            'status' => 'approved',
        ]);
        GpoaActivity::create([
            'gpoa_id' => $gpoa->id,
            'title' => 'Not Yet Requested',
            'date' => '2026-11-02',
            'venue' => 'Main Hall',
            'category' => 'Education',
        ]);

        $this->actingAs($user)
            ->get(route('activity-monitor.index', ['tab' => 'todo']))
            ->assertOk()
            ->assertSee('Not Yet Requested')
            ->assertSee('Request')
            ->assertDontSee('Narrative pending');
    }

    public function test_requested_activity_shows_pending_badge_and_unrequested_todo_row_is_locked_with_reason(): void
    {
        $this->travelTo('2026-10-25 10:00:00');
        $user = User::factory()->create(['term' => '1st Term', 'school_year' => '2026-2027']);
        $gpoa = Gpoa::create([
            'user_id' => $user->id,
            'term' => '1st Term',
            'school_year' => '2026-2027',
            'college' => 'CICS',
            'status' => 'approved',
        ]);
        $requestedActivity = GpoaActivity::create([
            'gpoa_id' => $gpoa->id,
            'title' => 'Earlier Activity',
            'date' => '2026-10-03',
            'venue' => 'Main Hall',
            'category' => 'Education',
        ]);
        $blockingRequest = ActivityRequest::create([
            'user_id' => $user->id,
            'gpoa_id' => $gpoa->id,
            'gpoa_activity_id' => $requestedActivity->id,
            'title' => $requestedActivity->title,
            'date' => $requestedActivity->date,
            'venue' => $requestedActivity->venue,
            'category' => $requestedActivity->category,
            'status' => ActivityRequest::STATUS_PENDING,
        ]);
        $requestedActivity->update(['activity_request_id' => $blockingRequest->id]);
        GpoaActivity::create([
            'gpoa_id' => $gpoa->id,
            'title' => 'Not Yet Requested',
            'date' => '2026-11-02',
            'venue' => 'Other Hall',
            'category' => 'Education',
        ]);

        $this->actingAs($user)
            ->get(route('activity-monitor.index', ['tab' => 'todo']))
            ->assertOk()
            ->assertSee('Not Yet Requested')
            ->assertSee('>Locked</button>', false)
            ->assertSee('Complete previous activity first.')
            ->assertSee('Pending requirement: Earlier Activity Narrative Report')
            ->assertSee(route('activity-reports.create', $blockingRequest), false)
            ->assertSee('Request locked');

        $this->get(route('activity-monitor.index', ['tab' => 'submitted']))
            ->assertOk()
            ->assertSee('Earlier Activity')
            ->assertSee('Narrative pending');
    }

    public function test_user_activity_monitor_shows_current_gpoa_activities_without_approval_gate(): void
    {
        $user = User::factory()->create();

        $gpoa = Gpoa::create([
            'user_id' => $user->id,
            'term' => $user->term,
            'school_year' => $user->school_year,
            'college' => 'CICS',
            'status' => 'pending',
        ]);

        $activity = GpoaActivity::create([
            'gpoa_id' => $gpoa->id,
            'title' => 'Activity 1',
            'date' => '2025-10-15',
            'venue' => 'Main Hall',
            'category' => 'Education',
        ]);

        $request = ActivityRequest::create([
            'user_id' => $user->id,
            'gpoa_id' => $gpoa->id,
            'gpoa_activity_id' => $activity->id,
            'title' => 'Activity 1',
            'date' => '2025-10-15',
            'venue' => 'Main Hall',
            'category' => 'Education',
            'communication_letter' => 'letters/activity-1.pdf',
            'status' => 'pending',
        ]);

        $activity->update(['activity_request_id' => $request->id]);

        ActivityReport::create([
            'activity_request_id' => $request->id,
            'narrative_report' => 'reports/activity-1.pdf',
            'submitted_at' => now(),
            'narrative_source' => 'uploaded',
            'status' => 'approved',
        ]);

        $response = $this->actingAs($user)->get(route('activity-monitor.index'));

        $response->assertOk();
        $response->assertSee('Activity Monitor');
        $response->assertSee('Activity 1');
        $response->assertSee('Completed');
        $response->assertSee('100%');
        $response->assertDontSee('You must have an approved GPOA');
    }

    public function test_user_monitor_view_opens_details_with_uploaded_document_viewer_links(): void
    {
        $user = User::factory()->create([
            'org_name' => 'CICS Student Council',
        ]);
        $gpoa = Gpoa::create([
            'user_id' => $user->id,
            'term' => $user->term,
            'school_year' => $user->school_year,
            'college' => 'CICS',
            'status' => 'approved',
        ]);
        $activity = GpoaActivity::create([
            'gpoa_id' => $gpoa->id,
            'title' => 'Popup Preview Activity',
            'date' => '2025-10-15',
            'venue' => 'Main Hall',
            'category' => 'Education',
        ]);
        $request = ActivityRequest::create([
            'user_id' => $user->id,
            'gpoa_id' => $gpoa->id,
            'gpoa_activity_id' => $activity->id,
            'title' => 'Popup Preview Activity',
            'date' => '2025-10-15',
            'venue' => 'Main Hall',
            'category' => 'Education',
            'communication_letter' => 'letters/popup-preview.pdf',
            'status' => 'pending',
        ]);
        $activity->update(['activity_request_id' => $request->id]);
        ActivityReport::create([
            'activity_request_id' => $request->id,
            'narrative_report' => 'reports/popup-preview.pdf',
            'narrative_source' => 'uploaded',
            'status' => 'pending',
            'submitted_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('activity-monitor.index'))
            ->assertOk()
            ->assertSee('Popup Preview Activity')
            ->assertSee('data-file-viewer', false)
            ->assertSee('Open full page')
            ->assertSee('Program flow')
            ->assertSee('Communication letter:')
            ->assertSee('Narrative report:');
    }

    public function test_archived_activities_are_hidden_by_default_and_shown_by_status_filter(): void
    {
        $user = User::factory()->create();
        $gpoa = Gpoa::create([
            'user_id' => $user->id,
            'term' => $user->term,
            'school_year' => $user->school_year,
            'college' => 'CICS',
            'status' => 'approved',
        ]);
        $activity = GpoaActivity::create([
            'gpoa_id' => $gpoa->id,
            'title' => 'Archived Field Activity',
            'date' => '2025-10-15',
            'archived_at' => now(),
            'venue' => 'Main Hall',
            'category' => 'Education',
        ]);

        $this->actingAs($user)
            ->get(route('activity-monitor.index'))
            ->assertOk()
            ->assertDontSee('Archived Field Activity')
            ->assertSee('Archived');

        $this->get(route('activity-monitor.index', ['status' => 'Archived']))
            ->assertOk()
            ->assertSee('Archived Field Activity');
    }

    public function test_org_monitor_orders_submissions_and_only_lists_the_owners_activities(): void
    {
        $user = User::factory()->create();
        $gpoa = Gpoa::create([
            'user_id' => $user->id,
            'term' => $user->term,
            'school_year' => $user->school_year,
            'college' => 'CICS',
            'status' => 'approved',
        ]);
        foreach ([
            ['My oldest submission', '2025-09-01', '2025-09-01 08:00:00'],
            ['My newest submission', '2025-08-01', '2025-09-03 10:00:00'],
            ['My middle submission', '2025-09-02', '2025-09-02 09:00:00'],
        ] as [$title, $date, $submittedAt]) {
            $activity = GpoaActivity::create([
                'gpoa_id' => $gpoa->id,
                'title' => $title,
                'date' => $date,
                'venue' => 'Main Hall',
                'category' => 'Education',
            ]);
            $request = ActivityRequest::create([
                'user_id' => $user->id,
                'gpoa_id' => $gpoa->id,
                'gpoa_activity_id' => $activity->id,
                'title' => $title,
                'date' => $date,
                'venue' => 'Main Hall',
                'category' => 'Education',
                'communication_letter' => 'letters/' . str_replace(' ', '-', $title) . '.pdf',
                'communication_letter_signed_at' => $submittedAt,
                'status' => 'pending',
            ]);
            $activity->update(['activity_request_id' => $request->id]);
        }

        $otherUser = User::factory()->create();
        $otherGpoa = Gpoa::create([
            'user_id' => $otherUser->id,
            'term' => $otherUser->term,
            'school_year' => $otherUser->school_year,
            'college' => 'CET',
            'status' => 'approved',
        ]);
        GpoaActivity::create([
            'gpoa_id' => $otherGpoa->id,
            'title' => 'Other organization private activity',
            'date' => '2025-09-01',
            'venue' => 'Other Hall',
            'category' => 'Education',
        ]);

        $this->actingAs($user)
            ->get(route('activity-monitor.index'))
            ->assertOk()
            ->assertSeeInOrder(['My newest submission', 'My middle submission', 'My oldest submission'])
            ->assertDontSee('Other organization private activity');
    }

    public function test_org_monitor_pagination_preserves_filters(): void
    {
        $user = User::factory()->create();
        $gpoa = Gpoa::create([
            'user_id' => $user->id,
            'term' => $user->term,
            'school_year' => $user->school_year,
            'college' => 'CICS',
            'status' => 'approved',
        ]);
        for ($number = 1; $number <= 11; $number++) {
            $activity = GpoaActivity::create([
                'gpoa_id' => $gpoa->id,
                'title' => "Own record {$number}",
                'date' => today()->subDays($number)->toDateString(),
                'venue' => 'Main Hall',
                'category' => 'Education',
            ]);
            $request = ActivityRequest::create([
                'user_id' => $user->id,
                'gpoa_id' => $gpoa->id,
                'gpoa_activity_id' => $activity->id,
                'title' => $activity->title,
                'date' => $activity->date,
                'venue' => $activity->venue,
                'category' => $activity->category,
                'communication_letter' => "letters/own-{$number}.pdf",
                'communication_letter_signed_at' => now()->subMinutes($number),
                'status' => 'pending',
            ]);
            $activity->update(['activity_request_id' => $request->id]);
        }

        $this->actingAs($user)
            ->get(route('activity-monitor.index', ['search' => 'Own', 'tab' => 'submitted']))
            ->assertOk()
            ->assertSee('page=2', false)
            ->assertSee('search=Own', false)
            ->assertSee('tab=submitted', false);
    }
}
