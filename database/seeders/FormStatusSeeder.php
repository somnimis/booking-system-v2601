<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\FormStatus;

class FormStatusSeeder extends Seeder
{
    public function run(): void
    {
        
        $statuses = [
            ['status_name' => 'Pending Approval', 'color_code' => '#0D6E8A'], // Blue-teal
            ['status_name' => 'Awaiting Payment', 'color_code' => '#6C757D'], // Neutral gray
            ['status_name' => 'Verifying Payment', 'color_code' => '#B7791F'], // Amber
            ['status_name' => 'Reserved', 'color_code' => '#247A45'], // Green
            ['status_name' => 'Completed', 'color_code' => '#198754'], // Success green
            ['status_name' => 'Rejected', 'color_code' => '#9B2C2C'], // Red
            ['status_name' => 'Cancelled', 'color_code' => '#495057'], // Dark gray
        ];

        foreach ($statuses as $status) {
            FormStatus::firstOrCreate(
                ['status_name' => $status['status_name']],
                ['color_code' => $status['color_code']]
            );
        }
    }
}
