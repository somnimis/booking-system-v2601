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

            'access_code' => strtoupper(Str::random(8)),
            'num_participants' => 40,
            'purpose_id' => 2,
            'additional_requests' => 'Projector and whiteboard needed',

            'formal_letter_url' => 'https://example.com/formal_letter_alex.pdf',
            'formal_letter_public_id' => 'letter_alex',
            'facility_layout_url' => 'https://example.com/layout.pdf',
            'facility_layout_public_id' => 'layout_456',
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

            'endorser' => 'Dr. Reyes',
            'date_endorsed' => now()->subDay(),

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

            'access_code' => strtoupper(Str::random(8)),
            'num_participants' => 25,
            'purpose_id' => 1,
            'additional_requests' => 'Need extra chairs and microphones',

            'formal_letter_url' => 'https://example.com/formal_letter.pdf',
            'formal_letter_public_id' => 'formal_letter_123',
            'facility_layout_url' => null,
            'facility_layout_public_id' => null,
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

            'endorser' => null,
            'date_endorsed' => null,

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

            'access_code' => strtoupper(Str::random(8)),
            'num_participants' => 15,
            'purpose_id' => 3,
            'additional_requests' => 'Need extra tables and projector',

            'formal_letter_url' => 'https://example.com/formal_letter_alex.pdf',
            'formal_letter_public_id' => 'formal_letter_alex',
            'facility_layout_url' => null,
            'facility_layout_public_id' => null,
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

            'endorser' => 'Dr. Cruz',
            'date_endorsed' => now()->addDays(1),

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

            'access_code' => strtoupper(Str::random(8)),
            'num_participants' => 15,
            'purpose_id' => 3,
            'additional_requests' => 'Projector and seating for participants',

            'formal_letter_url' => 'https://example.com/formal_letter_carlos.pdf',
            'formal_letter_public_id' => 'formal_letter_carlos',
            'facility_layout_url' => null,
            'facility_layout_public_id' => null,
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

            'endorser' => null,
            'date_endorsed' => null,

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

            'access_code' => strtoupper(Str::random(8)),
            'num_participants' => 30,
            'purpose_id' => 2,
            'additional_requests' => 'Include sound system and banners',

            'formal_letter_url' => 'https://example.com/formal_letter_maria.pdf',
            'formal_letter_public_id' => 'formal_letter_maria',
            'facility_layout_url' => null,
            'facility_layout_public_id' => null,
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

            'endorser' => 'Prof. Santos',
            'date_endorsed' => now()->subDays(4),

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

        // Event 6: Mid-morning (Day 2)
        DB::table('requisition_forms')->insert([
            'user_type' => 'Internal',
            'first_name' => 'Maria',
            'last_name' => 'Lopez',
            'email' => 'maria.lopez@example.com',
            'school_id' => 'INT2025-001',
            'organization_name' => 'University Club',
            'contact_number' => '09331234567',

            'access_code' => strtoupper(Str::random(8)),
            'num_participants' => 30,
            'purpose_id' => 2,
            'additional_requests' => 'Audio system and stage setup',

            'formal_letter_url' => 'https://example.com/formal_letter_maria.pdf',
            'formal_letter_public_id' => 'formal_letter_maria',
            'facility_layout_url' => 'https://example.com/layout_maria.pdf',
            'facility_layout_public_id' => 'layout_maria',
            'proof_of_payment_url' => 'https://example.com/payment_maria.pdf',
            'proof_of_payment_public_id' => 'payment_maria',
            'upload_token' => Str::random(20),

            'status_id' => 8,
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

            'endorser' => 'Dean Smith',
            'date_endorsed' => now()->subDays(5),

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

            'access_code' => strtoupper(Str::random(8)),
            'num_participants' => 50,
            'purpose_id' => 1,
            'additional_requests' => 'Networking setup, coffee station',

            'formal_letter_url' => 'https://example.com/formal_letter_sarah.pdf',
            'formal_letter_public_id' => 'formal_letter_sarah',
            'facility_layout_url' => 'https://example.com/layout_sarah.pdf',
            'facility_layout_public_id' => 'layout_sarah',
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

            'endorser' => 'Dr. Williams',
            'date_endorsed' => now(),

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

            'access_code' => strtoupper(Str::random(8)),
            'num_participants' => 20,
            'purpose_id' => 2,
            'additional_requests' => 'Workshop materials needed',

            'formal_letter_url' => 'https://example.com/formal_letter_robert.pdf',
            'formal_letter_public_id' => 'formal_letter_robert',
            'facility_layout_url' => null,
            'facility_layout_public_id' => null,
            'proof_of_payment_url' => null,
            'proof_of_payment_public_id' => null,
            'upload_token' => Str::random(20),

            'status_id' => 8,
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

            'endorser' => 'Prof. Garcia',
            'date_endorsed' => now()->subDays(12),

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

            'access_code' => strtoupper(Str::random(8)),
            'num_participants' => 35,
            'purpose_id' => 3,
            'additional_requests' => 'Art display setup',

            'formal_letter_url' => 'https://example.com/formal_letter_lisa.pdf',
            'formal_letter_public_id' => 'formal_letter_lisa',
            'facility_layout_url' => 'https://example.com/layout_lisa.pdf',
            'facility_layout_public_id' => 'layout_lisa',
            'proof_of_payment_url' => 'https://example.com/payment_lisa.pdf',
            'proof_of_payment_public_id' => 'payment_lisa',
            'upload_token' => Str::random(20),

            'status_id' => 8,
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

            'endorser' => 'Ms. Tanaka',
            'date_endorsed' => now()->subDays(9),

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
            
            'access_code' => strtoupper(Str::random(8)),
            'num_participants' => 25,
            'purpose_id' => 2,
            'additional_requests' => 'Morning seminar setup with projector',
            
            'formal_letter_url' => 'https://example.com/formal_letter_james.pdf',
            'formal_letter_public_id' => 'formal_letter_james',
            'facility_layout_url' => null,
            'facility_layout_public_id' => null,
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
            
            'endorser' => 'Dr. Martinez',
            'date_endorsed' => now()->subDays(6),
            
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
            
            'access_code' => strtoupper(Str::random(8)),
            'num_participants' => 40,
            'purpose_id' => 1, // Meeting
            'additional_requests' => 'Boardroom setup with coffee',
            
            'formal_letter_url' => 'https://example.com/formal_letter_patricia.pdf',
            'formal_letter_public_id' => 'formal_letter_patricia',
            'facility_layout_url' => null,
            'facility_layout_public_id' => null,
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
            
            'endorser' => null,
            'date_endorsed' => null,
            
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
            
            'access_code' => strtoupper(Str::random(8)),
            'num_participants' => 60,
            'purpose_id' => 3, // Event
            'additional_requests' => 'Workshop - need whiteboards and markers',
            
            'formal_letter_url' => 'https://example.com/formal_letter_michael.pdf',
            'formal_letter_public_id' => 'formal_letter_michael',
            'facility_layout_url' => 'https://example.com/layout_michael.pdf',
            'facility_layout_public_id' => 'layout_michael',
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
            
            'endorser' => 'Dean Roberts',
            'date_endorsed' => now()->subDays(2),
            
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
            
            'access_code' => strtoupper(Str::random(8)),
            'num_participants' => 35,
            'purpose_id' => 1,
            'additional_requests' => 'Pitch night - need stage and microphone',
            
            'formal_letter_url' => 'https://example.com/formal_letter_jennifer.pdf',
            'formal_letter_public_id' => 'formal_letter_jennifer',
            'facility_layout_url' => 'https://example.com/layout_jennifer.pdf',
            'facility_layout_public_id' => 'layout_jennifer',
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
            
            'endorser' => null,
            'date_endorsed' => null,
            
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
            
            'access_code' => strtoupper(Str::random(8)),
            'num_participants' => 80,
            'purpose_id' => 3,
            'additional_requests' => 'Cultural night - full sound and lights',
            
            'formal_letter_url' => 'https://example.com/formal_letter_david.pdf',
            'formal_letter_public_id' => 'formal_letter_david',
            'facility_layout_url' => 'https://example.com/layout_david.pdf',
            'facility_layout_public_id' => 'layout_david',
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
            
            'endorser' => 'Prof. Nakamura',
            'date_endorsed' => now()->subDays(5),
            
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
    }

    
}