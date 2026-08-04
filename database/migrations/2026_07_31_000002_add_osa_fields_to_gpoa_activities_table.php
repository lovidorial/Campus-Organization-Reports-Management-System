<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gpoa_activities', function (Blueprint $table) {
            if (!Schema::hasColumn('gpoa_activities', 'objectives')) {
                $table->text('objectives')->nullable()->after('category');
            }
            if (!Schema::hasColumn('gpoa_activities', 'target_participants')) {
                $table->string('target_participants')->nullable()->after('objectives');
            }
            if (!Schema::hasColumn('gpoa_activities', 'estimated_budget')) {
                $table->decimal('estimated_budget', 15, 2)->nullable()->after('target_participants');
            }
            if (!Schema::hasColumn('gpoa_activities', 'source_of_funds')) {
                $table->string('source_of_funds')->nullable()->after('estimated_budget');
            }
            if (!Schema::hasColumn('gpoa_activities', 'person_in_charge')) {
                $table->string('person_in_charge')->nullable()->after('source_of_funds');
            }
            if (!Schema::hasColumn('gpoa_activities', 'sdgs')) {
                $table->json('sdgs')->nullable()->after('person_in_charge');
            }
            if (!Schema::hasColumn('gpoa_activities', 'preceding_activity')) {
                $table->string('preceding_activity')->nullable()->after('sdgs');
            }
        });
    }

    public function down(): void
    {
        Schema::table('gpoa_activities', function (Blueprint $table) {
            $table->dropColumn([
                'objectives',
                'target_participants',
                'estimated_budget',
                'source_of_funds',
                'person_in_charge',
                'sdgs',
                'preceding_activity',
            ]);
        });
    }
};
