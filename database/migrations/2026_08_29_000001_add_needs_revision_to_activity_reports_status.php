<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE activity_reports MODIFY status ENUM('pending','approved','rejected','needs_revision') NOT NULL DEFAULT 'pending'");
            return;
        }

        Schema::table('activity_reports', function (Blueprint $table) {
            $table->string('status')->default('pending')->change();
        });
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE activity_reports MODIFY status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending'");
            return;
        }

        Schema::table('activity_reports', function (Blueprint $table) {
            $table->string('status')->default('pending')->change();
        });
    }
};
