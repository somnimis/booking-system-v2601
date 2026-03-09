<?php
// tests/Feature/PendingRequestsFilterTest.php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Admin;
use App\Models\Department;
use App\Models\Facility;
use App\Models\Equipment;
use App\Models\RequisitionForm;
use App\Models\RequestedFacility;
use App\Models\RequestedEquipment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

class PendingRequestsFilterTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_filters_pending_requests_based_on_admin_departments()
    {
        // Create two departments
        $dept1 = Department::factory()->create(['department_name' => 'Dept 1']);
        $dept2 = Department::factory()->create(['department_name' => 'Dept 2']);

        // Create admin for dept1
        $admin = Admin::factory()->approvingOfficer()->create();
        $admin->departments()->attach($dept1->department_id);

        // Create facilities in different departments
        $facility1 = Facility::factory()->create([
            'department_id' => $dept1->department_id,
            'facility_name' => 'Facility in Dept 1'
        ]);
        
        $facility2 = Facility::factory()->create([
            'department_id' => $dept2->department_id,
            'facility_name' => 'Facility in Dept 2'
        ]);

        // Create requisitions
        $req1 = RequisitionForm::factory()->pendingApproval()->create();
        RequestedFacility::create([
            'request_id' => $req1->request_id,
            'facility_id' => $facility1->facility_id
        ]);

        $req2 = RequisitionForm::factory()->pendingApproval()->create();
        RequestedFacility::create([
            'request_id' => $req2->request_id,
            'facility_id' => $facility2->facility_id
        ]);

        Sanctum::actingAs($admin, ['*']);

        // Test the filtering logic (you'll need to implement this in your controller)
        $response = $this->getJson('/api/admin/pending-requests?filter_by_admin=true');

        $response->assertStatus(200);
        $responseData = $response->json();
        
        // Should only see req1 (matches admin's department)
        $this->assertEquals(1, $responseData['total']);
        $this->assertEquals($req1->request_id, $responseData['data'][0]['request_id']);
    }
}