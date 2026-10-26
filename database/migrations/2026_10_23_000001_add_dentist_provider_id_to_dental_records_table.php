<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dental_records', function (Blueprint $table): void {
            $table->foreignId('dentist_provider_id')
                ->nullable()
                ->after('dentist_id')
                ->constrained('clinic_providers')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('dental_records', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('dentist_provider_id');
        });
    }
};
