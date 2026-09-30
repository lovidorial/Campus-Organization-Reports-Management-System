<?php

namespace App\Services;

use DateTimeImmutable;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\IOFactory;
use RuntimeException;
use Throwable;
use ZipArchive;

class GpoaImportParser
{
    private const MAX_FILE_SIZE = 10 * 1024 * 1024;
    private const MAX_ROWS = 34;

    public function parse(UploadedFile $file, string $schoolYear): array
    {
        if (($file->getSize() ?: 0) > self::MAX_FILE_SIZE) {
            throw new RuntimeException('The selected file exceeds the 10 MB import limit.');
        }

        $extension = strtolower($file->getClientOriginalExtension());

        try {
            $tables = match ($extension) {
                'docx' => $this->readDocxTables($file->getRealPath()),
                'xlsx' => $this->readXlsxTables($file->getRealPath()),
                default => throw new RuntimeException('Choose a .docx Word document or .xlsx Excel workbook.'),
            };
        } catch (RuntimeException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            throw new RuntimeException('This file could not be read. Check that it is a valid, unprotected DOCX or XLSX file.', 0, $exception);
        }

        $activityRows = null;
        foreach ($tables as $table) {
            $parsed = $this->rowsFromTable($table, $schoolYear);
            if ($parsed !== null && $parsed !== []) {
                $activityRows = $parsed;
                break;
            }
        }

        if ($activityRows === null) {
            throw new RuntimeException('No planned-activities table with PROGRAM and TIME FRAME headers was found.');
        }

        $skipped = max(0, count($activityRows) - self::MAX_ROWS);
        $rows = array_slice($activityRows, 0, self::MAX_ROWS);
        $counts = [];
        foreach ($rows as $row) {
            $key = mb_strtolower($row['title']);
            $counts[$key] = ($counts[$key] ?? 0) + 1;
        }

        $repeatedTitles = [];
        foreach ($counts as $title => $count) {
            if ($count > 2) {
                $repeatedTitles[] = ['title' => $rows[array_search($title, array_map(fn ($row) => mb_strtolower($row['title']), $rows), true)]['title'], 'count' => $count];
            }
        }

        $warnings = [];
        if ($skipped > 0) {
            $warnings[] = "{$skipped} additional activity rows were skipped because the maximum is 34.";
        }
        foreach ($repeatedTitles as $repeated) {
            $warnings[] = $repeated['title'] . ' x' . $repeated['count'];
        }

        return [
            'rows' => $rows,
            'warnings' => $warnings,
            'skipped' => $skipped,
            'repeated_titles' => $repeatedTitles,
        ];
    }

    public function parseTimeFrameCell(string $value, string $schoolYear): array
    {
        $text = preg_replace('/\s+/u', ' ', str_replace(',', ', ', trim($value))) ?? trim($value);
        $text = preg_replace('/,\s+/', ', ', $text) ?? $text;
        $warnings = [];
        $startTime = null;
        $endTime = null;

        if (preg_match('/\b(\d{1,2}):(\d{2})\s*(AM|PM)?\s*[-–—]\s*(\d{1,2}):(\d{2})\s*(AM|PM)?\b/i', $text, $timeMatch, PREG_OFFSET_CAPTURE)) {
            $first = $this->normalizeTime($timeMatch[1][0], $timeMatch[2][0] ?? '', $timeMatch[3][0] ?? '');
            $second = $this->normalizeTime($timeMatch[4][0], $timeMatch[5][0] ?? '', $timeMatch[6][0] ?? '');
            $startTime = $first['time'];
            $endTime = $second['time'];
            if ($first['assumed'] || $second['assumed']) {
                $warnings[] = 'Time assumed, please check';
            }
            $text = trim(substr_replace($text, ' ', $timeMatch[0][1], strlen($timeMatch[0][0])));
        }

        $year = null;
        if (preg_match('/\b(\d{4})\b/', $text, $yearMatch)) {
            $year = (int) $yearMatch[1];
            $text = trim(str_replace($yearMatch[0], '', $text));
            $text = trim($text, " ,\t\n\r\0\x0B");
        }

        $months = 'January|February|March|April|May|June|July|August|September|October|November|December';
        $result = [
            'time_frame' => '',
            'date' => null,
            'end_date' => null,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'warnings' => $warnings,
        ];

        if (preg_match('/^(' . $months . ')\s+(\d{1,2})\s*[-–—]\s*(\d{1,2})(?:\s*,?\s*(\d{4}))?$/i', $text, $match)) {
            $resolvedYear = isset($match[4]) && $match[4] !== '' ? (int) $match[4] : ($year ?? $this->inferYear($match[1], $schoolYear));
            $start = $this->makeDate($resolvedYear, $match[1], (int) $match[2]);
            $end = $this->makeDate($resolvedYear, $match[1], (int) $match[3]);
            if ($start && $end) {
                $result['time_frame'] = 'range';
                $result['date'] = $start;
                $result['end_date'] = $end;
            }
        } elseif (preg_match('/^(' . $months . ')\s+(\d{1,2})(?:\s*,?\s*(\d{4}))?$/i', $text, $match)) {
            $resolvedYear = isset($match[3]) && $match[3] !== '' ? (int) $match[3] : ($year ?? $this->inferYear($match[1], $schoolYear));
            $date = $this->makeDate($resolvedYear, $match[1], (int) $match[2]);
            if ($date) {
                $result['time_frame'] = 'exact';
                $result['date'] = $date;
            }
        } elseif (preg_match('/^(' . $months . ')\s+(\d{4})$/i', $text, $match)) {
            $result['time_frame'] = 'month';
            $result['date'] = sprintf('%04d-%02d-01', (int) $match[2], $this->monthNumber($match[1]));
        } elseif ($year !== null && preg_match('/^(' . $months . ')$/i', $text, $match)) {
            $result['time_frame'] = 'month';
            $result['date'] = sprintf('%04d-%02d-01', $year, $this->monthNumber($match[1]));
        } elseif (preg_match('/^(' . $months . ')$/i', $text, $match)) {
            $result['time_frame'] = 'month';
            $result['date'] = sprintf('%04d-%02d-01', $this->inferYear($match[1], $schoolYear), $this->monthNumber($match[1]));
        }

        if ($result['time_frame'] === '') {
            $result['warnings'][] = 'Time frame could not be parsed; please check this row.';
        }

        return $result;
    }

    public function extractVenue(string $facilitiesMaterials, string $deliveryStrategy = ''): ?string
    {
        foreach ([$facilitiesMaterials, $deliveryStrategy] as $source) {
            if (preg_match('/\bVenue\s*:\s*([^\r\n]*)/i', $source, $match) && trim($match[1]) !== '') {
                return trim($match[1]);
            }
        }

        return null;
    }

    private function rowsFromTable(array $table, string $schoolYear): ?array
    {
        $header = $this->findHeader($table);
        if ($header === null) {
            return null;
        }

        [$headerEnd, $columns] = $header;
        while (isset($table[$headerEnd + 1]) && $this->isHeaderRow($table[$headerEnd + 1], $columns)) {
            $headerEnd++;
        }

        $rows = [];
        foreach (array_slice($table, $headerEnd + 1) as $cells) {
            $combined = mb_strtolower(implode(' ', $cells));
            $title = $this->cleanText($cells[$columns['title']] ?? '');
            $timeFrameHeader = mb_strtolower($this->cleanText((string) ($cells[$columns['time_frame']] ?? '')));
            if ($this->isHeaderText($combined)
                || (str_contains(mb_strtolower($title), 'program') && str_contains($timeFrameHeader, 'time frame'))) {
                continue;
            }

            if ($title === '') {
                continue;
            }

            $parsedTime = $this->parseTimeFrameCell((string) ($cells[$columns['time_frame']] ?? ''), $schoolYear);
            $rows[] = [
                'title' => $title,
                'time_frame' => $parsedTime['time_frame'],
                'date' => $parsedTime['date'],
                'end_date' => $parsedTime['end_date'],
                'start_time' => $parsedTime['start_time'],
                'end_time' => $parsedTime['end_time'],
                'venue' => $this->extractVenue(
                    (string) ($columns['facilities'] !== null ? ($cells[$columns['facilities']] ?? '') : ''),
                    (string) ($columns['delivery'] !== null ? ($cells[$columns['delivery']] ?? '') : '')
                ),
                'warnings' => $parsedTime['warnings'],
            ];
        }

        return $rows;
    }

    private function findHeader(array $table): ?array
    {
        $rowCount = count($table);
        for ($start = 0; $start < $rowCount; $start++) {
            for ($height = 1; $height <= 3 && $start + $height <= $rowCount; $height++) {
                $headers = [];
                for ($row = $start; $row < $start + $height; $row++) {
                    foreach ($table[$row] as $column => $value) {
                        $headers[$column] = trim(($headers[$column] ?? '') . ' ' . $this->cleanText((string) $value));
                    }
                }

                $joined = mb_strtolower(implode(' ', $headers));
                if (! str_contains($joined, 'program') || ! str_contains($joined, 'time frame')) {
                    continue;
                }

                $columns = ['title' => null, 'time_frame' => null, 'facilities' => null, 'delivery' => null];
                foreach ($headers as $index => $text) {
                    $normalized = mb_strtolower($text);
                    if ($columns['time_frame'] === null && str_contains($normalized, 'time frame')) {
                        $columns['time_frame'] = $index;
                    }
                    if ($columns['title'] === null && (str_contains($normalized, 'program') || str_contains($normalized, 'activities') || str_contains($normalized, 'project'))) {
                        $columns['title'] = $index;
                    }
                    if ($columns['facilities'] === null && (str_contains($normalized, 'facilities') || str_contains($normalized, 'materials'))) {
                        $columns['facilities'] = $index;
                    }
                    if ($columns['delivery'] === null && str_contains($normalized, 'delivery strategy')) {
                        $columns['delivery'] = $index;
                    }
                }

                if ($columns['title'] !== null && $columns['time_frame'] !== null) {
                    return [$start + $height - 1, $columns];
                }
            }
        }

        return null;
    }

    private function readDocxTables(string|false $path): array
    {
        if (! $path || ! class_exists(ZipArchive::class) || ! class_exists(\DOMDocument::class)) {
            throw new RuntimeException('DOCX import is unavailable because the required ZIP/XML support is missing.');
        }

        $archive = new ZipArchive();
        if ($archive->open($path) !== true) {
            throw new RuntimeException('This DOCX file is invalid or could not be opened.');
        }

        try {
            $xml = $archive->getFromName('word/document.xml');
        } finally {
            $archive->close();
        }

        if ($xml === false) {
            throw new RuntimeException('This DOCX file does not contain a readable Word document.');
        }

        $document = new \DOMDocument();
        $previous = libxml_use_internal_errors(true);
        try {
            if (! $document->loadXML($xml, LIBXML_NONET | LIBXML_NOBLANKS)) {
                throw new RuntimeException('This DOCX file contains invalid document XML.');
            }
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }

        $xpath = new \DOMXPath($document);
        $xpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');
        $tables = [];
        foreach ($xpath->query('//w:tbl') as $tableNode) {
            $rows = [];
            $verticalMerge = [];
            foreach ($xpath->query('./w:tr', $tableNode) as $rowNode) {
                $cells = [];
                $column = 0;
                foreach ($xpath->query('./w:tc', $rowNode) as $cellNode) {
                    $spanNode = $xpath->query('./w:tcPr/w:gridSpan', $cellNode)->item(0);
                    $span = $spanNode ? max(1, (int) $spanNode->getAttributeNS('http://schemas.openxmlformats.org/wordprocessingml/2006/main', 'val')) : 1;
                    $mergeNode = $xpath->query('./w:tcPr/w:vMerge', $cellNode)->item(0);
                    $mergeValue = $mergeNode ? $mergeNode->getAttributeNS('http://schemas.openxmlformats.org/wordprocessingml/2006/main', 'val') : null;
                    $text = $this->wordCellText($xpath, $cellNode);
                    if ($mergeNode && ($mergeValue === '' || $mergeValue === 'continue')) {
                        $text = $verticalMerge[$column] ?? '';
                    }
                    for ($offset = 0; $offset < $span; $offset++) {
                        $cells[$column + $offset] = $text;
                        if ($mergeNode && $mergeValue !== 'continue') {
                            $verticalMerge[$column + $offset] = $text;
                        }
                    }
                    $column += $span;
                }
                ksort($cells);
                $rows[] = array_values($cells);
            }
            $tables[] = $rows;
        }

        return $tables;
    }

    private function wordCellText(\DOMXPath $xpath, \DOMElement $cell): string
    {
        $paragraphs = [];
        foreach ($xpath->query('.//w:p', $cell) as $paragraph) {
            $parts = [];
            foreach ($xpath->query('.//w:t | .//w:tab | .//w:br', $paragraph) as $node) {
                $parts[] = match ($node->localName) {
                    'tab' => "\t",
                    'br' => ' ',
                    default => $node->textContent,
                };
            }
            $paragraphs[] = implode('', $parts);
        }

        return implode("\n", $paragraphs);
    }

    private function readXlsxTables(string|false $path): array
    {
        if (! $path || ! class_exists(IOFactory::class)) {
            throw new RuntimeException('XLSX import is unavailable because PhpSpreadsheet is not installed.');
        }

        $spreadsheet = IOFactory::load($path);
        $tables = [];
        foreach ($spreadsheet->getWorksheetIterator() as $worksheet) {
            $rows = $worksheet->toArray(null, true, true, false);
            foreach ($worksheet->getMergeCells() as $range) {
                [$start, $end] = explode(':', $range);
                [$startColumn, $startRow] = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::coordinateFromString($start);
                [$endColumn, $endRow] = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::coordinateFromString($end);
                $startIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($startColumn) - 1;
                $endIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($endColumn) - 1;
                $anchor = $worksheet->getCell($start)->getFormattedValue();
                for ($row = (int) $startRow; $row <= (int) $endRow; $row++) {
                    for ($column = $startIndex; $column <= $endIndex; $column++) {
                        $rows[$row - 1][$column] = $anchor;
                    }
                }
            }
            $tables[] = array_map(fn ($row) => array_map(fn ($cell) => (string) ($cell ?? ''), $row), $rows);
        }
        $spreadsheet->disconnectWorksheets();

        return $tables;
    }

    private function normalizeTime(string $hour, string $minute, string $meridiem): array
    {
        $hourValue = (int) $hour;
        $minuteValue = $minute === '' ? 0 : (int) $minute;
        $meridiem = strtoupper($meridiem);
        $assumed = $meridiem === '';
        if ($assumed) {
            $meridiem = ($hourValue >= 1 && $hourValue <= 6) || $hourValue === 12 ? 'PM' : 'AM';
        }
        if ($meridiem === 'AM' && $hourValue === 12) {
            $hourValue = 0;
        } elseif ($meridiem === 'PM' && $hourValue < 12) {
            $hourValue += 12;
        }

        return ['time' => sprintf('%02d:%02d', $hourValue, $minuteValue), 'assumed' => $assumed];
    }

    private function inferYear(string $month, string $schoolYear): int
    {
        if (! preg_match('/(\d{4})\D+(\d{2,4})/', $schoolYear, $match)) {
            return (int) date('Y');
        }

        $firstYear = (int) $match[1];
        $secondYear = (int) $match[2];
        if ($secondYear < 100) {
            $secondYear += (intdiv($firstYear, 100) * 100);
            if ($secondYear < $firstYear) {
                $secondYear += 100;
            }
        }

        return $this->monthNumber($month) >= 8 ? $firstYear : $secondYear;
    }

    private function makeDate(int $year, string $month, int $day): ?string
    {
        $monthNumber = $this->monthNumber($month);
        if ($monthNumber === 0 || ! checkdate($monthNumber, $day, $year)) {
            return null;
        }

        return sprintf('%04d-%02d-%02d', $year, $monthNumber, $day);
    }

    private function monthNumber(string $month): int
    {
        $date = DateTimeImmutable::createFromFormat('!F', ucfirst(strtolower($month)));

        return $date ? (int) $date->format('n') : 0;
    }

    private function cleanText(string $value): string
    {
        return trim(preg_replace('/\s+/u', ' ', str_replace(["\u{00A0}", "\t", "\r", "\n"], ' ', $value)) ?? $value);
    }

    private function isHeaderText(string $text): bool
    {
        return str_contains($text, 'program') && str_contains($text, 'time frame');
    }

    private function isHeaderRow(array $cells, array $columns): bool
    {
        $title = mb_strtolower($this->cleanText((string) ($cells[$columns['title']] ?? '')));
        $titleHeader = preg_match('/^(programs?|activities|projects?|activities\s*\/\s*projects?|program\s*\/\s*activities\s*\/\s*projects?)$/i', $title) === 1;
        if ($titleHeader) {
            return true;
        }

        foreach ($cells as $cell) {
            $text = mb_strtolower($this->cleanText((string) $cell));
            if (str_contains($text, 'time frame')
                || str_contains($text, 'facilities/materials')
                || str_contains($text, 'delivery strategy')) {
                return true;
            }
        }

        return false;
    }
}