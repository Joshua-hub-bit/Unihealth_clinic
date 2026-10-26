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
            $table->foreignId('consultation_request_id')
                ->nullable()
                ->after('student_id')
                ->constrained('consultation_requests')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('medical_transcriptions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('consultation_request_id');
        });
    }
};
