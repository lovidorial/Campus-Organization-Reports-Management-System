<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activity_requests', function (Blueprint $table) {
            $table->string('reservation_slip')->nullable()->after('communication_letter');
        });
    }

    public function down(): void
    {
        Schema::table('activity_requests', function (Blueprint $table) {
            $table->dropColumn('reservation_slip');
        });
    }
};