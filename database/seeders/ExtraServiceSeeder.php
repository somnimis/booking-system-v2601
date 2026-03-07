<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ExtraServiceSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('extra_services')->insert([
            ['service_name' => 'Projector'],
            ['service_name' => 'Projection Screen'],
            ['service_name' => 'Sound Reinforcement System'],
            ['service_name' => 'LED Wall'],
            ['service_name' => 'Electrical'],
            ['service_name' => 'Internet Connection'],
            ['service_name' => 'Plants for Decoration'],
            ['service_name' => 'Platform'],
            ['service_name' => 'Security Guard'],
            ['service_name' => 'Emergency Response Team'],
        ]);
    }
}
