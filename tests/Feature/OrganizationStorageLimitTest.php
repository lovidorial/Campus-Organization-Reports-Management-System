<?php

namespace Tests\Feature;

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
        $response = $this->actingAs($user)->patch(route('profile.update'), [
            'name' => $user->name,
            'email' => $user->email,
            'photo' => UploadedFile::fake()->create('oversized.png', 1025, 'image/png'),
        ]);

        $response->assertSessionHasErrors('storage_limit');
    }
}