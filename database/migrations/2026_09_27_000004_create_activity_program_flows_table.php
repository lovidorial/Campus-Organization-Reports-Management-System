<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_program_flows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('activity_request_id')->constrained()->cascadeOnDelete();
            $table->string('time');
            $table->string('flow');
            $table->string('person_in_charge');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->index(['activity_request_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_program_flows');
    }
};