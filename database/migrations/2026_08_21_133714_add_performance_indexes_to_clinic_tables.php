<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // clinic_visits: dashboard queries filter/group by visit_date and campus_id together
        Schema::table('clinic_visits', function (Blueprint $table) {
            $table->index(['visit_date', 'campus_id'], 'idx_visits_date_campus');
            $table->index('visit_date', 'idx_visits_date');
        });

        // appointments: dashboard filters by appt_date + status + campus_id
        Schema::table('appointments', function (Blueprint $table) {
            $table->index(['appt_date', 'campus_id'], 'idx_appointments_date_campus');
            $table->index(['status', 'campus_id'], 'idx_appointments_status_campus');
        });

        // referrals: has no indexes at all besides PK — status and from_campus_id are both filtered heavily
        Schema::table('referrals', function (Blueprint $table) {
            $table->index(['status', 'from_campus_id'], 'idx_referrals_status_campus');
            $table->index('from_campus_id', 'idx_referrals_campus');
        });
    }

    public function down(): void
    {
        Schema::table('clinic_visits', function (Blueprint $table) {
            $table->dropIndex('idx_visits_date_campus');
            $table->dropIndex('idx_visits_date');
        });

        Schema::table('appointments', function (Blueprint $table) {
            $table->dropIndex('idx_appointments_date_campus');
            $table->dropIndex('idx_appointments_status_campus');
        });

        Schema::table('referrals', function (Blueprint $table) {
            $table->dropIndex('idx_referrals_status_campus');
            $table->dropIndex('idx_referrals_campus');
        });
    }
};
