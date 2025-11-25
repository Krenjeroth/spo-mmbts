<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $adminRole = Role::where('title', 'Admin')->first();
        $tabRole = Role::where('title', 'Tabulator')->first();

        // Create Admin
        $admin = User::create([
            'name' => 'System Administrator',
            'email' => 'admin@mmb.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'remember_token' => Str::random(10),
        ]);

        $admin->roles()->attach($adminRole->id);

        // Create 5 Tabulators
        for ($i = 1; $i <= 5; $i++) {
            $tabulator = User::create([
                'name' => "Tabulator {$i}",
                'email' => "tabulator{$i}@mmb.com",
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'remember_token' => Str::random(10),
            ]);

            $tabulator->roles()->attach($tabRole->id);
        }
    }
}
