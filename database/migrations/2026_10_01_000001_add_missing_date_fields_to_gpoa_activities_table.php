<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gpoa_activities', function (Blueprint $table) {
            if (! Schema::hasColumn('gpoa_activities', 'time_frame')) {
                $table->string('time_frame')->nullable();
            }

            if (! Schema::hasColumn('gpoa_activities', 'end_date')) {
                $table->date('end_date')->nullable();
            }

            if (! Schema::hasColumn('gpoa_activities', 'start_time')) {
                $table->time('start_time')->nullable();
            }

            if (! Schema::hasColumn('gpoa_activities', 'end_time')) {
                $table->time('end_time')->nullable();
            }

            if (! Schema::hasColumn('gpoa_activities', 'date_is_month_only')) {
                $table->boolean('date_is_month_only')->default(false);
            }
        });
    }

    public function down(): void
    {
    }
};