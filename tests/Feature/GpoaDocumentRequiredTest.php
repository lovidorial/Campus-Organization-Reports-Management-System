<?php

namespace Tests\Feature;

use App\Models\Gpoa;
use App\Models\User;
use App\Services\DocumentValidationService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class GpoaDocumentRequiredTest extends TestCase
{
    use RefreshDatabase;

    public function test_gpoa_store_requires_an_approved_document(): void
    {
        $user = User::factory()->create([
            'role' => 'user',
        ]);

        $response = $this->actingAs($user)->post(route('gpoa.store'), [
            'colleges' => 'CICS',
            'term' => '1st Term',
            'school_year' => '2026-2027',
            'prepared_by' => 'Jane Doe',
            'planned_activities' => [[
                ...$this->completePlannedActivity(),
            ]],
            'approved_confirmation' => '1',
            'verify' => '1',
        ]);

        $response->assertSessionHasErrors('document_path');
        $this->assertDatabaseCount('gpoas', 0);
    }

    public function test_gpoa_store_requires_approved_confirmation(): void
    {
        Storage::fake('private');
        $user = User::factory()->create(['role' => 'user', 'org_name' => 'Campus Organization']);

        $response = $this->actingAs($user)->post(route('gpoa.store'), [
            'colleges' => 'CICS',
            'term' => '1st Term',
            'school_year' => '2026-2027',
            'prepared_by' => 'Jane Doe',
            'document_path' => UploadedFile::fake()->createWithContent('approved-gpoa.pdf', Pdf::loadHTML('<p>Campus Organization Date: October 28, 2026 Approved by: Adviser</p>')->output()),
            'planned_activities' => [[
                ...$this->completePlannedActivity(),
            ]],
            'verify' => '1',
        ]);

        $response->assertSessionHasErrors('approved_confirmation');
        $this->assertDatabaseCount('gpoas', 0);
    }

    public function test_gpoa_store_creates_an_approved_gpoa(): void
    {
        Storage::fake('private');
        $user = User::factory()->create(['role' => 'user', 'org_name' => 'Campus Organization']);

        $response = $this->actingAs($user)->post(route('gpoa.store'), [
            'colleges' => 'CICS',
            'term' => '1st Term',
            'school_year' => '2026-2027',
            'prepared_by' => 'Jane Doe',
            'document_path' => UploadedFile::fake()->createWithContent('approved-gpoa.pdf', Pdf::loadHTML('<p>Campus Organization Date: October 28, 2026 Approved by: Adviser</p>')->output()),
            'planned_activities' => [[
                ...$this->completePlannedActivity(),
            ]],
            'approved_confirmation' => '1',
            'verify' => '1',
        ]);

        $response->assertRedirect(route('dashboard', absolute: false));
        $this->assertDatabaseHas('gpoas', [
            'user_id' => $user->id,
            'status' => 'approved',
        ]);
        $gpoa = Gpoa::where('user_id', $user->id)->firstOrFail();
        $this->assertNotNull($gpoa->approved_at);
        Storage::disk('private')->assertExists($gpoa->document_path);
    }

    public function test_gpoa_store_keeps_scanned_document_as_needs_review(): void
    {
        Storage::fake('private');
        $user = User::factory()->create(['role' => 'user', 'org_name' => 'Campus Organization']);
        $this->app->bind(DocumentValidationService::class, fn () => new class extends DocumentValidationService
        {
            protected function extractWithOcr(string $path, string $mime): ?string
            {
                return null;
            }
        });

        $this->actingAs($user)
            ->post(route('gpoa.store'), [
                'colleges' => 'CICS',
                'term' => '1st Term',
                'school_year' => '2026-2027',
                'prepared_by' => 'Jane Doe',
                'document_path' => UploadedFile::fake()->createWithContent('scanned.pdf', Pdf::loadHTML('<html><body></body></html>')->output()),
                'planned_activities' => [$this->completePlannedActivity()],
                'approved_confirmation' => '1',
                'verify' => '1',
            ])
            ->assertRedirect(route('dashboard', absolute: false))
            ->assertSessionHas('documentValidation.status', 'needs_review');

        $gpoa = Gpoa::query()->firstOrFail();
        $this->assertSame('needs_review', $gpoa->validation_status);
        $this->assertStringContainsString('OCR is unavailable', $gpoa->validation_notes);
    }

    public function test_invalid_gpoa_is_not_saved_and_validation_attempt_is_logged(): void
    {
        Storage::fake('private');
        $user = User::factory()->create(['role' => 'user', 'org_name' => 'Campus Organization']);

        $this->actingAs($user)
            ->from(route('gpoa.create'))
            ->post(route('gpoa.store'), [
                'colleges' => 'CICS',
                'term' => '1st Term',
                'school_year' => '2026-2027',
                'prepared_by' => 'Jane Doe',
                'document_path' => UploadedFile::fake()->createWithContent('wrong-gpoa.pdf', Pdf::loadHTML('<p>Other Organization</p>')->output()),
                'planned_activities' => [$this->completePlannedActivity()],
                'approved_confirmation' => '1',
                'verify' => '1',
            ])
            ->assertRedirect(route('gpoa.create'))
            ->assertSessionHasErrors('document_path');

        $this->assertDatabaseCount('gpoas', 0);
        $this->assertDatabaseHas('workflow_events', [
            'event_type' => 'document_validated',
            'description' => 'Gpoa validation: invalid',
        ]);
    }

    public function test_migration_approves_legacy_submitted_gpoas_and_backfills_approval_time(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $gpoa = Gpoa::create([
            'user_id' => $user->id,
            'term' => '1st Term',
            'school_year' => '2026-2027',
            'college' => 'CICS',
            'status' => 'submitted',
        ]);
        $createdAt = $gpoa->created_at;

        $migration = require database_path('migrations/2026_09_30_000001_approve_existing_submitted_gpoas.php');
        $migration->up();

        $gpoa->refresh();
        $this->assertSame('approved', $gpoa->status);
        $this->assertTrue($gpoa->approved_at->equalTo($createdAt));
    }

    public function test_gpoa_store_rejects_an_incomplete_planned_activity_row(): void
    {
        $user = User::factory()->create([
            'role' => 'user',
        ]);

        $response = $this->actingAs($user)->post(route('gpoa.store'), [
            'colleges' => 'CICS',
            'term' => '1st Term',
            'school_year' => '2026-2027',
            'prepared_by' => 'Jane Doe',
            'planned_activities' => [
                $this->completePlannedActivity(),
                array_merge($this->completePlannedActivity(), [
                    'title' => '',
                    'time_frame' => '',
                    'date' => '',
                ]),
            ],
            'verify' => '1',
        ]);

        $response->assertSessionHasErrors([
            'planned_activities.1.title',
            'planned_activities.1.time_frame',
        ]);
        $this->assertSame('Activity 2: Title is required.', $response->getSession()->get('errors')->get('planned_activities.1.title')[0]);
        $this->assertSame('Activity 2: Date or time frame is required.', $response->getSession()->get('errors')->get('planned_activities.1.time_frame')[0]);
        $this->assertDatabaseCount('gpoas', 0);
    }

    public function test_officer_cannot_update_a_submitted_gpoa(): void
    {
        $organization = \App\Models\Organization::create([
            'name' => 'CICS SC',
            'type' => 'Minor Student Organization',
            'college' => 'CICS',
            'is_active' => true,
        ]);

        $user = User::factory()->create([
            'role' => 'user',
            'organization_id' => $organization->id,
            'org_name' => $organization->name,
            'org_type' => $organization->type,
            'college' => $organization->college,
        ]);

        $gpoa = Gpoa::create([
            'user_id' => $user->id,
            'term' => '1st Term',
            'school_year' => '2026-2027',
            'college' => 'CICS',
            'document_path' => 'uploads/gpoa/original.pdf',
            'prepared_by' => 'Jane Doe',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($user)->put(route('gpoa.update', $gpoa), [
            'colleges' => 'CICS',
            'prepared_by' => 'Jane Doe',
            'planned_activities' => [[
                'title' => 'Student Leadership Summit',
                'time_frame' => 'exact_date',
                'date' => '2026-10-01',
                'venue' => 'Main Hall',
                'category' => 'Symposium',
                'sdgs' => [4],
            ]],
        ]);

        $response->assertForbidden();
        $this->assertSame('uploads/gpoa/original.pdf', $gpoa->fresh()->document_path);
        $this->assertSame('Jane Doe', $gpoa->fresh()->prepared_by);
    }

    public function test_admin_can_still_update_a_gpoa(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'terms_accepted_at' => now()]);
        $owner = User::factory()->create();
        $gpoa = Gpoa::create([
            'user_id' => $owner->id,
            'term' => '1st Term',
            'school_year' => '2026-2027',
            'college' => 'CICS',
            'prepared_by' => 'Original Officer',
            'document_path' => 'uploads/gpoa/existing.pdf',
            'status' => 'approved',
        ]);
        $activityDate = today()->addDays(2)->toDateString();
        $activity = $gpoa->activities()->create([
            'title' => 'Existing planned activity',
            'time_frame' => 'exact_date',
            'date' => $activityDate,
            'venue' => 'Main Hall',
            'category' => 'Symposium',
            'sdgs' => [4],
            'objectives' => 'Develop leadership skills.',
            'expected_outcome' => 'Participants gain confidence.',
            'plan_key_strategy' => 'Guided workshop.',
            'target_participants' => 'Student leaders',
            'person_in_charge' => 'Organization officers',
            'estimated_budget' => 2500,
        ]);

        $this->actingAs($admin)->get(route('gpoa.edit', $gpoa))->assertOk();
        $this->put(route('gpoa.update', $gpoa), [
            'colleges' => 'CICS',
            'prepared_by' => 'Admin Updated',
            'planned_activities' => [[
                'id' => $activity->id,
                'title' => $activity->title,
                'time_frame' => 'exact_date',
                'date' => $activityDate,
                'venue' => 'Main Hall',
                'category' => 'Symposium',
                'sdgs' => [4],
                'objectives' => 'Develop leadership skills.',
                'expected_outcome' => 'Participants gain confidence.',
                'plan_key_strategy' => 'Guided workshop.',
                'target_participants' => 'Student leaders',
                'person_in_charge' => 'Organization officers',
                'estimated_budget' => 2500,
            ]],
        ])->assertRedirect(route('dashboard', absolute: false));

        $this->assertSame('Admin Updated', $gpoa->fresh()->prepared_by);
    }

    private function completePlannedActivity(): array
    {
        return [
            'title' => 'Student Leadership Summit',
            'time_frame' => 'exact_date',
            'date' => today()->addDays(2)->toDateString(),
            'venue' => 'Main Hall',
            'category' => 'Symposium',
            'sdgs' => [4],
            'objectives' => 'Develop leadership skills.',
            'expected_outcome' => 'Participants gain confidence.',
            'plan_key_strategy' => 'Guided workshop.',
            'target_participants' => 'Student leaders',
            'person_in_charge' => 'Organization officers',
            'estimated_budget' => 2500,
        ];
    }

    public function test_org_gpoa_document_route_is_owner_scoped_and_private(): void
    {
        Storage::fake('private');
        $ownerOrganization = \App\Models\Organization::create([
            'name' => 'Owner Org', 'type' => 'Major Student Organization', 'college' => 'CICS', 'is_active' => true,
        ]);
        $owner = User::factory()->create([
            'role' => 'user', 'organization_id' => $ownerOrganization->id, 'org_name' => $ownerOrganization->name, 'terms_accepted_at' => now(),
        ]);
        $otherOrganization = \App\Models\Organization::create([
            'name' => 'Other Org', 'type' => 'Major Student Organization', 'college' => 'CET', 'is_active' => true,
        ]);
        $other = User::factory()->create([
            'role' => 'user', 'organization_id' => $otherOrganization->id, 'org_name' => $otherOrganization->name, 'terms_accepted_at' => now(),
        ]);
        $gpoa = Gpoa::create([
            'user_id' => $owner->id,
            'term' => '1st Term',
            'school_year' => '2026-2027',
            'college' => 'CICS',
            'document_path' => 'uploads/gpoa/owner.pdf',
            'status' => 'approved',
        ]);
        Storage::disk('private')->put($gpoa->document_path, 'private gpoa');

        $this->actingAs($owner)
            ->get(route('gpoa.document', $gpoa))
            ->assertOk();

        $this->actingAs($other)
            ->get(route('gpoa.document', $gpoa))
            ->assertForbidden();
    }

    public function test_public_storage_route_allows_image_assets_but_rejects_upload_documents(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('organization-logos/org.png', 'public image');
        Storage::disk('public')->put('uploads/gpoa/private.pdf', 'should not be served');

        $this->get('/storage/organization-logos/org.png')->assertOk();
        $this->get('/storage/uploads/gpoa/private.pdf')->assertNotFound();
        $this->get('/storage/uploads/activity-photos/evidence.jpg')->assertNotFound();
    }
}
