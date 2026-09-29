<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicActivitiesTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_activities_page_remains_available(): void
    {
        $this->get(route('public.activities'))
            ->assertOk()
            ->assertSee('Public Activities');
    }
}