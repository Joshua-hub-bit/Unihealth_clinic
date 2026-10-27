<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('students')
            ->whereIn('patient_type', ['faculty', 'instructor'])
            ->update(['patient_type' => 'instructor_faculty']);
    }

    public function down(): void
    {
        DB::table('students')
            ->where('patient_type', 'instructor_faculty')
            ->update(['patient_type' => 'faculty']);
    }
};
