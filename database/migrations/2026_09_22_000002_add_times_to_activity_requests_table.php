<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activity_requests', function (Blueprint $table) {
            if (! Schema::hasColumn('activity_requests', 'start_time')) {
                $table->time('start_time')->nullable()->after('end_date');
            }

            if (! Schema::hasColumn('activity_requests', 'end_time')) {
                $table->time('end_time')->nullable()->after('start_time');
            }
        });
    }

    public function down(): void
    {
        Schema::table('activity_requests', function (Blueprint $table) {
            $columns = collect(['start_time', 'end_time'])
                ->filter(fn ($column) => Schema::hasColumn('activity_requests', $column))
                ->all();

            if ($columns) {
                $table->dropColumn($columns);
            }
        });
    }
};