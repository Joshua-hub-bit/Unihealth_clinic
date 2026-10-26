<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('referrals', function (Blueprint $table): void {
            $table->dateTime('scheduled_at')->nullable()->after('accepted_at');
            $table->foreignId('source_appointment_id')
                ->nullable()
                ->after('scheduled_at')
                ->constrained('appointments')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('referrals', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('source_appointment_id');
            $table->dropColumn('scheduled_at');
        });
    }
};
