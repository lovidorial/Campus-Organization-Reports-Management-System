<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_deadlines', function (Blueprint $table) {
            $table->id();
            $table->string('document_type', 40);
            $table->string('term', 50);
            $table->string('school_year', 20);
            $table->date('deadline_date');
            $table->timestamps();

            $table->unique(
                ['document_type', 'term', 'school_year'],
                'document_deadlines_document_period_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_deadlines');
    }
};
