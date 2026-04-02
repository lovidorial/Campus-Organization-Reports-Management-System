<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add term, SY, SC president, org fields to users
        Schema::table('users', function (Blueprint $table) {
            $table->string('term')->nullable()->after('role');           // e.g. "1st Term"
            $table->string('school_year')->nullable()->after('term');    // e.g. "2025-2026"
            $table->string('sc_president')->nullable()->after('school_year');
            $table->string('position')->nullable()->after('sc_president'); // position in org
            $table->string('org_name')->nullable()->after('position');
            $table->string('org_type')->nullable()->after('org_name');   // e.g. "Student Council", "Academic Org"
            $table->string('college')->nullable()->after('org_type');
        });

        // Add term, SY, category, basis_grading, reject_reason to activities
        Schema::table('activities', function (Blueprint $table) {
            $table->string('term')->nullable()->after('status');
            $table->string('school_year')->nullable()->after('term');
            $table->string('category')->nullable()->after('school_year');  // e.g. "Academic", "Sports"
            $table->string('basis_grading')->nullable()->after('category');
            $table->text('reject_reason')->nullable()->after('basis_grading');
        });

        // New table: organizations
        Schema::create('organizations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type')->nullable();       // e.g. "Student Council", "Academic Org"
            $table->string('college')->nullable();
            $table->string('sc_president')->nullable();
            $table->string('term')->nullable();
            $table->string('school_year')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Link users to organizations
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('organization_id')->nullable()->after('college');
            $table->foreign('organization_id')->references('id')->on('organizations')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['organization_id']);
            $table->dropColumn(['term','school_year','sc_president','position','org_name','org_type','college','organization_id']);
        });
        Schema::table('activities', function (Blueprint $table) {
            $table->dropColumn(['term','school_year','category','basis_grading','reject_reason']);
        });
        Schema::dropIfExists('organizations');
    }
};
