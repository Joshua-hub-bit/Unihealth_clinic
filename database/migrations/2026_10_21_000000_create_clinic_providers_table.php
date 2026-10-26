<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clinic_providers', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 150);
            $table->string('provider_type', 20);
            $table->string('designation', 100)->nullable();
            $table->string('specialty', 150)->nullable();
            $table->string('license_no', 50)->nullable();
            $table->string('email', 150)->nullable();
            $table->string('contact', 30)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['provider_type', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clinic_providers');
    }
};
