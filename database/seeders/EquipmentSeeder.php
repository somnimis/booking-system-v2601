<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EquipmentSeeder extends Seeder
{
      public function run()
    {
        DB::table('equipment')->insert([
            // --- SOUND SYSTEMS ---
            [
                'equipment_name' => 'Sound System (Small)',
                'base_fee' => 5000.00,
                'rate_type' => 'Per Hour',
                'category_id' => 1, 
                'status_id' => 1,  
                'managed_by' => 4, 
                'created_by' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'equipment_name' => 'Sound System (Large)',
                'base_fee' => 10000.00,
                'rate_type' => 'Per Hour',
                'category_id' => 1,
                'status_id' => 1,
                'managed_by' => 4,
                'created_by' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [ //3 
                'equipment_name' => 'Additional Speakers',
                'base_fee' => 5000.00,
                'rate_type' => 'Per Hour',
                'category_id' => 1,
                'status_id' => 1,
                'managed_by' => 4,
                'created_by' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [ // 4
                'equipment_name' => 'Wireless Microphone',
                'base_fee' => 700.00, 
                'rate_type' => 'Per Hour',
                'category_id' => 1,
                'status_id' => 1,
                'managed_by' => 4,
                'created_by' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // --- LIGHTS & EFFECTS ---
            [ // 5
                'equipment_name' => 'RGB Lights',
                'base_fee' => 600.00,
                'rate_type' => 'Per Event', // Was 'Per Piece, Show'
                'category_id' => 2, // Assuming new category
                'status_id' => 1,
                'managed_by' => 4,
                'created_by' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [ // 6
                'equipment_name' => 'Moving Heads',
                'base_fee' => 1200.00,
                'rate_type' => 'Per Event', // Was 'Per Piece, Show'
                'category_id' => 2,
                'status_id' => 1,
                'managed_by' => 4,
                'created_by' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [ // 7
                'equipment_name' => 'Smoke Machine',
                'base_fee' => 600.00,
                'rate_type' => 'Per Event', // Was 'Piece, Show'
                'category_id' => 2,
                'status_id' => 1,
                'managed_by' => 4,
                'created_by' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [ // 8
                'equipment_name' => 'Follow Spot',
                'base_fee' => 1500.00,
                'rate_type' => 'Per Event', // Was 'Show'
                'category_id' => 2,
                'status_id' => 1,
                'managed_by' => 4,
                'created_by' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // --- VISUAL & CONFERENCE ---
            [
                'equipment_name' => 'Projector',
                'base_fee' => 5000.00,
                'rate_type' => 'Per Hour', // Default
                'category_id' => 3, // Assuming new category
                'status_id' => 1,
                'managed_by' => 4,
                'created_by' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'equipment_name' => '65 Inch Television',
                'base_fee' => 0.00, // Was null in image
                'rate_type' => 'Per Hour', // Default
                'category_id' => 3,
                'status_id' => 1,
                'managed_by' => 4,
                'created_by' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'equipment_name' => 'Conference System',
                'base_fee' => 200.00, // Was null in image
                'rate_type' => 'Per Event',
                'category_id' => 3,
                'status_id' => 1,
                'managed_by' => 4,
                'created_by' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // --- MUSICAL INSTRUMENTS & ACCESSORIES ---
            [
                'equipment_name' => 'Drum Set',
                'base_fee' => 3500.00,
                'rate_type' => 'Per Event', // Was 'Per Show'
                'category_id' => 4, // Assuming new category
                'status_id' => 1,
                'managed_by' => 4,
                'created_by' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'equipment_name' => 'Guitar Amplifier',
                'base_fee' => 3000.00,
                'rate_type' => 'Per Event', // Was 'Per Show'
                'category_id' => 4,
                'status_id' => 1,
                'managed_by' => 4,
                'created_by' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'equipment_name' => 'HDMI Splitter',
                'base_fee' => 0.00, // Was null in image
                'rate_type' => 'Per Event',
                'category_id' => 5, // Assuming new category
                'status_id' => 1,
                'managed_by' => 4,
                'created_by' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'equipment_name' => 'Capture Card/Sound Card',
                'base_fee' => 0.00, // Was null in image
                'rate_type' => 'Per Event',
                'category_id' => 5,
                'status_id' => 1,
                'managed_by' => 4,
                'created_by' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'equipment_name' => 'Microphone Stand',
                'base_fee' => 100.00, // Was null in image
                'rate_type' => 'Per Event',
                'category_id' => 5,
                'status_id' => 1,
                'managed_by' => 4,
                'created_by' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'equipment_name' => 'Keyboard',
                'base_fee' => 100.00, // Was null in image
                'rate_type' => 'Per Event',
                'category_id' => 4,
                'status_id' => 1,
                'managed_by' => 4,
                'created_by' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'equipment_name' => 'Lapel Microphone',
                'base_fee' => 100.00, // Was null in image
                'rate_type' => 'Per Hour', // Default
                'category_id' => 1,
                'status_id' => 1,
                'managed_by' => 4,
                'created_by' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'equipment_name' => 'Wired Microphone',
                'base_fee' => 100.00, // Was null in image
                'rate_type' => 'Per Hour', // Default
                'category_id' => 1,
                'status_id' => 1,
                'managed_by' => 4,
                'created_by' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'equipment_name' => 'Communication System',
                'base_fee' => 5000.00,
                'rate_type' => 'Per Event', // Was 'Per Show'
                'category_id' => 1,
                'status_id' => 1,
                'managed_by' => 4,
                'created_by' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}