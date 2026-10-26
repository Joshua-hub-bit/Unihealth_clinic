<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consultation_recordings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('consultation_request_id')
                ->unique()
                ->constrained('consultation_requests')
                ->cascadeOnDelete();
            $table->binary('student_voice_data')->nullable();
            $table->string('student_voice_mime', 100)->nullable();
            $table->string('student_voice_filename')->nullable();
            $table->binary('official_audio_data')->nullable();
            $table->string('official_audio_mime', 100)->nullable();
            $table->string('official_audio_filename')->nullable();
            $table->timestamps();
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement(
                'ALTER TABLE consultation_recordings '
                .'MODIFY student_voice_data LONGBLOB NULL, '
                .'MODIFY official_audio_data LONGBLOB NULL',
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('consultation_recordings');
    }
};
