<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clinic_availabilities', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('campus_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->constrained('staff')->cascadeOnDelete();
            $table->string('provider_type', 20);
            $table->date('available_date');
            $table->time('opens_at');
            $table->time('closes_at');
            $table->unsignedTinyInteger('capacity');
            $table->json('available_times');
            $table->timestamps();
            $table->unique(['campus_id', 'provider_type', 'available_date'], 'clinic_availability_day_unique');
        });

        Schema::table('appointments', function (Blueprint $table): void {
            $table->foreignId('availability_id')
                ->nullable()
                ->after('campus_id')
                ->constrained('clinic_availabilities')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('availability_id');
        });

        Schema::dropIfExists('clinic_availabilities');
    }
};
