<?php

namespace Tests\Feature;

use App\Models\ActivityReport;
use App\Models\ActivityRequest;
use App\Models\DocumentDeadline;
use App\Models\Gpoa;
use App\Models\GpoaActivity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardMonitoringFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_uses_monitoring_status_instead_of_approval_workflow(): void
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
            'date' => '2025-11-10',
            'venue' => 'Main Hall',
            'category' => 'Education',
        ]);

        $request = ActivityRequest::create([
            'user_id' => $user->id,
            'gpoa_id' => $gpoa->id,
            'gpoa_activity_id' => $activity->id,
            'title' => 'Activity 1',
            'date' => '2025-11-10',
            'venue' => 'Main Hall',
            'category' => 'Education',
            'communication_letter' => 'letters/letter.pdf',
            'status' => 'pending',
        ]);

        $activity->update(['activity_request_id' => $request->id]);

        ActivityReport::create([
            'activity_request_id' => $request->id,
            'narrative_report' => 'reports/report.pdf',
            'submitted_at' => now(),
            'narrative_source' => 'uploaded',
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('GPOA submitted');
        $response->assertSee('Monitoring Progress');
        $response->assertSee('Ongoing');
        $response->assertSee('Open Activity Monitor');
        $response->assertDontSee('Summary Report');
        $response->assertDontSee('Submit report');
        $response->assertDontSee('Awaiting GPOA approval');
        $response->assertDontSee('GPOA approved');
    }

    public function test_dashboard_status_cards_have_correct_counts_links_and_archived_note(): void
    {
        $this->travelTo(now()->setDate(2026, 10, 2)->startOfDay());
        $user = User::factory()->create(['term' => '1st Term', 'school_year' => '2026-2027']);
        $gpoa = Gpoa::create([
            'user_id' => $user->id,
            'term' => '1st Term',
            'school_year' => '2026-2027',
            'college' => 'CICS',
            'status' => 'approved',
        ]);

        DocumentDeadline::create([
            'document_type' => DocumentDeadline::TYPE_ACTIVITY_REPORT,
            'term' => '1st Term',
            'school_year' => '2026-2027',
            'deadline_date' => '2026-09-30',
            'grace_days' => 0,
        ]);

        $pending = GpoaActivity::create([
            'gpoa_id' => $gpoa->id,
            'title' => 'Late pending activity',
            'date' => '2026-10-01',
            'venue' => 'Hall A',
        ]);
        $ongoing = GpoaActivity::create([
            'gpoa_id' => $gpoa->id,
            'title' => 'Late ongoing activity',
            'date' => '2026-10-01',
            'venue' => 'Hall B',
        ]);
        $ongoingRequest = ActivityRequest::create([
            'user_id' => $user->id,
            'gpoa_id' => $gpoa->id,
            'gpoa_activity_id' => $ongoing->id,
            'title' => $ongoing->title,
            'date' => $ongoing->date,
            'venue' => $ongoing->venue,
            'category' => 'Symposium',
            'communication_letter' => 'letters/ongoing.pdf',
            'status' => ActivityRequest::STATUS_APPROVED,
        ]);
        $ongoing->update(['activity_request_id' => $ongoingRequest->id]);

        $completed = GpoaActivity::create([
            'gpoa_id' => $gpoa->id,
            'title' => 'Completed activity',
            'date' => '2026-09-20',
            'venue' => 'Hall C',
        ]);
        $completedRequest = ActivityRequest::create([
            'user_id' => $user->id,
            'gpoa_id' => $gpoa->id,
            'gpoa_activity_id' => $completed->id,
            'title' => $completed->title,
            'date' => $completed->date,
            'venue' => $completed->venue,
            'category' => 'Symposium',
            'communication_letter' => 'letters/completed.pdf',
            'status' => ActivityRequest::STATUS_APPROVED,
        ]);
        $completed->update(['activity_request_id' => $completedRequest->id]);
        ActivityReport::create([
            'activity_request_id' => $completedRequest->id,
            'narrative_report' => 'reports/completed.pdf',
            'narrative_source' => 'uploaded',
            'submitted_at' => now(),
            'status' => 'approved',
        ]);
        GpoaActivity::create([
            'gpoa_id' => $gpoa->id,
            'title' => 'Archived activity',
            'date' => '2026-09-10',
            'archived_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Total')
            ->assertSee('Pending')
            ->assertSee('Ongoing')
            ->assertSee('Completed')
            ->assertSee('Late')
            ->assertSee('Archived')
            ->assertSee('text-2xl font-bold text-slate-700">3</p>', false)
            ->assertSee('text-2xl font-bold text-amber-600">1</p>', false)
            ->assertSee('text-2xl font-bold text-sky-600">1</p>', false)
            ->assertSee('text-2xl font-bold text-emerald-600">1</p>', false)
            ->assertSee('Latest status from your GPOA entries.')
            ->assertSee('Narrative report missing after the deadline.')
            ->assertSee('Late 2')
            ->assertSee('Archived 1')
            ->assertSee(e(route('activity-monitor.index', ['tab' => 'submitted'])), false)
            ->assertSee(e(route('activity-monitor.index', ['tab' => 'todo'])), false)
            ->assertSee(e(route('activity-monitor.index', ['tab' => 'submitted', 'status' => 'Ongoing'])), false)
            ->assertSee(e(route('activity-monitor.index', ['tab' => 'submitted', 'status' => 'Completed'])), false)
            ->assertSee(e(route('activity-monitor.index', ['tab' => 'submitted', 'status' => 'Late'])), false)
            ->assertSee(e(route('activity-monitor.index', ['tab' => 'submitted', 'status' => 'Archived'])), false);
    }

    public function test_dashboard_without_gpoa_shows_zero_status_cards_without_card_links(): void
    {
        $user = User::factory()->create(['term' => '1st Term', 'school_year' => '2026-2027']);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Action Required')
            ->assertSee('Total')
            ->assertSee('Pending')
            ->assertSee('Ongoing')
            ->assertSee('Completed')
            ->assertSee('Late')
            ->assertSee('Archived')
            ->assertSee('Archived 0')
            ->assertDontSee(e(route('activity-monitor.index', ['tab' => 'todo'])), false)
            ->assertDontSee(e(route('activity-monitor.index', ['tab' => 'submitted', 'status' => 'Ongoing'])), false)
            ->assertDontSee(e(route('activity-monitor.index', ['tab' => 'submitted', 'status' => 'Completed'])), false)
            ->assertDontSee(e(route('activity-monitor.index', ['tab' => 'submitted', 'status' => 'Late'])), false)
            ->assertDontSee(e(route('activity-monitor.index', ['tab' => 'submitted', 'status' => 'Archived'])), false);
    }

    public function test_dashboard_renders_uploaded_and_approved_document_pills_without_external_image_markup(): void
    {
        $user = User::factory()->create(['term' => '1st Term', 'school_year' => '2026-2027']);
        $gpoa = Gpoa::create([
            'user_id' => $user->id,
            'term' => '1st Term',
            'school_year' => '2026-2027',
            'college' => 'CICS',
            'status' => 'approved',
        ]);
        $activity = GpoaActivity::create([
            'gpoa_id' => $gpoa->id,
            'title' => 'Approved document activity',
            'date' => '2026-10-01',
            'venue' => 'Main Hall',
            'category' => 'Symposium',
        ]);
        $request = ActivityRequest::create([
            'user_id' => $user->id,
            'gpoa_id' => $gpoa->id,
            'gpoa_activity_id' => $activity->id,
            'title' => $activity->title,
            'date' => $activity->date,
            'venue' => $activity->venue,
            'category' => 'Symposium',
            'communication_letter' => 'letters/approved-letter.pdf',
            'status' => ActivityRequest::STATUS_APPROVED,
        ]);
        $activity->update(['activity_request_id' => $request->id]);
        ActivityReport::create([
            'activity_request_id' => $request->id,
            'narrative_report' => 'reports/approved-report.pdf',
            'narrative_source' => 'uploaded',
            'submitted_at' => now(),
            'status' => 'approved',
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Letter</th>', false)
            ->assertSee('Report</th>', false)
            ->assertSee('bg-emerald-50 text-emerald-700 border border-emerald-200', false)
            ->assertSee('Uploaded')
            ->assertSee('Approved')
            ->assertDontSee('static.xx.fbcdn.net');
    }
}
