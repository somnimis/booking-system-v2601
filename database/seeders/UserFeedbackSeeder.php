<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UserFeedbackSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('feedback')->insert([
            [
                'email' => 'user1@example.com',
                'request_id' => 1,
                'system_performance' => 'very good',
                'booking_experience' => 'excellent',
                'ease_of_use' => 'easy',
                'useability' => 'very likely',
                'additional_feedback' => 'The system made it easy to track my request status.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'email' => 'user2@example.com',
                'request_id' => null,
                'system_performance' => 'satisfactory',
                'booking_experience' => 'good',
                'ease_of_use' => 'neutral',
                'useability' => 'likely',
                'additional_feedback' => 'Uploading documents took a while, but overall okay.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'email' => 'user3@example.com',
                'request_id' => 2,
                'system_performance' => 'outstanding',
                'booking_experience' => 'very good',
                'ease_of_use' => 'very easy',
                'useability' => 'very likely',
                'additional_feedback' => 'Very responsive support team. Highly recommended!',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'email' => 'user4@example.com',
                'request_id' => null,
                'system_performance' => 'fair',
                'booking_experience' => 'fair',
                'ease_of_use' => 'difficult',
                'useability' => 'unlikely',
                'additional_feedback' => 'Had trouble finding the payment upload section.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'email' => 'user5@example.com',
                'request_id' => 1,
                'system_performance' => 'very good',
                'booking_experience' => 'excellent',
                'ease_of_use' => 'easy',
                'useability' => 'very likely',
                'additional_feedback' => 'The timeline view for activities is very helpful.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}