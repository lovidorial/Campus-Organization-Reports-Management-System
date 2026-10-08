<?php

namespace Tests;

use App\Models\GpoaActivity;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        GpoaActivity::flushMonitoringDeadlineCache();
    }
}
