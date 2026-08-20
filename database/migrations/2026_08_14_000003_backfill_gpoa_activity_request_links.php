<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('gpoa_activities', 'activity_request_id')) {
            return;
        }

        DB::table('gpoa_activities')
            ->join('activity_requests', 'activity_requests.gpoa_activity_id', '=', 'gpoa_activities.id')
            ->whereNull('gpoa_activities.activity_request_id')
            ->update([
                'gpoa_activities.activity_request_id' => DB::raw('activity_requests.id'),
            ]);
    }

    public function down(): void
    {
        // Data backfill only; no schema changes to revert.
    }
};
