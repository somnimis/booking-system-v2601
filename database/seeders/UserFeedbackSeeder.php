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
        // Scores are tinyint 1–5. Label map lives in App\Models\Feedback::RATING_LABELS.
        DB::table('feedback')->insert([
            [
                'email' => 'user1@example.com',
                'request_id' => 1,
                'system_performance' => 5, // very good
                'booking_experience' => 5, // excellent
                'ease_of_use' => 4,        // easy
                'useability' => 5,         // very likely
                'additional_feedback' => 'The system made it easy to track my request status.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'email' => 'user2@example.com',
                'request_id' => null,
                'system_performance' => 3, // satisfactory
                'booking_experience' => 4, // good
                'ease_of_use' => 3,        // neutral
                'useability' => 4,         // likely
                'additional_feedback' => 'Uploading documents took a while, but overall okay.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'email' => 'user3@example.com',
                'request_id' => 2,
                'system_performance' => 5, // outstanding
                'booking_experience' => 5, // very good
                'ease_of_use' => 5,        // very easy
                'useability' => 5,         // very likely
                'additional_feedback' => 'Very responsive support team. Highly recommended!',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'email' => 'user4@example.com',
                'request_id' => null,
                'system_performance' => 2, // fair
                'booking_experience' => 2, // fair
                'ease_of_use' => 2,        // difficult
                'useability' => 2,         // unlikely
                'additional_feedback' => 'Had trouble finding the payment upload section.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'email' => 'user5@example.com',
                'request_id' => 1,
                'system_performance' => 5, // very good
                'booking_experience' => 5, // excellent
                'ease_of_use' => 4,        // easy
                'useability' => 5,         // very likely
                'additional_feedback' => 'The timeline view for activities is very helpful.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}