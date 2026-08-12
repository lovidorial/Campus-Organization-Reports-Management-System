<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gpoa_activities', function (Blueprint $table) {
            if (!Schema::hasColumn('gpoa_activities', 'expected_outcome')) {
                $table->text('expected_outcome')->nullable()->after('objectives');
            }

            if (!Schema::hasColumn('gpoa_activities', 'plan_key_strategy')) {
                $table->text('plan_key_strategy')->nullable()->after('preceding_activity');
            }

            if (!Schema::hasColumn('gpoa_activities', 'facilities_materials')) {
                $table->string('facilities_materials')->nullable()->after('person_in_charge');
            }

            if (!Schema::hasColumn('gpoa_activities', 'remarks')) {
                $table->string('remarks')->nullable()->after('facilities_materials');
            }

            if (!Schema::hasColumn('gpoa_activities', 'activity_level')) {
                $table->string('activity_level')->nullable()->after('category');
            }
        });
    }

    public function down(): void
    {
        Schema::table('gpoa_activities', function (Blueprint $table) {
            $columns = ['expected_outcome', 'plan_key_strategy', 'facilities_materials', 'remarks'];

            foreach ($columns as $column) {
                if (Schema::hasColumn('gpoa_activities', $column)) {
                    $table->dropColumn($column);
                }
            }

            if (Schema::hasColumn('gpoa_activities', 'activity_level')) {
                $table->dropColumn('activity_level');
            }
        });
    }
};
