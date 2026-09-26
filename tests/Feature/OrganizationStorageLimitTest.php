<?php

namespace Tests\Feature;

use App\Models\ActivityRequest;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class OrganizationStorageLimitTest extends TestCase
{
    use RefreshDatabase;

    public function test_upload_exceeding_organization_storage_limit_fails_validation(): void
    {
        $organization = Organization::create([
            'name' => 'Limited Storage Organization',
            'type' => 'Minor Student Organization',
            'college' => 'CICS',
            'is_active' => true,
            'storage_limit_mb' => 1,
        ]);
        $user = User::factory()->create([
            'role' => 'user',
            'organization_id' => $organization->id,
            'org_name' => $organization->name,
        ]);
        $activityRequest = ActivityRequest::create([
            'user_id' => $user->id,
            'title' => 'Approved Venue Activity',
            'date' => '2026-10-10',
            'venue' => 'Main Hall',
            'communication_letter' => 'uploads/comm/letter.pdf',
            'status' => ActivityRequest::STATUS_APPROVED,
        ]);

        $response = $this->actingAs($user)->post(route('activity-requests.reservation-slip', $activityRequest), [
            'reservation_slip' => UploadedFile::fake()->create('reservation.pdf', 1025, 'application/pdf'),
        ]);

        $response->assertSessionHasErrors('storage_limit');
        $this->assertNull($activityRequest->fresh()->reservation_slip);
    }
}