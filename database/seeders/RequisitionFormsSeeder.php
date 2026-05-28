<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RequisitionFormsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        // Get a specific date for multiple events (e.g., next Monday)
        $targetDate = now()->next('Monday')->toDateString();

        // Another date for more events
        $targetDate2 = now()->next('Monday')->addDays(7)->toDateString();

        // Date for past events
        $pastDate = now()->subDays(7)->toDateString();

        // ============= DAY 1 - MULTIPLE EVENTS (Monday) =============

        // Event 1: Morning session
        DB::table('requisition_forms')->insert([
            'user_type' => 'Internal',
            'first_name' => 'Maria',
            'last_name' => 'Santos',
            'email' => 'maria.santos@university.edu',
            'school_id' => '202512345',
            'organization_name' => null,
            'contact_number' => '09179876543',
            'event_title' => 'Morning Workshop on AI',

            'access_code' => strtoupper(Str::random(8)),
            'num_participants' => 40,
            'purpose_id' => 2,
            'additional_requests' => 'Projector and whiteboard needed',

            'event_documents_url' => 'https://example.com/formal_letter_alex.pdf',
            'event_documents_public_id' => 'letter_alex',
            'proof_of_payment_url' => 'https://example.com/payment.pdf',
            'proof_of_payment_public_id' => 'payment_456',
            'upload_token' => Str::random(20),

            'status_id' => 2,
            'start_date' => $targetDate,
            'end_date' => $targetDate,
            'start_time' => '08:00:00',
            'end_time' => '10:00:00',

            'is_late' => false,
            'returned_at' => null,

            'is_finalized' => false,
            'finalized_at' => null,
            'finalized_by' => null,

            'tentative_fee' => 3500.00,
            'approved_fee' => null,

            'is_closed' => false,
            'closed_at' => null,
            'closed_by' => null,

            'created_at' => now()->subDays(2),
            'updated_at' => now(),
        ]);
        $lastId = DB::getPdo()->lastInsertId();
        DB::table('requested_facilities')->insert([
            ['request_id' => $lastId, 'facility_id' => 1],
            ['request_id' => $lastId, 'facility_id' => 2],
        ]);

        // Event 2: Late Morning session (same day)
        DB::table('requisition_forms')->insert([
            'user_type' => 'External',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'johndoe@example.com',
            'school_id' => null,
            'organization_name' => 'Sample Organization',
            'contact_number' => '09171234567',
            'event_title' => 'Late Morning Session',
            'access_code' => strtoupper(Str::random(8)),
            'num_participants' => 25,
            'purpose_id' => 1,
            'additional_requests' => 'Need extra chairs and microphones',

            'event_documents_url' => 'https://example.com/formal_letter.pdf',
            'event_documents_public_id' => 'formal_letter_123',
            'proof_of_payment_url' => null,
            'proof_of_payment_public_id' => null,
            'upload_token' => Str::random(20),

            'status_id' => 1,
            'start_date' => $targetDate,
            'end_date' => $targetDate,
            'start_time' => '10:30:00',
            'end_time' => '12:30:00',

            'is_late' => true,
            'returned_at' => now()->addDays(3),

            'is_finalized' => false,
            'finalized_at' => null,
            'finalized_by' => null,

            'tentative_fee' => 5000.00,
            'approved_fee' => null,

            'is_closed' => false,
            'closed_at' => null,
            'closed_by' => null,

            'created_at' => now()->subDays(1),
            'updated_at' => now(),
        ]);
        $lastId = DB::getPdo()->lastInsertId();
        DB::table('requested_facilities')->insert([
            ['request_id' => $lastId, 'facility_id' => 2],
            ['request_id' => $lastId, 'facility_id' => 3],
        ]);

        // Event 3: Afternoon session (same day)
        DB::table('requisition_forms')->insert([
            'user_type' => 'External',
            'first_name' => 'Alex',
            'last_name' => 'Tan',
            'email' => 'alex.tan@example.com',
            'school_id' => null,
            'organization_name' => 'Tech Innovators Club',
            'contact_number' => '09221234567',
            'event_title' => 'Afternoon Workshop on Machine Learning',
            'access_code' => strtoupper(Str::random(8)),
            'num_participants' => 15,
            'purpose_id' => 3,
            'additional_requests' => 'Need extra tables and projector',

            'event_documents_url' => 'https://example.com/formal_letter_alex.pdf',
            'event_documents_public_id' => 'formal_letter_alex',
            'proof_of_payment_url' => null,
            'proof_of_payment_public_id' => null,
            'upload_token' => Str::random(20),

            'status_id' => 3,
            'start_date' => $targetDate,
            'end_date' => $targetDate,
            'start_time' => '13:30:00',
            'end_time' => '15:30:00',

            'is_late' => false,
            'returned_at' => null,

            'is_finalized' => true,
            'finalized_at' => now(),
            'finalized_by' => 1,

            'tentative_fee' => 4500.00,
            'approved_fee' => 4500.00,

            'is_closed' => false,
            'closed_at' => null,
            'closed_by' => null,

            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $lastId = DB::getPdo()->lastInsertId();
        DB::table('requested_facilities')->insert([
            ['request_id' => $lastId, 'facility_id' => 3],
            ['request_id' => $lastId, 'facility_id' => 4],
        ]);
        DB::table('requested_equipment')->insert([
            ['request_id' => $lastId, 'equipment_id' => 1, 'quantity' => 2],
            ['request_id' => $lastId, 'equipment_id' => 3, 'quantity' => 1],
        ]);

        // Event 4: Late afternoon session (same day)
        DB::table('requisition_forms')->insert([
            'user_type' => 'External',
            'first_name' => 'Carlos',
            'last_name' => 'Gomez',
            'email' => 'carlos.gomez@example.com',
            'school_id' => null,
            'organization_name' => 'Community Group',
            'contact_number' => '09221234567',
            'event_title' => 'Late Afternoon Session',
            'access_code' => strtoupper(Str::random(8)),
            'num_participants' => 15,
            'purpose_id' => 3,
            'additional_requests' => 'Projector and seating for participants',

            'event_documents_url' => 'https://example.com/formal_letter_carlos.pdf',
            'event_documents_public_id' => 'formal_letter_carlos',
            'proof_of_payment_url' => null,
            'proof_of_payment_public_id' => null,
            'upload_token' => Str::random(20),

            'status_id' => 5,
            'start_date' => $targetDate,
            'end_date' => $targetDate,
            'start_time' => '16:00:00',
            'end_time' => '18:00:00',

            'is_late' => false,
            'returned_at' => null,

            'is_finalized' => false,
            'finalized_at' => null,
            'finalized_by' => null,

            'tentative_fee' => 2500.00,
            'approved_fee' => null,

            'is_closed' => true,
            'closed_at' => now()->addDays(6),
            'closed_by' => 1,

            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $lastId = DB::getPdo()->lastInsertId();
        DB::table('requested_facilities')->insert([
            ['request_id' => $lastId, 'facility_id' => 5],
        ]);
        DB::table('requested_equipment')->insert([
            ['request_id' => $lastId, 'equipment_id' => 3, 'quantity' => 2],
        ]);

        // ============= DAY 2 - MULTIPLE EVENTS (Next Monday) =============

        // Event 5: Early morning (Day 2)
        DB::table('requisition_forms')->insert([
            'user_type' => 'Internal',
            'first_name' => 'Maria',
            'last_name' => 'Lopez',
            'email' => 'maria.lopez@example.com',
            'school_id' => '20231234',
            'organization_name' => 'Student Council',
            'contact_number' => '09181234567',
            'event_title' => 'Early Morning Session',
            'access_code' => strtoupper(Str::random(8)),
            'num_participants' => 30,
            'purpose_id' => 2,
            'additional_requests' => 'Include sound system and banners',

            'event_documents_url' => 'https://example.com/formal_letter_maria.pdf',
            'event_documents_public_id' => 'formal_letter_maria',
            'proof_of_payment_url' => 'https://example.com/payment_proof_maria.pdf',
            'proof_of_payment_public_id' => 'payment_proof_maria',
            'upload_token' => Str::random(20),

            'status_id' => 4,
            'start_date' => $targetDate2,
            'end_date' => $targetDate2,
            'start_time' => '07:30:00',
            'end_time' => '09:30:00',

            'is_late' => false,
            'returned_at' => now()->subDays(2)->addHours(1),

            'is_finalized' => true,
            'finalized_at' => now()->subDays(3),
            'finalized_by' => 2,

            'tentative_fee' => 6000.00,
            'approved_fee' => 6000.00,

            'is_closed' => true,
            'closed_at' => now()->subDays(1),
            'closed_by' => 2,
            'created_at' => now()->subDays(5),
            'updated_at' => now()->subDays(1),
        ]);
        $lastId = DB::getPdo()->lastInsertId();
        DB::table('requested_facilities')->insert([
            ['request_id' => $lastId, 'facility_id' => 1],
            ['request_id' => $lastId, 'facility_id' => 3],
        ]);
        DB::table('requested_equipment')->insert([
            ['request_id' => $lastId, 'equipment_id' => 2, 'quantity' => 1],
            ['request_id' => $lastId, 'equipment_id' => 4, 'quantity' => 3],
        ]);

        // Event 6: Midday Workshop
        DB::table('requisition_forms')->insert([
            'user_type' => 'Internal',
            'first_name' => 'James',
            'last_name' => 'Reyes',
            'email' => 'james.reyes@example.com',
            'school_id' => '20234567',
            'organization_name' => 'IT Society',
            'contact_number' => '09170011223',
            'event_title' => 'Midday Workshop on Web Development',
            'access_code' => strtoupper(Str::random(8)),
            'num_participants' => 45,
            'purpose_id' => 1,
            'additional_requests' => 'Projector, whiteboard markers, extension cords',

            'event_documents_url' => 'https://example.com/formal_letter_james.pdf',
            'event_documents_public_id' => 'formal_letter_james',
            'proof_of_payment_url' => 'https://example.com/payment_proof_james.pdf',
            'proof_of_payment_public_id' => 'payment_proof_james',
            'upload_token' => Str::random(20),

            'status_id' => 7,
            'start_date' => $targetDate2,
            'end_date' => $targetDate2,
            'start_time' => '11:00:00',
            'end_time' => '13:00:00',

            'is_late' => false,
            'returned_at' => null,

            'is_finalized' => true,
            'finalized_at' => now()->subDays(2),
            'finalized_by' => 3,

            'tentative_fee' => 5000.00,
            'approved_fee' => 5000.00,

            'is_closed' => false,
            'closed_at' => null,
            'closed_by' => null,
            'created_at' => now()->subDays(6),
            'updated_at' => now()->subDays(2),
        ]);

        $lastId = DB::getPdo()->lastInsertId();

        DB::table('requested_facilities')->insert([
            ['request_id' => $lastId, 'facility_id' => 2],
        ]);

        DB::table('requested_equipment')->insert([
            ['request_id' => $lastId, 'equipment_id' => 1, 'quantity' => 2],
        ]);


        // Event 7: Afternoon Seminar
        DB::table('requisition_forms')->insert([
            'user_type' => 'External',
            'first_name' => 'Anna',
            'last_name' => 'Cruz',
            'email' => 'anna.cruz@example.com',
            'school_id' => 'EXT-88921',
            'organization_name' => 'Tech Innovators PH',
            'contact_number' => '09175556677',
            'event_title' => 'AI and Future Tech Seminar',
            'access_code' => strtoupper(Str::random(8)),
            'num_participants' => 80,
            'purpose_id' => 3,
            'additional_requests' => 'Stage setup, microphones, livestream support',

            'event_documents_url' => 'https://example.com/formal_letter_anna.pdf',
            'event_documents_public_id' => 'formal_letter_anna',
            'proof_of_payment_url' => 'https://example.com/payment_proof_anna.pdf',
            'proof_of_payment_public_id' => 'payment_proof_anna',
            'upload_token' => Str::random(20),

            'status_id' => 7,
            'start_date' => $targetDate2,
            'end_date' => $targetDate2,
            'start_time' => '14:00:00',
            'end_time' => '16:30:00',

            'is_late' => false,
            'returned_at' => null,

            'is_finalized' => true,
            'finalized_at' => now()->subDays(1),
            'finalized_by' => 2,

            'tentative_fee' => 12000.00,
            'approved_fee' => 12000.00,

            'is_closed' => false,
            'closed_at' => null,
            'closed_by' => null,
            'created_at' => now()->subDays(4),
            'updated_at' => now()->subDays(1),
        ]);

        $lastId = DB::getPdo()->lastInsertId();

        DB::table('requested_facilities')->insert([
            ['request_id' => $lastId, 'facility_id' => 1],
            ['request_id' => $lastId, 'facility_id' => 4],
        ]);

        DB::table('requested_equipment')->insert([
            ['request_id' => $lastId, 'equipment_id' => 3, 'quantity' => 2],
        ]);


        // Event 8: Evening Training
        DB::table('requisition_forms')->insert([
            'user_type' => 'Internal',
            'first_name' => 'Daniel',
            'last_name' => 'Santos',
            'email' => 'daniel.santos@example.com',
            'school_id' => '20239876',
            'organization_name' => 'Sports Development Unit',
            'contact_number' => '09178889900',
            'event_title' => 'Evening Skills Training Session',
            'access_code' => strtoupper(Str::random(8)),
            'num_participants' => 25,
            'purpose_id' => 2,
            'additional_requests' => 'Gym mats, sound system, hydration station',

            'event_documents_url' => 'https://example.com/formal_letter_daniel.pdf',
            'event_documents_public_id' => 'formal_letter_daniel',
            'proof_of_payment_url' => 'https://example.com/payment_proof_daniel.pdf',
            'proof_of_payment_public_id' => 'payment_proof_daniel',
            'upload_token' => Str::random(20),

            'status_id' => 7,
            'start_date' => $targetDate2,
            'end_date' => $targetDate2,
            'start_time' => '17:30:00',
            'end_time' => '19:00:00',

            'is_late' => false,
            'returned_at' => null,

            'is_finalized' => true,
            'finalized_at' => now()->subHours(10),
            'finalized_by' => 1,

            'tentative_fee' => 4000.00,
            'approved_fee' => 4000.00,

            'is_closed' => false,
            'closed_at' => null,
            'closed_by' => null,
            'created_at' => now()->subDays(3),
            'updated_at' => now()->subHours(10),
        ]);

        $lastId = DB::getPdo()->lastInsertId();

        DB::table('requested_facilities')->insert([
            ['request_id' => $lastId, 'facility_id' => 3],
        ]);

        DB::table('requested_equipment')->insert([
            ['request_id' => $lastId, 'equipment_id' => 2, 'quantity' => 1],
            ['request_id' => $lastId, 'equipment_id' => 5, 'quantity' => 2],
        ]);

        // Event 6: Mid-morning (Day 2)
        DB::table('requisition_forms')->insert([
            'user_type' => 'Internal',
            'first_name' => 'Maria',
            'last_name' => 'Lopez',
            'email' => 'maria.lopez@example.com',
            'school_id' => 'INT2025-001',
            'organization_name' => 'University Club',
            'contact_number' => '09331234567',
            'event_title' => 'Mid-Morning Workshop on Data Science',
            'access_code' => strtoupper(Str::random(8)),
            'num_participants' => 30,
            'purpose_id' => 2,
            'additional_requests' => 'Audio system and stage setup',

            'event_documents_url' => 'https://example.com/formal_letter_maria.pdf',
            'event_documents_public_id' => 'formal_letter_maria',
            'proof_of_payment_url' => 'https://example.com/payment_maria.pdf',
            'proof_of_payment_public_id' => 'payment_maria',
            'upload_token' => Str::random(20),

            'status_id' => 1,
            'start_date' => $targetDate2,
            'end_date' => $targetDate2,
            'start_time' => '10:00:00',
            'end_time' => '12:00:00',

            'is_late' => false,
            'returned_at' => now()->subDays(2),

            'is_finalized' => true,
            'finalized_at' => now()->subDays(4),
            'finalized_by' => 1,

            'tentative_fee' => 4000.00,
            'approved_fee' => 4000.00,

            'is_closed' => true,
            'closed_at' => now()->subDays(1),
            'closed_by' => 1,

            'created_at' => now()->subDays(10),
            'updated_at' => now()->subDays(1),
        ]);
        $lastId = DB::getPdo()->lastInsertId();
        DB::table('requested_facilities')->insert([
            ['request_id' => $lastId, 'facility_id' => 2],
            ['request_id' => $lastId, 'facility_id' => 4],
        ]);
        DB::table('requested_equipment')->insert([
            ['request_id' => $lastId, 'equipment_id' => 2, 'quantity' => 5],
        ]);

        // Event 7: Afternoon (Day 2)
        DB::table('requisition_forms')->insert([
            'user_type' => 'External',
            'first_name' => 'Sarah',
            'last_name' => 'Johnson',
            'email' => 'sarah.j@example.com',
            'school_id' => null,
            'organization_name' => 'Business Network',
            'contact_number' => '09451234567',
            'event_title' => 'Afternoon Workshop on Business Strategy',
            'access_code' => strtoupper(Str::random(8)),
            'num_participants' => 50,
            'purpose_id' => 1,
            'additional_requests' => 'Networking setup, coffee station',

            'event_documents_url' => 'https://example.com/formal_letter_sarah.pdf',
            'event_documents_public_id' => 'formal_letter_sarah',
            'proof_of_payment_url' => 'https://example.com/payment_sarah.pdf',
            'proof_of_payment_public_id' => 'payment_sarah',
            'upload_token' => Str::random(20),

            'status_id' => 2,
            'start_date' => $targetDate2,
            'end_date' => $targetDate2,
            'start_time' => '13:00:00',
            'end_time' => '17:00:00',

            'is_late' => false,
            'returned_at' => null,

            'is_finalized' => true,
            'finalized_at' => now(),
            'finalized_by' => 2,

            'tentative_fee' => 8000.00,
            'approved_fee' => 7500.00,

            'is_closed' => false,
            'closed_at' => null,
            'closed_by' => null,

            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $lastId = DB::getPdo()->lastInsertId();
        DB::table('requested_facilities')->insert([
            ['request_id' => $lastId, 'facility_id' => 1],
            ['request_id' => $lastId, 'facility_id' => 3],
            ['request_id' => $lastId, 'facility_id' => 5],
        ]);
        DB::table('requested_equipment')->insert([
            ['request_id' => $lastId, 'equipment_id' => 1, 'quantity' => 3],
            ['request_id' => $lastId, 'equipment_id' => 2, 'quantity' => 2],
            ['request_id' => $lastId, 'equipment_id' => 4, 'quantity' => 1],
        ]);

        // ============= PAST DATE WITH MULTIPLE EVENTS =============

        // Event 8: Past event - Morning
        DB::table('requisition_forms')->insert([
            'user_type' => 'Internal',
            'first_name' => 'Robert',
            'last_name' => 'Chen',
            'email' => 'robert.chen@university.edu',
            'school_id' => '20239876',
            'organization_name' => null,
            'contact_number' => '09561234567',
            'event_title' => 'Past Event - Morning',

            'access_code' => strtoupper(Str::random(8)),
            'num_participants' => 20,
            'purpose_id' => 2,
            'additional_requests' => 'Workshop materials needed',

            'event_documents_url' => 'https://example.com/formal_letter_robert.pdf',
            'event_documents_public_id' => 'formal_letter_robert',
            'proof_of_payment_url' => null,
            'proof_of_payment_public_id' => null,
            'upload_token' => Str::random(20),

            'status_id' => 2,
            'start_date' => $pastDate,
            'end_date' => $pastDate,
            'start_time' => '09:00:00',
            'end_time' => '11:00:00',

            'is_late' => false,
            'returned_at' => now()->subDays(5),

            'is_finalized' => true,
            'finalized_at' => now()->subDays(10),
            'finalized_by' => 1,

            'tentative_fee' => 3000.00,
            'approved_fee' => 3000.00,

            'is_closed' => true,
            'closed_at' => now()->subDays(3),
            'closed_by' => 1,
            'created_at' => now()->subDays(15),
            'updated_at' => now()->subDays(3),
        ]);
        $lastId = DB::getPdo()->lastInsertId();
        DB::table('requested_facilities')->insert([
            ['request_id' => $lastId, 'facility_id' => 2],
        ]);

        // Event 9: Past event - Afternoon
        DB::table('requisition_forms')->insert([
            'user_type' => 'External',
            'first_name' => 'Lisa',
            'last_name' => 'Wong',
            'email' => 'lisa.wong@example.com',
            'school_id' => null,
            'organization_name' => 'Art Collective',
            'contact_number' => '09671234567',
            'event_title' => 'Past Event - Afternoon',
            'access_code' => strtoupper(Str::random(8)),
            'num_participants' => 35,
            'purpose_id' => 3,
            'additional_requests' => 'Art display setup',

            'event_documents_url' => 'https://example.com/formal_letter_lisa.pdf',
            'event_documents_public_id' => 'formal_letter_lisa',
            'proof_of_payment_url' => 'https://example.com/payment_lisa.pdf',
            'proof_of_payment_public_id' => 'payment_lisa',
            'upload_token' => Str::random(20),

            'status_id' => 1,
            'start_date' => $pastDate,
            'end_date' => $pastDate,
            'start_time' => '14:00:00',
            'end_time' => '18:00:00',

            'is_late' => false,
            'returned_at' => now()->subDays(5),

            'is_finalized' => true,
            'finalized_at' => now()->subDays(8),
            'finalized_by' => 2,

            'tentative_fee' => 5500.00,
            'approved_fee' => 5500.00,

            'is_closed' => true,
            'closed_at' => now()->subDays(2),
            'closed_by' => 2,

            'created_at' => now()->subDays(20),
            'updated_at' => now()->subDays(2),
        ]);
        $lastId = DB::getPdo()->lastInsertId();
        DB::table('requested_facilities')->insert([
            ['request_id' => $lastId, 'facility_id' => 3],
            ['request_id' => $lastId, 'facility_id' => 4],
        ]);
        DB::table('requested_equipment')->insert([
            ['request_id' => $lastId, 'equipment_id' => 3, 'quantity' => 4],
        ]);

        // ============= MARCH 5 - 5 EVENTS AT DIFFERENT TIMES =============
        $march5 = '2026-03-05'; // Using 2026 as example year - adjust as needed

        // Event 1: Early Morning (8:00 AM - 10:00 AM)
        DB::table('requisition_forms')->insert([
            'user_type' => 'Internal',
            'first_name' => 'James',
            'last_name' => 'Wilson',
            'email' => 'james.wilson@university.edu',
            'school_id' => '202511234',
            'organization_name' => null,
            'contact_number' => '09181234567',
            'event_title' => 'Early Morning Session',
            'access_code' => strtoupper(Str::random(8)),
            'num_participants' => 25,
            'purpose_id' => 2,
            'additional_requests' => 'Morning seminar setup with projector',

            'event_documents_url' => 'https://example.com/formal_letter_james.pdf',
            'event_documents_public_id' => 'formal_letter_james',
            'proof_of_payment_url' => null,
            'proof_of_payment_public_id' => null,
            'upload_token' => Str::random(20),

            'status_id' => 3, // Scheduled
            'start_date' => $march5,
            'end_date' => $march5,
            'start_time' => '08:00:00',
            'end_time' => '10:00:00',

            'is_late' => false,
            'returned_at' => null,

            'is_finalized' => true,
            'finalized_at' => now()->subDays(5),
            'finalized_by' => 1,

            'tentative_fee' => 2800.00,
            'approved_fee' => 2800.00,

            'is_closed' => false,
            'closed_at' => null,
            'closed_by' => null,

            'created_at' => now()->subDays(10),
            'updated_at' => now()->subDays(2),
        ]);
        $lastId = DB::getPdo()->lastInsertId();
        DB::table('requested_facilities')->insert([
            ['request_id' => $lastId, 'facility_id' => 1], // Main Hall
        ]);

        // Event 2: Late Morning (10:30 AM - 12:30 PM)
        DB::table('requisition_forms')->insert([
            'user_type' => 'External',
            'first_name' => 'Patricia',
            'last_name' => 'Lim',
            'email' => 'patricia.lim@example.com',
            'school_id' => null,
            'organization_name' => 'Business Association',
            'contact_number' => '09221234568',
            'event_title' => 'Late Morning Session',
            'access_code' => strtoupper(Str::random(8)),
            'num_participants' => 40,
            'purpose_id' => 1, // Meeting
            'additional_requests' => 'Boardroom setup with coffee',

            'event_documents_url' => 'https://example.com/formal_letter_patricia.pdf',
            'event_documents_public_id' => 'formal_letter_patricia',
            'proof_of_payment_url' => 'https://example.com/payment_patricia.pdf',
            'proof_of_payment_public_id' => 'payment_patricia',
            'upload_token' => Str::random(20),

            'status_id' => 3, // Scheduled
            'start_date' => $march5,
            'end_date' => $march5,
            'start_time' => '10:30:00',
            'end_time' => '12:30:00',

            'is_late' => false,
            'returned_at' => null,

            'is_finalized' => true,
            'finalized_at' => now()->subDays(3),
            'finalized_by' => 2,

            'tentative_fee' => 4200.00,
            'approved_fee' => 4000.00,

            'is_closed' => false,
            'closed_at' => null,
            'closed_by' => null,


            'created_at' => now()->subDays(8),
            'updated_at' => now()->subDays(1),
        ]);
        $lastId = DB::getPdo()->lastInsertId();
        DB::table('requested_facilities')->insert([
            ['request_id' => $lastId, 'facility_id' => 2], // Conference Room A
            ['request_id' => $lastId, 'facility_id' => 3], // Conference Room B
        ]);

        // Event 3: Early Afternoon (1:30 PM - 3:30 PM)
        DB::table('requisition_forms')->insert([
            'user_type' => 'Internal',
            'first_name' => 'Michael',
            'last_name' => 'Chang',
            'email' => 'michael.chang@university.edu',
            'school_id' => '202545678',
            'organization_name' => null,
            'contact_number' => '09331234569',
            'event_title' => 'Early Afternoon Workshop',
            'access_code' => strtoupper(Str::random(8)),
            'num_participants' => 60,
            'purpose_id' => 3, // Event
            'additional_requests' => 'Workshop - need whiteboards and markers',

            'event_documents_url' => 'https://example.com/formal_letter_michael.pdf',
            'event_documents_public_id' => 'formal_letter_michael',
            'proof_of_payment_url' => null,
            'proof_of_payment_public_id' => null,
            'upload_token' => Str::random(20),

            'status_id' => 2, // Pencil Booked
            'start_date' => $march5,
            'end_date' => $march5,
            'start_time' => '13:30:00',
            'end_time' => '15:30:00',

            'is_late' => false,
            'returned_at' => null,

            'is_finalized' => false,
            'finalized_at' => null,
            'finalized_by' => null,

            'tentative_fee' => 5500.00,
            'approved_fee' => null,

            'is_closed' => false,
            'closed_at' => null,
            'closed_by' => null,


            'created_at' => now()->subDays(5),
            'updated_at' => now(),
        ]);
        $lastId = DB::getPdo()->lastInsertId();
        DB::table('requested_facilities')->insert([
            ['request_id' => $lastId, 'facility_id' => 4], // Auditorium
        ]);
        DB::table('requested_equipment')->insert([
            ['request_id' => $lastId, 'equipment_id' => 1, 'quantity' => 2],
            ['request_id' => $lastId, 'equipment_id' => 3, 'quantity' => 3],
        ]);

        // Event 4: Late Afternoon (4:00 PM - 6:00 PM)
        DB::table('requisition_forms')->insert([
            'user_type' => 'External',
            'first_name' => 'Jennifer',
            'last_name' => 'Garcia',
            'email' => 'jennifer.garcia@example.com',
            'school_id' => null,
            'organization_name' => 'Tech Startups Inc.',
            'contact_number' => '09451234570',
            'event_title' => 'Late Afternoon Session',

            'access_code' => strtoupper(Str::random(8)),
            'num_participants' => 35,
            'purpose_id' => 1,
            'additional_requests' => 'Pitch night - need stage and microphone',

            'event_documents_url' => 'https://example.com/formal_letter_jennifer.pdf',
            'event_documents_public_id' => 'formal_letter_jennifer',
            'proof_of_payment_url' => 'https://example.com/payment_jennifer.pdf',
            'proof_of_payment_public_id' => 'payment_jennifer',
            'upload_token' => Str::random(20),

            'status_id' => 5, // Ongoing
            'start_date' => $march5,
            'end_date' => $march5,
            'start_time' => '16:00:00',
            'end_time' => '18:00:00',

            'is_late' => false,
            'returned_at' => null,

            'is_finalized' => true,
            'finalized_at' => now()->subDays(2),
            'finalized_by' => 1,

            'tentative_fee' => 4800.00,
            'approved_fee' => 4500.00,

            'is_closed' => false,
            'closed_at' => null,
            'closed_by' => null,

            'created_at' => now()->subDays(7),
            'updated_at' => now()->subDays(1),
        ]);
        $lastId = DB::getPdo()->lastInsertId();
        DB::table('requested_facilities')->insert([
            ['request_id' => $lastId, 'facility_id' => 1],
            ['request_id' => $lastId, 'facility_id' => 5], // Function Room
        ]);
        DB::table('requested_equipment')->insert([
            ['request_id' => $lastId, 'equipment_id' => 2, 'quantity' => 4],
        ]);

        // Event 5: Evening (6:30 PM - 9:00 PM)
        DB::table('requisition_forms')->insert([
            'user_type' => 'Internal',
            'first_name' => 'David',
            'last_name' => 'Park',
            'email' => 'david.park@university.edu',
            'school_id' => '202589012',
            'organization_name' => null,
            'contact_number' => '09561234571',
            'event_title' => 'Evening Cultural Event',

            'access_code' => strtoupper(Str::random(8)),
            'num_participants' => 80,
            'purpose_id' => 3,
            'additional_requests' => 'Cultural night - full sound and lights',

            'event_documents_url' => 'https://example.com/formal_letter_david.pdf',
            'event_documents_public_id' => 'formal_letter_david',
            'proof_of_payment_url' => 'https://example.com/payment_david.pdf',
            'proof_of_payment_public_id' => 'payment_david',
            'upload_token' => Str::random(20),

            'status_id' => 3, // Scheduled
            'start_date' => $march5,
            'end_date' => $march5,
            'start_time' => '18:30:00',
            'end_time' => '21:00:00',

            'is_late' => false,
            'returned_at' => null,

            'is_finalized' => true,
            'finalized_at' => now()->subDays(4),
            'finalized_by' => 2,

            'tentative_fee' => 7200.00,
            'approved_fee' => 7000.00,

            'is_closed' => false,
            'closed_at' => null,
            'closed_by' => null,

            'created_at' => now()->subDays(12),
            'updated_at' => now()->subDays(1),
        ]);
        $lastId = DB::getPdo()->lastInsertId();
        DB::table('requested_facilities')->insert([
            ['request_id' => $lastId, 'facility_id' => 4], // Auditorium
            ['request_id' => $lastId, 'facility_id' => 1], // Main Hall
        ]);
        DB::table('requested_equipment')->insert([
            ['request_id' => $lastId, 'equipment_id' => 1, 'quantity' => 3],
            ['request_id' => $lastId, 'equipment_id' => 2, 'quantity' => 5],
            ['request_id' => $lastId, 'equipment_id' => 4, 'quantity' => 2],
        ]);

        // ============= TODAY'S EVENTS (Always current date) =============

        // Today's Event 1: General Assembly
        DB::table('requisition_forms')->insert([
            'user_type' => 'Internal',
            'first_name' => 'Juan',
            'last_name' => 'Dela Cruz',
            'email' => 'juan.delacruz@example.com',
            'school_id' => '2020-12345',
            'organization_name' => 'University Student Council',
            'contact_number' => '09171234567',
            'event_title' => 'General Assembly',
            'event_details' => 'General assembly for all student leaders to discuss upcoming activities.',
            'access_code' => 'REQ-' . strtoupper(Str::random(6)), // REQ- + 6 chars = 10 total
            'num_participants' => 150,
            'purpose_id' => 1,
            'additional_requests' => 'Extension wire and 2 projectors',
            'num_tables' => 15,
            'num_chairs' => 150,
            'num_microphones' => 4,
            'event_documents_url' => 'https://example.com/uploads/event_doc.pdf',
            'event_documents_public_id' => 'events/ga_doc_001',
            'proof_of_payment_url' => null,
            'proof_of_payment_public_id' => null,
            'upload_token' => 'tok_' . uniqid(),
            'status_id' => 3,
            'start_date' => date('Y-m-d'),
            'end_date' => date('Y-m-d'),
            'start_time' => '09:00:00',
            'end_time' => '17:00:00',
            'all_day' => false,
            'late_penalty_fee' => 0,
            'is_late' => false,
            'returned_at' => null,
            'tentative_fee' => 1250.00,
            'approved_fee' => 1250.00,
            'is_finalized' => true,
            'finalized_at' => date('Y-m-d H:i:s', strtotime('-2 days')),
            'finalized_by' => 1,
            'official_receipt_num' => 'OR-' . date('Ymd') . '-001',
            'is_closed' => false,
            'closed_at' => null,
            'closed_by' => null,
            'closure_reason' => null,
            'created_at' => date('Y-m-d H:i:s', strtotime('-5 days')),
            'updated_at' => date('Y-m-d H:i:s', strtotime('-5 days')),
        ]);
        $lastId = DB::getPdo()->lastInsertId();
        DB::table('requested_facilities')->insert([
            ['request_id' => $lastId, 'facility_id' => 1],
            ['request_id' => $lastId, 'facility_id' => 3],
        ]);

        // Today's Event 2: Leadership Workshop
        DB::table('requisition_forms')->insert([
            'user_type' => 'External',
            'first_name' => 'Maria',
            'last_name' => 'Santos',
            'email' => 'maria.santos@external.org',
            'school_id' => null,
            'organization_name' => 'Youth Leadership Summit PH',
            'contact_number' => '09987654321',
            'event_title' => 'Leadership Workshop',
            'event_details' => 'Workshop for youth leaders from different municipalities.',
            'access_code' => 'REQ-' . strtoupper(Str::random(6)), // REQ- + 6 chars = 10 total
            'num_participants' => 80,
            'purpose_id' => 2,
            'additional_requests' => 'Whiteboard and markers',
            'num_tables' => 10,
            'num_chairs' => 80,
            'num_microphones' => 2,
            'event_documents_url' => 'https://example.com/uploads/workshop_proposal.pdf',
            'event_documents_public_id' => 'events/workshop_001',
            'proof_of_payment_url' => null,
            'proof_of_payment_public_id' => null,
            'upload_token' => 'tok_' . uniqid(),
            'status_id' => 2,
            'start_date' => date('Y-m-d'),
            'end_date' => date('Y-m-d'),
            'start_time' => '13:00:00',
            'end_time' => '18:00:00',
            'all_day' => false,
            'late_penalty_fee' => 0,
            'is_late' => false,
            'returned_at' => null,
            'tentative_fee' => 750.00,
            'approved_fee' => null,
            'is_finalized' => false,
            'finalized_at' => null,
            'finalized_by' => null,
            'official_receipt_num' => null,
            'is_closed' => false,
            'closed_at' => null,
            'closed_by' => null,
            'closure_reason' => null,
            'created_at' => date('Y-m-d H:i:s', strtotime('-1 day')),
            'updated_at' => date('Y-m-d H:i:s', strtotime('-1 day')),
        ]);
        $lastId = DB::getPdo()->lastInsertId();
        DB::table('requested_facilities')->insert([
            ['request_id' => $lastId, 'facility_id' => 2],
            ['request_id' => $lastId, 'facility_id' => 4],
        ]);
        // Today's Event 3: Department Meeting
        DB::table('requisition_forms')->insert([
            'user_type' => 'Internal',
            'first_name' => 'Robert',
            'last_name' => 'Johnson',
            'email' => 'robert.johnson@university.edu',
            'school_id' => '202112345',
            'organization_name' => 'College of Engineering',
            'contact_number' => '09181234567',
            'event_title' => 'Engineering Faculty Meeting',
            'event_details' => 'Monthly faculty meeting to discuss curriculum updates.',
            'access_code' => 'REQ-' . strtoupper(Str::random(6)),
            'num_participants' => 45,
            'purpose_id' => 1,
            'additional_requests' => 'Conference phone for remote attendees',
            'num_tables' => 5,
            'num_chairs' => 50,
            'num_microphones' => 2,
            'event_documents_url' => 'https://example.com/uploads/faculty_meeting.pdf',
            'event_documents_public_id' => 'events/faculty_001',
            'proof_of_payment_url' => null,
            'proof_of_payment_public_id' => null,
            'upload_token' => 'tok_' . uniqid(),
            'status_id' => 3,
            'start_date' => date('Y-m-d'),
            'end_date' => date('Y-m-d'),
            'start_time' => '10:00:00',
            'end_time' => '12:00:00',
            'all_day' => false,
            'late_penalty_fee' => 0,
            'is_late' => false,
            'returned_at' => null,
            'tentative_fee' => 500.00,
            'approved_fee' => 500.00,
            'is_finalized' => true,
            'finalized_at' => date('Y-m-d H:i:s', strtotime('-1 day')),
            'finalized_by' => 1,
            'official_receipt_num' => 'OR-' . date('Ymd') . '-003',
            'is_closed' => false,
            'closed_at' => null,
            'closed_by' => null,
            'closure_reason' => null,
            'created_at' => date('Y-m-d H:i:s', strtotime('-3 days')),
            'updated_at' => date('Y-m-d H:i:s', strtotime('-3 days')),
        ]);
        $lastId = DB::getPdo()->lastInsertId();
        DB::table('requested_facilities')->insert([
            ['request_id' => $lastId, 'facility_id' => 1],
        ]);

        // Today's Event 4: Student Orientation
        DB::table('requisition_forms')->insert([
            'user_type' => 'Internal',
            'first_name' => 'Sarah',
            'last_name' => 'Chen',
            'email' => 'sarah.chen@university.edu',
            'school_id' => '201998765',
            'organization_name' => 'Office of Student Affairs',
            'contact_number' => '09171112233',
            'event_title' => 'Freshman Orientation',
            'event_details' => 'Welcome orientation for new students.',
            'access_code' => 'REQ-' . strtoupper(Str::random(6)),
            'num_participants' => 200,
            'purpose_id' => 2,
            'additional_requests' => 'Sound system and 2 LCD projectors',
            'num_tables' => 20,
            'num_chairs' => 200,
            'num_microphones' => 4,
            'event_documents_url' => 'https://example.com/uploads/orientation.pdf',
            'event_documents_public_id' => 'events/orientation_001',
            'proof_of_payment_url' => null,
            'proof_of_payment_public_id' => null,
            'upload_token' => 'tok_' . uniqid(),
            'status_id' => 3,
            'start_date' => date('Y-m-d'),
            'end_date' => date('Y-m-d'),
            'start_time' => '13:00:00',
            'end_time' => '17:00:00',
            'all_day' => false,
            'late_penalty_fee' => 0,
            'is_late' => false,
            'returned_at' => null,
            'tentative_fee' => 2500.00,
            'approved_fee' => 2500.00,
            'is_finalized' => true,
            'finalized_at' => date('Y-m-d H:i:s', strtotime('-2 days')),
            'finalized_by' => 1,
            'official_receipt_num' => 'OR-' . date('Ymd') . '-004',
            'is_closed' => false,
            'closed_at' => null,
            'closed_by' => null,
            'closure_reason' => null,
            'created_at' => date('Y-m-d H:i:s', strtotime('-4 days')),
            'updated_at' => date('Y-m-d H:i:s', strtotime('-4 days')),
        ]);
        $lastId = DB::getPdo()->lastInsertId();
        DB::table('requested_facilities')->insert([
            ['request_id' => $lastId, 'facility_id' => 2],
            ['request_id' => $lastId, 'facility_id' => 3],
        ]);

        // Today's Event 5: Sports Tournament
        DB::table('requisition_forms')->insert([
            'user_type' => 'Internal',
            'first_name' => 'Mike',
            'last_name' => 'Torres',
            'email' => 'mike.torres@university.edu',
            'school_id' => '202045678',
            'organization_name' => 'University Athletics',
            'contact_number' => '09175556677',
            'event_title' => 'Inter-College Basketball Tournament',
            'event_details' => 'Annual basketball competition between colleges.',
            'access_code' => 'REQ-' . strtoupper(Str::random(6)),
            'num_participants' => 300,
            'purpose_id' => 3,
            'additional_requests' => 'Scoreboard and PA system',
            'num_tables' => 10,
            'num_chairs' => 100,
            'num_microphones' => 3,
            'event_documents_url' => 'https://example.com/uploads/tournament.pdf',
            'event_documents_public_id' => 'events/tournament_001',
            'proof_of_payment_url' => null,
            'proof_of_payment_public_id' => null,
            'upload_token' => 'tok_' . uniqid(),
            'status_id' => 3,
            'start_date' => date('Y-m-d'),
            'end_date' => date('Y-m-d'),
            'start_time' => '09:00:00',
            'end_time' => '18:00:00',
            'all_day' => false,
            'late_penalty_fee' => 0,
            'is_late' => false,
            'returned_at' => null,
            'tentative_fee' => 5000.00,
            'approved_fee' => 5000.00,
            'is_finalized' => true,
            'finalized_at' => date('Y-m-d H:i:s', strtotime('-1 day')),
            'finalized_by' => 1,
            'official_receipt_num' => 'OR-' . date('Ymd') . '-005',
            'is_closed' => false,
            'closed_at' => null,
            'closed_by' => null,
            'closure_reason' => null,
            'created_at' => date('Y-m-d H:i:s', strtotime('-6 days')),
            'updated_at' => date('Y-m-d H:i:s', strtotime('-6 days')),
        ]);
        $lastId = DB::getPdo()->lastInsertId();
        DB::table('requested_facilities')->insert([
            ['request_id' => $lastId, 'facility_id' => 4],
        ]);

        // Today's Event 6: Thesis Defense
        DB::table('requisition_forms')->insert([
            'user_type' => 'Internal',
            'first_name' => 'Patricia',
            'last_name' => 'Lim',
            'email' => 'patricia.lim@university.edu',
            'school_id' => '202178945',
            'organization_name' => 'Graduate School',
            'contact_number' => '09178889900',
            'event_title' => 'PhD Thesis Defense',
            'event_details' => 'Final defense for doctoral candidate.',
            'access_code' => 'REQ-' . strtoupper(Str::random(6)),
            'num_participants' => 30,
            'purpose_id' => 1,
            'additional_requests' => 'HDMI cable for laptop',
            'num_tables' => 3,
            'num_chairs' => 35,
            'num_microphones' => 1,
            'event_documents_url' => 'https://example.com/uploads/thesis.pdf',
            'event_documents_public_id' => 'events/thesis_001',
            'proof_of_payment_url' => null,
            'proof_of_payment_public_id' => null,
            'upload_token' => 'tok_' . uniqid(),
            'status_id' => 3,
            'start_date' => date('Y-m-d'),
            'end_date' => date('Y-m-d'),
            'start_time' => '14:00:00',
            'end_time' => '16:00:00',
            'all_day' => false,
            'late_penalty_fee' => 0,
            'is_late' => false,
            'returned_at' => null,
            'tentative_fee' => 300.00,
            'approved_fee' => 300.00,
            'is_finalized' => true,
            'finalized_at' => date('Y-m-d H:i:s', strtotime('-3 days')),
            'finalized_by' => 1,
            'official_receipt_num' => 'OR-' . date('Ymd') . '-006',
            'is_closed' => false,
            'closed_at' => null,
            'closed_by' => null,
            'closure_reason' => null,
            'created_at' => date('Y-m-d H:i:s', strtotime('-7 days')),
            'updated_at' => date('Y-m-d H:i:s', strtotime('-7 days')),
        ]);
        $lastId = DB::getPdo()->lastInsertId();
        DB::table('requested_facilities')->insert([
            ['request_id' => $lastId, 'facility_id' => 1],
        ]);

        // Today's Event 7: Cultural Show
        DB::table('requisition_forms')->insert([
            'user_type' => 'External',
            'first_name' => 'Anna',
            'last_name' => 'Reyes',
            'email' => 'anna.reyes@culturalgroup.org',
            'school_id' => null,
            'organization_name' => 'Philippine Cultural Society',
            'contact_number' => '09172223344',
            'event_title' => 'Cultural Dance Performance',
            'event_details' => 'Traditional Filipino dance showcase.',
            'access_code' => 'REQ-' . strtoupper(Str::random(6)),
            'num_participants' => 120,
            'purpose_id' => 2,
            'additional_requests' => 'Stage lighting and sound system',
            'num_tables' => 8,
            'num_chairs' => 120,
            'num_microphones' => 5,
            'event_documents_url' => 'https://example.com/uploads/cultural.pdf',
            'event_documents_public_id' => 'events/cultural_001',
            'proof_of_payment_url' => null,
            'proof_of_payment_public_id' => null,
            'upload_token' => 'tok_' . uniqid(),
            'status_id' => 3,
            'start_date' => date('Y-m-d'),
            'end_date' => date('Y-m-d'),
            'start_time' => '18:00:00',
            'end_time' => '21:00:00',
            'all_day' => false,
            'late_penalty_fee' => 0,
            'is_late' => false,
            'returned_at' => null,
            'tentative_fee' => 2000.00,
            'approved_fee' => 1800.00,
            'is_finalized' => true,
            'finalized_at' => date('Y-m-d H:i:s', strtotime('-5 days')),
            'finalized_by' => 1,
            'official_receipt_num' => 'OR-' . date('Ymd') . '-007',
            'is_closed' => false,
            'closed_at' => null,
            'closed_by' => null,
            'closure_reason' => null,
            'created_at' => date('Y-m-d H:i:s', strtotime('-8 days')),
            'updated_at' => date('Y-m-d H:i:s', strtotime('-8 days')),
        ]);
        $lastId = DB::getPdo()->lastInsertId();
        DB::table('requested_facilities')->insert([
            ['request_id' => $lastId, 'facility_id' => 2],
            ['request_id' => $lastId, 'facility_id' => 4],
        ]);

        // Today's Event 8: Alumni Homecoming
        DB::table('requisition_forms')->insert([
            'user_type' => 'External',
            'first_name' => 'David',
            'last_name' => 'Garcia',
            'email' => 'david.garcia@alumni.org',
            'school_id' => null,
            'organization_name' => 'University Alumni Association',
            'contact_number' => '09173334455',
            'event_title' => 'Alumni Homecoming 2026',
            'event_details' => 'Annual gathering of university alumni.',
            'access_code' => 'REQ-' . strtoupper(Str::random(6)),
            'num_participants' => 500,
            'purpose_id' => 2,
            'additional_requests' => 'Stage, sound system, and catering area',
            'num_tables' => 50,
            'num_chairs' => 500,
            'num_microphones' => 6,
            'event_documents_url' => 'https://example.com/uploads/alumni.pdf',
            'event_documents_public_id' => 'events/alumni_001',
            'proof_of_payment_url' => null,
            'proof_of_payment_public_id' => null,
            'upload_token' => 'tok_' . uniqid(),
            'status_id' => 3,
            'start_date' => date('Y-m-d'),
            'end_date' => date('Y-m-d'),
            'start_time' => '16:00:00',
            'end_time' => '22:00:00',
            'all_day' => false,
            'late_penalty_fee' => 0,
            'is_late' => false,
            'returned_at' => null,
            'tentative_fee' => 8000.00,
            'approved_fee' => 7500.00,
            'is_finalized' => true,
            'finalized_at' => date('Y-m-d H:i:s', strtotime('-4 days')),
            'finalized_by' => 1,
            'official_receipt_num' => 'OR-' . date('Ymd') . '-008',
            'is_closed' => false,
            'closed_at' => null,
            'closed_by' => null,
            'closure_reason' => null,
            'created_at' => date('Y-m-d H:i:s', strtotime('-10 days')),
            'updated_at' => date('Y-m-d H:i:s', strtotime('-10 days')),
        ]);
        $lastId = DB::getPdo()->lastInsertId();
        DB::table('requested_facilities')->insert([
            ['request_id' => $lastId, 'facility_id' => 1],
            ['request_id' => $lastId, 'facility_id' => 2],
            ['request_id' => $lastId, 'facility_id' => 4],
        ]);
    }




}