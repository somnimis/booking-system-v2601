<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RequisitionCommentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Comments for request_id = 1 (General Assembly)
        DB::table('requisition_comments')->insert([
            [
                'request_id' => 1,
                'admin_id' => 1,
                'comment' => 'Reviewed the event details. Please ensure that the number of participants matches the venue capacity.',
                'created_at' => '2025-06-25 09:15:00',
                'updated_at' => '2025-06-25 09:15:00',
            ],
            [
                'request_id' => 1,
                'admin_id' => 1,
                'comment' => 'Approved after checking the submitted documents. Proceed to payment.',
                'created_at' => '2025-06-26 14:30:00',
                'updated_at' => '2025-06-26 14:30:00',
            ],
            [
                'request_id' => 1,
                'admin_id' => 2,
                'comment' => 'Finalized the request. Official receipt generated.',
                'created_at' => '2025-06-27 10:45:00',
                'updated_at' => '2025-06-27 10:45:00',
            ],
        ]);

        // Comments for request_id = 2 (Leadership Workshop)
        DB::table('requisition_comments')->insert([
            [
                'request_id' => 2,
                'admin_id' => 1,
                'comment' => 'Missing proof of affiliation for external organization. Please upload.',
                'created_at' => '2025-07-01 11:20:00',
                'updated_at' => '2025-07-01 11:20:00',
            ],
            [
                'request_id' => 2,
                'admin_id' => 2,
                'comment' => 'Referred to the Dean\'s office for further evaluation of the event purpose.',
                'created_at' => '2025-07-02 08:00:00',
                'updated_at' => '2025-07-02 08:00:00',
            ],
        ]);
    }
}