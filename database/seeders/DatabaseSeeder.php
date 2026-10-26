<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Laravel starter-kit demo user
        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        // Base reference data only. Synthetic past records are opt-in via PastRecordsSeeder.
        $this->call([
            CampusSeeder::class,
            StudentSeeder::class,
            StaffSeeder::class,
            SystemSettingSeeder::class,
        ]);
    }
}
