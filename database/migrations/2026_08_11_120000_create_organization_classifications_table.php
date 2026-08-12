<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organization_classifications', function (Blueprint $table) {
            $table->id();
            $table->string('org_name')->unique();
            $table->json('aliases')->nullable();
            $table->string('classification');
            $table->string('college_area');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organization_classifications');
    }
};
