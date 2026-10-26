<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $professionals = DB::table('staff')
            ->where('is_active', true)
            ->whereIn('role', ['physician', 'dentist'])
            ->orderBy('id')
            ->get(['id', 'campus_id', 'role'])
            ->groupBy('role');

        DB::table('appointments')
            ->whereNull('assigned_professional_id')
            ->whereIn('status', ['pending', 'confirmed'])
            ->orderBy('id')
            ->chunkById(200, function ($appointments) use ($professionals): void {
                $assignments = [];

                foreach ($appointments as $appointment) {
                    $role = Str::contains(Str::lower((string) $appointment->service_type), ['dental', 'dentist', 'oral'])
                        ? 'dentist'
                        : 'physician';
                    $roleProfessionals = $professionals->get($role, collect());
                    $professional = $appointment->campus_id === null
                        ? $roleProfessionals->first(fn ($staff) => $staff->campus_id === null)
                        : $roleProfessionals->first(fn ($staff) => (int) $staff->campus_id === (int) $appointment->campus_id)
                            ?? $roleProfessionals->first(fn ($staff) => $staff->campus_id === null);

                    if ($professional !== null) {
                        $assignments[$professional->id][] = $appointment->id;
                    }
                }

                foreach ($assignments as $professionalId => $appointmentIds) {
                    DB::table('appointments')
                        ->whereIn('id', $appointmentIds)
                        ->whereNull('assigned_professional_id')
                        ->update(['assigned_professional_id' => $professionalId]);
                }
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Keep the backfilled assignments if this data migration is rolled back alone.
    }
};
