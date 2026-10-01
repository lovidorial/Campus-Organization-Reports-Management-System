<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('activity_reports', 'attendance_sheet_path')) {
            Schema::table('activity_reports', function (Blueprint $table) {
                $table->string('attendance_sheet_path')->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('activity_reports', 'attendance_sheet_path')) {
            Schema::table('activity_reports', function (Blueprint $table) {
                $table->dropColumn('attendance_sheet_path');
            });
        }
    }
};