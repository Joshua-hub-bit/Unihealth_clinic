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
        Schema::table('health_records', function (Blueprint $table) {
            $table->text('imported_medical_history')->nullable();
            $table->text('imported_booking_choice')->nullable();
            $table->text('imported_reason_symptoms')->nullable();
            $table->string('imported_priority_level', 50)->nullable();
            $table->string('imported_prescriber', 150)->nullable();
            $table->text('imported_prescription')->nullable();
            $table->string('source_dataset', 255)->nullable();
            $table->unsignedInteger('source_record_no')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('health_records', function (Blueprint $table) {
            $table->dropColumn([
                'imported_medical_history',
                'imported_booking_choice',
                'imported_reason_symptoms',
                'imported_priority_level',
                'imported_prescriber',
                'imported_prescription',
                'source_dataset',
                'source_record_no',
            ]);
        });
    }
};
