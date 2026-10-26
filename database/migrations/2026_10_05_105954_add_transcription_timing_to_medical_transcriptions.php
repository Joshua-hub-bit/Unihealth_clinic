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
        Schema::table('medical_transcriptions', function (Blueprint $table) {
            $table->unsignedInteger('audio_duration_seconds')->nullable();
            $table->unsignedInteger('processing_duration_seconds')->nullable();
            $table->timestamp('processing_started_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('medical_transcriptions', function (Blueprint $table) {
            $table->dropColumn([
                'audio_duration_seconds',
                'processing_duration_seconds',
                'processing_started_at',
            ]);
        });
    }
};
