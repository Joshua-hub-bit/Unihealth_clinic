<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('consultation_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('campus_id')->nullable()->constrained('campuses')->nullOnDelete();
            $table->foreignId('nurse_id')->nullable()->constrained('staff')->nullOnDelete();
            $table->foreignId('physician_id')->nullable()->constrained('staff')->nullOnDelete();
            $table->foreignId('clinic_visit_id')->nullable()->constrained('clinic_visits')->nullOnDelete();
            $table->foreignId('referral_id')->nullable()->constrained('referrals')->nullOnDelete();
            $table->foreignId('medical_transcription_id')->nullable()->constrained('medical_transcriptions')->nullOnDelete();
            $table->foreignId('triage_log_id')->nullable()->constrained('ml_triage_logs')->nullOnDelete();
            $table->string('care_type', 20)->default('medical');
            $table->text('concern');
            $table->string('symptom_onset', 120)->nullable();
            $table->unsignedTinyInteger('severity')->nullable();
            $table->string('status', 40)->default('submitted');
            $table->string('triage_priority', 20)->nullable();
            $table->decimal('triage_confidence', 5, 4)->nullable();
            $table->json('triage_details')->nullable();
            $table->string('student_voice_path')->nullable();
            $table->string('student_photo_path')->nullable();
            $table->string('official_audio_path')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('identity_confirmed_at')->nullable();
            $table->timestamp('recording_consent_at')->nullable();
            $table->timestamp('recording_started_at')->nullable();
            $table->timestamp('audio_uploaded_at')->nullable();
            $table->timestamp('nurse_reviewed_at')->nullable();
            $table->timestamp('physician_reviewed_at')->nullable();
            $table->dateTime('scheduled_at')->nullable();
            $table->text('nurse_summary')->nullable();
            $table->text('staff_notes')->nullable();
            $table->text('nursing_advice')->nullable();
            $table->text('approved_instructions')->nullable();
            $table->text('diagnosis')->nullable();
            $table->text('treatment_plan')->nullable();
            $table->text('follow_up')->nullable();
            $table->text('emergency_instructions')->nullable();
            $table->timestamps();

            $table->index(['campus_id', 'status']);
            $table->index(['student_id', 'created_at']);
            $table->index(['nurse_id', 'status']);
            $table->index(['physician_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('consultation_requests');
    }
};
