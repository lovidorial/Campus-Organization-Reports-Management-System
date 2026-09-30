<?php

namespace Tests\Unit;

use App\Services\GpoaImportParser;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class GpoaImportParserTest extends TestCase
{
    #[DataProvider('dateExamples')]
    public function test_it_parses_supported_date_formats(string $value, array $expected): void
    {
        $parsed = (new GpoaImportParser())->parseTimeFrameCell($value, '2026-2027');

        foreach ($expected as $key => $expectedValue) {
            $this->assertSame($expectedValue, $parsed[$key]);
        }
        $this->assertSame([], $parsed['warnings']);
    }

    public static function dateExamples(): array
    {
        return [
            'exact with comma and space' => ['September 13, 2024', ['time_frame' => 'exact', 'date' => '2024-09-13', 'end_date' => null]],
            'exact without comma spacing' => ['October 9,2024', ['time_frame' => 'exact', 'date' => '2024-10-09', 'end_date' => null]],
            'range without comma' => ['October 21-22 2024', ['time_frame' => 'range', 'date' => '2024-10-21', 'end_date' => '2024-10-22']],
            'range with comma' => ['October 28-30,2024', ['time_frame' => 'range', 'date' => '2024-10-28', 'end_date' => '2024-10-30']],
            'month only' => ['November 2024', ['time_frame' => 'month', 'date' => '2024-11-01', 'end_date' => null]],
            'infer first school year' => ['September 13', ['time_frame' => 'exact', 'date' => '2026-09-13', 'end_date' => null]],
            'infer second school year' => ['January 13', ['time_frame' => 'exact', 'date' => '2027-01-13', 'end_date' => null]],
            'infer year for month only' => ['November', ['time_frame' => 'month', 'date' => '2026-11-01', 'end_date' => null]],
        ];
    }

    public function test_it_assumes_meridiem_for_times_without_am_or_pm(): void
    {
        $parsed = (new GpoaImportParser())->parseTimeFrameCell('October 9,2024 1:00-3:00', '2026-2027');

        $this->assertSame('13:00', $parsed['start_time']);
        $this->assertSame('15:00', $parsed['end_time']);
        $this->assertContains('Time assumed, please check', $parsed['warnings']);
    }

    public function test_it_extracts_venue_from_facilities_then_delivery_strategy(): void
    {
        $parser = new GpoaImportParser();

        $this->assertSame('Main Hall', $parser->extractVenue("Projector\nvenue: Main Hall\nSound system", 'Venue: Other Hall'));
        $this->assertSame('Covered Court', $parser->extractVenue('Tables and chairs', "Set-up details\nVENUE : Covered Court\nOther notes"));
        $this->assertNull($parser->extractVenue('Projector and sound system', 'Outdoor set-up'));
    }
}