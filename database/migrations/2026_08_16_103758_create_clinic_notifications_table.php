<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clinic_notifications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('recipient_id');
            $table->string('recipient_type', 20)->default('student'); // student, staff
            $table->string('type', 50)->default('general');           // appointment, referral, transcription, system
            $table->string('title', 150);
            $table->text('body')->nullable();
            $table->json('data')->nullable();                          // extra context (appointment_id, etc.)
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['recipient_id', 'recipient_type', 'is_read']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clinic_notifications');
    }
};
