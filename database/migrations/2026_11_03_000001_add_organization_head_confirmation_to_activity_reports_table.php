<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('activity_reports', 'signed_by_organization_head')) {
            Schema::table('activity_reports', function (Blueprint $table) {
                $table->boolean('signed_by_organization_head')->default(false);
            });
        }

        if (Schema::hasColumn('activity_reports', 'signed_by_governor')) {
            DB::table('activity_reports')
                ->where('signed_by_governor', true)
                ->update(['signed_by_organization_head' => true]);
        }
    }

    public function down(): void
    {
    }
};