<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gpoa_modification_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gpoa_id')->constrained()->cascadeOnDelete();
            $table->foreignId('gpoa_activity_id')->nullable()->constrained('gpoa_activities')->nullOnDelete();
            $table->enum('type', ['add', 'remove', 'edit']);
            $table->json('payload')->nullable();
            $table->text('remarks');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->foreignId('requested_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['gpoa_id', 'status']);
        });
    }

    public function down(): void { Schema::dropIfExists('gpoa_modification_requests'); }
};