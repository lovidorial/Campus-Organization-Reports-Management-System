<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('activity_requests', function (Blueprint $table) {
            if (! Schema::hasColumn('activity_requests', 'end_date')) {
                $table->date('end_date')->nullable()->after('date');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('activity_requests', function (Blueprint $table) {
            if (Schema::hasColumn('activity_requests', 'end_date')) {
                $table->dropColumn('end_date');
            }
        });
    }
};
