<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gpoa_activities', function (Blueprint $table) {
            if (!Schema::hasColumn('gpoa_activities', 'activity_request_id')) {
                $table->foreignId('activity_request_id')->nullable()->constrained('activity_requests')->nullOnDelete()->after('gpoa_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('gpoa_activities', function (Blueprint $table) {
            if (Schema::hasColumn('gpoa_activities', 'activity_request_id')) {
                $table->dropForeign(['activity_request_id']);
                $table->dropColumn('activity_request_id');
            }
        });
    }
};
