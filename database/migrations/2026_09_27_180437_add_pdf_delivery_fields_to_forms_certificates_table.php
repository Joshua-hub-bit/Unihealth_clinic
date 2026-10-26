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
        Schema::table('forms_certificates', function (Blueprint $table) {
            $table->string('pdf_path')->nullable();
            $table->timestamp('emailed_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('forms_certificates', function (Blueprint $table) {
            $table->dropColumn(['pdf_path', 'emailed_at']);
        });
    }
};
