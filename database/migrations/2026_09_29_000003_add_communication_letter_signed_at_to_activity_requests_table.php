<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('activity_requests', 'communication_letter_signed_at')) {
            Schema::table('activity_requests', function (Blueprint $table) {
                $table->timestamp('communication_letter_signed_at')->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('activity_requests', 'communication_letter_signed_at')) {
            Schema::table('activity_requests', function (Blueprint $table) {
                $table->dropColumn('communication_letter_signed_at');
            });
        }
    }
};