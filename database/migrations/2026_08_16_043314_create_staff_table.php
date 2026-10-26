<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff', function (Blueprint $table) {
            $table->id();
            $table->string('employee_id', 30)->unique();
            $table->string('last_name', 80);
            $table->string('first_name', 80);
            $table->string('middle_name', 80)->default('');
            $table->string('email', 150)->unique();
            $table->string('password', 255);
            $table->string('role', 20)->default('staff');
            $table->string('license_no', 50)->nullable();
            $table->string('contact', 30)->default('');
            $table->string('specialization', 150)->default('');
            $table->boolean('is_active')->default(true);
            $table->foreignId('campus_id')->nullable()->constrained('campuses')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff');
    }
};
