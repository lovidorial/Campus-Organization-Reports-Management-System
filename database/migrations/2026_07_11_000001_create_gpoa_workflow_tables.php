<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gpoas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('term');
            $table->string('school_year');
            $table->string('college')->nullable();
            $table->string('document_path')->nullable();
            $table->string('status')->default('pending');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('stored_at')->nullable();
            $table->text('reject_reason')->nullable();
            $table->timestamps();
        });

        Schema::create('gpoa_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gpoa_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->date('date');
            $table->string('venue');
            $table->string('category')->nullable();
            $table->text('description')->nullable();
            $table->integer('participants_count')->nullable();
            $table->string('basis_grading')->nullable();
            $table->timestamps();
        });

        Schema::create('activity_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('gpoa_activity_id')->constrained('gpoa_activities')->cascadeOnDelete();
            $table->string('title');
            $table->date('date');
            $table->string('venue');
            $table->string('category')->nullable();
            $table->text('description')->nullable();
            $table->integer('participants_count')->nullable();
            $table->string('communication_letter')->nullable();
            $table->string('status')->default('pending');
            $table->text('reject_reason')->nullable();
            $table->timestamps();
        });

        Schema::create('activity_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('activity_request_id')->constrained()->cascadeOnDelete();
            $table->string('narrative_report');
            $table->timestamp('submitted_at');
            $table->timestamps();
        });

        Schema::create('monitoring_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('activity_request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('gpoa_activity_id')->constrained('gpoa_activities')->cascadeOnDelete();
            $table->foreignId('admin_id')->constrained('users')->cascadeOnDelete();
            $table->string('compliance_status');
            $table->text('compliance_notes')->nullable();
            $table->timestamp('recorded_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monitoring_results');
        Schema::dropIfExists('activity_reports');
        Schema::dropIfExists('activity_requests');
        Schema::dropIfExists('gpoa_activities');
        Schema::dropIfExists('gpoas');
    }
};
