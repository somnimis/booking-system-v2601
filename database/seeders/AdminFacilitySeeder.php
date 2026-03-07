<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AdminFacilitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all admin IDs using admin_id as the primary key
        $adminIds = DB::table('admins')->pluck('admin_id')->toArray();
        
        // Get all facility IDs using facility_id as the primary key
        $facilityIds = DB::table('facilities')->pluck('facility_id')->toArray();

        // If no admins or facilities exist, skip
        if (empty($adminIds) || empty($facilityIds)) {
            return;
        }

        $adminFacilities = [];

        // Assign random facilities to each admin
        foreach ($adminIds as $adminId) {
            // Randomly decide how many facilities this admin will manage (1 to all facilities)
            $numberOfFacilities = rand(1, count($facilityIds));
            
            // Randomly select facility IDs
            $randomFacilityKeys = array_rand($facilityIds, $numberOfFacilities);
            
            // Ensure $randomFacilityKeys is always an array
            if (!is_array($randomFacilityKeys)) {
                $randomFacilityKeys = [$randomFacilityKeys];
            }
            
            foreach ($randomFacilityKeys as $facilityKey) {
                $facilityId = $facilityIds[$facilityKey];
                
                $adminFacilities[] = [
                    'admin_id' => $adminId,
                    'facility_id' => $facilityId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        // Insert the records
        DB::table('admin_facilities')->insert($adminFacilities);
    }
}