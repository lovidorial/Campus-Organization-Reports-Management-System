<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('backup_settings', function (Blueprint $table) {
            $table->id();
            $table->enum('frequency', ['manual', 'monthly', 'per_semester', 'per_school_year'])->default('manual');
            $table->timestamp('last_run_at')->nullable();
            $table->unsignedInteger('retention_count')->default(5);
        });

        DB::table('backup_settings')->insert([
            [
                'frequency' => 'manual',
                'last_run_at' => null,
                'retention_count' => 5,
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('backup_settings');
    }
};
