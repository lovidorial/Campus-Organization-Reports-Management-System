<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('document_deadlines', function (Blueprint $table) {
            $table->date('deadline_date')->nullable()->change();
            $table->unsignedSmallInteger('grace_days')->nullable()->after('deadline_date');
        });
    }

    public function down(): void
    {
        if (DB::table('document_deadlines')->whereNull('deadline_date')->exists()) {
            throw new RuntimeException('Cannot make document_deadlines.deadline_date required while grace-only rows exist.');
        }

        Schema::table('document_deadlines', function (Blueprint $table) {
            $table->date('deadline_date')->nullable(false)->change();
            $table->dropColumn('grace_days');
        });
    }
};