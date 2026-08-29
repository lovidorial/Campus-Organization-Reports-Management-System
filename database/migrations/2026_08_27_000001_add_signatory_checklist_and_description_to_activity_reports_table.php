<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activity_reports', function (Blueprint $table) {
            $table->boolean('signed_by_secretary')->default(false);
            $table->boolean('signed_by_governor')->default(false);
            $table->boolean('signed_by_advisor')->default(false);
            $table->boolean('signed_by_dean_president')->default(false);
            $table->text('description')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('activity_reports', function (Blueprint $table) {
            $table->dropColumn([
                'signed_by_secretary',
                'signed_by_governor',
                'signed_by_advisor',
                'signed_by_dean_president',
                'description',
            ]);
        });
    }
};