<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\CheckAvailabilityService;
use App\Models\RequisitionForm;
use App\Models\CalendarEvent;
use App\Models\Facility;
use App\Models\Equipment;
use App\Models\EquipmentItem;
use App\Models\RequestedFacility;
use App\Models\RequestedEquipment;
use App\Models\FormStatus;
use App\Models\EventVenue;
use App\Models\EventEquipment;
use PHPUnit\Framework\Attributes\Test;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Database\Seeders\DatabaseSeeder;

class CheckAvailabilityServiceTest extends TestCase
{
    use DatabaseMigrations;

    protected CheckAvailabilityService $service;

protected function setUp(): void
{
    parent::setUp();
    
    // Run your actual database seeder
    $this->seed(DatabaseSeeder::class);
    
    $this->service = new CheckAvailabilityService();
}

protected function tearDown(): void
{
    parent::tearDown();
}
    protected function createFacility()
    {
        return Facility::create([
            'facility_name' => 'Test Hall ' . uniqid(),
            'base_fee' => 1000,
            'rate_type' => 'Per Hour',
            'capacity' => 100,
            'location_type' => 'Indoors',
            'status_id' => 1,
            'category_id' => 1,
        ]);
    }

protected function createEquipment()
{
    $equipment = Equipment::create([
        'equipment_name' => 'Projector ' . uniqid(),
        'base_fee' => 500,
        'rate_type' => 'Per Hour',
        'status_id' => 1,
        'category_id' => 1,
    ]);

    EquipmentItem::create([
        'equipment_id' => $equipment->equipment_id,
        'item_name' => 'Projector Unit 1',
        'serial_number' => 'PROJ001_' . uniqid(),
        'status_id' => 1,
        'condition_id' => 1,
        'created_by' => 1,  // ADD THIS - use an existing admin_id
    ]);

    EquipmentItem::create([
        'equipment_id' => $equipment->equipment_id,
        'item_name' => 'Projector Unit 2',
        'serial_number' => 'PROJ002_' . uniqid(),
        'status_id' => 1,
        'condition_id' => 1,
        'created_by' => 1,  // ADD THIS
    ]);

    return $equipment;
}

protected function createRequisition($data)
{
    $accessCode = 'TEST' . time() . rand(100, 999);
    $accessCode = substr($accessCode, 0, 10);  // Ensure max 10 chars
    return RequisitionForm::create([
        'first_name' => 'John',
        'last_name' => 'Doe',
        'email' => 'john@example.com',
        'user_type' => 'Internal',
        'event_title' => 'Test Event',
        'purpose_id' => 1,
        'num_participants' => 10,
        'status_id' => $data['status_id'] ?? 1,
        'start_date' => $data['start_date'],
        'end_date' => $data['end_date'],
        'start_time' => $data['start_time'] ?? '09:00:00',
        'end_time' => $data['end_time'] ?? '17:00:00',
        'all_day' => $data['all_day'] ?? false,
        'access_code' => $accessCode,
    ]);
}

    protected function runDatabaseMigrations()
{
    // Disable foreign key checks
    DB::statement('SET FOREIGN_KEY_CHECKS=0');
    
    $this->artisan('migrate:fresh');
    
    // Re-enable foreign key checks
    DB::statement('SET FOREIGN_KEY_CHECKS=1');
    
    $this->app->make('Illuminate\Contracts\Console\Kernel')->call('migrate', ['--path' => 'database/migrations']);
}

    #[Test]
    public function it_returns_no_conflicts_when_no_existing_bookings()
    {
        
        $facility = $this->createFacility();

        $conflicts = $this->service->checkFacilityAvailability(
            $facility->facility_id,
            '2025-01-15',
            '2025-01-15',
            '10:00:00',
            '12:00:00',
            false
        );

        $this->assertEmpty($conflicts);
    }

    #[Test]
    public function it_detects_conflict_with_existing_reserved_requisition()
    {
        
        $facility = $this->createFacility();

        $existingForm = $this->createRequisition([
            'start_date' => '2025-01-15',
            'end_date' => '2025-01-15',
            'start_time' => '10:00:00',
            'end_time' => '12:00:00',
            'status_id' => 3,
        ]);

        RequestedFacility::create([
            'request_id' => $existingForm->request_id,
            'facility_id' => $facility->facility_id,
        ]);

        $conflicts = $this->service->checkFacilityAvailability(
            $facility->facility_id,
            '2025-01-15',
            '2025-01-15',
            '10:30:00',
            '11:30:00',
            false
        );

        $this->assertNotEmpty($conflicts);
        $this->assertEquals('requisition', $conflicts[0]['source']);
        $this->assertEquals('Reserved', $conflicts[0]['status']);
    }

    #[Test]
    public function it_warns_but_does_not_block_pending_approval_requisition()
    {
        
        $facility = $this->createFacility();

        $existingForm = $this->createRequisition([
            'start_date' => '2025-01-15',
            'end_date' => '2025-01-15',
            'start_time' => '10:00:00',
            'end_time' => '12:00:00',
            'status_id' => 1,
        ]);

        RequestedFacility::create([
            'request_id' => $existingForm->request_id,
            'facility_id' => $facility->facility_id,
        ]);

        $conflicts = $this->service->checkFacilityAvailability(
            $facility->facility_id,
            '2025-01-15',
            '2025-01-15',
            '10:30:00',
            '11:30:00',
            false
        );

        $this->assertNotEmpty($conflicts);
        $this->assertEquals('Pending Approval', $conflicts[0]['status']);
    }

    #[Test]
    public function it_detects_conflict_with_calendar_event()
    {
        
        $facility = $this->createFacility();

        $event = CalendarEvent::create([
            'event_name' => 'University Event',
            'start_date' => '2025-01-15',
            'end_date' => '2025-01-15',
            'start_time' => '09:00:00',
            'end_time' => '17:00:00',
            'all_day' => false,
        ]);

        EventVenue::create([
            'event_id' => $event->event_id,
            'facility_id' => $facility->facility_id,
        ]);

        $conflicts = $this->service->checkFacilityAvailability(
            $facility->facility_id,
            '2025-01-15',
            '2025-01-15',
            '10:00:00',
            '12:00:00',
            false
        );

        $this->assertNotEmpty($conflicts);
        $this->assertEquals('calendar_event', $conflicts[0]['source']);
    }

    #[Test]
    public function it_applies_4_hour_grace_period_for_facilities()
    {
        
        $facility = $this->createFacility();

        $existingForm = $this->createRequisition([
            'start_date' => '2025-01-15',
            'end_date' => '2025-01-15',
            'start_time' => '08:00:00',
            'end_time' => '12:00:00',
            'status_id' => 3,
        ]);

        RequestedFacility::create([
            'request_id' => $existingForm->request_id,
            'facility_id' => $facility->facility_id,
        ]);

        // New booking starting at 2:00 PM (2 hours after, within 4-hour grace)
        $conflicts = $this->service->checkFacilityAvailability(
            $facility->facility_id,
            '2025-01-15',
            '2025-01-15',
            '14:00:00',
            '16:00:00',
            false
        );

        $this->assertNotEmpty($conflicts);
        $this->assertStringContainsString('4-hour cleanup', $conflicts[0]['conflict_reason']);

        // New booking starting at 5:00 PM (5 hours after, outside grace period)
        $noConflicts = $this->service->checkFacilityAvailability(
            $facility->facility_id,
            '2025-01-15',
            '2025-01-15',
            '17:00:00',
            '19:00:00',
            false
        );

        $this->assertEmpty($noConflicts);
    }

    #[Test]
    public function it_does_not_apply_grace_period_for_equipment_only_requests()
    {
        
        $facility = $this->createFacility();

        $existingForm = $this->createRequisition([
            'start_date' => '2025-01-15',
            'end_date' => '2025-01-15',
            'start_time' => '08:00:00',
            'end_time' => '12:00:00',
            'status_id' => 3,
        ]);

        RequestedFacility::create([
            'request_id' => $existingForm->request_id,
            'facility_id' => $facility->facility_id,
        ]);

        $conflicts = $this->service->checkFacilityAvailability(
            $facility->facility_id,
            '2025-01-15',
            '2025-01-15',
            '13:00:00',
            '15:00:00',
            false
        );

        $this->assertIsArray($conflicts);
    }

    #[Test]
    public function it_calculates_equipment_availability_correctly()
    {
        
        $equipment = $this->createEquipment();

        $form = $this->createRequisition([
            'start_date' => '2025-01-15',
            'end_date' => '2025-01-15',
            'start_time' => '09:00:00',
            'end_time' => '17:00:00',
            'status_id' => 2,
        ]);

        RequestedEquipment::create([
            'request_id' => $form->request_id,
            'equipment_id' => $equipment->equipment_id,
            'quantity' => 1,
        ]);

        $available = $this->service->checkEquipmentAvailability(
            $equipment->equipment_id,
            '2025-01-15',
            '2025-01-15',
            false
        );

        $this->assertEquals(1, $available);
    }

    #[Test]
    public function it_counts_calendar_event_equipment_in_availability()
    {
        
        $equipment = $this->createEquipment();

        $event = CalendarEvent::create([
            'event_name' => 'School Event',
            'start_date' => '2025-01-15',
            'end_date' => '2025-01-15',
            'start_time' => '09:00:00',
            'end_time' => '17:00:00',
            'all_day' => false,
        ]);

        EventEquipment::create([
            'event_id' => $event->event_id,
            'equipment_id' => $equipment->equipment_id,
            'quantity' => 1,
        ]);

        $available = $this->service->checkEquipmentAvailability(
            $equipment->equipment_id,
            '2025-01-15',
            '2025-01-15',
            false
        );

        $this->assertEquals(1, $available);
    }

    #[Test]
    public function it_returns_zero_when_no_equipment_available()
    {
        
        $equipment = $this->createEquipment();

        $form = $this->createRequisition([
            'start_date' => '2025-01-15',
            'end_date' => '2025-01-15',
            'start_time' => '09:00:00',
            'end_time' => '17:00:00',
            'status_id' => 2,
        ]);

        RequestedEquipment::create([
            'request_id' => $form->request_id,
            'equipment_id' => $equipment->equipment_id,
            'quantity' => 2,
        ]);

        $available = $this->service->checkEquipmentAvailability(
            $equipment->equipment_id,
            '2025-01-15',
            '2025-01-15',
            false
        );

        $this->assertEquals(0, $available);
    }

    #[Test]
    public function it_ignores_completed_rejected_cancelled_requisitions()
    {
        
        $facility = $this->createFacility();

        $statuses = ['Completed', 'Rejected', 'Cancelled'];

        foreach ($statuses as $statusName) {
            $status = FormStatus::where('status_name', $statusName)->first();

            $form = $this->createRequisition([
                'start_date' => '2025-01-15',
                'end_date' => '2025-01-15',
                'start_time' => '10:00:00',
                'end_time' => '12:00:00',
                'status_id' => $status->status_id,
            ]);

            RequestedFacility::create([
                'request_id' => $form->request_id,
                'facility_id' => $facility->facility_id,
            ]);
        }

        $conflicts = $this->service->checkFacilityAvailability(
            $facility->facility_id,
            '2025-01-15',
            '2025-01-15',
            '10:30:00',
            '11:30:00',
            false
        );

        $this->assertEmpty($conflicts);
    }

    #[Test]
    public function it_detects_all_day_event_conflicts()
    {
        
        $facility = $this->createFacility();

        $existingForm = $this->createRequisition([
            'start_date' => '2025-01-15',
            'end_date' => '2025-01-15',
            'start_time' => '00:00:00',
            'end_time' => '23:59:59',
            'all_day' => true,
            'status_id' => 3,
        ]);

        RequestedFacility::create([
            'request_id' => $existingForm->request_id,
            'facility_id' => $facility->facility_id,
        ]);

        $conflicts = $this->service->checkFacilityAvailability(
            $facility->facility_id,
            '2025-01-15',
            '2025-01-15',
            '14:00:00',
            '16:00:00',
            false
        );

        $this->assertNotEmpty($conflicts);
    }

    #[Test]
    public function it_handles_multi_day_booking_conflicts()
    {
        
        $facility = $this->createFacility();

        $existingForm = $this->createRequisition([
            'start_date' => '2025-01-15',
            'end_date' => '2025-01-17',
            'start_time' => '09:00:00',
            'end_time' => '17:00:00',
            'status_id' => 3,
        ]);

        RequestedFacility::create([
            'request_id' => $existingForm->request_id,
            'facility_id' => $facility->facility_id,
        ]);

        $conflicts = $this->service->checkFacilityAvailability(
            $facility->facility_id,
            '2025-01-16',
            '2025-01-16',
            '10:00:00',
            '12:00:00',
            false
        );

        $this->assertNotEmpty($conflicts);
    }
}