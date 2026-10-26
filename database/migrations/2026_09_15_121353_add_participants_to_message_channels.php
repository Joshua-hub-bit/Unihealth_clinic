<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('message_channels', function (Blueprint $table) {
            // For direct (DM) channels: store the two participant staff IDs.
            // NULL means it's a group / campus channel.
            $table->unsignedBigInteger('participant_a')->nullable()->after('campus_id');
            $table->unsignedBigInteger('participant_b')->nullable()->after('participant_a');

            // Unique constraint so we never create duplicate DM channels.
            $table->unique(['participant_a', 'participant_b'], 'uq_dm_participants');
        });
    }

    public function down(): void
    {
        Schema::table('message_channels', function (Blueprint $table) {
            $table->dropUnique('uq_dm_participants');
            $table->dropColumn(['participant_a', 'participant_b']);
        });
    }
};
