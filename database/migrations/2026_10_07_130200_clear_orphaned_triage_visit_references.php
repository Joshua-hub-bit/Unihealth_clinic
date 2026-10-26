<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('ml_triage_logs')
            || ! Schema::hasTable('clinic_visits')
            || ! Schema::hasColumn('ml_triage_logs', 'clinic_visit_id')
        ) {
            return;
        }

        DB::table('ml_triage_logs')
            ->whereNotNull('clinic_visit_id')
            ->whereNotExists(function ($query): void {
                $query->selectRaw('1')
                    ->from('clinic_visits')
                    ->whereColumn('clinic_visits.id', 'ml_triage_logs.clinic_visit_id');
            })
            ->update(['clinic_visit_id' => null]);
    }

    public function down(): void
    {
        // A deleted visit cannot be safely re-linked during rollback.
    }
};
