<?php

namespace Database\Seeders;

use App\Models\Campus;
use Illuminate\Database\Seeder;

class CampusSeeder extends Seeder
{
    public function run(): void
    {
        $campuses = [
            ['id' => 1, 'name' => 'Old Sagay Campus',  'code' => 'OLD-SAGAY'],
            ['id' => 2, 'name' => 'Main Campus', 'code' => 'SAGAY-MAIN'],
            ['id' => 3, 'name' => 'Laga-an Campus',    'code' => 'LAGA-AN'],
            ['id' => 4, 'name' => 'Escalante Campus',  'code' => 'ESCALANTE'],
        ];

        foreach ($campuses as $campus) {
            Campus::updateOrCreate(
                ['code' => $campus['code']],
                $campus,
            );
        }
    }
}
