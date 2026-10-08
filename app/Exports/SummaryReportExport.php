<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SummaryReportExport implements WithMultipleSheets
{
    public function __construct(
        private readonly Collection $activities,
        private readonly Collection $organizationSummary,
        private readonly Collection $categorySummary,
        private readonly Collection $statusSummary,
        private readonly array $filters = [],
        private readonly bool $includeSummaries = true,
    ) {
    }

    public function sheets(): array
    {
        $sheets = [new SummaryReportDataSheet($this->activities, $this->filters)];

        if ($this->includeSummaries) {
            $sheets[] = new OrganizationSummarySheet($this->organizationSummary);
            $sheets[] = new CategorySummarySheet($this->categorySummary);
            $sheets[] = new StatusSummarySheet($this->statusSummary);
        }

        return $sheets;
    }
}

class SummaryReportDataSheet implements FromCollection, ShouldAutoSize, WithEvents, WithHeadings, WithMapping, WithStyles, WithTitle
{
    public function __construct(
        private readonly Collection $activities,
        private readonly array $filters = [],
    ) {
    }

    public function collection(): Collection
    {
        return $this->activities;
    }

    public function headings(): array
    {
        return [
            'Organization',
            'Submitted By',
            'Term / SY',
            'Activity # / Title',
            'Category',
            'Date',
            'Venue',
            'Status',
            'Assessment',
            'Communication Letter',
            'Narrative Report',
            'Estimated Budget',
        ];
    }

    public function map($activity): array
    {
        $request = $activity->activityRequest;
        $report = $request?->report;
        $statusLabel = $activity->monitoring_status === 'Pending' ? 'Not Started' : $activity->monitoring_status;
        $assessment = match ($activity->monitoringResult?->compliance_status ?? '') {
            'aligned' => 'Aligned',
            'partial' => 'Partially Aligned',
            'not_aligned' => 'Not Aligned',
            default => '',
        };

        return [
            $activity->gpoa?->user?->org_name ?? $activity->gpoa?->user?->name ?? '—',
            $activity->gpoa?->user?->name ?? '—',
            trim(($activity->gpoa?->term ?? '—') . ' / ' . ($activity->gpoa?->school_year ?? '—'), ' / '),
            'Activity #' . ($activity->activity_number ?? '—') . ': ' . $activity->title,
            $activity->category ?: '—',
            $activity->date?->format('M d, Y') ?? '—',
            $activity->venue ?: '—',
            $statusLabel,
            $assessment,
            filled($request?->communication_letter) ? 'Uploaded' : 'Pending',
            (filled($report?->narrative_report) || filled($report?->narrative_content)) ? 'Submitted' : 'Pending',
            (float) ($activity->estimated_budget ?? 0),
        ];
    }

    public function title(): string
    {
        return 'Activities';
    }

    public function styles(Worksheet $sheet): array
    {
        $lastRow = max(1, $sheet->getHighestRow());

        return [
            "A1:L{$lastRow}" => ['borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFD1D5DB']]]],
            '1' => ['font' => ['bold' => true], 'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFE5E7EB']]],
            'H' => ['alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]],
            'I' => ['alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]],
            'L' => ['numberFormat' => ['formatCode' => '"PHP "#,##0.00']],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $sheet = $event->sheet->getDelegate();
                $sheet->insertNewRowBefore(1, 1);

                $filterText = 'Filters: Organization=' . ($this->filters['organization'] ?: 'All')
                    . ' | Category=' . ($this->filters['category'] ?: 'All')
                    . ' | College=' . ($this->filters['college'] ?: 'All')
                    . ' | Term=' . ($this->filters['term'] ?: 'All')
                    . ' | School Year=' . ($this->filters['school_year'] ?: 'All')
                    . ' | Status=' . ($this->filters['status'] ?: 'All')
                    . ' | Assessment=' . ($this->filters['assessment'] ?: 'All')
                    . ' | Date=' . (($this->filters['date_from'] ?: 'Any') . ' to ' . ($this->filters['date_to'] ?: 'Any'))
                    . ' | Generated=' . now()->format('M d, Y h:i A');

                $sheet->fromArray([$filterText], null, 'A1');
                $sheet->mergeCells('A1:L1');
                $sheet->getStyle('A1')->getFont()->setBold(true);
                $sheet->freezePane('A3');

                foreach ($this->activities->values() as $index => $activity) {
                    $row = $index + 3;
                    $statusColor = match ($activity->monitoring_status) {
                        'Pending' => 'FFFFEDD5',
                        'Ongoing' => 'FFE0F2FE',
                        'Completed' => 'FFD1FAE5',
                        'Archived' => 'FFE2E8F0',
                        default => 'FFF3F4F6',
                    };

                    $sheet->getStyle("H{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB($statusColor);

                    if ($activity->monitoring_late) {
                        $sheet->getStyle("I{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFFFE4E6');
                        $sheet->getStyle("I{$row}")->getFont()->getColor()->setARGB('FF9F1239');
                    }

                    $request = $activity->activityRequest;
                    if (filled($request?->communication_letter)) {
                        $this->setHyperlink($sheet->getCell("J{$row}"), route('admin.file.view', [$request->id, 'communication']));
                    }
                    if (filled($request?->report?->narrative_report) || filled($request?->report?->narrative_content)) {
                        $this->setHyperlink($sheet->getCell("K{$row}"), route('admin.file.view', [$request->id, 'narrative']));
                    }
                }
            },
        ];
    }

    private function setHyperlink($cell, string $url): void
    {
        $cell->getHyperlink()->setUrl($url);
        $cell->getStyle()->getFont()->setColor(new Color('FF0563C1'))->setUnderline('single');
    }
}

class OrganizationSummarySheet implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping, WithStyles, WithTitle
{
    public function __construct(private readonly Collection $organizationSummary)
    {
    }

    public function collection(): Collection
    {
        return $this->organizationSummary;
    }

    public function headings(): array
    {
        return ['Organization', 'Term / SY', 'Total Activities', 'Finished', 'Archived', 'Late', 'Ongoing', 'Pending', 'GPOA finished?', 'Progress %'];
    }

    public function map($summary): array
    {
        return [
            $summary['organization'],
            $summary['term_sy'],
            $summary['activity_count'],
            $summary['completed'],
            $summary['archived'],
            $summary['late'],
            $summary['ongoing'],
            $summary['pending'],
            $summary['gpoa_finished'],
            $summary['progress'],
        ];
    }

    public function title(): string
    {
        return 'Organization Summary';
    }

    public function styles(Worksheet $sheet): array
    {
        $lastRow = max(1, $sheet->getHighestRow());

        return [
            "A1:J{$lastRow}" => ['borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFD1D5DB']]]],
            '1' => ['font' => ['bold' => true], 'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFE5E7EB']]],
            'J' => ['alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT], 'numberFormat' => ['formatCode' => '0"%"']],
        ];
    }
}

class CategorySummarySheet implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping, WithStyles, WithTitle
{
    public function __construct(private readonly Collection $categorySummary)
    {
    }

    public function collection(): Collection
    {
        return $this->categorySummary;
    }

    public function headings(): array
    {
        return ['Category', 'Activity Count', 'Finished', 'Archived', 'Late'];
    }

    public function map($summary): array
    {
        return [$summary['category'], $summary['activity_count'], $summary['completed'], $summary['archived'], $summary['late']];
    }

    public function title(): string
    {
        return 'Category Summary';
    }

    public function styles(Worksheet $sheet): array
    {
        $lastRow = max(1, $sheet->getHighestRow());

        return [
            "A1:E{$lastRow}" => ['borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFD1D5DB']]]],
            '1' => ['font' => ['bold' => true], 'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFE5E7EB']]],
        ];
    }
}

class StatusSummarySheet implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping, WithStyles, WithTitle
{
    public function __construct(private readonly Collection $statusSummary)
    {
    }

    public function collection(): Collection
    {
        return $this->statusSummary;
    }

    public function headings(): array
    {
        return ['Status', 'Activity Count'];
    }

    public function map($summary): array
    {
        return [$summary['status'], $summary['activity_count']];
    }

    public function title(): string
    {
        return 'Status Summary';
    }

    public function styles(Worksheet $sheet): array
    {
        $lastRow = max(1, $sheet->getHighestRow());

        return [
            "A1:B{$lastRow}" => ['borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFD1D5DB']]]],
            '1' => ['font' => ['bold' => true], 'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFE5E7EB']]],
        ];
    }
}

