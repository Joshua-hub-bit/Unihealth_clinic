<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ml_triage_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->nullable()->constrained('students')->nullOnDelete();
            $table->foreignId('clinic_visit_id')->nullable()->constrained('clinic_visits')->nullOnDelete();
            $table->foreignId('campus_id')->nullable()->constrained('campuses')->nullOnDelete();
            // Input features
            $table->json('input_features');
            // Output
            $table->string('predicted_priority', 20);   // Priority 1 / 2 / 3
            $table->decimal('confidence_score', 5, 4)->nullable();
            $table->boolean('reviewed_by_staff')->default(false);
            $table->string('final_priority', 20)->nullable(); // staff override
            $table->timestamps();
        });

        Schema::create('ml_traffic_predictions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campus_id')->nullable()->constrained('campuses')->nullOnDelete();
            $table->date('prediction_date');
            $table->integer('predicted_count');
            $table->integer('actual_count')->nullable();
            $table->json('input_features')->nullable();
            $table->decimal('mae', 8, 4)->nullable();
            $table->timestamps();

            $table->unique(['campus_id', 'prediction_date']);
        });

        Schema::create('ml_anomaly_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campus_id')->nullable()->constrained('campuses')->nullOnDelete();
            $table->date('log_date');
            $table->json('input_features');
            $table->decimal('anomaly_score', 8, 6)->nullable();
            $table->boolean('is_anomaly')->default(false);
            $table->text('description')->nullable();
            $table->boolean('reviewed')->default(false);
            $table->timestamps();

            $table->index(['campus_id', 'log_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ml_anomaly_logs');
        Schema::dropIfExists('ml_traffic_predictions');
        Schema::dropIfExists('ml_triage_logs');
    }
};
