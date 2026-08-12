<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activity_requests', function (Blueprint $table) {
            if (! Schema::hasColumn('activity_requests', 'activity_level')) {
                $table->string('activity_level')->nullable()->after('category');
            }
            if (! Schema::hasColumn('activity_requests', 'sdgs')) {
                $table->json('sdgs')->nullable()->after('activity_level');
            }
            if (! Schema::hasColumn('activity_requests', 'objectives')) {
                $table->text('objectives')->nullable()->after('sdgs');
            }
            if (! Schema::hasColumn('activity_requests', 'expected_outcome')) {
                $table->text('expected_outcome')->nullable()->after('objectives');
            }
            if (! Schema::hasColumn('activity_requests', 'plan_key_strategy')) {
                $table->text('plan_key_strategy')->nullable()->after('expected_outcome');
            }
            if (! Schema::hasColumn('activity_requests', 'target_participants')) {
                $table->string('target_participants')->nullable()->after('plan_key_strategy');
            }
            if (! Schema::hasColumn('activity_requests', 'person_in_charge')) {
                $table->string('person_in_charge')->nullable()->after('target_participants');
            }
            if (! Schema::hasColumn('activity_requests', 'facilities_materials')) {
                $table->string('facilities_materials')->nullable()->after('person_in_charge');
            }
            if (! Schema::hasColumn('activity_requests', 'estimated_budget')) {
                $table->decimal('estimated_budget', 10, 2)->nullable()->after('facilities_materials');
            }
            if (! Schema::hasColumn('activity_requests', 'remarks')) {
                $table->string('remarks')->nullable()->after('estimated_budget');
            }
            if (! Schema::hasColumn('activity_requests', 'source_of_funds')) {
                $table->string('source_of_funds')->nullable()->after('remarks');
            }
            if (! Schema::hasColumn('activity_requests', 'preceding_activity')) {
                $table->string('preceding_activity')->nullable()->after('source_of_funds');
            }
        });
    }

    public function down(): void
    {
        Schema::table('activity_requests', function (Blueprint $table) {
            $table->dropColumn([
                'activity_level',
                'sdgs',
                'objectives',
                'expected_outcome',
                'plan_key_strategy',
                'target_participants',
                'person_in_charge',
                'facilities_materials',
                'estimated_budget',
                'remarks',
                'source_of_funds',
                'preceding_activity',
            ]);
        });
    }
};
