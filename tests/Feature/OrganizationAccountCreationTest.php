<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrganizationAccountCreationTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_an_organization_requires_term_school_year_and_college(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->post(route('admin.organizations.store'), [
                'name' => 'Campus Organization',
                'type' => 'Major Student Organization',
                'secretary_name' => 'Organization Secretary',
                'secretary_email' => 'secretary@example.com',
                'secretary_password' => 'password',
                'secretary_password_confirmation' => 'password',
            ])
            ->assertSessionHasErrors(['term', 'school_year', 'college']);
    }

    public function test_school_year_must_end_one_year_after_it_begins(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->post(route('admin.organizations.store'), [
                'name' => 'Campus Organization',
                'type' => 'Major Student Organization',
                'college' => 'CICS',
                'term' => '1st Term',
                'school_year' => '2026-2028',
                'secretary_name' => 'Organization Secretary',
                'secretary_email' => 'secretary@example.com',
                'secretary_password' => 'password',
                'secretary_password_confirmation' => 'password',
            ])
            ->assertSessionHasErrors('school_year');
    }
}