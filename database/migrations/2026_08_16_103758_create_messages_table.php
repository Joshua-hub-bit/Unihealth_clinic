<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('message_channels', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('type', 30)->default('campus'); // campus, team, referrals, direct, messenger
            $table->foreignId('campus_id')->nullable()->constrained('campuses')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('channel_id')->constrained('message_channels')->cascadeOnDelete();
            $table->unsignedBigInteger('sender_id');
            $table->string('sender_type', 20)->default('staff'); // staff, student, messenger
            $table->text('body');
            $table->boolean('is_system')->default(false);
            $table->timestamps();

            $table->index(['channel_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('messages');
        Schema::dropIfExists('message_channels');
    }
};
