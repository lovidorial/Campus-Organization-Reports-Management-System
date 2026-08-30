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
        Schema::table('organization_members', function (Blueprint $table) {
            if (! Schema::hasColumn('organization_members', 'photo_path')) {
                $table->string('photo_path')->nullable()->after('position');
            }

            if (! Schema::hasColumn('organization_members', 'facebook_url')) {
                $table->string('facebook_url')->nullable()->after('year_level');
            }

            if (Schema::hasColumn('organization_members', 'contact_info')) {
                $table->dropColumn('contact_info');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('organization_members', function (Blueprint $table) {
            if (! Schema::hasColumn('organization_members', 'contact_info')) {
                $table->string('contact_info')->nullable()->after('year_level');
            }

            if (Schema::hasColumn('organization_members', 'facebook_url')) {
                $table->dropColumn('facebook_url');
            }

            if (Schema::hasColumn('organization_members', 'photo_path')) {
                $table->dropColumn('photo_path');
            }
        });
    }
};
