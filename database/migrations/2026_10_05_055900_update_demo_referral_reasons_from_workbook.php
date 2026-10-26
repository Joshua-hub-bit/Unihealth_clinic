<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $referrals = [
            'REF-DEMO-2026-00001' => [
                'student_id' => '23-0094',
                'reason' => 'Ginatingog ug nagasakit ang ulo tungod sa sige tan-aw sa screen',
            ],
            'REF-DEMO-2026-00002' => [
                'student_id' => '23-0116',
                'reason' => 'Nagasakit kag nagapula ang mga mata tungod saabog sa dalan',
            ],
            'REF-DEMO-2026-00003' => [
                'student_id' => '23-0143',
                'reason' => 'Nagasakit ang tyan kag nagabaliskad ang tyan human magkaon sa canteen',
            ],
            'REF-DEMO-2026-00004' => [
                'student_id' => '23-0148',
                'reason' => 'Gakainit ang lawas, ginapoy, kag ginapaban-og',
            ],
            'REF-DEMO-2026-00005' => [
                'student_id' => '23-0184',
                'reason' => 'Nagaubo, nagasakit ang tutunlan, kag may sip-on',
            ],
            'REF-DEMO-2026-00006' => [
                'student_id' => '23-0249',
                'reason' => 'Nalipong kag gapanuot ang panulok kay wala nakakaon',
            ],
            'REF-DEMO-2026-00007' => [
                'student_id' => '23-0262',
                'reason' => 'Regular check-up kag gusto pacheck sang blood pressure',
            ],
            'REF-DEMO-2026-00008' => [
                'student_id' => '23-0294',
                'reason' => 'Nagapangayo fit-to-return certificate human nag-trangkaso',
            ],
            'REF-DEMO-2026-00009' => [
                'student_id' => '23-0307',
                'reason' => 'Regular check-up kag gusto pacheck sang blood pressure',
            ],
            'REF-DEMO-2026-00010' => [
                'student_id' => '23-0334',
                'reason' => 'Nagasakit ang tyan kag nagabaliskad ang tyan human magkaon sa canteen',
            ],
            'REF-DEMO-2026-00011' => [
                'student_id' => '23-0347',
                'reason' => 'Napiang ang tiil sa hampang sa intramurals',
            ],
            'REF-DEMO-2026-00012' => [
                'student_id' => '23-0350',
                'reason' => 'Nasunog/napaso ang panit sa laboratory experiment',
            ],
        ];

        foreach ($referrals as $referralNo => $referral) {
            DB::table('referrals')
                ->where('referral_no', $referralNo)
                ->where('reason', 'Synthetic sample referral for specialist follow-up.')
                ->whereIn('student_id', DB::table('students')
                    ->select('id')
                    ->where('student_id', $referral['student_id']))
                ->update(['reason' => $referral['reason']]);
        }
    }

    public function down(): void {}
};
