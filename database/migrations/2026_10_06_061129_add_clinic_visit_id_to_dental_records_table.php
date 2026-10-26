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
        Schema::table('dental_records', function (Blueprint $table) {
            $table->foreignId('clinic_visit_id')
                ->nullable()
                ->unique()
                ->constrained('clinic_visits')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dental_records', function (Blueprint $table) {
            $table->dropUnique(['clinic_visit_id']);
            $table->dropConstrainedForeignId('clinic_visit_id');
        });
    }
};
