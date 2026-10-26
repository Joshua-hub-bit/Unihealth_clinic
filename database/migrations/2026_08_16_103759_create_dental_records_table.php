<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dental_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('dentist_id')->nullable()->constrained('staff')->nullOnDelete();
            $table->foreignId('campus_id')->nullable()->constrained('campuses')->nullOnDelete();
            $table->date('record_date');
            $table->text('chief_complaint')->nullable();
            $table->text('dental_history')->nullable();
            $table->text('oral_exam_findings')->nullable();
            // Tooth chart stored as JSON: { "11": "caries", "21": "missing", ... }
            $table->json('tooth_chart')->nullable();
            $table->text('treatment_done')->nullable();
            $table->text('treatment_plan')->nullable();
            $table->string('next_appointment', 100)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['student_id', 'record_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dental_records');
    }
};
