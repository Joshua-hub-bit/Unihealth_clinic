<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medical_transcriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('nurse_id')->nullable()->constrained('staff')->nullOnDelete();
            $table->foreignId('physician_id')->nullable()->constrained('staff')->nullOnDelete();
            $table->foreignId('clinic_visit_id')->nullable()->constrained('clinic_visits')->nullOnDelete();
            $table->foreignId('campus_id')->nullable()->constrained('campuses')->nullOnDelete();
            $table->date('consultation_date');
            $table->text('raw_transcript')->nullable();      // speech-to-text raw output
            $table->text('ai_draft')->nullable();            // AI-structured summary
            $table->text('approved_notes')->nullable();      // after physician review
            $table->string('status', 30)->default('draft'); // draft, pending_review, approved, rejected
            $table->text('rejection_reason')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->index(['student_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medical_transcriptions');
    }
};
