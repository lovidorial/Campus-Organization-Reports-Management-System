<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gpoa_activities', function (Blueprint $table) {
            $table->string('category')->nullable()->change();
            $table->string('source_of_funds')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('gpoa_activities', function (Blueprint $table) {
            $table->string('category')->nullable()->change();
            $table->string('source_of_funds')->nullable()->change();
        });
    }
};