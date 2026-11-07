<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Admin;
use App\Models\RequisitionApproval;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class AdminEmailNotificationTest extends TestCase
{
    public function test_admin_receives_email_when_managing_resource_submitted()
    {
        Mail::fake();

        $adminRey = Admin::where('email', 'hunniegwyn37@gmail.com')->first();
        $adminIvan = Admin::where('email', 'hg.escosar@gmail.com')->first();

        $this->assertNotNull($adminRey);
        $this->assertNotNull($adminIvan);

        // Use 2027 dates
        $startDate = Carbon::create(2027, 1, 15, 9, 0, 0);
        $endDate = Carbon::create(2027, 1, 15, 17, 0, 0);

        // Try different resources that are less likely to have conflicts
        // Facility IDs: 4, 13, 24 (BUM managed - department 3)
        // Equipment IDs: 1-20 (EMS managed - department 4)
        $facilityId = 13; // Try different facility
        $equipmentId = 5; // Try different equipment

        session([
            'request_info' => [
                'user_type' => 'Internal',
                'first_name' => 'John',
                'last_name' => 'Doe',
                'email' => 'john.doe@example.com',
                'contact_number' => '09123456789',
                'school_id' => '12345',
                'num_participants' => 50,
                'purpose_id' => 1,
                'additional_requests' => 'Test submission',
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
                'start_time' => $startDate->format('H:i'),
                'end_time' => $endDate->format('H:i'),
                'all_day' => false,
            ],
            'selected_items' => [
                [
                    'type' => 'facility',
                    'facility_id' => $facilityId,
                    'id' => $facilityId,
                    'name' => 'Test Facility',
                    'base_fee' => 100,
                    'total_fee' => 100,
                    'rate_type' => 'hourly',
                    'quantity' => 1,
                ],
                [
                    'type' => 'equipment',
                    'equipment_id' => $equipmentId,
                    'id' => $equipmentId,
                    'name' => 'Test Equipment',
                    'base_fee' => 50,
                    'total_fee' => 100,
                    'rate_type' => 'hourly',
                    'quantity' => 2,
                ]
            ]
        ]);

        $payload = [
            'user_type' => 'Internal',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john.doe@example.com',
            'contact_number' => '09123456789',
            'school_id' => '12345',
            'num_participants' => 50,
            'purpose_id' => 1,
            'additional_requests' => 'Test submission',
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
            'start_time' => $startDate->format('H:i'),
            'end_time' => $endDate->format('H:i'),
            'all_day' => false,
            'event_title' => 'Test Event',
            'event_details' => 'Test event details',
            'num_tables' => 0,
            'num_chairs' => 0,
            'num_microphones' => 0,
        ];

        $response = $this->post('/requisition/submit', $payload);
        
        if ($response->status() !== 200) {
            Log::error('Test failed:', ['content' => $response->getContent()]);
            $this->fail('Request failed: ' . $response->getContent());
        }

        $requestId = $response->json('data.request_id');
        Log::info('Request created:', ['request_id' => $requestId]);

        $reyApprovals = RequisitionApproval::where('request_id', $requestId)
            ->where('admin_id', $adminRey->admin_id)
            ->count();
        $ivanApprovals = RequisitionApproval::where('request_id', $requestId)
            ->where('admin_id', $adminIvan->admin_id)
            ->count();

        Log::info('Approval counts:', [
            'rey' => $reyApprovals,
            'ivan' => $ivanApprovals
        ]);

        $this->assertGreaterThan(0, $reyApprovals, 'Rey has no approvals');
        $this->assertGreaterThan(0, $ivanApprovals, 'Ivan has no approvals');

        Mail::assertSent('emails.admin-approval-request', function ($mail) use ($adminRey) {
            return $mail->hasTo($adminRey->email);
        });

        Mail::assertSent('emails.admin-approval-request', function ($mail) use ($adminIvan) {
            return $mail->hasTo($adminIvan->email);
        });
    }
}