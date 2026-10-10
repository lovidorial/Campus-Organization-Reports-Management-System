<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ActivityMonitoringExport implements FromCollection, WithColumnFormatting, WithEvents, WithHeadings, WithMapping, WithTitle
{
    public function __construct(
        private readonly Collection $activities,
        private readonly array $filters,
        private readonly array $counts,
    ) {
    }

    public function collection(): Collection
    {
        return $this->activities;
    }

    public function headings(): array
    {
        return [
            'Activity ID',
            'Activity Title',
            'Organization / Office',
            'Submitted By',
            'Activity Venue',
            'Start Date',
            'End Date',
            'Monitoring Status',
            'Late Submission',
            'Communication Letter',
            'Narrative Report',
            'Academic Term',
            'Academic Year',
        ];
    }

    public function map($activity): array
    {
        $activityRequest = $activity->activityRequest;
        $startDate = $activityRequest?->date ?? $activity->date;

        return [
            $activity->id,
            $activity->title ?? '',
            $activity->gpoa?->user?->org_name ?? $activity->gpoa?->user?->name ?? '',
            $activity->gpoa?->user?->name ?? '',
            $activityRequest?->venue ?? $activity->venue ?? '',
            $startDate ? ExcelDate::dateTimeToExcel($startDate->toDate()) : null,
            $activityRequest?->end_date ? ExcelDate::dateTimeToExcel($activityRequest->end_date->toDate()) : null,
            $activity->monitoring_status,
            $activity->monitoring_late ? 'Yes' : 'No',
            filled($activityRequest?->communication_letter) ? 'Submitted' : 'Pending',
            filled($activityRequest?->report?->narrative_report) || filled($activityRequest?->report?->narrative_content) ? 'Submitted' : 'Pending',
            $activity->gpoa?->term ?? '',
            $activity->gpoa?->school_year ?? '',
        ];
    }

    public function columnFormats(): array
    {
        return [
            'F' => 'mmm d, yyyy',
            'G' => 'mmm d, yyyy',
        ];
    }

    public function title(): string
    {
        return 'Activity Monitoring';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $sheet = $event->sheet->getDelegate();
                $sheet->insertNewRowBefore(1, 5);
                $lastRow = max(6, $sheet->getHighestRow());

                $schoolYear = $this->filters['school_year'] ?? '';
                $term = $this->filters['term'] ?? '';
                $sheet->setCellValue('A1', 'OSDW — Activity Monitoring Report');
                $sheet->setCellValue('A2', 'Academic Year: ' . ($schoolYear !== '' ? $schoolYear : 'All') . ' | Term: ' . ($term !== '' ? $term : 'All'));
                $sheet->setCellValue('A3', 'Generated: ' . now()->format('M d, Y H:i:s'));
                $sheet->setCellValue('A4', 'Pending');
                $sheet->setCellValue('B4', $this->counts['Pending'] ?? 0);
                $sheet->setCellValue('C4', 'Ongoing');
                $sheet->setCellValue('D4', $this->counts['Ongoing'] ?? 0);
                $sheet->setCellValue('E4', 'Completed');
                $sheet->setCellValue('F4', $this->counts['Completed'] ?? 0);
                $sheet->setCellValue('G4', 'Late');
                $sheet->setCellValue('H4', $this->counts['Late'] ?? 0);
                $sheet->mergeCells('A1:M1');
                $sheet->mergeCells('A2:M2');
                $sheet->mergeCells('A3:M3');
                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16)->getColor()->setARGB('FF17365D');
                $sheet->getStyle('A2:A3')->getFont()->getColor()->setARGB('FF475569');
                $sheet->getStyle('A4:H4')->getFont()->setBold(true);
                $sheet->getStyle('A4:H4')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFE2E8F0');

                $sheet->getStyle('A6:M6')->getFont()->setBold(true)->getColor()->setARGB('FFFFFFFF');
                $sheet->getStyle('A6:M6')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF17365D');
                $sheet->getStyle('A6:M' . $lastRow)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setARGB('FFD1D5DB');
                $sheet->getStyle('B:B')->getAlignment()->setWrapText(true);
                $sheet->getStyle('C:C')->getAlignment()->setWrapText(true);
                $sheet->getStyle('A6:M' . $lastRow)->getAlignment()->setVertical(Alignment::VERTICAL_TOP);
                $sheet->freezePane('A7');
                $sheet->setAutoFilter('A6:M' . $lastRow);

                for ($row = 7; $row <= $lastRow; $row++) {
                    if (($row - 7) % 2 === 1) {
                        $sheet->getStyle("A{$row}:M{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFF8FAFC');
                    }

                    $statusColor = match ($sheet->getCell("H{$row}")->getValue()) {
                        'Pending' => 'FFFFEDD5',
                        'Ongoing' => 'FFE0F2FE',
                        'Completed' => 'FFD1FAE5',
                        default => 'FFE2E8F0',
                    };
                    $sheet->getStyle("H{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB($statusColor);
                }

                foreach (range('A', 'M') as $column) {
                    $maximumLength = 0;
                    for ($row = 6; $row <= $lastRow; $row++) {
                        $maximumLength = max($maximumLength, strlen((string) $sheet->getCell("{$column}{$row}")->getFormattedValue()));
                    }
                    $sheet->getColumnDimension($column)->setWidth(min(max($maximumLength + 2, 12), 36));
                }
                $sheet->getColumnDimension('A')->setWidth(14);
            },
        ];
    }
}