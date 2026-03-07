<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DepartmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        DB::table('departments')->insert([
            [
                'department_name' => 'President of Administration',
                'department_code' => 'PA',
            ],
            [
                'department_name' => 'Vice President of Administration',
                'department_code' => 'VPA',
            ],
            [
                'department_name' => 'Buildings and Upkeep Management',
                'department_code' => 'BUM',
            ],
            [
                'department_name' => 'Educational Media Center',
                'department_code' => 'EMC',
            ],
            [
                'department_name' => 'Electrical Maintenance Services',
                'department_code' => 'EMS',
            ],
            [
                'department_name' => 'University Computer Service Center',
                'department_code' => 'UCSC',
            ],
            [
                'department_name' => 'Security Services',
                'department_code' => 'CTSSO',
            ],
            [
                'department_name' => 'Grounds and Upkeep Maintenance Services',
                'department_code' => 'GUM',
            ],
            [
                'department_name' => 'Schedule Coordinator',
                'department_code' => 'SC',
            ],
        ]);
    }
}
