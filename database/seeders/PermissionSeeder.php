<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            // Users
            ['title' => 'user_view', 'created_at' => now(), 'updated_at' => now()],
            ['title' => 'user_manage', 'created_at' => now(), 'updated_at' => now()],

            // Roles
            ['title' => 'role_view', 'created_at' => now(), 'updated_at' => now()],
            ['title' => 'role_manage', 'created_at' => now(), 'updated_at' => now()],

            // Contestants
            ['title' => 'contestant_view', 'created_at' => now(), 'updated_at' => now()],
            ['title' => 'contestant_manage', 'created_at' => now(), 'updated_at' => now()],

            // Judges
            ['title' => 'judge_view', 'created_at' => now(), 'updated_at' => now()],
            ['title' => 'judge_manage', 'created_at' => now(), 'updated_at' => now()],

            // Scores
            ['title' => 'score_view', 'created_at' => now(), 'updated_at' => now()],
            ['title' => 'score_manage', 'created_at' => now(), 'updated_at' => now()],

            // Results
            ['title' => 'result_view', 'created_at' => now(), 'updated_at' => now()],
            ['title' => 'result_manage', 'created_at' => now(), 'updated_at' => now()],
            ['title' => 'result_print', 'created_at' => now(), 'updated_at' => now()],

            // Pageant Events
            ['title' => 'event_view', 'created_at' => now(), 'updated_at' => now()],
            ['title' => 'event_manage', 'created_at' => now(), 'updated_at' => now()],
        ];

        Permission::insert($permissions);
    }
}
