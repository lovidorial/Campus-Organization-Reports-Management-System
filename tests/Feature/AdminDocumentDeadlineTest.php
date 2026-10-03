<?php

namespace Tests\Feature;

use App\Models\DocumentDeadline;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDocumentDeadlineTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_save_report_grace_and_cutoff_without_changing_other_deadline_types(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $term = '1st Term';
        $schoolYear = '2026-2027';

        foreach ([DocumentDeadline::TYPE_GPOA, DocumentDeadline::TYPE_ACTIVITY_REQUEST, DocumentDeadline::TYPE_SUMMARY_REPORT] as $type) {
            DocumentDeadline::create([
                'document_type' => $type,
                'term' => $term,
                'school_year' => $schoolYear,
                'deadline_date' => '2026-10-10',
            ]);
        }

        $this->actingAs($admin)
            ->put(route('admin.document-deadlines.update'), [
                'term' => $term,
                'school_year' => $schoolYear,
                'grace_days' => '7',
                'deadlines' => ['activity_report' => '2026-10-25'],
            ])
            ->assertRedirect(route('admin.document-deadlines.index', ['term' => $term, 'school_year' => $schoolYear]))
            ->assertSessionHas('success', 'Document deadlines updated.');

        $this->assertDatabaseHas('document_deadlines', [
            'document_type' => DocumentDeadline::TYPE_ACTIVITY_REPORT,
            'term' => $term,
            'school_year' => $schoolYear,
            'grace_days' => 7,
            'deadline_date' => '2026-10-25 00:00:00',
        ]);

        foreach ([DocumentDeadline::TYPE_GPOA, DocumentDeadline::TYPE_ACTIVITY_REQUEST, DocumentDeadline::TYPE_SUMMARY_REPORT] as $type) {
            $this->assertDatabaseHas('document_deadlines', [
                'document_type' => $type,
                'term' => $term,
                'school_year' => $schoolYear,
                'deadline_date' => '2026-10-10 00:00:00',
            ]);
        }

        $this->assertDatabaseHas('activity_log', [
            'description' => 'document_deadline.updated',
            'causer_id' => $admin->id,
        ]);
    }

    public function test_admin_can_clear_both_report_settings_without_deleting_other_deadline_types(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $term = '2nd Term';
        $schoolYear = '2026-2027';

        $reportDeadline = DocumentDeadline::create([
            'document_type' => DocumentDeadline::TYPE_ACTIVITY_REPORT,
            'term' => $term,
            'school_year' => $schoolYear,
            'grace_days' => 5,
            'deadline_date' => '2026-10-25',
        ]);
        $otherDeadline = DocumentDeadline::create([
            'document_type' => DocumentDeadline::TYPE_ACTIVITY_REQUEST,
            'term' => $term,
            'school_year' => $schoolYear,
            'deadline_date' => '2026-10-12',
        ]);

        $this->actingAs($admin)
            ->put(route('admin.document-deadlines.update'), [
                'term' => $term,
                'school_year' => $schoolYear,
                'grace_days' => '',
                'deadlines' => ['activity_report' => ''],
            ])
            ->assertRedirect(route('admin.document-deadlines.index', ['term' => $term, 'school_year' => $schoolYear]));

        $this->assertDatabaseMissing('document_deadlines', ['id' => $reportDeadline->id]);
        $this->assertDatabaseHas('document_deadlines', ['id' => $otherDeadline->id]);
    }

    public function test_grace_days_validation_is_limited_to_zero_through_sixty(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->from(route('admin.document-deadlines.index'))
            ->put(route('admin.document-deadlines.update'), [
                'term' => '1st Term',
                'school_year' => '2026-2027',
                'grace_days' => 61,
                'deadlines' => ['activity_report' => '2026-10-25'],
            ])
            ->assertSessionHasErrors('grace_days');
    }
}
