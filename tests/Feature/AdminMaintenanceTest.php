<?php

namespace Tests\Feature;

use App\Models\ActivityRequest;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminMaintenanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_maintenance_page_shows_storage_and_organization_usage(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $organization = Organization::create([
            'name' => 'Maintenance Test Organization',
            'type' => 'Minor Student Organization',
            'college' => 'CICS',
            'is_active' => true,
        ]);
        $user = User::factory()->create([
            'role' => 'user',
            'organization_id' => $organization->id,
            'org_name' => $organization->name,
        ]);

        $relativePath = 'uploads/maintenance-test-' . uniqid() . '.pdf';
        $absolutePath = storage_path('app/public/' . $relativePath);
        if (! is_dir(dirname($absolutePath))) {
            mkdir(dirname($absolutePath), 0777, true);
        }
        file_put_contents($absolutePath, str_repeat('x', 8192));

        try {
            ActivityRequest::create([
                'user_id' => $user->id,
                'title' => 'Storage Sample',
                'date' => '2026-10-10',
                'venue' => 'Main Hall',
                'communication_letter' => $relativePath,
                'status' => ActivityRequest::STATUS_PENDING,
            ]);

            $response = $this->actingAs($admin)->get(route('admin.maintenance.index'));

            $response->assertOk();
            $response->assertSee('Database size');
            $response->assertSee('Storage used (storage/app)');
            $response->assertSee('Maintenance Test Organization');
            $response->assertSee('0.01 MB');
        } finally {
            @unlink($absolutePath);
        }
    }
}