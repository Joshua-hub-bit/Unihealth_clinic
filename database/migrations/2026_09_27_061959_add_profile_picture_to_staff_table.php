<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->mediumText('profile_picture')->nullable()->change();
        });

        Schema::table('staff', function (Blueprint $table) {
            $table->mediumText('profile_picture')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('staff', function (Blueprint $table) {
            $table->dropColumn('profile_picture');
        });

        Schema::table('students', function (Blueprint $table) {
            $table->text('profile_picture')->nullable()->change();
        });
    }
};
