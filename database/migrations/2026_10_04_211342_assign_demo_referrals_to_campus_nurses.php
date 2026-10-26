<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $referrals = DB::table('referrals')
            ->whereNull('referred_by')
            ->where('referral_no', 'like', 'REF-DEMO-2026-%')
            ->where('reason', 'Synthetic sample referral for specialist follow-up.')
            ->get(['id', 'student_id']);

        foreach ($referrals as $referral) {
            $campusId = DB::table('students')->where('id', $referral->student_id)->value('campus_id');
            if ($campusId === null) {
                continue;
            }

            $nurseId = DB::table('staff')
                ->where('campus_id', $campusId)
                ->where('role', 'nurse')
                ->where('is_active', true)
                ->orderBy('id')
                ->value('id');

            if ($nurseId !== null) {
                DB::table('referrals')
                    ->where('id', $referral->id)
                    ->whereNull('referred_by')
                    ->update(['referred_by' => $nurseId]);
            }
        }
    }

    public function down(): void
    {
        // Nurse attribution is retained because it is valid referral history.
    }
};
