<?php

namespace Tests\Feature;

use App\Models\ActivityRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ReservationSlipUploadTest extends TestCase
{
    use RefreshDatabase;

    public function test_reservation_slip_is_unavailable_until_the_communication_letter_is_approved(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $activityRequest = $this->createRequest($user, ActivityRequest::STATUS_PENDING);

        $response = $this->actingAs($user)->post(route('activity-requests.reservation-slip', $activityRequest), [
            'reservation_slip' => UploadedFile::fake()->create('reservation.pdf', 100, 'application/pdf'),
        ]);

        $response->assertForbidden();
        $this->assertNull($activityRequest->fresh()->reservation_slip);
    }

    public function test_owner_can_upload_reservation_slip_after_approval(): void
    {
        Storage::fake('public');
        $user = User::factory()->create(['role' => 'user']);
        $activityRequest = $this->createRequest($user, ActivityRequest::STATUS_APPROVED);

        $response = $this->actingAs($user)->post(route('activity-requests.reservation-slip', $activityRequest), [
            'reservation_slip' => UploadedFile::fake()->create('reservation.pdf', 100, 'application/pdf'),
        ]);

        $response->assertRedirect();
        $storedPath = $activityRequest->fresh()->reservation_slip;
        $this->assertNotNull($storedPath);
        Storage::disk('public')->assertExists($storedPath);
    }

    private function createRequest(User $user, string $status): ActivityRequest
    {
        return ActivityRequest::create([
            'user_id' => $user->id,
            'title' => 'Approved Venue Activity',
            'date' => '2026-10-10',
            'venue' => 'Main Hall',
            'communication_letter' => 'uploads/comm/letter.pdf',
            'status' => $status,
        ]);
    }
}