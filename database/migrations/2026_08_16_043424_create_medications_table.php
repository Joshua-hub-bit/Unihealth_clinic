<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->string('name', 150);
            $table->string('dosage', 80)->nullable();
            $table->string('schedule', 100)->nullable();
            $table->string('purpose', 150)->nullable();
            $table->boolean('school_kept')->default(false);
            $table->boolean('consent_on_file')->default(false);
            $table->boolean('active')->default(true);
            $table->foreignId('prescribed_by')->nullable()->constrained('staff')->nullOnDelete();
            $table->foreignId('campus_id')->nullable()->constrained('campuses')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medications');
    }
};
