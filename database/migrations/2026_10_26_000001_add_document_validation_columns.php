<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['activity_requests', 'gpoas', 'workflow_submissions'] as $tableName) {
            if (! Schema::hasTable($tableName)) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) use ($tableName): void {
                if (! Schema::hasColumn($tableName, 'validation_status')) {
                    $table->string('validation_status', 20)->nullable();
                }
                if (! Schema::hasColumn($tableName, 'validation_notes')) {
                    $table->text('validation_notes')->nullable();
                }
                if (! Schema::hasColumn($tableName, 'validated_at')) {
                    $table->timestamp('validated_at')->nullable();
                }
            });
        }
    }

    public function down(): void
    {
        foreach (['activity_requests', 'gpoas', 'workflow_submissions'] as $tableName) {
            if (! Schema::hasTable($tableName)) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) use ($tableName): void {
                $columns = array_values(array_filter(
                    ['validation_status', 'validation_notes', 'validated_at'],
                    fn (string $column): bool => Schema::hasColumn($tableName, $column)
                ));
                if ($columns !== []) {
                    $table->dropColumn($columns);
                }
            });
        }
    }
};