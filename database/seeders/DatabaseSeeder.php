<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        $this->call([
            MunicipalitySeeder::class,
            PermissionSeeder::class,
            RoleSeeder::class,
            PermissionRoleSeeder::class,
            UserSeeder::class,
            PageantEventSeeder::class,
            // PhaseSeeder::class,
            // CategoryAndCriteriaSeeder::class,
            ContestantSeeder::class,
            // JudgeSeeder::class,
            // ScoreSeeder::class,
        ]);
    }
}
