<?php

namespace Database\Seeders;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class AdminDepartmentSeeder extends Seeder
{
    public function run()
    {
        // Clear existing records
        DB::table('admin_departments')->truncate();

        // Define the specific records from your export with the exact timestamps
        $adminDepartments = [
            [
                'admin_id' => 1,
                'department_id' => 1,
                'is_primary' => 1,
                'created_at' => '2026-03-07 08:18:43',
                'updated_at' => '2026-03-07 08:18:43'
            ],
            [
                'admin_id' => 2,
                'department_id' => 1,
                'is_primary' => 1,
                'created_at' => '2026-03-07 08:18:43',
                'updated_at' => '2026-03-07 08:18:43'
            ],
            [
                'admin_id' => 3,
                'department_id' => 2,
                'is_primary' => 1,
                'created_at' => '2026-03-07 08:18:43',
                'updated_at' => '2026-03-07 08:18:43'
            ],
            [
                'admin_id' => 4,
                'department_id' => 3,
                'is_primary' => 0,
                'created_at' => '2026-03-07 12:12:28',
                'updated_at' => '2026-03-07 12:12:28'
            ],
            [
                'admin_id' => 5,
                'department_id' => 5,
                'is_primary' => 1,
                'created_at' => '2026-03-07 08:18:43',
                'updated_at' => '2026-03-07 08:18:43'
            ],
            [
                'admin_id' => 6,
                'department_id' => 7,
                'is_primary' => 1,
                'created_at' => '2026-03-07 08:18:43',
                'updated_at' => '2026-03-07 08:18:43'
            ],
            [
                'admin_id' => 7,
                'department_id' => 4,
                'is_primary' => 1,
                'created_at' => '2026-03-07 08:18:43',
                'updated_at' => '2026-03-07 08:18:43'
            ],
            [
                'admin_id' => 8,
                'department_id' => 8,
                'is_primary' => 1,
                'created_at' => '2026-03-07 08:18:43',
                'updated_at' => '2026-03-07 08:18:43'
            ],
            [
                'admin_id' => 9,
                'department_id' => 6,
                'is_primary' => 1,
                'created_at' => '2026-03-07 08:18:43',
                'updated_at' => '2026-03-07 08:18:43'
            ],
            [
                'admin_id' => 10,
                'department_id' => 9,
                'is_primary' => 1,
                'created_at' => '2026-03-07 08:18:43',
                'updated_at' => '2026-03-07 08:18:43'
            ],
            [
                'admin_id' => 11,
                'department_id' => 4,
                'is_primary' => 0,
                'created_at' => '2026-03-07 12:31:44',
                'updated_at' => '2026-03-07 12:31:44'
            ]
        ];

        // Insert the records
        DB::table('admin_departments')->insert($adminDepartments);
        
        $this->command->info('Admin departments seeded successfully with ' . count($adminDepartments) . ' records.');
    }
}