<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->string('student_id', 30)->unique();
            $table->string('last_name', 80);
            $table->string('first_name', 80);
            $table->string('middle_name', 80)->default('');
            $table->string('email', 150)->unique();
            $table->string('password', 255);
            $table->string('course_year', 100)->default('');
            $table->string('department', 120)->default('');
            $table->string('section', 30)->default('');
            $table->string('contact', 30)->default('');
            $table->string('address', 255)->default('');
            $table->string('sex', 20)->default('');
            $table->string('civil_status', 20)->default('');
            $table->integer('age')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('blood_type', 10)->nullable();
            $table->text('profile_picture')->nullable();
            $table->string('role', 20)->default('student');
            $table->boolean('is_active')->default(true);
            $table->foreignId('campus_id')->nullable()->constrained('campuses')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
