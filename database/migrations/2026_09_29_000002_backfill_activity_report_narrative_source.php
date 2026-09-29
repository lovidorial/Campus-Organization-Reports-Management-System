<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('activity_reports')
            ->whereNotNull('narrative_report')
            ->where(function ($query) {
                $query->whereNull('narrative_source')
                    ->orWhere('narrative_source', '');
            })
            ->update([
                'narrative_source' => 'uploaded',
            ]);
    }

    public function down(): void
    {
        DB::table('activity_reports')
            ->where('narrative_source', 'uploaded')
            ->update([
                'narrative_source' => null,
            ]);
    }
};
