<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('health_records')
            ->whereNotNull('imported_medical_history')
            ->where('imported_medical_history', '<>', '')
            ->where(function (Builder $query): void {
                $query->whereNull('past_medical_history')
                    ->orWhere('past_medical_history', '');
            })
            ->update([
                'past_medical_history' => DB::raw('imported_medical_history'),
            ]);
    }

    public function down(): void
    {
        // The backfill may be edited after migration, so copied history is not safe to remove.
    }
};
