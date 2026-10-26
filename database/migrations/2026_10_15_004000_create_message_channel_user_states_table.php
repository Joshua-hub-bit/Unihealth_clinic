<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('message_channel_user_states', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('channel_id')->constrained('message_channels')->cascadeOnDelete();
            $table->foreignId('staff_id')->constrained('staff')->cascadeOnDelete();
            $table->unsignedBigInteger('hidden_before_message_id')->nullable();
            $table->boolean('person_hidden')->default(false);
            $table->timestamps();

            $table->unique(['channel_id', 'staff_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('message_channel_user_states');
    }
};
