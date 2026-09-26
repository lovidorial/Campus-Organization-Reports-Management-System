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
        private readonly Collection $activityRequests,
        private readonly Collection $organizationSummary,
        private readonly Collection $categorySummary,
        private readonly Collection $statusSummary,
        private readonly bool $includeCategorySummary = true,
    ) {
    }

    public function sheets(): array
    {
        $sheets = [new SummaryReportDataSheet($this->activityRequests)];

        if ($this->includeCategorySummary) {
            $sheets[] = new OrganizationSummarySheet($this->organizationSummary);
            $sheets[] = new CategorySummarySheet($this->categorySummary);
            $sheets[] = new StatusSummarySheet($this->statusSummary);
        }

        return $sheets;
    }
}

class SummaryReportDataSheet implements FromCollection, ShouldAutoSize, WithEvents, WithHeadings, WithMapping, WithStyles, WithTitle
{
    public function __construct(private readonly Collection $activityRequests)
    {
    }

    public function collection(): Collection
    {
        return $this->activityRequests;
    }

    public function headings(): array
    {
        return [
            'Organization',
            'Term/School Year',
            'Title',
            'Category',
            'Date',
            'Venue',
            'Status',
            'Estimated Budget',
            'Communication Letter',
            'Narrative Report',
        ];
    }

    public function map($activity): array
    {
        $gpoa = $activity->gpoaActivity?->gpoa ?? $activity->gpoa;

        return [
            $activity->user->org_name ?? $activity->user->name ?? '—',
            ($gpoa?->term ?? '—') . ' / SY ' . ($gpoa?->school_year ?? '—'),
            $activity->title,
            $activity->category ?? '—',
            $activity->date?->format('M d, Y') ?? '—',
            $activity->venue ?? '—',
            str_replace('_', ' ', ucfirst($activity->status)),
            (float) ($activity->estimated_budget ?? 0),
            $activity->communication_letter ? 'View Letter' : '—',
            $activity->report ? 'View Report' : 'Not submitted',
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
            "A1:J{$lastRow}" => [
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['argb' => 'FFD1D5DB'],
                    ],
                ],
            ],
            '1' => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FFE5E7EB'],
                ],
            ],
            'G' => [
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
            'H' => [
                'numberFormat' => ['formatCode' => '"PHP "#,##0.00'],
            ],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $sheet = $event->sheet->getDelegate();
                $sheet->freezePane('A2');

                if ($sheet->getHighestRow() >= 2) {
                    foreach ($sheet->getColumnIterator('G', 'G') as $column) {
                        foreach ($column->getCellIterator(2, $sheet->getHighestRow()) as $cell) {
                            if ($cell->getValue() === 'Closed') {
                                $cell->getStyle()->getFill()
                                    ->setFillType(Fill::FILL_SOLID)
                                    ->getStartColor()->setARGB('FFD1FAE5');
                            }
                        }
                    }
                }

                foreach ($this->activityRequests->values() as $index => $activity) {
                    $row = $index + 2;

                    if ($activity->communication_letter) {
                        $this->setHyperlink($sheet->getCell("I{$row}"), asset('storage/' . $activity->communication_letter));
                    }

                    if ($activity->report) {
                        $this->setHyperlink($sheet->getCell("J{$row}"), route('admin.file.view', [$activity->id, 'narrative']));
                    }
                }
            },
        ];
    }

    private function setHyperlink($cell, string $url): void
    {
        $cell->getHyperlink()->setUrl($url);
        $cell->getStyle()->getFont()
            ->setColor(new Color('FF0563C1'))
            ->setUnderline('single');
    }
}

class CategorySummarySheet implements FromCollection, ShouldAutoSize, WithEvents, WithHeadings, WithMapping, WithStyles, WithTitle
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
        return ['Category', 'Activity Count', 'Total Participants', 'Total Estimated Budget'];
    }

    public function map($summary): array
    {
        return [
            $summary['category'],
            $summary['activity_count'],
            $summary['participants'],
            (float) $summary['budget'],
        ];
    }

    public function title(): string
    {
        return 'Category Summary';
    }

    public function styles(Worksheet $sheet): array
    {
        $lastRow = max(1, $sheet->getHighestRow());

        return [
            "A1:D{$lastRow}" => [
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['argb' => 'FFD1D5DB'],
                    ],
                ],
            ],
            '1' => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FFE5E7EB'],
                ],
            ],
            'D' => [
                'numberFormat' => ['formatCode' => '"PHP "#,##0.00'],
            ],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $event->sheet->getDelegate()->freezePane('A2');
            },
        ];
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
        return ['Organization', 'Activity Count', 'Total Participants', 'Total Estimated Budget'];
    }

    public function map($summary): array
    {
        return [
            $summary['organization'],
            $summary['activity_count'],
            $summary['participants'],
            (float) $summary['budget'],
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
            "A1:D{$lastRow}" => ['borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFD1D5DB']]]],
            '1' => ['font' => ['bold' => true], 'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFE5E7EB']]],
            'D' => ['numberFormat' => ['formatCode' => '"PHP "#,##0.00']],
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
        return ['Status', 'Activity Count', 'Total Participants', 'Total Estimated Budget'];
    }

    public function map($summary): array
    {
        return [
            $summary['status'],
            $summary['activity_count'],
            $summary['participants'],
            (float) $summary['budget'],
        ];
    }

    public function title(): string
    {
        return 'Status Summary';
    }

    public function styles(Worksheet $sheet): array
    {
        $lastRow = max(1, $sheet->getHighestRow());

        return [
            "A1:D{$lastRow}" => ['borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFD1D5DB']]]],
            '1' => ['font' => ['bold' => true], 'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFE5E7EB']]],
            'D' => ['numberFormat' => ['formatCode' => '"PHP "#,##0.00']],
        ];
    }
}
