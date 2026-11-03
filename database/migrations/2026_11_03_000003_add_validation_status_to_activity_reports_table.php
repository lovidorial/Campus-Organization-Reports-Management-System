<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activity_reports', function (Blueprint $table): void {
            if (! Schema::hasColumn('activity_reports', 'validation_status')) {
                $table->string('validation_status', 20)->nullable();
            }
            if (! Schema::hasColumn('activity_reports', 'validation_notes')) {
                $table->text('validation_notes')->nullable();
            }
            if (! Schema::hasColumn('activity_reports', 'validated_at')) {
                $table->timestamp('validated_at')->nullable();
            }
        });
    }

    public function down(): void
    {
    }
};
