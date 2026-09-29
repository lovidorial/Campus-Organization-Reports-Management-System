<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('monitoring_results', function (Blueprint $table) {
            $table->foreignId('activity_request_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        if (DB::table('monitoring_results')->whereNull('activity_request_id')->exists()) {
            throw new RuntimeException('Cannot make monitoring_results.activity_request_id required while results exist for planned activities without requests.');
        }

        Schema::table('monitoring_results', function (Blueprint $table) {
            $table->foreignId('activity_request_id')->nullable(false)->change();
        });
    }
};