<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('referrals', function (Blueprint $table) {
            $table->text('vital_signs')->nullable();
            $table->string('signature_name', 150)->nullable();
            $table->string('signature_role', 150)->nullable();
            $table->string('signature_license_no', 50)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('referrals', function (Blueprint $table) {
            $table->dropColumn([
                'vital_signs',
                'signature_name',
                'signature_role',
                'signature_license_no',
            ]);
        });
    }
};
