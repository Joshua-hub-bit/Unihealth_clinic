<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('message_channels', function (Blueprint $table): void {
            $table->string('messenger_psid', 191)->nullable()->unique();
        });

        Schema::table('messages', function (Blueprint $table): void {
            $table->string('external_message_id', 191)->nullable()->unique();
        });
    }

    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table): void {
            $table->dropUnique(['external_message_id']);
            $table->dropColumn('external_message_id');
        });

        Schema::table('message_channels', function (Blueprint $table): void {
            $table->dropUnique(['messenger_psid']);
            $table->dropColumn('messenger_psid');
        });
    }
};
