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
        Schema::table('messages', function (Blueprint $table) {
            $table->boolean('is_unsent')->default(false);
            $table->boolean('is_pinned')->default(false);
            $table->unsignedBigInteger('pinned_by')->nullable();
            $table->timestamp('edited_at')->nullable();
            $table->unsignedBigInteger('forwarded_from_id')->nullable()->index();
            $table->index(['channel_id', 'updated_at', 'id'], 'messages_channel_updated_at_id_index');
        });

        Schema::create('message_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('message_id')->constrained('messages')->cascadeOnDelete();
            $table->unsignedBigInteger('reported_by');
            $table->text('reason')->nullable();
            $table->timestamps();
            $table->unique(['message_id', 'reported_by']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('message_reports');

        Schema::table('messages', function (Blueprint $table) {
            $table->dropIndex(['forwarded_from_id']);
            $table->dropIndex('messages_channel_updated_at_id_index');
            $table->dropColumn([
                'is_unsent',
                'is_pinned',
                'pinned_by',
                'edited_at',
                'forwarded_from_id',
            ]);
        });
    }
};
