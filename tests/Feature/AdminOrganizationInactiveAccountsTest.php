<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminOrganizationInactiveAccountsTest extends TestCase
{
    use RefreshDatabase;

    public function test_earlier_term_account_appears_only_in_the_inactive_tab(): void
    {
        $organization = $this->organization();
        $endedUser = User::factory()->create([
            'name' => 'Earlier Term Secretary',
            'organization_id' => $organization->id,
            'term' => '2nd Term',
            'school_year' => '2025-2026',
        ]);
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get(route('admin.organizations.index'))
            ->assertOk()
            ->assertDontSee('Earlier Term Secretary')
            ->assertViewHas('tab', 'active');

        $this->get(route('admin.organizations.index', ['tab' => 'inactive']))
            ->assertOk()
            ->assertSee('Earlier Term Secretary')
            ->assertViewHas('inactiveAccounts', fn ($accounts) => $accounts->contains('id', $endedUser->id));
    }

    public function test_organization_with_term_ended_account_appears_only_in_inactive_and_counts_partition(): void
    {
        $organization = $this->organization('CICS-SC');
        $endedUser = User::factory()->create([
            'name' => 'CICS-SC Secretary',
            'organization_id' => $organization->id,
            'term' => '2nd Term',
            'school_year' => '2025-2026',
        ]);
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get(route('admin.organizations.index'))
            ->assertOk()
            ->assertViewHas('organizations', fn ($organizations) => $organizations->total() === 0)
            ->assertViewHas('summary', ['total' => 1, 'active' => 0, 'inactive' => 1]);

        $this->get(route('admin.organizations.index', ['tab' => 'inactive']))
            ->assertOk()
            ->assertSee('CICS-SC Secretary')
            ->assertViewHas('inactiveAccounts', fn ($accounts) => $accounts->total() === 1
                && $accounts->contains('id', $endedUser->id));
    }

    public function test_admin_is_never_listed_and_counts_match_the_organization_list(): void
    {
        $organization = $this->organization();
        User::factory()->create([
            'organization_id' => $organization->id,
            'term' => '1st Term',
            'school_year' => '2026-2027',
        ]);
        $endedOrganization = $this->organization('Former CICS Council');
        $endedUser = User::factory()->create([
            'name' => 'Archived User Counted Once',
            'organization_id' => $endedOrganization->id,
            'term' => '2nd Term',
            'school_year' => '2025-2026',
        ]);
        $admin = User::factory()->create([
            'role' => 'admin',
            'name' => 'Admin Must Not Appear',
            'term' => '1st Term',
            'school_year' => '2020-2021',
        ]);
        $this->actingAs($admin)
            ->get(route('admin.organizations.index', ['tab' => 'inactive']))
            ->assertOk()
            ->assertSee('Archived User Counted Once')
            ->assertViewHas('summary', [
                'total' => 2,
                'active' => 1,
                'inactive' => 1,
            ])
            ->assertViewHas('organizations', fn ($organizations) => $organizations->total() === 2)
            ->assertViewHas('inactiveAccounts', fn ($accounts) => $accounts->contains('id', $endedUser->id)
                && ! $accounts->contains('id', $admin->id));

    }

    public function test_non_admin_cannot_access_inactive_organization_accounts(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        $this->actingAs($user)
            ->get(route('admin.organizations.index', ['tab' => 'inactive']))
            ->assertForbidden();
    }

    public function test_inactive_tab_empty_state_is_shown_when_no_accounts_are_ended(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get(route('admin.organizations.index', ['tab' => 'inactive']))
            ->assertOk()
            ->assertSee('No inactive accounts yet');
    }

    public function test_search_college_and_type_filters_work_on_both_tabs(): void
    {
        $cics = $this->organization('CICS Council');
        $formerCics = $this->organization('Former CICS Council');
        $otherCollege = $this->organization('CTE Council', 'CTE');
        $activeSecretary = User::factory()->create([
            'name' => 'Current CICS Secretary',
            'organization_id' => $cics->id,
            'college' => 'CICS',
            'term' => '1st Term',
            'school_year' => '2026-2027',
        ]);
        $endedSecretary = User::factory()->create([
            'name' => 'Historical CICS Secretary',
            'organization_id' => $formerCics->id,
            'college' => 'CICS',
            'term' => '2nd Term',
            'school_year' => '2025-2026',
        ]);
        User::factory()->create([
            'name' => 'Current CTE Secretary',
            'organization_id' => $otherCollege->id,
            'college' => 'CTE',
            'term' => '1st Term',
            'school_year' => '2026-2027',
        ]);
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get(route('admin.organizations.index', [
                'tab' => 'active', 'search' => 'CICS Secretary', 'college' => 'CICS', 'type' => 'Student Council',
            ]))
            ->assertOk()
            ->assertSee('CICS Council')
            ->assertDontSee('CTE Council')
            ->assertViewHas('organizations', fn ($organizations) => $organizations->contains('id', $cics->id));

        $this->get(route('admin.organizations.index', [
            'tab' => 'inactive', 'search' => 'Historical CICS', 'college' => 'CICS', 'type' => 'Student Council',
        ]))
            ->assertOk()
            ->assertSee('Historical CICS Secretary')
            ->assertDontSee('Current CICS Secretary')
            ->assertViewHas('inactiveAccounts', fn ($accounts) => $accounts->contains('id', $endedSecretary->id)
                && ! $accounts->contains('id', $activeSecretary->id));
    }

    private function organization(string $name = 'CICS Student Council', string $college = 'CICS'): Organization
    {
        return Organization::create([
            'name' => $name,
            'type' => 'Student Council',
            'college' => $college,
            'term' => '1st Term',
            'school_year' => '2026-2027',
            'is_active' => true,
        ]);
    }
}