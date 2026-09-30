<?php

namespace Tests\Unit;

use Tests\TestCase;

class SdgConfigTest extends TestCase
{
    public function test_sdg_config_has_seventeen_entries_with_valid_hex_colors(): void
    {
        $sdgs = config('sdg');

        $this->assertCount(17, $sdgs);
        $this->assertSame(range(1, 17), array_keys($sdgs));

        foreach ($sdgs as $sdg) {
            $this->assertMatchesRegularExpression('/^#[0-9A-Fa-f]{6}$/', $sdg['color']);
            $this->assertMatchesRegularExpression('/^#[0-9A-Fa-f]{6}$/', $sdg['text']);
        }
    }
}