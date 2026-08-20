<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gpoas', function (Blueprint $table) {
            if (!Schema::hasColumn('gpoas', 'prepared_by')) {
                $table->string('prepared_by')->nullable()->after('college');
            }
        });
    }

    public function down(): void
    {
        Schema::table('gpoas', function (Blueprint $table) {
            if (Schema::hasColumn('gpoas', 'prepared_by')) {
                $table->dropColumn('prepared_by');
            }
        });
    }
};
