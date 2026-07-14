<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('organization_workflows')) {
            Schema::create('organization_workflows', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->string('term');
                $table->string('school_year');
                $table->string('current_stage')->default('gpoa_pending');
                $table->unsignedTinyInteger('completion_percentage')->default(0);
                $table->boolean('is_completed')->default(false);
                $table->boolean('is_locked')->default(false);
                $table->foreignId('reopened_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('reopened_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->timestamps();

                $table->unique(['user_id', 'term', 'school_year'], 'org_wf_user_term_sy_unique');
            });
        }

        if (!Schema::hasTable('workflow_submissions')) {
            Schema::create('workflow_submissions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('organization_workflow_id')->constrained()->cascadeOnDelete();
                $table->string('document_type');
                $table->unsignedInteger('version')->default(1);
                $table->foreignId('gpoa_id')->nullable()->constrained()->nullOnDelete();
                $table->string('file_path')->nullable();
                $table->string('status')->default('pending');
                $table->timestamp('submitted_at')->nullable();
                $table->timestamp('approved_at')->nullable();
                $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->text('approval_remarks')->nullable();
                $table->text('reject_reason')->nullable();
                $table->boolean('is_current')->default(true);
                $table->timestamps();

                $table->index(['organization_workflow_id', 'document_type', 'is_current'], 'wf_subs_doc_current_idx');
            });
        }

        if (!Schema::hasTable('workflow_events')) {
            Schema::create('workflow_events', function (Blueprint $table) {
                $table->id();
                $table->foreignId('organization_workflow_id')->constrained()->cascadeOnDelete();
                $table->foreignId('workflow_submission_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
                $table->string('event_type');
                $table->string('description');
                $table->json('metadata')->nullable();
                $table->timestamp('created_at')->useCurrent();
            });
        }

        if (!Schema::hasTable('user_notifications')) {
            Schema::create('user_notifications', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->string('type');
                $table->string('title');
                $table->text('message');
                $table->timestamp('read_at')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('user_notifications');
        Schema::dropIfExists('workflow_events');
        Schema::dropIfExists('workflow_submissions');
        Schema::dropIfExists('organization_workflows');
    }
};
