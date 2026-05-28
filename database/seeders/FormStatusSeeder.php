<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\FormStatus;

class FormStatusSeeder extends Seeder
{
    public function run(): void
    {
        $statuses = [
            ['status_name' => 'Pending Approval', 'color_code' => 'rgb(0, 184, 138)'],
            ['status_name' => 'Awaiting Payment', 'color_code' => 'rgb(28, 133, 143)'],
            ['status_name' => 'Reserved', 'color_code' => '#1e7941ff'],  // replaced scheduled and ongoing statuses 
            ['status_name' => 'Completed', 'color_code' => 'rgb(48, 155, 59)'],
            ['status_name' => 'Rejected', 'color_code' => 'rgb(96, 40, 40)'],
            ['status_name' => 'Cancelled', 'color_code' => '#3e5568ff'],
            ['status_name' => 'Payment Submitted', 'color_code' => 'rgb(167, 125, 8)'],
        ];

        foreach ($statuses as $status) {
            FormStatus::firstOrCreate(
                ['status_name' => $status['status_name']],
                ['color_code' => $status['color_code']]
            );
        }
    }
}
