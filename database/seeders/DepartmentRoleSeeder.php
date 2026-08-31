<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DepartmentRoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            [
                'role_name' => 'Department Head',
                'description' => 'Oversees all departmental operations, manages resources, and approves major requisitions and decisions.',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'role_name' => 'Staff',
                'description' => 'Handles day-to-day departmental tasks, manages requests, and assists with operational duties.',
                'created_at' => now(),
                'updated_at' => now()
            ]
        ];
        DB::table('department_roles')->insert($roles);
    }
}
