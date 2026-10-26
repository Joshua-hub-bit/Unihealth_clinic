<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table): void {
            $table->date('checkup_date')->nullable()->after('appt_date');
            $table->time('checkup_time')->nullable()->after('checkup_date');
            $table->timestamp('checkup_completed_at')->nullable()->after('checkup_time');
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table): void {
            $table->dropColumn(['checkup_date', 'checkup_time', 'checkup_completed_at']);
        });
    }
};
