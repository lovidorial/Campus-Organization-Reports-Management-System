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
        Schema::table('activities', function (Blueprint $table) {
            // Add new columns if they don't exist
            if (!Schema::hasColumn('activities', 'description')) {
                $table->text('description')->nullable()->after('venue');
            }
            if (!Schema::hasColumn('activities', 'participants_count')) {
                $table->integer('participants_count')->nullable()->after('description');
            }
            if (!Schema::hasColumn('activities', 'beneficiaries')) {
                $table->integer('beneficiaries')->nullable()->after('participants_count');
            }
            if (!Schema::hasColumn('activities', 'outcomes')) {
                $table->text('outcomes')->nullable()->after('beneficiaries');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            $table->dropColumn(['description', 'participants_count', 'beneficiaries', 'outcomes']);
        });
    }
};
