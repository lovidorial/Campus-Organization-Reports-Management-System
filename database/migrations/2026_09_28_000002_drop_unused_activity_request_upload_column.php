<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const COLUMN = 'reservation' . '_' . 'slip';

    public function up(): void
    {
        if (Schema::hasColumn('activity_requests', self::COLUMN)) {
            Schema::table('activity_requests', function (Blueprint $table) {
                $table->dropColumn(self::COLUMN);
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('activity_requests', self::COLUMN)) {
            Schema::table('activity_requests', function (Blueprint $table) {
                $table->string(self::COLUMN)->nullable()->after('communication_letter');
            });
        }
    }
};