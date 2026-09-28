<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('backup_settings', 'last_successful_at')) {
            Schema::table('backup_settings', function (Blueprint $table) {
                $table->timestamp('last_successful_at')->nullable();
            });
        }
        if (! Schema::hasColumn('backup_settings', 'last_error')) {
            Schema::table('backup_settings', function (Blueprint $table) {
                $table->text('last_error')->nullable();
            });
        }
        DB::table('backup_settings')
            ->whereNull('last_successful_at')
            ->whereNotNull('last_run_at')
            ->update(['last_successful_at' => DB::raw('last_run_at')]);

        if (! Schema::hasTable('backup_archives')) {
            Schema::create('backup_archives', function (Blueprint $table) {
                $table->id();
                $table->string('filename')->unique();
                $table->timestamp('last_downloaded_at')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('backup_archives');
        if (Schema::hasColumn('backup_settings', 'last_error')) {
            Schema::table('backup_settings', function (Blueprint $table) {
                $table->dropColumn('last_error');
            });
        }
        if (Schema::hasColumn('backup_settings', 'last_successful_at')) {
            Schema::table('backup_settings', function (Blueprint $table) {
                $table->dropColumn('last_successful_at');
            });
        }
    }
};
