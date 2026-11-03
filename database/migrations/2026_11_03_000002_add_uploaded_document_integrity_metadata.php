<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $columns = [
            'activity_requests' => [
                'communication_letter_size' => 'size',
                'communication_letter_mime_type' => 'mime',
                'communication_letter_sha256' => 'sha',
            ],
            'activity_reports' => [
                'narrative_report_size' => 'size',
                'narrative_report_mime_type' => 'mime',
                'narrative_report_sha256' => 'sha',
                'attendance_sheet_size' => 'size',
                'attendance_sheet_mime_type' => 'mime',
                'attendance_sheet_sha256' => 'sha',
            ],
            'gpoas' => [
                'document_size' => 'size',
                'document_mime_type' => 'mime',
                'document_sha256' => 'sha',
            ],
            'activity_report_photos' => [
                'file_size' => 'size',
                'mime_type' => 'mime',
                'sha256' => 'sha',
            ],
        ];

        foreach ($columns as $tableName => $tableColumns) {
            if (! Schema::hasTable($tableName)) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) use ($tableName, $tableColumns): void {
                foreach ($tableColumns as $column => $type) {
                    if (Schema::hasColumn($tableName, $column)) {
                        continue;
                    }

                    match ($type) {
                        'size' => $table->unsignedBigInteger($column)->nullable(),
                        'mime' => $table->string($column, 127)->nullable(),
                        'sha' => $table->char($column, 64)->nullable(),
                    };
                }
            });
        }
    }

    public function down(): void
    {
    }
};
