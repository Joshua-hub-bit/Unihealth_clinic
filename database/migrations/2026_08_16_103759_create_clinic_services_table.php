<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clinic_services', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->string('category', 50)->default('Medical'); // Medical, Dental, Laboratory, Certificate
            $table->text('description')->nullable();
            $table->string('duration_minutes', 20)->nullable();
            $table->boolean('requires_appointment')->default(true);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clinic_services');
    }
};
