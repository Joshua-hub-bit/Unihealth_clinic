<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('referrals', function (Blueprint $table) {
            // Where the nurse routes the referral: 'physician' or 'dentist'.
            // Nullable so the existing seed data and pre-route_to rows remain
            // valid; new rows created by nurses must always set it.
            $table->string('route_to', 20)->nullable()->after('referral_type');
        });
    }

    public function down(): void
    {
        Schema::table('referrals', function (Blueprint $table) {
            $table->dropColumn('route_to');
        });
    }
};
