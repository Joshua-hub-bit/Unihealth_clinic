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
        Schema::table('appointments', function (Blueprint $table) {
            $table->string('triage_priority', 20)->nullable();
            $table->decimal('triage_confidence', 5, 4)->nullable();
            $table->json('triage_details')->nullable();
            $table->foreignId('triage_log_id')->nullable()->constrained('ml_triage_logs')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('triage_log_id');
            $table->dropColumn(['triage_priority', 'triage_confidence', 'triage_details']);
        });
    }
};
