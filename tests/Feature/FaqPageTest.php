<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FaqPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_regular_users_see_only_the_user_faq(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        $response = $this->actingAs($user)->get(route('faq'));

        $response->assertOk();
        $response->assertSee('What is a GPOA, and why must it be approved before I submit activities?');
        $response->assertDontSee('How do I review and approve or reject a GPOA or Activity Request?');
    }

    public function test_admins_see_only_the_admin_faq(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get(route('faq'));

        $response->assertOk();
        $response->assertSee('How do I review and approve or reject a GPOA or Activity Request?');
        $response->assertDontSee('What is a GPOA, and why must it be approved before I submit activities?');
    }
}