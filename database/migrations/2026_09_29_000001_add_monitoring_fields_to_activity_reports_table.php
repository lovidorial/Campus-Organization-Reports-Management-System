<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activity_reports', function (Blueprint $table) {
            if (! Schema::hasColumn('activity_reports', 'narrative_source')) {
                $table->string('narrative_source')->nullable()->after('activity_request_id');
            }

            if (! Schema::hasColumn('activity_reports', 'narrative_content')) {
                $table->json('narrative_content')->nullable()->after('narrative_source');
            }

            if (Schema::hasColumn('activity_reports', 'narrative_report')) {
                $table->string('narrative_report')->nullable()->change();
            }
        });
    }

    public function down(): void
    {
        Schema::table('activity_reports', function (Blueprint $table) {
            if (Schema::hasColumn('activity_reports', 'narrative_content')) {
                $table->dropColumn('narrative_content');
            }

            if (Schema::hasColumn('activity_reports', 'narrative_source')) {
                $table->dropColumn('narrative_source');
            }
        });
    }
};
