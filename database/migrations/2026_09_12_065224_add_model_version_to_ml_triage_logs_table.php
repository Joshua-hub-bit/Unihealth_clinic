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
        Schema::table('ml_triage_logs', function (Blueprint $table) {
            $table->string('model_version', 50)->nullable()->after('final_priority');
            $table->json('metadata')->nullable()->after('model_version');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ml_triage_logs', function (Blueprint $table) {
            $table->dropColumn(['model_version', 'metadata']);
        });
    }
};
