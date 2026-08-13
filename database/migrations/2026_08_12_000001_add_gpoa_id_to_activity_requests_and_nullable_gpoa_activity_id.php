<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('activity_requests', 'gpoa_id')) {
            Schema::table('activity_requests', function (Blueprint $table) {
                $table->foreignId('gpoa_id')->nullable()->after('id')->constrained('gpoas')->nullOnDelete();
            });
        }

        if (! Schema::hasColumn('activity_requests', 'gpoa_activity_id')) {
            return;
        }

        // Drop the existing FK on gpoa_activity_id before altering it.
        $foreignKeyName = $this->getForeignKeyName('activity_requests', 'gpoa_activity_id');
        if ($foreignKeyName) {
            Schema::table('activity_requests', function (Blueprint $table) use ($foreignKeyName) {
                $table->dropForeign($foreignKeyName);
            });
        }

        DB::statement('ALTER TABLE activity_requests MODIFY gpoa_activity_id BIGINT UNSIGNED NULL');

        Schema::table('activity_requests', function (Blueprint $table) {
            $table->foreign('gpoa_activity_id')->references('id')->on('gpoa_activities')->nullOnDelete();
        });
    }

    public function down(): void
    {
        // Revert gpoa_activity_id to NOT NULL and restore cascade FK
        if (Schema::hasColumn('activity_requests', 'gpoa_activity_id')) {
            $foreignKeyName = $this->getForeignKeyName('activity_requests', 'gpoa_activity_id');
            if ($foreignKeyName) {
                Schema::table('activity_requests', function (Blueprint $table) use ($foreignKeyName) {
                    $table->dropForeign($foreignKeyName);
                });
            }

            DB::statement('ALTER TABLE activity_requests MODIFY gpoa_activity_id BIGINT UNSIGNED NOT NULL');

            Schema::table('activity_requests', function (Blueprint $table) {
                $table->foreign('gpoa_activity_id')->references('id')->on('gpoa_activities')->cascadeOnDelete();
            });
        }

        // Drop gpoa_id and its FK if present
        if (Schema::hasColumn('activity_requests', 'gpoa_id')) {
            $fk = $this->getForeignKeyName('activity_requests', 'gpoa_id');
            if ($fk) {
                Schema::table('activity_requests', function (Blueprint $table) use ($fk) {
                    $table->dropForeign($fk);
                });
            }
            Schema::table('activity_requests', function (Blueprint $table) {
                $table->dropColumn('gpoa_id');
            });
        }
    }

    private function getForeignKeyName(string $table, string $column): ?string
    {
        $dbName = DB::getDatabaseName();
        $result = DB::select(
            "SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE
             WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?
             AND REFERENCED_TABLE_NAME IS NOT NULL",
            [$dbName, $table, $column]
        );
        return $result[0]->CONSTRAINT_NAME ?? null;
    }
};
