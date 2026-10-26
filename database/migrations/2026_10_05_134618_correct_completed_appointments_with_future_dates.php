<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $today = today()->toDateString();

        DB::table('appointments')
            ->where('status', 'completed')
            ->whereDate('appt_date', '>', $today)
            ->whereNotNull('updated_at')
            ->whereDate('updated_at', '<=', $today)
            ->update(['appt_date' => DB::raw('DATE(updated_at)')]);

        DB::table('appointments')
            ->where('status', 'completed')
            ->whereDate('appt_date', '>', $today)
            ->update(['appt_date' => $today]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Completed appointments are historical records; their corrected dates cannot be reconstructed.
    }
};
