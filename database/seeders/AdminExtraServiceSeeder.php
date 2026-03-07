<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AdminExtraServiceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clear existing records
        DB::table('admin_services')->truncate();

        $adminServices = [
            // Admin ID 1
            ['admin_id' => 1, 'service_id' => 2],
            ['admin_id' => 1, 'service_id' => 4],
            ['admin_id' => 1, 'service_id' => 5],
            ['admin_id' => 1, 'service_id' => 7],
            ['admin_id' => 1, 'service_id' => 10],
            
            // Admin ID 5
            ['admin_id' => 5, 'service_id' => 4],
            ['admin_id' => 5, 'service_id' => 5],
            
            // Admin ID 6
            ['admin_id' => 6, 'service_id' => 9],
            
            // Admin ID 7
            ['admin_id' => 7, 'service_id' => 1],
            ['admin_id' => 7, 'service_id' => 2],
            ['admin_id' => 7, 'service_id' => 3],
            ['admin_id' => 7, 'service_id' => 7],
            ['admin_id' => 7, 'service_id' => 8],
            ['admin_id' => 7, 'service_id' => 10],
            
            // Admin ID 9
            ['admin_id' => 9, 'service_id' => 6],
        ];

        // Insert the records
        DB::table('admin_services')->insert($adminServices);
        
        $this->command->info('Admin services seeded successfully with ' . count($adminServices) . ' records.');
    }
}