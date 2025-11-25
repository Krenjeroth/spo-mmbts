<?php

namespace Database\Seeders;

use App\Models\Municipality;
use Illuminate\Database\Seeder;

class MunicipalitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $municipalities = [
            ['name' => 'Atok'],
            ['name' => 'Bakun'],
            ['name' => 'Bokod'],
            ['name' => 'Buguias'],
            ['name' => 'Itogon'],
            ['name' => 'Kabayan'],
            ['name' => 'Kapangan'],
            ['name' => 'Kibungan'],
            ['name' => 'La Trinidad'],
            ['name' => 'Mankayan'],
            ['name' => 'Sablan'],
            ['name' => 'Tuba'],
            ['name' => 'Tublay'],
        ];

        foreach ($municipalities as $municipality) {
            Municipality::updateOrCreate(
                ['name' => $municipality['name']],
                ['updated_at' => now()] // Ensures existing entries get timestamp refreshed
            );
        }

        $count = Municipality::count();
        $this->command->info("✅ Seeded {$count} municipalities successfully.");
    }
}
