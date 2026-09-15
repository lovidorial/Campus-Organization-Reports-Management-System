<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! $this->isSqlite() || ! Schema::hasTable('activity_requests')) {
            return;
        }

        $columns = DB::select("PRAGMA table_info('activity_requests')");
        $gpoaActivityColumn = collect($columns)->firstWhere('name', 'gpoa_activity_id');

        if (! $gpoaActivityColumn || (int) $gpoaActivityColumn->notnull === 0) {
            return;
        }

        $existingRows = DB::table('activity_requests')->get()->map(fn ($row) => (array) $row)->all();

        DB::statement('ALTER TABLE activity_requests RENAME TO activity_requests_old');

        Schema::create('activity_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('gpoa_id')->nullable()->constrained('gpoas')->nullOnDelete();
            $table->foreignId('gpoa_activity_id')->nullable()->constrained('gpoa_activities')->nullOnDelete();
            $table->string('title');
            $table->date('date');
            $table->date('end_date')->nullable();
            $table->string('venue');
            $table->string('category')->nullable();
            $table->string('activity_level')->nullable();
            $table->json('sdgs')->nullable();
            $table->text('objectives')->nullable();
            $table->text('expected_outcome')->nullable();
            $table->text('plan_key_strategy')->nullable();
            $table->string('target_participants')->nullable();
            $table->string('person_in_charge')->nullable();
            $table->string('facilities_materials')->nullable();
            $table->decimal('estimated_budget', 10, 2)->nullable();
            $table->string('remarks')->nullable();
            $table->string('source_of_funds')->nullable();
            $table->string('preceding_activity')->nullable();
            $table->text('description')->nullable();
            $table->integer('participants_count')->nullable();
            $table->string('communication_letter')->nullable();
            $table->string('status')->default('pending');
            $table->text('reject_reason')->nullable();
            $table->timestamps();
        });

        if (! empty($existingRows)) {
            DB::table('activity_requests')->insert($existingRows);
        }

        DB::statement('DROP TABLE activity_requests_old');
    }

    public function down(): void
    {
        // Intentionally left as a no-op because this migration is only for SQLite test schema repair.
    }

    private function isSqlite(): bool
    {
        return DB::connection()->getDriverName() === 'sqlite';
    }
};
