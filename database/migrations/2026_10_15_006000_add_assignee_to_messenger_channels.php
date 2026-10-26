<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('message_channels', function (Blueprint $table): void {
            $table->foreignId('assigned_staff_id')
                ->nullable()
                ->after('messenger_psid')
                ->constrained('staff')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('message_channels', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('assigned_staff_id');
        });
    }
};
