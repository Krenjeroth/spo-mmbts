<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class PermissionRoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        $admin_permissions = Permission::all();

        // ?? Fix permissions for tabulators soon
        $tabulator_permissions = Permission::whereIn('title', [
          'score_view',
          'score_manage',
          'result_view',
          'result_manage',
          'result_print',
        ])->get();

        $judge_permissions = Permission::whereIn('title', [
          'score_view',
          'score_manage',
        ])->get();

        Role::find(1)->permissions()->attach($admin_permissions); // 1 = System Administrator
        Role::find(2)->permissions()->attach($tabulator_permissions); // 2 = Tabulator
        Role::find(3)->permissions()->attach($judge_permissions); // 3 = Judge
    }
}
