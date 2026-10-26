<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('ml_traffic_predictions')) {
            Schema::create('ml_traffic_predictions', function (Blueprint $table): void {
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
        }

        if (! Schema::hasTable('ml_anomaly_logs')) {
            Schema::create('ml_anomaly_logs', function (Blueprint $table): void {
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
    }

    public function down(): void
    {
        // Preserve repaired tables and any data written after this migration.
    }
};
