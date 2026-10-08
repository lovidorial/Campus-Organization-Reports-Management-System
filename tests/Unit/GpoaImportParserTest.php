<?php

namespace Tests\Unit;

use App\Services\GpoaImportParser;
use Illuminate\Http\UploadedFile;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;
use ZipArchive;

class GpoaImportParserTest extends TestCase
{
    #[DataProvider('dateExamples')]
    public function test_it_parses_supported_date_formats(string $value, array $expected): void
    {
        $parsed = (new GpoaImportParser())->parseTimeFrameCell($value, '2026-2027');

        foreach ($expected as $key => $expectedValue) {
            $this->assertSame($expectedValue, $parsed[$key]);
        }
        if ($value === 'October 3 & 5, 2026') {
            $this->assertContains('Multiple dates found; first date selected.', $parsed['warnings']);
        } else {
            $this->assertSame([], $parsed['warnings']);
        }
    }

    public static function dateExamples(): array
    {
        return [
            'exact with comma and space' => ['September 13, 2024', ['time_frame' => 'exact', 'date' => '2024-09-13', 'end_date' => null]],
                'exact without comma spacing' => ['October 9,2026', ['time_frame' => 'exact', 'date' => '2026-10-09', 'end_date' => null]],
            'range without comma' => ['October 21-22 2024', ['time_frame' => 'range', 'date' => '2024-10-21', 'end_date' => '2024-10-22']],
            'range with comma' => ['October 28-30,2024', ['time_frame' => 'range', 'date' => '2024-10-28', 'end_date' => '2024-10-30']],
            'abbreviated month' => ['Oct. 3', ['time_frame' => 'exact', 'date' => '2026-10-03', 'end_date' => null]],
            'abbreviated month range' => ['Oct 3-5', ['time_frame' => 'range', 'date' => '2026-10-03', 'end_date' => '2026-10-05']],
            'day before month' => ['3 October 2026', ['time_frame' => 'exact', 'date' => '2026-10-03', 'end_date' => null]],
            'weekday suffix' => ['October 3, 2026 (Saturday)', ['time_frame' => 'exact', 'date' => '2026-10-03', 'end_date' => null]],
            'multi-date uses first date' => ['October 3 & 5, 2026', ['time_frame' => 'exact', 'date' => '2026-10-03', 'end_date' => null]],
            'month only' => ['November 2024', ['time_frame' => 'month', 'date' => '2024-11-01', 'end_date' => null]],
            'infer first school year' => ['September 13', ['time_frame' => 'exact', 'date' => '2026-09-13', 'end_date' => null]],
            'infer second school year' => ['January 13', ['time_frame' => 'exact', 'date' => '2027-01-13', 'end_date' => null]],
            'infer year for month only' => ['November', ['time_frame' => 'month', 'date' => '2026-11-01', 'end_date' => null]],
        ];
    }

    public function test_it_assumes_meridiem_for_times_without_am_or_pm(): void
    {
        $parsed = (new GpoaImportParser())->parseTimeFrameCell("October 9,2026\n1:00-3:00", '2026-2027');

        $this->assertSame('13:00', $parsed['start_time']);
        $this->assertSame('15:00', $parsed['end_time']);
        $this->assertContains('Time assumed, please check', $parsed['warnings']);
        $this->assertSame('2026-10-09', $parsed['date']);
    }

    public function test_it_parses_times_with_compact_and_spaced_meridiem(): void
    {
        $parser = new GpoaImportParser();
        $compact = $parser->parseTimeFrameCell('October 3, 2026 8AM-5PM', '2026-2027');
        $spaced = $parser->parseTimeFrameCell('October 3, 2026 8 AM to 5 PM', '2026-2027');
        $colon = $parser->parseTimeFrameCell('October 3, 2026 8:00 AM - 5:00 PM', '2026-2027');

        $this->assertSame('08:00', $compact['start_time']);
        $this->assertSame('17:00', $compact['end_time']);
        $this->assertSame([], $compact['warnings']);
        $this->assertSame('08:00', $spaced['start_time']);
        $this->assertSame('17:00', $spaced['end_time']);
        $this->assertSame('08:00', $colon['start_time']);
        $this->assertSame('17:00', $colon['end_time']);
    }

    public function test_two_row_docx_header_maps_fields_and_preserves_multiline_values(): void
    {
        $file = $this->twoRowHeaderDocx();

        try {
            $parsed = (new GpoaImportParser())->parse($file, '2026-2027');
        } finally {
            @unlink($file->getRealPath());
        }

        $row = $parsed['rows'][0];
        $this->assertSame('1.CICS elected and appointed officers / 2.Academic Achievers...', $row['person_in_charge']);
        $this->assertSame('CICS Love Hall', $row['venue']);
        $this->assertSame('Certificate / Token for the Speaker', $row['facilities_materials']);
        $this->assertSame(200.0, $row['estimated_budget']);
        $this->assertSame('2026-10-03', $row['date']);
        $this->assertSame([4, 5, 10, 16, 17], $row['sdgs']);
        $this->assertNull($row['category']);
        $this->assertNull($row['source_of_funds']);
        $this->assertNotContains('Column not found: Source of Funds', $parsed['warnings']);
        $this->assertNotContains('Column not found: Source of Funds', $row['warnings']);
    }

    public function test_venue_only_facilities_cell_keeps_materials_empty(): void
    {
        $row = $this->parseFirstDocxRow('Venue: CICS Love Hall');

        $this->assertSame('CICS Love Hall', $row['venue']);
        $this->assertSame('', $row['facilities_materials']);
    }

    public function test_facilities_and_person_lines_are_joined_after_venue_extraction(): void
    {
        $row = $this->parseFirstDocxRow(
            "Venue: X\nCertificate\nToken",
            "Officer One\nOfficer Two",
            "First-year students\nSecond-year students"
        );

        $this->assertSame('X', $row['venue']);
        $this->assertSame('Certificate / Token', $row['facilities_materials']);
        $this->assertSame('Officer One / Officer Two', $row['person_in_charge']);
        $this->assertSame('First-year students / Second-year students', $row['target_participants']);
    }

    public function test_real_gpoa_2026_2027_docx_imports_all_rows_and_expected_details(): void
    {
        $filePath = base_path('tests/fixtures/GPOA-2026-2027.docx');
        $file = new UploadedFile(
            $filePath,
            'GPOA-2026-2027.docx',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            null,
            true
        );

        $parsed = (new GpoaImportParser())->parse($file, '2026-2027');
        $allTitles = array_merge(array_column($parsed['rows'], 'title'), $parsed['skipped_rows']);
        $firstRow = $parsed['rows'][0];
        $teachersDay = collect($parsed['rows'])->first(fn (array $row): bool => str_contains(mb_strtolower($row['title']), 'teachers'));

        $this->assertCount(39, $allTitles);
        $this->assertStringStartsWith('Induction and Oath Taking of Newly Elected College Student Council and', $firstRow['title']);
        $this->assertSame('2026-10-03', $firstRow['date']);
        $this->assertSame('CICS Love Hall', $firstRow['venue']);
        $this->assertNotNull($teachersDay);
        $this->assertSame('13:00', $teachersDay['start_time']);
        $this->assertSame('15:00', $teachersDay['end_time']);

        foreach (['Intramurals', 'Simba Bigayan', 'Year-End Party', 'KKK'] as $titleFragment) {
            $row = collect($parsed['rows'])->first(fn (array $activity): bool => str_contains(mb_strtolower($activity['title']), mb_strtolower($titleFragment)));
            $this->assertNotNull($row, "Expected row containing {$titleFragment}.");
            $this->assertNull($row['venue'], "Expected {$titleFragment} to have no inferred venue.");
        }

        foreach ($parsed['rows'] as $row) {
            $this->assertStringNotContainsString('Category', implode(' ', $row['warnings']));
            $this->assertStringNotContainsString('Source of Funds', implode(' ', $row['warnings']));
        }
    }

    private function parseFirstDocxRow(
        string $facilities,
        string $personInCharge = 'Responsible Officer',
        string $targetParticipants = 'CICS students'
    ): array
    {
        $file = $this->twoRowHeaderDocx($facilities, $personInCharge, $targetParticipants);

        try {
            return (new GpoaImportParser())->parse($file, '2026-2027')['rows'][0];
        } finally {
            @unlink($file->getRealPath());
        }
    }

    private function twoRowHeaderDocx(
        string $facilities = "Venue: CICS Love Hall\nCertificate / Token for the Speaker",
        string $personInCharge = '1.CICS elected and appointed officers / 2.Academic Achievers...',
        string $targetParticipants = 'CICS students'
    ): UploadedFile
    {
        $filePath = tempnam(sys_get_temp_dir(), 'gpoa-import-') . '.docx';
        $zip = new ZipArchive();
        $zip->open($filePath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        $cell = static function (string $text, string $properties = ''): string {
            $paragraphs = array_map(
                static fn (string $paragraph): string => '<w:p><w:r><w:t xml:space="preserve">' . htmlspecialchars($paragraph, ENT_XML1) . '</w:t></w:r></w:p>',
                explode("\n", $text)
            );

            return '<w:tc><w:tcPr>' . $properties . '</w:tcPr>' . implode('', $paragraphs) . '</w:tc>';
        };
        $merge = '<w:vMerge w:val="restart"/>';
        $continuation = '<w:vMerge w:val="continue"/>';
        $row = static fn (array $cells): string => '<w:tr>' . implode('', $cells) . '</w:tr>';
        $rows = [
            $row([
                $cell('PROGRAM/ACTIVITIES/PROJECT', $merge),
                $cell('SDGs addressed', $merge),
                $cell('OBJECTIVES', '<w:gridSpan w:val="2"/>' . $merge),
                $cell('EXPECTED OUTCOME', $merge),
                $cell('TARGET PARTICIPANTS', $merge),
                $cell('TIME FRAME', $merge),
                $cell('DELIVERY STRATEGY', $merge),
                $cell('RESOURCES', '<w:gridSpan w:val="3"/>'),
            ]),
            $row([
                $cell('', $continuation),
                $cell('', $continuation),
                $cell('', '<w:gridSpan w:val="2"/>' . $continuation),
                $cell('', $continuation),
                $cell('', $continuation),
                $cell('', $continuation),
                $cell('', $continuation),
                $cell('PERSONS INVOLVED'),
                $cell('FACILITIES/ MATERIALS'),
                $cell('BUDGET ALLOCATION'),
            ]),
            $row([
                $cell('Induction and Oath Taking...'),
                $cell('SDGs 4, 5, 10, 16, 17'),
                $cell('Build leadership skills', '<w:gridSpan w:val="2"/>'),
                $cell('Students are prepared'),
                $cell($targetParticipants),
                $cell('October 3, 2026'),
                $cell('Interactive program'),
                $cell($personInCharge),
                $cell($facilities),
                $cell('200.00'),
            ]),
        ];
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:body><w:tbl>' . implode('', $rows) . '</w:tbl></w:body></w:document>';
        $zip->addFromString('word/document.xml', $xml);
        $zip->close();

        return new UploadedFile($filePath, 'GPOA-2026-2027.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', null, true);
    }

    public function test_it_extracts_venue_from_facilities_then_delivery_strategy(): void
    {
        $parser = new GpoaImportParser();

        $this->assertSame('Main Hall', $parser->extractVenue("Projector\nvenue: Main Hall\nSound system", 'Venue: Other Hall'));
        $this->assertSame('Covered Court', $parser->extractVenue('Tables and chairs', "Set-up details\nVENUE : Covered Court\nOther notes"));
        $this->assertNull($parser->extractVenue('Projector and sound system', 'Outdoor set-up'));
    }
}