<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FacilityCategorySeeder extends Seeder
{
    public function run(): void
    {
        DB::table('facility_categories')->insert([
            [
                'category_name' => 'Buildings', //1
                'description' => 'Standalone structures such as halls and churches.',
            ],
            [
                'category_name' => 'Campus Rooms', //2
                'description' => 'Enclosed indoor spaces such as classrooms and laboratories.',
            ],
            [
                'category_name' => 'Residencies/Dorms', //3
                'description' => 'Buildings intended for housing, including dormitories.',
            ],
            [
                'category_name' => 'Outside Spaces', //4 
                'description' => 'Open areas outside of buildings, like gardens and Halfmoon Drive.',
            ],
            [
                'category_name' => 'Sports Venues', //5
                'description' => 'Facilities designated for physical activities and sports events.',
            ],

            // dont hard-code ABYTHING. always use facility ID instead
        ]);
    }
}
