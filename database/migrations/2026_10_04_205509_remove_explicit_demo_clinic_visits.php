<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('clinic_visits')
            ->where('reason', 'Routine wellness screening (synthetic demo data)')
            ->where('treatment', 'Synthetic sample: routine vital-sign check; no real treatment provided.')
            ->where('notes', 'DEMO DATA: synthetic vital-sign encounter; not a real clinical visit.')
            ->delete();
    }

    public function down(): void
    {
        // Deleted synthetic records cannot be restored by rolling back this migration.
    }
};
