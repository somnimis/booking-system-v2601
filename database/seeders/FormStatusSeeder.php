<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\FormStatus;

class FormStatusSeeder extends Seeder
{
    public function run(): void
    {
        $statuses = [
            ['status_name' => 'Pencil Booked',   'color_code' => 'rgb(127, 0, 196)'],
            ['status_name' => 'Pending Approval',   'color_code' => 'rgb(0, 184, 138)'],
            ['status_name' => 'Awaiting Payment',   'color_code' => 'rgb(28, 133, 143)'], 
            ['status_name' => 'Scheduled',          'color_code' => '#1e7941ff'], 
            ['status_name' => 'Ongoing',            'color_code' => '#ac7a0fff'], 
            ['status_name' => 'Overdue',        'color_code' => '#8f2a2aff'], 
            ['status_name' => 'Completed',          'color_code' => '#3e5568ff'], 
            ['status_name' => 'Rejected',           'color_code' => '#3e5568ff'], 
            ['status_name' => 'Cancelled',          'color_code' => '#3e5568ff'], 
        ];

        foreach ($statuses as $status) {
            FormStatus::firstOrCreate(
                ['status_name' => $status['status_name']],
                ['color_code' => $status['color_code']]
            );
        }
    }
}
