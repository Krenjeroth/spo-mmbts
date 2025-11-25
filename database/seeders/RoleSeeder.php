<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
          ['title' => 'Admin', 'created_at' => now(), 'updated_at' => now()],
          ['title' => 'Tabulator', 'created_at' => now(), 'updated_at' => now()],
          ['title' => 'Judge', 'created_at' => now(), 'updated_at' => now()],
        ];

        Role::insert($roles);
    }
}
