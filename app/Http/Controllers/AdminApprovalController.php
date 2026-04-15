<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\RequisitionApproval;
use App\Models\RequestedFacility;
use App\Models\RequestedEquipment;
use App\Models\RequisitionFee;
use App\Models\FormStatus;
use App\Models\CompletedTransaction;
use App\Models\RequisitionForm;
use App\Models\RequisitionComment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/* AdminApprovalController — Summary Documentation

This controller manages the entire admin-side approval and fee handling process
for requisition forms within the booking system. It provides endpoints for viewing,
approving, rejecting, and modifying requests, as well as managing related financial actions.

The controller includes methods to fetch pending and completed requests, allowing
admins to review requisition forms that are awaiting approval or have been finalized.
Only authorized roles such as Head Admin, Vice President of Administration, and
Approving Officer can perform approval or rejection actions. When a request is approved
or rejected, a corresponding record is created in the requisition_approvals table,
capturing details such as the admin who performed the action, remarks, and the timestamp.

It also handles the financial side of the approval process. Through dedicated methods,
admins can add fees or discounts to a requisition form, each stored in the
requisition_fees table with details like label, amount, and references to any
waived facilities or equipment. Additional methods allow specific items or entire
forms to be marked as waived, updating related database records to reflect that
charges have been removed or discounted.

Overall, the AdminApprovalController serves as the core module for managing
the administrative workflow of requisition approval, ensuring that all actions,
statuses, and fee-related transactions are properly validated, recorded, and
restricted to the appropriate user roles.
*/


class AdminApprovalController extends Controller
{

    public function getApprovalHistory($requestId)
    {
        try {
            $approvals = RequisitionApproval::with(['approvedBy', 'rejectedBy'])
                ->where('request_id', $requestId)
                ->orderBy('date_updated', 'desc')
                ->get()
                ->map(function ($approval) {
                    $admin = $approval->approvedBy ?: $approval->rejectedBy;
                    $action = $approval->approved_by ? 'approved' : 'rejected';
                    $actionClass = $approval->approved_by ? 'text-success' : 'text-danger';
                    $actionIcon = $approval->approved_by ? 'fa-thumbs-up' : 'fa-thumbs-down';

                    return [
                        'admin_id' => $admin ? $admin->admin_id : null, // Add admin_id
                        'admin_name' => $admin ? $admin->first_name . ' ' . $admin->last_name : 'Unknown Admin',
                        'admin_photo' => $admin->photo_url ?? null,
                        'action' => $action,
                        'action_class' => $actionClass,
                        'action_icon' => $actionIcon,
                        'remarks' => $approval->remarks,
                        'date_updated' => $approval->date_updated,
                        'formatted_date' => Carbon::parse($approval->date_updated)->format('M j, Y g:i A')
                    ];
                });

            return response()->json($approvals);
        } catch (\Exception $e) {
            \Log::error('Failed to fetch approval history', [
                'request_id' => $requestId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'Failed to fetch approval history',
                'details' => $e->getMessage()
            ], 500);
        }
    }

public function getCalendarEvents(Request $request)
{
    try {
        // Determine if this is an admin request
        $isAdmin = $request->has('admin_view') ||
            $request->user() instanceof Admin ||
            str_contains($request->path(), 'admin');

        // Remove the different status filtering - use the same for both views
        $excludedStatuses = FormStatus::whereIn('status_name', [
            'Returned',
            'Late Return',
            'Completed',
            'Rejected',
            'Cancelled'
        ])->pluck('status_id');

        $statusQuery = function ($query) use ($excludedStatuses) {
            $query->whereNotIn('status_id', $excludedStatuses);
        };

        // Get filter parameters
        $facilityIds = $request->get('facilities');
        $statuses = $request->get('statuses');

        $query = RequisitionForm::with([
            'requestedFacilities.facility',
            'requestedEquipment.equipment',
            'purpose',
            'formStatus'
        ]);

        // Apply status filter (same for both admin and public)
        $query->where($statusQuery);

        // Apply additional status filter if provided
        if ($statuses) {
            $statusIds = explode(',', $statuses);
            $query->whereIn('status_id', $statusIds);
        }

        // Filter by facilities if provided
        if ($facilityIds) {
            $facilityIdArray = explode(',', $facilityIds);
            $query->whereHas('requestedFacilities', function ($q) use ($facilityIdArray) {
                $q->whereIn('facility_id', $facilityIdArray);
            });
        }

        // Filter by equipment_id if provided
        $equipmentId = $request->get('equipment_id');
        if ($equipmentId) {
            $query->whereHas('requestedEquipment', function ($q) use ($equipmentId) {
                $q->where('equipment_id', $equipmentId);
            });
        }

        $forms = $query->get();

        // Transform data - SIMPLE transformation, no timezone tricks
        $events = $forms->map(function ($requisition) use ($isAdmin) {
            return $this->transformCalendarEvent($requisition, $isAdmin);
        });

        return response()->json([
            'success' => true,
            'data' => $events,
            'is_admin' => $isAdmin,
            'filters' => [
                'facilities' => $facilityIds,
                'statuses' => $statuses
            ]
        ]);

    } catch (\Exception $e) {
        \Log::error('Calendar events error: ' . $e->getMessage());
        return response()->json([
            'success' => false,
            'message' => 'Failed to load calendar events: ' . $e->getMessage(),
            'data' => []
        ], 500);
    }
}

private function transformCalendarEvent($requisition, $isAdmin = false)
{
    // Event title
    $title = $requisition->calendar_title ?: "Booking #{$requisition->request_id}";

    // Status color
    $statusColor = $requisition->formStatus->color_code;

    // ⚠️ CRITICAL: Remove seconds from time strings
    $startTime = substr($requisition->start_time, 0, 5); // Get only HH:MM
    $endTime = substr($requisition->end_time, 0, 5);     // Get only HH:MM
    
    // Just concatenate date and time exactly as they come from database
    $startDateTime = $requisition->start_date . ' ' . $startTime;
    $endDateTime = $requisition->end_date . ' ' . $endTime;

    // For FullCalendar, handle all-day events differently
    if ($requisition->all_day) {
        // For all-day events, use date-only format with 'T' for FullCalendar
        $startISO = $requisition->start_date; // YYYY-MM-DD only
        $endISO = $requisition->end_date . 'T23:59:59'; // End of last day

        // Format for display
        $formattedStartDate = date('M j, Y', strtotime($requisition->start_date));
        $formattedEndDate = date('M j, Y', strtotime($requisition->end_date));
        $formattedStartTime = 'All Day';
        $formattedEndTime = 'All Day';
    } else {
        // For regular events - use HH:MM format without seconds
        $startISO = $requisition->start_date . 'T' . $startTime;  // Removed seconds
        $endISO = $requisition->end_date . 'T' . $endTime;        // Removed seconds

        // Format for display
        $formattedStartDate = date('M j, Y', strtotime($requisition->start_date));
        $formattedEndDate = date('M j, Y', strtotime($requisition->end_date));
        $formattedStartTime = date('g:i A', strtotime($startTime));
        $formattedEndTime = date('g:i A', strtotime($endTime));
    }

    // Check if multi-day
    $isMultiDay = $requisition->start_date != $requisition->end_date;

    // Create schedule string
    if ($requisition->all_day) {
        $fullSchedule = $isMultiDay
            ? "{$formattedStartDate} (All Day) to {$formattedEndDate} (All Day)"
            : "{$formattedStartDate} (All Day)";
    } else {
        $fullSchedule = $isMultiDay
            ? "{$formattedStartDate} {$formattedStartTime} to {$formattedEndDate} {$formattedEndTime}"
            : "{$formattedStartDate} {$formattedStartTime} to {$formattedEndTime}";
    }

    // Get facility names with category and subcategory information
    $facilities = $requisition->requestedFacilities->map(function ($requestedFacility) {
        $facility = $requestedFacility->facility;
        
        // Get category and subcategory information
        $categoryId = null;
        $categoryName = null;
        $subcategoryId = null;
        $subcategoryName = null;
        
        if ($facility) {
            // Assuming your facility has relationships to category and subcategory
            // You may need to adjust this based on your actual database structure
            if (isset($facility->category)) {
                $categoryId = $facility->category->category_id ?? null;
                $categoryName = $facility->category->category_name ?? null;
            }
            
            if (isset($facility->subcategory)) {
                $subcategoryId = $facility->subcategory->subcategory_id ?? null;
                $subcategoryName = $facility->subcategory->subcategory_name ?? null;
            }
            
            // Alternative: If subcategory IS the facility (based on your earlier data)
            // Then the facility_id might actually be the subcategory_id
            $subcategoryId = $facility->facility_id; // The facility_id is the subcategory_id
        }

        return [
            'facility_id' => $requestedFacility->facility_id,
            'name' => $facility->facility_name ?? 'Unknown Facility',
            'fee' => $facility->base_fee ?? 0,
            'rate_type' => $facility->rate_type ?? 'Per Event',
            'is_waived' => $requestedFacility->is_waived ?? false,
            // Add category and subcategory information for filtering
            'category_id' => $categoryId,
            'category_name' => $categoryName,
            'subcategory_id' => $subcategoryId,
            'subcategory_name' => $subcategoryName,
        ];
    })->toArray();

    // Get equipment with quantities
    $equipment = $requisition->requestedEquipment->map(function ($requestedEquipment) {
        return [
            'equipment_id' => $requestedEquipment->equipment_id,
            'name' => $requestedEquipment->equipment->equipment_name ?? 'Unknown Equipment',
            'quantity' => $requestedEquipment->quantity,
            'fee' => $requestedEquipment->equipment->base_fee ?? 0,
            'rate_type' => $requestedEquipment->equipment->rate_type ?? 'Per Event',
            'is_waived' => $requestedEquipment->is_waived ?? false
        ];
    })->toArray();

    // Calculate fees if admin
    $fees = null;
    if ($isAdmin) {
        $baseFees = $this->calculateBaseFees($requisition);
        $additionalFees = $this->calculateAdditionalFees($requisition);
        $discounts = $this->calculateTotalDiscounts($requisition, $baseFees + $additionalFees);
        $approvedFee = $baseFees + $additionalFees - $discounts;

        if ($requisition->is_late) {
            $approvedFee += $requisition->late_penalty_fee;
        }

        $fees = [
            'tentative_fee' => $baseFees + ($requisition->is_late ? $requisition->late_penalty_fee : 0),
            'approved_fee' => max(0, $approvedFee),
            'late_penalty_fee' => $requisition->late_penalty_fee ?? 0,
            'is_late' => $requisition->is_late ?? false,
            'breakdown' => [
                'base_fees' => $baseFees,
                'additional_fees' => $additionalFees,
                'discounts' => $discounts
            ]
        ];
    }

    // Extract all category and subcategory IDs for easy filtering
    $categoryIds = [];
    $subcategoryIds = [];
    foreach ($facilities as $facility) {
        if ($facility['category_id']) {
            $categoryIds[] = $facility['category_id'];
        }
        if ($facility['subcategory_id']) {
            $subcategoryIds[] = $facility['subcategory_id'];
        }
    }
    
    // Remove duplicates
    $categoryIds = array_unique($categoryIds);
    $subcategoryIds = array_unique($subcategoryIds);

    // Build event data - MATCHING exactly what working admin view expects
    $eventData = [
        'id' => $requisition->request_id,
        'request_id' => $requisition->request_id,
        'title' => $title,
        'start' => $startISO,
        'end' => $endISO,
        'allDay' => $requisition->all_day, // CRITICAL: This tells FullCalendar it's an all-day event
        'color' => $statusColor,
        'backgroundColor' => $statusColor,
        'borderColor' => $this->darkenColor($statusColor),
        'textColor' => '#ffffff',
        'extendedProps' => [
            'status' => $requisition->formStatus->status_name,
            'status_id' => $requisition->formStatus->status_id,
            'requester' => $requisition->first_name . ' ' . $requisition->last_name,
            'purpose' => $requisition->purpose->purpose_name ?? 'N/A',
            'num_participants' => $requisition->num_participants,
            'facilities' => $facilities,
            'equipment' => $equipment,
            'calendar_title' => $title,
            'calendar_description' => $requisition->calendar_description,
            'all_day' => $requisition->all_day,
            // Add these for category/subcategory filtering
            'category_ids' => $categoryIds,
            'subcategory_ids' => $subcategoryIds,
            'schedule' => [
                'start_date' => $requisition->start_date,
                'end_date' => $requisition->end_date,
                'start_time' => $requisition->all_day ? 'All Day' : $startTime,
                'end_time' => $requisition->all_day ? 'All Day' : $endTime,
                'formatted_start' => $formattedStartDate . ($requisition->all_day ? '' : ' ' . $formattedStartTime),
                'formatted_end' => $formattedEndDate . ($requisition->all_day ? '' : ' ' . $formattedEndTime),
                'full_duration' => $fullSchedule,
                'is_multi_day' => $isMultiDay
            ],
            'is_admin_view' => $isAdmin
        ]
    ];

    // Add admin-only data
    if ($isAdmin) {
        $eventData['extendedProps']['user_details'] = [
            'user_type' => $requisition->user_type,
            'first_name' => $requisition->first_name,
            'last_name' => $requisition->last_name,
            'email' => $requisition->email,
            'school_id' => $requisition->school_id,
            'organization_name' => $requisition->organization_name,
            'contact_number' => $requisition->contact_number
        ];

        $eventData['extendedProps']['fees'] = $fees;
        $eventData['extendedProps']['additional_requests'] = $requisition->additional_requests;
        $eventData['extendedProps']['access_code'] = $requisition->access_code;
    }

    return $eventData;
}

/**
 * Helper function to darken a color for borders
 * Accepts both hex (#RRGGBB) and rgb(r,g,b) formats
 */
private function darkenColor($color)
{
    // Handle RGB format (e.g., "rgb(127, 0, 196)")
    if (preg_match('/rgb\((\d+),\s*(\d+),\s*(\d+)\)/i', $color, $matches)) {
        $r = (int)$matches[1];
        $g = (int)$matches[2];
        $b = (int)$matches[3];
    } 
    // Handle hex format
    else {
        // Remove # if present
        $hex = str_replace('#', '', $color);

        // Ensure it's a 6-character hex
        if (strlen($hex) == 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }

        // Convert to RGB
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
    }

    // Darken by 20%
    $r = max(0, min(255, (int)($r * 0.8)));
    $g = max(0, min(255, (int)($g * 0.8)));
    $b = max(0, min(255, (int)($b * 0.8)));

    // Convert back to hex
    return sprintf("#%02x%02x%02x", $r, $g, $b);
}


    public function pendingRequests()
    {
        // Get status IDs to exclude
        $excludedStatuses = FormStatus::whereIn('status_name', [
            'Returned',
            'Late Return',
            'Completed',
            'Rejected',
            'Cancelled'
        ])->pluck('status_id');

        // Get pending forms with relationships - ADD requisitionFees relationship
        $forms = RequisitionForm::whereNotIn('status_id', $excludedStatuses)
            ->with([
                'formStatus',
                'requestedFacilities.facility',
                'requestedEquipment.equipment',
                'requisitionApprovals',
                'requisitionFees.addedBy',
                'purpose',
                'finalizedBy.role', // Eager load roles relationship
                'closedBy'
            ])
            ->get()
            ->map(function ($form) {
                // Calculate tentative fee from facilities and equipment
                $facilityFees = $form->requestedFacilities->sum(function ($facility) {
                    return $facility->is_waived ? 0 : $facility->facility->base_fee;
                });

                $equipmentFees = $form->requestedEquipment->sum(function ($equipment) {
                    return $equipment->is_waived ? 0 : ($equipment->equipment->base_fee * $equipment->quantity);
                });

                $totalTentativeFee = $facilityFees + $equipmentFees;
                if ($form->is_late) {
                    $totalTentativeFee += $form->late_penalty_fee;
                }

                // Calculate approved fee including requisition fees
                $approvedFee = $this->calculateApprovedFee($form);

                // Add approval and rejection counts
                $approvalCount = $form->requisitionApprovals->whereNotNull('approved_by')->count();
                $rejectionCount = $form->requisitionApprovals->whereNotNull('rejected_by')->count();

                // Add finalization info - FIXED: Handle null roles safely
                $isFinalized = $form->is_finalized;
                $finalizedBy = $form->finalizedBy ? [
                    'id' => $form->finalizedBy->admin_id,
                    'name' => $form->finalizedBy->first_name . ' ' . $form->finalizedBy->last_name,
                    'role' => $form->finalizedBy->role->role_title ?? 'Unknown' // Changed to role->role_title
                ] : null;

                // Format requisition fees for response
                $requisitionFees = $form->requisitionFees->map(function ($fee) {
                    return [
                        'fee_id' => $fee->fee_id,
                        'label' => $fee->label,
                        'account_num' => $fee->account_num,
                        'fee_amount' => (float) $fee->fee_amount,
                        'discount_amount' => (float) $fee->discount_amount,
                        'discount_type' => $fee->discount_type,
                        'type' => $fee->fee_amount > 0 ?
                            ($fee->fee_amount > 0 && $fee->discount_amount > 0 ? 'mixed' : 'fee') :
                            'discount',
                        'added_by' => $fee->addedBy ? [
                            'admin_id' => $fee->addedBy->admin_id,
                            'name' => $fee->addedBy->first_name . ' ' . $fee->addedBy->last_name
                        ] : null,
                        'created_at' => $fee->created_at,
                        'updated_at' => $fee->updated_at
                    ];
                });

                // Get overlapping requests that share facilities or equipment
                $overlappingRequests = $this->getOverlappingRequests($form);

                // Format schedule display based on all_day flag
                $formattedStartTime = $form->all_day ? 'All Day' : $form->start_time;
                $formattedEndTime = $form->all_day ? 'All Day' : $form->end_time;

                $formattedStartDateTime = $form->all_day
                    ? date('M j, Y', strtotime($form->start_date)) . ' (All Day)'
                    : date('M j, Y', strtotime($form->start_date)) . ' ' . date('g:i A', strtotime($form->start_time));

                $formattedEndDateTime = $form->all_day
                    ? date('M j, Y', strtotime($form->end_date)) . ' (All Day)'
                    : date('M j, Y', strtotime($form->end_date)) . ' ' . date('g:i A', strtotime($form->end_time));

                // Return the same structure as pendingRequests() with enhanced fees section
                return [
                    'request_id' => $form->request_id,
                    'user_details' => [
                        'user_type' => $form->user_type,
                        'first_name' => $form->first_name,
                        'last_name' => $form->last_name,
                        'email' => $form->email,
                        'school_id' => $form->school_id,
                        'organization_name' => $form->organization_name,
                        'contact_number' => $form->contact_number
                    ],
                    'form_details' => [
                        'num_participants' => $form->num_participants,
                        'num_tables' => $form->num_tables,
                        'num_chairs' => $form->num_chairs,
                        'purpose' => $form->purpose->purpose_name,
                        'additional_requests' => $form->additional_requests,
                        'status' => [
                            'id' => $form->formStatus->status_id,
                            'name' => $form->formStatus->status_name,
                            'color' => $form->formStatus->color_code
                        ],
                        'calendar_info' => [
                            'title' => $form->calendar_title,
                            'description' => $form->calendar_description
                        ],
                        'official_receipt_num' => $form->official_receipt_num
                    ],
                    'schedule' => [
                        'start_date' => $form->start_date,
                        'end_date' => $form->end_date,
                        'start_time' => $form->start_time,
                        'end_time' => $form->end_time,
                        'all_day' => $form->all_day, // ADDED
                        'formatted_start_time' => $formattedStartTime, // ADDED
                        'formatted_end_time' => $formattedEndTime, // ADDED
                        'formatted_start_datetime' => $formattedStartDateTime, // ADDED
                        'formatted_end_datetime' => $formattedEndDateTime // ADDED
                    ],
                    'requested_items' => [
                        'facilities' => $form->requestedFacilities->map(function ($facility) {
                        return [
                            'requested_facility_id' => $facility->requested_facility_id,
                            'facility_id' => $facility->facility_id,
                            'name' => $facility->facility->facility_name,
                            'fee' => $facility->facility->base_fee,
                            'rate_type' => $facility->facility->rate_type,
                            'is_waived' => $facility->is_waived
                        ];
                    }),
                        'equipment' => $form->requestedEquipment->map(function ($equipment) {
                        return [
                            'requested_equipment_id' => $equipment->requested_equipment_id,
                            'name' => $equipment->equipment->equipment_name,
                            'quantity' => $equipment->quantity,
                            'fee' => $equipment->equipment->base_fee,
                            'rate_type' => $equipment->equipment->rate_type,
                            'is_waived' => $equipment->is_waived,
                            'total_fee' => $equipment->equipment->base_fee * $equipment->quantity
                        ];
                    })
                    ],
                    'fees' => [
                        'tentative_fee' => $totalTentativeFee,
                        'approved_fee' => $approvedFee,
                        'late_penalty_fee' => $form->late_penalty_fee,
                        'is_late' => $form->is_late,
                        'breakdown' => [
                            'base_fees' => $facilityFees + $equipmentFees,
                            'additional_fees' => $form->requisitionFees->sum('fee_amount'),
                            'discounts' => $form->requisitionFees->sum('discount_amount'),
                            'late_penalty' => $form->is_late ? $form->late_penalty_fee : 0
                        ],
                        'requisition_fees' => $requisitionFees
                    ],
                    'status_tracking' => [
                        'is_late' => $form->is_late,
                        'is_finalized' => $form->is_finalized,
                        'finalized_at' => $form->finalized_at,
                        'finalized_by' => $finalizedBy,
                        'is_closed' => $form->is_closed,
                        'closed_at' => $form->closed_at,
                        'closed_by' => $form->closedBy ? [
                            'id' => $form->closedBy->admin_id,
                            'name' => $form->closedBy->first_name . ' ' . $form->closedBy->last_name
                        ] : null,
                        'returned_at' => $form->returned_at
                    ],
                    'documents' => [
                        'endorser' => $form->endorser,
                        'date_endorsed' => $form->date_endorsed,
                        'formal_letter' => [
                            'url' => $form->formal_letter_url,
                            'public_id' => $form->formal_letter_public_id
                        ],
                        'facility_layout' => [
                            'url' => $form->facility_layout_url,
                            'public_id' => $form->facility_layout_public_id
                        ],
                        'official_receipt' => [
                            'number' => $form->official_receipt_no,
                            'url' => $form->official_receipt_url,
                            'public_id' => $form->official_receipt_public_id
                        ],
                        'proof_of_payment' => [
                            'url' => $form->proof_of_payment_url,
                            'public_id' => $form->proof_of_payment_public_id
                        ]
                    ],
                    'approval_info' => [
                        'approval_count' => $approvalCount,
                        'rejection_count' => $rejectionCount,
                        'is_finalized' => $isFinalized,
                        'finalized_by' => $finalizedBy,
                        'can_finalize' => $approvalCount >= 3 && !$isFinalized,
                        'latest_action' => $form->requisitionApprovals()->latest('date_updated')->first()
                    ],
                    'overlapping_requests' => $overlappingRequests,
                    'access_code' => $form->access_code
                ];
            });

        return response()->json($forms);
    }

    public function paginatedAdminReservations(Request $request)
    {
        // Get status IDs to exclude (same as pendingRequests)
        $excludedStatuses = FormStatus::whereIn('status_name', [
            'Returned',
            'Late Return',
            'Completed',
            'Rejected',
            'Cancelled'
        ])->pluck('status_id');

        // Base query
        $query = RequisitionForm::whereNotIn('status_id', $excludedStatuses)
            ->with([
                'formStatus',
                'requestedFacilities.facility',
                'requestedEquipment.equipment',
                'requisitionApprovals',
                'requisitionFees.addedBy',
                'purpose',
                'finalizedBy.role',
                'closedBy'
            ]);

        // Get pagination parameters
        $page = $request->get('page', 1);
        $perPage = $request->get('per_page', 10);

        // Get total count for pagination
        $total = $query->count();

        // Apply pagination
        $forms = $query->orderBy('request_id', 'desc')
            ->skip(($page - 1) * $perPage)
            ->take($perPage)
            ->get();

        // Map the forms using the same mapping logic
        $mappedForms = $forms->map(function ($form) {
            // Calculate tentative fee from facilities and equipment
            $facilityFees = $form->requestedFacilities->sum(function ($facility) {
                return $facility->is_waived ? 0 : $facility->facility->base_fee;
            });

            $equipmentFees = $form->requestedEquipment->sum(function ($equipment) {
                return $equipment->is_waived ? 0 : ($equipment->equipment->base_fee * $equipment->quantity);
            });

            $totalTentativeFee = $facilityFees + $equipmentFees;
            if ($form->is_late) {
                $totalTentativeFee += $form->late_penalty_fee;
            }

            // Calculate approved fee including requisition fees
            $approvedFee = $this->calculateApprovedFee($form);

            // Add approval and rejection counts
            $approvalCount = $form->requisitionApprovals->whereNotNull('approved_by')->count();
            $rejectionCount = $form->requisitionApprovals->whereNotNull('rejected_by')->count();

            // Add finalization info
            $isFinalized = $form->is_finalized;
            $finalizedBy = $form->finalizedBy ? [
                'id' => $form->finalizedBy->admin_id,
                'name' => $form->finalizedBy->first_name . ' ' . $form->finalizedBy->last_name,
                'role' => $form->finalizedBy->role->role_title ?? 'Unknown'
            ] : null;

            // Format requisition fees for response
            $requisitionFees = $form->requisitionFees->map(function ($fee) {
                return [
                    'fee_id' => $fee->fee_id,
                    'label' => $fee->label,
                    'account_num' => $fee->account_num,
                    'fee_amount' => (float) $fee->fee_amount,
                    'discount_amount' => (float) $fee->discount_amount,
                    'discount_type' => $fee->discount_type,
                    'type' => $fee->fee_amount > 0 ?
                        ($fee->fee_amount > 0 && $fee->discount_amount > 0 ? 'mixed' : 'fee') :
                        'discount',
                    'added_by' => $fee->addedBy ? [
                        'admin_id' => $fee->addedBy->admin_id,
                        'name' => $fee->addedBy->first_name . ' ' . $fee->addedBy->last_name
                    ] : null,
                    'created_at' => $fee->created_at,
                    'updated_at' => $fee->updated_at
                ];
            });

            // Get overlapping requests that share facilities or equipment
            $overlappingRequests = $this->getOverlappingRequests($form);

            // Format schedule display based on all_day flag
            $formattedStartTime = $form->all_day ? 'All Day' : $form->start_time;
            $formattedEndTime = $form->all_day ? 'All Day' : $form->end_time;

            $formattedStartDateTime = $form->all_day
                ? date('M j, Y', strtotime($form->start_date)) . ' (All Day)'
                : date('M j, Y', strtotime($form->start_date)) . ' ' . date('g:i A', strtotime($form->start_time));

            $formattedEndDateTime = $form->all_day
                ? date('M j, Y', strtotime($form->end_date)) . ' (All Day)'
                : date('M j, Y', strtotime($form->end_date)) . ' ' . date('g:i A', strtotime($form->end_time));

            return [
                'request_id' => $form->request_id,
                'user_details' => [
                    'user_type' => $form->user_type,
                    'first_name' => $form->first_name,
                    'last_name' => $form->last_name,
                    'email' => $form->email,
                    'school_id' => $form->school_id,
                    'organization_name' => $form->organization_name,
                    'contact_number' => $form->contact_number,
                    'is_admin_created' => $form->is_admin_created
                ],
                'form_details' => [
                    'num_participants' => $form->num_participants,
                    'num_tables' => $form->num_tables,
                    'num_chairs' => $form->num_chairs,
                    'purpose' => $form->purpose->purpose_name,
                    'additional_requests' => $form->additional_requests,
                    'status' => [
                        'id' => $form->formStatus->status_id,
                        'name' => $form->formStatus->status_name,
                        'color' => $form->formStatus->color_code
                    ],
                    'calendar_info' => [
                        'title' => $form->calendar_title,
                        'description' => $form->calendar_description
                    ],
                    'official_receipt_num' => $form->official_receipt_num,
                    'is_admin_created' => $form->is_admin_created
                ],
                'schedule' => [
                    'start_date' => $form->start_date,
                    'end_date' => $form->end_date,
                    'start_time' => $form->start_time,
                    'end_time' => $form->end_time,
                    'all_day' => $form->all_day, // ADDED
                    'formatted_start_time' => $formattedStartTime, // ADDED
                    'formatted_end_time' => $formattedEndTime, // ADDED
                    'formatted_start_datetime' => $formattedStartDateTime, // ADDED
                    'formatted_end_datetime' => $formattedEndDateTime // ADDED
                ],
                'requested_items' => [
                    'facilities' => $form->requestedFacilities->map(function ($facility) {
                        return [
                            'requested_facility_id' => $facility->requested_facility_id,
                            'facility_id' => $facility->facility_id,
                            'name' => $facility->facility->facility_name,
                            'fee' => $facility->facility->base_fee,
                            'rate_type' => $facility->facility->rate_type,
                            'is_waived' => $facility->is_waived
                        ];
                    }),
                    'equipment' => $form->requestedEquipment->map(function ($equipment) {
                        return [
                            'requested_equipment_id' => $equipment->requested_equipment_id,
                            'name' => $equipment->equipment->equipment_name,
                            'quantity' => $equipment->quantity,
                            'fee' => $equipment->equipment->base_fee,
                            'rate_type' => $equipment->equipment->rate_type,
                            'is_waived' => $equipment->is_waived,
                            'total_fee' => $equipment->equipment->base_fee * $equipment->quantity
                        ];
                    })
                ],
                'fees' => [
                    'tentative_fee' => $totalTentativeFee,
                    'approved_fee' => $approvedFee,
                    'late_penalty_fee' => $form->late_penalty_fee,
                    'is_late' => $form->is_late,
                    'breakdown' => [
                        'base_fees' => $facilityFees + $equipmentFees,
                        'additional_fees' => $form->requisitionFees->sum('fee_amount'),
                        'discounts' => $form->requisitionFees->sum('discount_amount'),
                        'late_penalty' => $form->is_late ? $form->late_penalty_fee : 0
                    ],
                    'requisition_fees' => $requisitionFees
                ],
                'status_tracking' => [
                    'is_late' => $form->is_late,
                    'is_finalized' => $form->is_finalized,
                    'finalized_at' => $form->finalized_at,
                    'finalized_by' => $finalizedBy,
                    'is_closed' => $form->is_closed,
                    'closed_at' => $form->closed_at,
                    'closed_by' => $form->closedBy ? [
                        'id' => $form->closedBy->admin_id,
                        'name' => $form->closedBy->first_name . ' ' . $form->closedBy->last_name
                    ] : null,
                    'returned_at' => $form->returned_at
                ],
                'documents' => [
                    'endorser' => $form->endorser,
                    'date_endorsed' => $form->date_endorsed,
                    'formal_letter' => [
                        'url' => $form->formal_letter_url,
                        'public_id' => $form->formal_letter_public_id
                    ],
                    'facility_layout' => [
                        'url' => $form->facility_layout_url,
                        'public_id' => $form->facility_layout_public_id
                    ],
                    'official_receipt' => [
                        'number' => $form->official_receipt_no,
                        'url' => $form->official_receipt_url,
                        'public_id' => $form->official_receipt_public_id
                    ],
                    'proof_of_payment' => [
                        'url' => $form->proof_of_payment_url,
                        'public_id' => $form->proof_of_payment_public_id
                    ]
                ],
                'approval_info' => [
                    'approval_count' => $approvalCount,
                    'rejection_count' => $rejectionCount,
                    'is_finalized' => $isFinalized,
                    'finalized_by' => $finalizedBy,
                    'can_finalize' => $approvalCount >= 3 && !$isFinalized,
                    'latest_action' => $form->requisitionApprovals()->latest('date_updated')->first()
                ],
                'overlapping_requests' => $overlappingRequests,
                'access_code' => $form->access_code
            ];
        });

        return response()->json([
            'data' => $mappedForms,
            'meta' => [
                'current_page' => (int) $page,
                'per_page' => (int) $perPage,
                'total' => $total,
                'total_pages' => ceil($total / $perPage),
                'has_more' => ($page * $perPage) < $total
            ]
        ]);
    }


    // Add this helper method to find overlapping requests
    private function getOverlappingRequests($currentForm)
    {
        try {
            // Get current form's facility and equipment IDs
            $currentFacilityIds = $currentForm->requestedFacilities->pluck('facility_id')->toArray();
            $currentEquipmentIds = $currentForm->requestedEquipment->pluck('equipment_id')->toArray();

            if (empty($currentFacilityIds) && empty($currentEquipmentIds)) {
                return [];
            }

            // Get status IDs to exclude (completed forms)
            $excludedStatuses = FormStatus::whereIn('status_name', [
                'Returned',
                'Late Return',
                'Completed',
                'Rejected',
                'Cancelled'
            ])->pluck('status_id');

            // Find other pending requests that share facilities or equipment
            $overlappingRequests = RequisitionForm::where('request_id', '!=', $currentForm->request_id)
                ->whereNotIn('status_id', $excludedStatuses)
                ->where(function ($query) use ($currentFacilityIds, $currentEquipmentIds, $currentForm) {
                    // Check for shared facilities with overlapping schedules
                    if (!empty($currentFacilityIds)) {
                        $query->whereHas('requestedFacilities', function ($q) use ($currentFacilityIds, $currentForm) {
                            $q->whereIn('facility_id', $currentFacilityIds);
                        })->where(function ($dateQ) use ($currentForm) {
                            $this->addScheduleOverlapCondition($dateQ, $currentForm);
                        });
                    }

                    // Check for shared equipment with overlapping schedules
                    if (!empty($currentEquipmentIds)) {
                        $query->orWhereHas('requestedEquipment', function ($q) use ($currentEquipmentIds, $currentForm) {
                            $q->whereIn('equipment_id', $currentEquipmentIds);
                        })->where(function ($dateQ) use ($currentForm) {
                            $this->addScheduleOverlapCondition($dateQ, $currentForm);
                        });
                    }
                })
                ->with(['formStatus', 'requestedFacilities.facility', 'requestedEquipment.equipment'])
                ->get()
                ->map(function ($form) use ($currentForm) {
                    // Format schedule display based on all_day flag
                    $formattedStartTime = $form->all_day ? 'All Day' : $form->start_time;
                    $formattedEndTime = $form->all_day ? 'All Day' : $form->end_time;

                    $formattedStartDateTime = $form->all_day
                        ? date('M j, Y', strtotime($form->start_date)) . ' (All Day)'
                        : date('M j, Y', strtotime($form->start_date)) . ' ' . date('g:i A', strtotime($form->start_time));

                    $formattedEndDateTime = $form->all_day
                        ? date('M j, Y', strtotime($form->end_date)) . ' (All Day)'
                        : date('M j, Y', strtotime($form->end_date)) . ' ' . date('g:i A', strtotime($form->end_time));

                    // Calculate overlap severity
                    $overlapSeverity = $this->calculateOverlapSeverity($currentForm, $form);

                    return [
                        'request_id' => $form->request_id,
                        'requester_name' => $form->first_name . ' ' . $form->last_name,
                        'status' => $form->formStatus->status_name,
                        'schedule' => [
                            'start_date' => $form->start_date,
                            'end_date' => $form->end_date,
                            'start_time' => $form->start_time,
                            'end_time' => $form->end_time,
                            'all_day' => $form->all_day, // ADDED
                            'formatted_start_time' => $formattedStartTime, // ADDED
                            'formatted_end_time' => $formattedEndTime, // ADDED
                            'formatted_start_datetime' => $formattedStartDateTime, // ADDED
                            'formatted_end_datetime' => $formattedEndDateTime, // ADDED
                        ],
                        'overlap_severity' => $overlapSeverity, // ADDED
                        'shared_facilities' => $form->requestedFacilities
                            ->whereIn('facility_id', $currentForm->requestedFacilities->pluck('facility_id'))
                            ->pluck('facility.facility_name')
                            ->unique()
                            ->values()
                            ->toArray(),
                        'shared_equipment' => $form->requestedEquipment
                            ->whereIn('equipment_id', $currentForm->requestedEquipment->pluck('equipment_id'))
                            ->groupBy('equipment.equipment_name')
                            ->map(function ($group) {
                                return $group->first()->equipment->equipment_name . ' (×' . $group->sum('quantity') . ')';
                            })->values()->toArray()
                    ];
                });

            return $overlappingRequests;
        } catch (\Exception $e) {
            \Log::error('Error finding overlapping requests', [
                'request_id' => $currentForm->request_id,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Helper method to check schedule overlap considering all-day events
     */
    private function addScheduleOverlapCondition($query, $currentForm)
    {
        if ($currentForm->all_day) {
            // Current form is all-day: check date overlap
            $query->where('start_date', '<=', $currentForm->end_date)
                ->where('end_date', '>=', $currentForm->start_date);
        } else {
            // Current form is time-based: check date and time overlap
            $query->where(function ($q) use ($currentForm) {
                $q->where('start_date', '<=', $currentForm->end_date)
                    ->where('end_date', '>=', $currentForm->start_date)
                    ->where(function ($timeQ) use ($currentForm) {
                        $timeQ->where('start_time', '<', $currentForm->end_time)
                            ->where('end_time', '>', $currentForm->start_time);
                    })
                    // Also check if other form is all-day (conflicts with any time)
                    ->orWhere(function ($allDayQ) use ($currentForm) {
                        $allDayQ->where('all_day', true)
                            ->where('start_date', '<=', $currentForm->end_date)
                            ->where('end_date', '>=', $currentForm->start_date);
                    });
            });
        }
    }

    /**
     * Helper method to calculate overlap severity
     */
    private function calculateOverlapSeverity($form1, $form2)
    {
        $severity = 'low';

        // Check if dates overlap
        $datesOverlap = !($form1->end_date < $form2->start_date || $form1->start_date > $form2->end_date);

        if (!$datesOverlap) {
            return 'none';
        }

        // Check if it's the exact same date range
        if ($form1->start_date === $form2->start_date && $form1->end_date === $form2->end_date) {
            if ($form1->all_day || $form2->all_day) {
                $severity = 'high';
            } else {
                // Check time overlap for non-all-day events
                $timeOverlap = !($form1->end_time <= $form2->start_time || $form1->start_time >= $form2->end_time);
                $severity = $timeOverlap ? 'high' : 'medium';
            }
        } else {
            // Partial date overlap
            $severity = 'medium';
        }

        return $severity;
    }

    public function getRequisitionFormById($requestId)
    {
        try {
            \Log::debug('Fetching specific requisition form', ['request_id' => $requestId]);

            $form = RequisitionForm::with([
                'formStatus',
                'requestedFacilities.facility',
                'requestedEquipment.equipment',
                'requisitionApprovals',
                'requisitionFees.addedBy',
                'purpose',
                'finalizedBy.role',
                'closedBy'
            ])->findOrFail($requestId);

            // Calculate tentative fee from facilities and equipment
            $facilityFees = $form->requestedFacilities->sum(function ($facility) {
                return $facility->is_waived ? 0 : $facility->facility->base_fee;
            });

            $equipmentFees = $form->requestedEquipment->sum(function ($equipment) {
                return $equipment->is_waived ? 0 : ($equipment->equipment->base_fee * $equipment->quantity);
            });

            $totalTentativeFee = $facilityFees + $equipmentFees;
            if ($form->is_late) {
                $totalTentativeFee += $form->late_penalty_fee;
            }

            // Calculate approved fee including requisition fees
            $approvedFee = $this->calculateApprovedFee($form);

            // Add approval and rejection counts
            $approvalCount = $form->requisitionApprovals->whereNotNull('approved_by')->count();
            $rejectionCount = $form->requisitionApprovals->whereNotNull('rejected_by')->count();

            // Add finalization info
            $isFinalized = $form->is_finalized;
            $finalizedBy = $form->finalizedBy ? [
                'id' => $form->finalizedBy->admin_id,
                'name' => $form->finalizedBy->first_name . ' ' . $form->finalizedBy->last_name,
                'role' => $form->finalizedBy->role->role_title ?? 'Unknown'
            ] : null;

            // Format requisition fees for response
            $requisitionFees = $form->requisitionFees->map(function ($fee) {
                return [
                    'fee_id' => $fee->fee_id,
                    'label' => $fee->label,
                    'account_num' => $fee->account_num,
                    'fee_amount' => (float) $fee->fee_amount,
                    'discount_amount' => (float) $fee->discount_amount,
                    'discount_type' => $fee->discount_type,
                    'type' => $fee->fee_amount > 0 ?
                        ($fee->fee_amount > 0 && $fee->discount_amount > 0 ? 'mixed' : 'fee') :
                        'discount',
                    'added_by' => $fee->addedBy ? [
                        'admin_id' => $fee->addedBy->admin_id,
                        'name' => $fee->addedBy->first_name . ' ' . $fee->addedBy->last_name
                    ] : null,
                    'created_at' => $fee->created_at,
                    'updated_at' => $fee->updated_at
                ];
            });

            // Return the same structure as pendingRequests() but for single form
            $response = [
                'request_id' => $form->request_id,
                'user_details' => [
                    'user_type' => $form->user_type,
                    'first_name' => $form->first_name,
                    'last_name' => $form->last_name,
                    'email' => $form->email,
                    'school_id' => $form->school_id,
                    'organization_name' => $form->organization_name,
                    'contact_number' => $form->contact_number
                ],
                'form_details' => [
                    'num_participants' => $form->num_participants,
                    'purpose' => $form->purpose->purpose_name,
                    'additional_requests' => $form->additional_requests,
                    'status' => [
                        'name' => $form->formStatus->status_name,
                        'color' => $form->formStatus->color_code
                    ],
                    'calendar_info' => [
                        'title' => $form->calendar_title,
                        'description' => $form->calendar_description
                    ]
                ],
                'schedule' => [
                    'start_date' => $form->start_date,
                    'end_date' => $form->end_date,
                    'start_time' => $form->start_time,
                    'end_time' => $form->end_time
                ],
                'requested_items' => [
                    'facilities' => $form->requestedFacilities->map(function ($facility) {
                        return [
                            'requested_facility_id' => $facility->requested_facility_id,
                            'name' => $facility->facility->facility_name,
                            'fee' => $facility->facility->base_fee,
                            'rate_type' => $facility->facility->rate_type,
                            'is_waived' => $facility->is_waived
                        ];
                    }),
                    'equipment' => $form->requestedEquipment->groupBy('equipment.equipment_id')->map(function ($group) {
                        $firstItem = $group->first();
                        $totalQuantity = $group->sum('quantity');

                        return [
                            'requested_equipment_ids' => $group->pluck('requested_equipment_id')->toArray(),
                            'name' => $firstItem->equipment->equipment_name,
                            'quantity' => $totalQuantity,
                            'fee' => $firstItem->equipment->base_fee,
                            'rate_type' => $firstItem->equipment->rate_type,
                            'is_waived' => $firstItem->is_waived,
                            'total_fee' => $firstItem->equipment->base_fee * $totalQuantity
                        ];
                    })->values()
                ],
                'fees' => [
                    'tentative_fee' => $totalTentativeFee,
                    'approved_fee' => $approvedFee,
                    'late_penalty_fee' => $form->late_penalty_fee,
                    'is_late' => $form->is_late,
                    'breakdown' => [
                        'base_fees' => $facilityFees + $equipmentFees,
                        'additional_fees' => $form->requisitionFees->sum('fee_amount'),
                        'discounts' => $form->requisitionFees->sum('discount_amount'),
                        'late_penalty' => $form->is_late ? $form->late_penalty_fee : 0
                    ],
                    'requisition_fees' => $requisitionFees
                ],
                'status_tracking' => [
                    'is_late' => $form->is_late,
                    'is_finalized' => $form->is_finalized,
                    'finalized_at' => $form->finalized_at,
                    'finalized_by' => $finalizedBy,
                    'is_closed' => $form->is_closed,
                    'closed_at' => $form->closed_at,
                    'closed_by' => $form->closedBy ? [
                        'id' => $form->closedBy->admin_id,
                        'name' => $form->closedBy->first_name . ' ' . $form->closedBy->last_name
                    ] : null,
                    'returned_at' => $form->returned_at
                ],
                'documents' => [
                    'endorser' => $form->endorser,
                    'date_endorsed' => $form->date_endorsed,
                    'formal_letter' => [
                        'url' => $form->formal_letter_url,
                        'public_id' => $form->formal_letter_public_id
                    ],
                    'facility_layout' => [
                        'url' => $form->facility_layout_url,
                        'public_id' => $form->facility_layout_public_id
                    ],
                    'official_receipt' => [
                        'number' => $form->official_receipt_no,
                        'url' => $form->official_receipt_url,
                        'public_id' => $form->official_receipt_public_id
                    ],
                    'proof_of_payment' => [
                        'url' => $form->proof_of_payment_url,
                        'public_id' => $form->proof_of_payment_public_id
                    ]
                ],
                'approval_info' => [
                    'approval_count' => $approvalCount,
                    'rejection_count' => $rejectionCount,
                    'approvals' => $form->requisitionApprovals->whereNotNull('approved_by')->map(function ($approval) {
                        return [
                            'admin_id' => $approval->approved_by,
                            'date_updated' => $approval->date_updated
                        ];
                    }),
                    'rejections' => $form->requisitionApprovals->whereNotNull('rejected_by')->map(function ($rejection) {
                        return [
                            'admin_id' => $rejection->rejected_by,
                            'date_updated' => $rejection->date_updated
                        ];
                    }),
                    'is_finalized' => $isFinalized,
                    'finalized_by' => $finalizedBy,
                    'can_finalize' => $approvalCount >= 3 && !$isFinalized,
                    'latest_action' => $form->requisitionApprovals()->latest('date_updated')->first()
                ],
                'access_code' => $form->access_code
            ];

            \Log::debug('Successfully fetched requisition form', [
                'request_id' => $requestId,
                'approval_count' => $approvalCount,
                'rejection_count' => $rejectionCount
            ]);

            return response()->json($response);
        } catch (\Exception $e) {
            \Log::error('Failed to fetch requisition form by ID', [
                'request_id' => $requestId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'Failed to fetch requisition form',
                'details' => $e->getMessage()
            ], 404);
        }
    }

    public function approveRequest(Request $request, $requestId)
    {
        try {
            \Log::debug('=== APPROVE REQUEST CALLED ===', [
                'request_id' => $requestId,
                'admin_id' => auth()->id(),
                'full_url' => $request->fullUrl(),
                'method' => $request->method(),
                'headers' => $request->headers->all()
            ]);

            $adminId = auth()->id();

            if (!$adminId) {
                \Log::warning('Admin not authenticated');
                return response()->json(['error' => 'Admin not authenticated'], 401);
            }

            // Create approval record - remarks are optional
            $approval = RequisitionApproval::create([
                'approved_by' => $adminId,
                'rejected_by' => null,
                'remarks' => $request->input('remarks', null),
                'request_id' => $requestId,
                'date_updated' => now()
            ]);

            // Create comment record for activity timeline
            $commentText = "Approved this request" . ($request->input('remarks') ? ": " . $request->input('remarks') : "");
            RequisitionComment::create([
                'request_id' => $requestId,
                'admin_id' => $adminId,
                'comment' => $commentText
            ]);

            \Log::debug('Approval record created successfully', ['approval_id' => $approval->id]);

            return response()->json([
                'message' => 'Request approved successfully',
                'approval_id' => $approval->id
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed to approve request', [
                'request_id' => $requestId,
                'admin_id' => auth()->id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);

            return response()->json([
                'error' => 'Failed to approve request',
                'details' => $e->getMessage()
            ], 500);
        }
    }


    public function rejectRequest(Request $request, $requestId)
    {
        try {
            \Log::debug('Simple reject request attempt', [
                'request_id' => $requestId,
                'admin_id' => auth()->id(),
                'input_data' => $request->all()
            ]);

            $adminId = auth()->id();

            if (!$adminId) {
                return response()->json(['error' => 'Admin not authenticated'], 401);
            }

            // Create rejection record - remarks are optional
            $rejection = RequisitionApproval::create([
                'approved_by' => null,
                'rejected_by' => $adminId,
                'remarks' => $request->input('remarks', null), // Optional remarks
                'request_id' => $requestId,
                'date_updated' => now()
            ]);

            // Create comment record for activity timeline
            $commentText = "Rejected this request" . ($request->input('remarks') ? ": " . $request->input('remarks') : "");
            RequisitionComment::create([
                'request_id' => $requestId,
                'admin_id' => $adminId,
                'comment' => $commentText
            ]);

            \Log::debug('Rejection record created', ['rejection_id' => $rejection->id]);

            return response()->json([
                'message' => 'Request rejected successfully',
                'rejection_id' => $rejection->id
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed to reject request', [
                'request_id' => $requestId,
                'admin_id' => auth()->id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'Failed to reject request',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    public function getSimplifiedForms()
    {
        // Get status IDs to exclude
        $excludedStatuses = FormStatus::whereIn('status_name', [
            'Late',
            'Ongoing',
            'Scheduled',
            'Returned',
            'Late Return',
            'Completed',
            'Rejected',
            'Cancelled'
        ])->pluck('status_id');

        // Get pending forms with necessary relationships
        $forms = RequisitionForm::whereNotIn('status_id', $excludedStatuses)
            ->with([
                'purpose',
                'formStatus',
                'requestedFacilities.facility',
                'requestedEquipment.equipment',
                'requisitionApprovals'
            ])
            ->get()
            ->map(function ($form) {
                // Calculate tentative fee
                $facilityFees = $form->requestedFacilities->sum(function ($facility) {
                    return $facility->is_waived ? 0 : $facility->facility->base_fee;
                });

                $equipmentFees = $form->requestedEquipment->sum(function ($equipment) {
                    return $equipment->is_waived ? 0 : ($equipment->equipment->base_fee * $equipment->quantity);
                });

                $totalTentativeFee = $facilityFees + $equipmentFees + ($form->is_late ? $form->late_penalty_fee : 0);

                // Format schedule
                $startDateTime = date('F j, Y g:i A', strtotime($form->start_date . ' ' . $form->start_time));
                $endDateTime = date('F j, Y g:i A', strtotime($form->end_date . ' ' . $form->end_time));

                // Group equipment by name and sum quantities
                $equipmentGroups = $form->requestedEquipment->groupBy('equipment.equipment_name')
                    ->map(function ($group) {
                    $totalQuantity = $group->sum('quantity');
                    return $group->first()->equipment->equipment_name . ' (×' . $totalQuantity . ')';
                });

                // Format requested items
                $requestedItems = collect([
                    ...$form->requestedFacilities->map(fn($rf) => $rf->facility->facility_name),
                    ...$equipmentGroups->values()
                ])->join(', ');

                return [
                    'request_id' => $form->request_id,
                    'purpose' => $form->purpose->purpose_name,
                    'schedule' => $startDateTime . ' to ' . $endDateTime,
                    'requester' => $form->first_name . ' ' . $form->last_name,
                    'status_id' => $form->status_id,
                    'requested_items' => $requestedItems,
                    'tentative_fee' => number_format($totalTentativeFee, 2),
                    'approvals' => $form->requisitionApprovals()->whereNotNull('approved_by')->count(),
                    'rejections' => $form->requisitionApprovals()->whereNotNull('rejected_by')->count(),
                    'date_submitted' => $form->created_at
                ];
            })
            ->sortBy('status_id') // Sort ascending by status_id
            ->values(); // Reset indexes

        return response()->json($forms);
    }


    public function getRequisitionFees($requestId)
    {
        try {
            $fees = RequisitionFee::with('addedBy')
                ->where('request_id', $requestId)
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($fee) {
                    return [
                        'fee_id' => $fee->fee_id,
                        'label' => $fee->label,
                        'account_num' => $fee->account_num,
                        'fee_amount' => $fee->fee_amount,
                        'discount_amount' => $fee->discount_amount,
                        'discount_type' => $fee->discount_type,
                        'type' => $fee->fee_amount > 0 ? ($fee->fee_amount > 0 && $fee->discount_amount > 0 ? 'mixed' : 'fee') : 'discount',
                        'added_by' => $fee->addedBy ? [
                            'admin_id' => $fee->addedBy->admin_id,
                            'name' => $fee->addedBy->first_name . ' ' . $fee->addedBy->last_name
                        ] : null,
                        'created_at' => $fee->created_at,
                        'updated_at' => $fee->updated_at
                    ];
                });

            return response()->json($fees);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to fetch fees',
                'details' => $e->getMessage()
            ], 500);
        }
    }


    public function addFee(Request $request, $requestId)
    {
        try {
            $validatedData = $request->validate([
                'label' => 'required|string|max:50',
                'fee_amount' => 'required|numeric|min:0.01',
                'account_num' => 'nullable|string|max:10', // Add this line
            ]);

            $admin = auth()->user();

            $fee = RequisitionFee::create([
                'request_id' => $requestId,
                'added_by' => $admin->admin_id,
                'label' => $validatedData['label'],
                'fee_amount' => $validatedData['fee_amount'],
                'discount_amount' => 0,
                'account_num' => $validatedData['account_num'] ?? null, // Add this line
            ]);

            // Recalculate approved fee
            $form = RequisitionForm::with(['requestedFacilities', 'requestedEquipment', 'requisitionFees'])
                ->findOrFail($requestId);

            $approvedFee = $this->calculateApprovedFee($form);
            $form->approved_fee = $approvedFee;
            $form->save();

            return response()->json([
                'message' => 'Fee added successfully',
                'fee' => $fee,
                'updated_approved_fee' => $approvedFee
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to add fee',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    public function removeFee($requestId, $feeId)
    {
        try {
            $fee = RequisitionFee::where('request_id', $requestId)
                ->where('fee_id', $feeId)
                ->firstOrFail();

            $fee->delete();

            // Recalculate approved fee
            $form = RequisitionForm::with(['requestedFacilities', 'requestedEquipment', 'requisitionFees'])
                ->findOrFail($requestId);

            $approvedFee = $this->calculateApprovedFee($form);
            $form->approved_fee = $approvedFee;
            $form->save();

            return response()->json([
                'message' => 'Fee removed successfully',
                'updated_approved_fee' => $approvedFee
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to remove fee',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    public function addDiscount(Request $request, $requestId)
    {
        try {
            $validatedData = $request->validate([
                'label' => 'required|string|max:50',
                'discount_amount' => 'required|numeric|min:0.01',
                'discount_type' => 'required|in:Fixed,Percentage',
                'account_num' => 'nullable|string|max:10', // Add this line
            ]);

            $admin = auth()->user();

            $discount = RequisitionFee::create([
                'request_id' => $requestId,
                'added_by' => $admin->admin_id,
                'label' => $validatedData['label'],
                'fee_amount' => 0,
                'discount_amount' => $validatedData['discount_amount'],
                'discount_type' => $validatedData['discount_type'],
                'account_num' => $validatedData['account_num'] ?? null, // Add this line
            ]);

            // Recalculate approved fee
            $form = RequisitionForm::with(['requestedFacilities', 'requestedEquipment', 'requisitionFees'])
                ->findOrFail($requestId);

            $approvedFee = $this->calculateApprovedFee($form);
            $form->approved_fee = $approvedFee;
            $form->save();

            return response()->json([
                'message' => 'Discount added successfully',
                'discount' => $discount,
                'discount_type' => $validatedData['discount_type'],
                'updated_approved_fee' => $approvedFee
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to add discount',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    public function addLatePenalty(Request $request, $requestId)
    {
        try {
            $validatedData = $request->validate([
                'penalty_amount' => 'required|numeric|min:0'
            ]);

            $form = RequisitionForm::findOrFail($requestId);

            // Check if the requisition is marked as late by the system
            if (!$form->is_late) {
                return response()->json([
                    'error' => 'Cannot add late penalty',
                    'details' => 'This requisition is not marked as late by the system'
                ], 422);
            }

            $form->late_penalty_fee = $validatedData['penalty_amount'];
            $form->save();

            // Recalculate approved fee
            $form->load(['requestedFacilities', 'requestedEquipment', 'requisitionFees']);
            $approvedFee = $this->calculateApprovedFee($form);
            $form->approved_fee = $approvedFee;
            $form->save();

            return response()->json([
                'message' => 'Late penalty added successfully',
                'penalty_amount' => $form->late_penalty_fee,
                'updated_approved_fee' => $approvedFee
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to add late penalty',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    public function removeLatePenalty(Request $request, $requestId)
    {
        try {
            $form = RequisitionForm::findOrFail($requestId);

            // Only reset the penalty fee, leave is_late status as determined by the system
            $form->late_penalty_fee = 0;
            $form->save();

            // Recalculate approved fee without penalty
            $form->load(['requestedFacilities', 'requestedEquipment', 'requisitionFees']);
            $approvedFee = $this->calculateApprovedFee($form);
            $form->approved_fee = $approvedFee;
            $form->save();

            return response()->json([
                'message' => 'Late penalty removed successfully',
                'penalty_amount' => $form->late_penalty_fee,
                'updated_approved_fee' => $approvedFee,
                'is_late' => $form->is_late // Include current late status in response
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to remove late penalty',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    public function waiveItems(Request $request, $requestId)
    {
        try {
            \Log::debug('Waive items request received', [
                'request_id' => $requestId,
                'waive_all' => $request->waive_all,
                'waived_facilities' => $request->waived_facilities,
                'waived_equipment' => $request->waived_equipment
            ]);

            // First, let's log all equipment for this request to see what should be valid
            $validEquipmentIds = RequestedEquipment::where('request_id', $requestId)
                ->pluck('requested_equipment_id')
                ->toArray();

            $validFacilityIds = RequestedFacility::where('request_id', $requestId)
                ->pluck('requested_facility_id')
                ->toArray();

            \Log::debug('Valid IDs for this request', [
                'valid_equipment_ids' => $validEquipmentIds,
                'valid_facility_ids' => $validFacilityIds,
                'requested_equipment' => $request->waived_equipment,
                'requested_facilities' => $request->waived_facilities
            ]);

            // Custom validation to check if items belong to this request
            $validator = Validator::make($request->all(), [
                'waive_all' => 'sometimes|boolean',
                'waived_facilities' => 'sometimes|array',
                'waived_facilities.*' => [
                    function ($attribute, $value, $fail) use ($requestId, $validFacilityIds) {
                        if (!in_array($value, $validFacilityIds)) {
                            $fail("The selected facility (ID: $value) is invalid for this request. Valid facilities: " . implode(', ', $validFacilityIds));
                        }
                    }
                ],
                'waived_equipment' => 'sometimes|array',
                'waived_equipment.*' => [
                    function ($attribute, $value, $fail) use ($requestId, $validEquipmentIds) {
                        if (!in_array($value, $validEquipmentIds)) {
                            $fail("The selected equipment (ID: $value) is invalid for this request. Valid equipment: " . implode(', ', $validEquipmentIds));
                        }
                    }
                ]
            ]);

            if ($validator->fails()) {
                \Log::error('Waive items validation failed', [
                    'errors' => $validator->errors()->toArray(),
                    'request_data' => $request->all(),
                    'valid_equipment_ids' => $validEquipmentIds,
                    'valid_facility_ids' => $validFacilityIds
                ]);

                return response()->json([
                    'error' => 'Validation failed',
                    'details' => $validator->errors(),
                    'debug' => [
                        'valid_equipment_ids' => $validEquipmentIds,
                        'valid_facility_ids' => $validFacilityIds
                    ]
                ], 422);
            }
            $validatedData = $validator->validated();

            DB::beginTransaction();

            if (isset($validatedData['waive_all']) && $validatedData['waive_all']) {
                // Waive all facilities and equipment
                RequestedFacility::where('request_id', $requestId)
                    ->update(['is_waived' => true]);

                RequestedEquipment::where('request_id', $requestId)
                    ->update(['is_waived' => true]);
            } else {
                // Only update waivers for specific items
                // Update facilities based on the provided list
                if (isset($validatedData['waived_facilities'])) {
                    // Waive the specified facilities
                    RequestedFacility::where('request_id', $requestId)
                        ->whereIn('requested_facility_id', $validatedData['waived_facilities'])
                        ->update(['is_waived' => true]);

                    // Unwaive facilities not in the list
                    RequestedFacility::where('request_id', $requestId)
                        ->whereNotIn('requested_facility_id', $validatedData['waived_facilities'])
                        ->update(['is_waived' => false]);
                } else {
                    // If no facilities specified, unwaive all facilities
                    RequestedFacility::where('request_id', $requestId)
                        ->update(['is_waived' => false]);
                }

                // Update equipment based on the provided list
                if (isset($validatedData['waived_equipment'])) {
                    // Waive the specified equipment
                    RequestedEquipment::where('request_id', $requestId)
                        ->whereIn('requested_equipment_id', $validatedData['waived_equipment'])
                        ->update(['is_waived' => true]);

                    // Unwaive equipment not in the list
                    RequestedEquipment::where('request_id', $requestId)
                        ->whereNotIn('requested_equipment_id', $validatedData['waived_equipment'])
                        ->update(['is_waived' => false]);
                } else {
                    // If no equipment specified, unwaive all equipment
                    RequestedEquipment::where('request_id', $requestId)
                        ->update(['is_waived' => false]);
                }
            }

            // Recalculate approved fee
            $form = RequisitionForm::with(['requestedFacilities', 'requestedEquipment', 'requisitionFees'])
                ->findOrFail($requestId);

            $approvedFee = $this->calculateApprovedFee($form);
            $form->approved_fee = $approvedFee;
            $form->save();

            DB::commit();

            return response()->json([
                'message' => 'Items waived successfully',
                'updated_approved_fee' => $approvedFee,
                'tentative_fee' => $this->calculateTentativeFee($requestId)
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Failed to waive items', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'Failed to waive items',
                'details' => $e->getMessage()
            ], 500);
        }
    }


    public function addComment(Request $request, $requestId)
    {
        try {
            $validatedData = $request->validate([
                'comment' => 'required|string|max:500'
            ]);

            $admin = auth()->user();

            $comment = RequisitionComment::create([
                'request_id' => $requestId,
                'admin_id' => $admin->admin_id,
                'comment' => $validatedData['comment']
            ]);

            return response()->json([
                'message' => 'Comment added successfully',
                'comment' => $comment
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to add comment',
                'details' => $e->getMessage()
            ], 500);
        }
    }
    public function getComments($requestId)
    {
        try {
            $comments = RequisitionComment::with('admin')
                ->where('request_id', $requestId)
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json($comments);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to fetch comments',
                'details' => $e->getMessage()
            ], 500);
        }
    }


public function finalizeForm(Request $request, $requestId)
{
    try {
        \Log::debug('Finalize form attempt', [
            'request_id' => $requestId,
            'admin_id' => auth()->id(),
            'input_data' => $request->all()
        ]);

        // Validate
        $validatedData = $request->validate([
            'calendar_title' => 'sometimes|string|max:50|nullable',
            'calendar_description' => 'sometimes|string|max:100|nullable',
        ]);

        $adminId = auth()->id();
        if (!$adminId) {
            return response()->json(['error' => 'Admin not authenticated'], 401);
        }

        // Get form with relationships
        $form = RequisitionForm::with([
            'requestedFacilities.facility',
            'requestedEquipment.equipment',
            'requisitionFees'
        ])->findOrFail($requestId);

        // Update form
        $this->updateFinalizedForm($form, $validatedData, $adminId);

        // Send email notification
        $this->sendApprovalEmail($form);

        return response()->json([
            'message' => 'Form finalized successfully',
            'new_status' => 'Awaiting Payment',
            'approved_fee' => $form->approved_fee
        ]);

    } catch (\Illuminate\Validation\ValidationException $e) {
        \Log::error('Finalize form validation failed', ['request_id' => $requestId, 'errors' => $e->errors()]);
        return response()->json(['error' => 'Validation failed', 'details' => $e->errors()], 422);
    } catch (\Exception $e) {
        \Log::error('Failed to finalize form', ['request_id' => $requestId, 'error' => $e->getMessage()]);
        return response()->json(['error' => 'Failed to finalize form', 'details' => $e->getMessage()], 500);
    }
}

/**
 * Update form with finalized status
 */
private function updateFinalizedForm($form, $data, $adminId)
{
    $form->is_finalized = true;
    $form->finalized_at = now();
    $form->finalized_by = $adminId;
    $form->status_id = FormStatus::where('status_name', 'Awaiting Payment')->first()->status_id;

    if (!empty($data['calendar_title'])) {
        $form->calendar_title = $data['calendar_title'];
    }
    if (!empty($data['calendar_description'])) {
        $form->calendar_description = $data['calendar_description'];
    }

    $form->approved_fee = $this->calculateApprovedFee($form);
    $form->save();

    return $form;
}

/**
 * Send approval email with booking details
 */
private function sendApprovalEmail($form)
{
    try {
        $emailData = $this->buildApprovalEmailData($form);

        \Mail::send('emails.booking-approved', $emailData, function ($message) use ($form) {
            $userName = $form->first_name . ' ' . $form->last_name;
            $message->to($form->email, $userName)
                ->subject('Your Booking Request Has Been Approved – Payment Required');
        });

        \Log::debug('Approval email sent', ['request_id' => $form->request_id]);
    } catch (\Exception $e) {
        \Log::error('Failed to send approval email', [
            'request_id' => $form->request_id,
            'error' => $e->getMessage()
        ]);
    }
}

/**
 * Build email data array
 */
private function buildApprovalEmailData($form)
{
    $userName = $form->first_name . ' ' . $form->last_name;
    
    // Calculate duration
    $durationData = $this->calculateBookingDuration($form);
    $bookingDurationHours = $durationData['hours'];
    $bookingDurationText = $durationData['text'];
    
    // Format schedule
    $scheduleData = $this->formatScheduleForDisplay($form);
    
    // Calculate fees
    $baseFee = 0;
    $facilitiesBreakdown = $this->getFacilitiesBreakdown($form, $bookingDurationHours, $baseFee);
    $equipmentBreakdown = $this->getEquipmentBreakdown($form, $bookingDurationHours, $baseFee);
    
    return [
        'user_name' => $userName,
        'request_id' => $form->request_id,
        'approved_fee' => $form->approved_fee,
        'base_fee' => $baseFee,
        'late_penalty_fee' => $form->late_penalty_fee,
        'payment_deadline' => now()->addDays(5)->format('F j, Y'),
        'access_code' => $form->access_code,
        'booking_duration' => $bookingDurationHours,
        'booking_duration_text' => $bookingDurationText,
        'schedule_start' => $scheduleData['start'],
        'schedule_end' => $scheduleData['end'],
        'is_all_day' => $form->all_day,
        'requested_facilities' => $this->getSimpleFacilitiesList($form),
        'requested_equipment' => $this->getSimpleEquipmentList($form),
        'facilities_breakdown' => $facilitiesBreakdown,
        'equipment_breakdown' => $equipmentBreakdown,
        'additional_fees' => $this->getAdditionalFeesList($form) // Make sure this includes account_num
    ];
}
/**
 * Calculate booking duration based on all_day flag
 */
private function calculateBookingDuration($form)
{
    if ($form->all_day) {
        $startDate = new \DateTime($form->start_date);
        $endDate = new \DateTime($form->end_date);
        $days = $startDate->diff($endDate)->days + 1;
        return [
            'hours' => $days * 8,
            'text' => $days . ' day(s) (All Day)'
        ];
    } else {
        $startDateTime = new \DateTime($form->start_date . ' ' . $form->start_time);
        $endDateTime = new \DateTime($form->end_date . ' ' . $form->end_time);
        $duration = $startDateTime->diff($endDateTime);
        $hours = $duration->h + ($duration->days * 24);
        return [
            'hours' => $hours,
            'text' => $hours . ' hour(s)'
        ];
    }
}

/**
 * Format schedule for display
 */
private function formatScheduleForDisplay($form)
{
    if ($form->all_day) {
        return [
            'start' => date('F j, Y', strtotime($form->start_date)) . ' (All Day)',
            'end' => date('F j, Y', strtotime($form->end_date)) . ' (All Day)'
        ];
    } else {
        $startDateTime = new \DateTime($form->start_date . ' ' . $form->start_time);
        $endDateTime = new \DateTime($form->end_date . ' ' . $form->end_time);
        return [
            'start' => $startDateTime->format('F j, Y \a\t g:i A'),
            'end' => $endDateTime->format('F j, Y \a\t g:i A')
        ];
    }
}

/**
 * Get facilities breakdown with fee calculation
 */
private function getFacilitiesBreakdown($form, $bookingDurationHours, &$baseFee)
{
    return $form->requestedFacilities->map(function ($facility) use ($bookingDurationHours, &$baseFee) {
        $unitFee = $facility->facility->base_fee;
        $totalFee = $facility->facility->rate_type === 'Per Hour' 
            ? $unitFee * $bookingDurationHours 
            : $unitFee;

        if (!$facility->is_waived) {
            $baseFee += $totalFee;
        }

        return [
            'facility_name' => $facility->facility->facility_name,
            'unit_fee' => $unitFee,
            'rate_type' => $facility->facility->rate_type,
            'total_fee' => $facility->is_waived ? 0 : $totalFee,
            'is_waived' => $facility->is_waived
        ];
    })->toArray();
}

/**
 * Get equipment breakdown with fee calculation
 */
private function getEquipmentBreakdown($form, $bookingDurationHours, &$baseFee)
{
    return $form->requestedEquipment->map(function ($equipment) use ($bookingDurationHours, &$baseFee) {
        $unitFee = $equipment->equipment->base_fee;
        $quantity = $equipment->quantity;
        
        $totalFee = $equipment->equipment->rate_type === 'Per Hour'
            ? $unitFee * $bookingDurationHours * $quantity
            : $unitFee * $quantity;

        if (!$equipment->is_waived) {
            $baseFee += $totalFee;
        }

        return [
            'equipment_name' => $equipment->equipment->equipment_name,
            'unit_fee' => $unitFee,
            'quantity' => $quantity,
            'rate_type' => $equipment->equipment->rate_type,
            'total_fee' => $equipment->is_waived ? 0 : $totalFee,
            'is_waived' => $equipment->is_waived
        ];
    })->toArray();
}

/**
 * Get simple facilities list for email
 */
private function getSimpleFacilitiesList($form)
{
    return $form->requestedFacilities->map(function ($facility) {
        return [
            'facility_name' => $facility->facility->facility_name,
            'is_waived' => $facility->is_waived
        ];
    })->toArray();
}

/**
 * Get simple equipment list for email
 */
private function getSimpleEquipmentList($form)
{
    return $form->requestedEquipment->map(function ($equipment) {
        return [
            'equipment_name' => $equipment->equipment->equipment_name,
            'quantity' => $equipment->quantity,
            'is_waived' => $equipment->is_waived
        ];
    })->toArray();
}

/**
 * Get additional fees list for email
 */
private function getAdditionalFeesList($form)
{
    return $form->requisitionFees->map(function ($fee) {
        return [
            'label' => $fee->label,
            'account_num' => $fee->account_num,
            'fee_amount' => (float) $fee->fee_amount,
            'discount_amount' => (float) $fee->discount_amount,
            'discount_type' => $fee->discount_type,
            'discount_percentage' => $fee->discount_type === 'Percentage' ? $fee->discount_amount : null
        ];
    })->toArray();
}

public function createReservation(Request $request)
{
    try {
        \Log::debug('Creating admin reservation', $request->all());

        DB::beginTransaction();

        // Build validation rules dynamically
        $rules = [
            'user_type' => 'required|in:Internal,External',
            'first_name' => 'required|string|max:50',
            'last_name' => 'required|string|max:50',
            'email' => 'required|email|max:100',
            'organization_name' => 'nullable|string|max:100',
            'contact_number' => 'nullable|string|max:15',
            'purpose_id' => 'required|exists:requisition_purposes,purpose_id',
            'num_participants' => 'required|integer|min:1',
            'access_code' => 'required|string|max:10|unique:requisition_forms,access_code',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'all_day' => 'required|boolean',
            'calendar_title' => 'nullable|string|max:50',
            'calendar_description' => 'nullable|string|max:100',
            'additional_requests' => 'nullable|string|max:250',
            'facilities' => 'required|array|min:1',
            'facilities.*.facility_id' => 'required|exists:facilities,facility_id',
            'equipment' => 'array',
            'equipment.*.equipment_id' => 'required|exists:equipment,equipment_id',
            'equipment.*.quantity' => 'required|integer|min:1',
            'status_id' => 'required|exists:form_statuses,status_id',
        ];

        // Conditionally add time rules based on all_day flag
        if (!$request->all_day) {
            $rules['start_time'] = 'required|date_format:H:i';
            $rules['end_time'] = 'required|date_format:H:i|after:start_time';
        } else {
            $rules['start_time'] = 'nullable';
            $rules['end_time'] = 'nullable';
        }

        $validatedData = $request->validate($rules);

        // Check for conflicts before creating
        $conflictItems = [];

        // Check facility conflicts
        foreach ($validatedData['facilities'] as $facility) {
            $conflicts = $this->checkFacilityAvailability(
                $facility['facility_id'],
                $validatedData['start_date'],
                $validatedData['end_date'],
                $validatedData['start_time'] ?? '00:00:00',
                $validatedData['end_time'] ?? '23:59:59',
                $validatedData['all_day']
            );

            if (!empty($conflicts)) {
                $conflictItems[] = [
                    'type' => 'facility',
                    'id' => $facility['facility_id'],
                    'name' => Facility::find($facility['facility_id'])->facility_name ?? 'Unknown',
                    'conflicts' => $conflicts
                ];
            }
        }

        // Check equipment conflicts
        if (!empty($validatedData['equipment'])) {
            foreach ($validatedData['equipment'] as $equipment) {
                if ($validatedData['all_day']) {
                    // Check if equipment is already booked on these dates
                    $existingBookings = RequestedEquipment::where('equipment_id', $equipment['equipment_id'])
                        ->whereHas('requisitionForm', function($q) use ($validatedData) {
                            $q->whereIn('status_id', function($sq) {
                                $sq->select('status_id')
                                   ->from('form_statuses')
                                   ->whereIn('status_name', ['Pending Approval', 'Awaiting Payment', 'Scheduled', 'Ongoing']);
                            })
                            ->where(function($dateQ) use ($validatedData) {
                                $dateQ->where('start_date', '<=', $validatedData['end_date'])
                                      ->where('end_date', '>=', $validatedData['start_date']);
                            });
                        })
                        ->sum('quantity');
                    
                    $availableCount = EquipmentItem::where('equipment_id', $equipment['equipment_id'])
                        ->where('status_id', 1)
                        ->whereIn('condition_id', [1, 2, 3])
                        ->count();
                        
                    $availableCount -= $existingBookings;
                } else {
                    // Regular time-based check
                    $availableCount = EquipmentItem::where('equipment_id', $equipment['equipment_id'])
                        ->where('status_id', 1)
                        ->whereIn('condition_id', [1, 2, 3])
                        ->count();
                }

                if ($availableCount < $equipment['quantity']) {
                    $conflictItems[] = [
                        'type' => 'equipment',
                        'id' => $equipment['equipment_id'],
                        'name' => EquipmentItem::find($equipment['equipment_id'])->equipment_name ?? 'Unknown',
                        'message' => "Only {$availableCount} available, requested {$equipment['quantity']}"
                    ];
                }
            }
        }

        if (!empty($conflictItems)) {
            DB::rollBack();
            return response()->json([
                'error' => 'Scheduling conflicts detected',
                'conflict_items' => $conflictItems
            ], 409);
        }

        // Create the requisition form
        $requisitionForm = RequisitionForm::create([
            'user_type' => $validatedData['user_type'],
            'first_name' => $validatedData['first_name'],
            'last_name' => $validatedData['last_name'],
            'email' => $validatedData['email'],
            'organization_name' => $validatedData['organization_name'] ?? null,
            'contact_number' => $validatedData['contact_number'] ?? null,
            'purpose_id' => $validatedData['purpose_id'],
            'num_participants' => $validatedData['num_participants'],
            'num_tables' => $request->num_tables ?? 0,
            'num_chairs' => $request->num_chairs ?? 0,
            'num_microphones' => $request->num_microphones ?? 0,
            'access_code' => $validatedData['access_code'],
            'start_date' => $validatedData['start_date'],
            'end_date' => $validatedData['end_date'],
            'start_time' => $validatedData['all_day'] ? '00:00:00' : ($validatedData['start_time'] ?? '00:00:00'),
            'end_time' => $validatedData['all_day'] ? '23:59:59' : ($validatedData['end_time'] ?? '23:59:59'),
            'all_day' => $validatedData['all_day'],
            'calendar_title' => $validatedData['calendar_title'] ?? 'Admin Reservation',
            'calendar_description' => $validatedData['calendar_description'] ?? null,
            'additional_requests' => $validatedData['additional_requests'] ?? null,
            'status_id' => $validatedData['status_id'],
            'is_finalized' => true, // Admin reservations are automatically finalized
            'finalized_at' => now(),
            'finalized_by' => auth()->id(),
        ]);

        // Add facilities
        foreach ($validatedData['facilities'] as $facility) {
            RequestedFacility::create([
                'request_id' => $requisitionForm->request_id,
                'facility_id' => $facility['facility_id'],
                'is_waived' => false,
            ]);
        }

        // Add equipment
        foreach ($validatedData['equipment'] ?? [] as $equipment) {
            RequestedEquipment::create([
                'request_id' => $requisitionForm->request_id,
                'equipment_id' => $equipment['equipment_id'],
                'quantity' => $equipment['quantity'],
                'is_waived' => false,
            ]);
        }

        // Create approval record for admin creation
        RequisitionApproval::create([
            'request_id' => $requisitionForm->request_id,
            'approved_by' => auth()->id(),
            'remarks' => 'Admin-created reservation',
            'date_updated' => now(),
        ]);

        // Create comment
        RequisitionComment::create([
            'request_id' => $requisitionForm->request_id,
            'admin_id' => auth()->id(),
            'comment' => 'Admin created this reservation manually',
        ]);

        DB::commit();

        return response()->json([
            'message' => 'Reservation created successfully',
            'request_id' => $requisitionForm->request_id,
            'access_code' => $requisitionForm->access_code,
            'all_day' => $requisitionForm->all_day,
        ], 201);

    } catch (\Illuminate\Validation\ValidationException $e) {
        DB::rollBack();
        \Log::error('Validation failed for admin reservation', [
            'errors' => $e->errors(),
        ]);
        return response()->json([
            'error' => 'Validation failed',
            'details' => $e->errors(),
        ], 422);
    } catch (\Exception $e) {
        DB::rollBack();
        \Log::error('Failed to create admin reservation', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);
        return response()->json([
            'error' => 'Failed to create reservation',
            'details' => $e->getMessage(),
        ], 500);
    }
}


    // Add this method to the AdminApprovalController class
    public function cancelRequestPublic($requestId)
    {
        try {
            \Log::info('Public cancellation request received', ['request_id' => $requestId]);

            $form = RequisitionForm::findOrFail($requestId);

            // Check if the request can be cancelled (only certain statuses)
            $cancellableStatuses = ['Pending Approval', 'Awaiting Payment', 'Scheduled'];
            if (!in_array($form->formStatus->status_name, $cancellableStatuses)) {
                return response()->json([
                    'error' => 'Cannot cancel request',
                    'details' => 'This request cannot be cancelled in its current status'
                ], 422);
            }

            DB::beginTransaction();

            // Update the requisition form
            $form->status_id = FormStatus::where('status_name', 'Cancelled')->first()->status_id;
            $form->is_closed = true;
            $form->closed_by = null; // No admin since it's public cancellation
            $form->closed_at = now();
            $form->updated_at = now();
            $form->save();

            // Create completed transaction record
            CompletedTransaction::create([
                'request_id' => $requestId,
                'official_receipt_no' => null,
                'official_receipt_url' => null,
                'official_receipt_public_id' => null
            ]);

            DB::commit();

            \Log::info('Request cancelled successfully via public route', ['request_id' => $requestId]);

            return response()->json([
                'message' => 'Request cancelled successfully',
                'request_id' => $requestId
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Failed to cancel request via public route', [
                'request_id' => $requestId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'Failed to cancel request',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    // Rename the existing cancel method for admin use
    public function cancelForm(Request $request, $requestId)
    {
        try {
            $adminId = auth()->id();

            if (!$adminId) {
                return response()->json(['error' => 'Admin not authenticated'], 401);
            }

            DB::beginTransaction();

            $form = RequisitionForm::findOrFail($requestId);

            // Update the requisition form
            $form->status_id = FormStatus::where('status_name', 'Cancelled')->first()->status_id;
            $form->is_closed = true;
            $form->closed_by = $adminId;
            $form->closed_at = now();
            $form->updated_at = now();
            $form->save();

            // Create completed transaction record
            CompletedTransaction::create([
                'request_id' => $requestId,
                'official_receipt_no' => null,
                'official_receipt_url' => null,
                'official_receipt_public_id' => null
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Form cancelled successfully',
                'request_id' => $requestId
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Failed to cancel form as admin', [
                'request_id' => $requestId,
                'admin_id' => auth()->id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'Failed to cancel form',
                'details' => $e->getMessage()
            ], 500);
        }
    }


    public function closeForm($requestId)
    {
        try {
            $admin = auth()->user();

            $form = RequisitionForm::findOrFail($requestId);

            $form->is_closed = true;
            $form->closed_at = now();
            $form->closed_by = $admin->admin_id;
            $form->status_id = FormStatus::where('status_name', 'Completed')->first()->status_id;
            $form->save();

            // Create completed transaction record
            CompletedTransaction::create([
                'request_id' => $requestId,
                'official_receipt_no' => null,
                'official_receipt_url' => null,
                'official_receipt_public_id' => null
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Form closed successfully',
                'form' => $form
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to close form',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    public function markReturned(Request $request, $requestId)
    {
        try {
            $validatedData = $request->validate([
                'is_late' => 'required|boolean',
                'late_penalty_fee' => 'required_if:is_late,true|numeric|min:0'
            ]);

            $form = RequisitionForm::findOrFail($requestId);

            $form->returned_at = now();
            $form->is_late = $validatedData['is_late'];

            if ($validatedData['is_late']) {
                $form->late_penalty_fee = $validatedData['late_penalty_fee'];
            }

            // Update status based on return time
            if ($validatedData['is_late']) {
                $form->status_id = FormStatus::where('status_name', 'Late Return')->first()->status_id;
            } else {
                $form->status_id = FormStatus::where('status_name', 'Returned')->first()->status_id;
            }

            // Recalculate approved fee
            $form->load(['requestedFacilities', 'requestedEquipment', 'requisitionFees']);
            $approvedFee = $this->calculateApprovedFee($form);
            $form->approved_fee = $approvedFee;
            $form->save();

            return response()->json([
                'message' => 'Equipment marked as returned',
                'is_late' => $form->is_late,
                'late_penalty_fee' => $form->late_penalty_fee,
                'updated_approved_fee' => $approvedFee
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to mark equipment as returned',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    public function updateStatus(Request $request, $requestId)
    {
        try {
            \Log::debug('Update status request received', [
                'request_id' => $requestId,
                'new_status' => $request->status_name,
                'admin_id' => auth()->id()
            ]);

            $validatedData = $request->validate([
                'status_name' => 'required|string|in:Scheduled,Ongoing,Late,Returned,Late Return,Completed',
                'late_penalty_fee' => 'sometimes|nullable|numeric|min:0'
            ]);

            $adminId = auth()->id();
            if (!$adminId) {
                \Log::warning('Admin not authenticated during status update');
                return response()->json(['error' => 'Admin not authenticated'], 401);
            }

            $form = RequisitionForm::with('formStatus')->findOrFail($requestId);

            // VALIDATION: Can only mark as Late if current status is Ongoing
            if ($validatedData['status_name'] === 'Late') {
                $currentStatus = $form->formStatus->status_name;
                if ($currentStatus !== 'Ongoing') {
                    return response()->json([
                        'error' => 'Cannot mark as Late',
                        'details' => 'Can only mark forms as Late when they are in Ongoing status. Current status: ' . $currentStatus
                    ], 422);
                }
            }

            // Get the status ID for the selected status name
            $status = FormStatus::where('status_name', $validatedData['status_name'])->first();
            if (!$status) {
                \Log::error('Status not found', ['status_name' => $validatedData['status_name']]);
                return response()->json(['error' => 'Invalid status'], 422);
            }

            // Handle Late status specifically
            if ($validatedData['status_name'] === 'Late') {
                $form->is_late = true;

                // Set late penalty fee if provided
                if (isset($validatedData['late_penalty_fee']) && $validatedData['late_penalty_fee'] > 0) {
                    $form->late_penalty_fee = $validatedData['late_penalty_fee'];
                }
            }
            // Handle unmarking late (when changing from Late to another status)
            elseif ($form->formStatus->status_name === 'Late' && $validatedData['status_name'] !== 'Late') {
                $form->is_late = false;
                $form->late_penalty_fee = 0; // Reset penalty fee
            }

            // Update the form status
            $form->status_id = $status->status_id;

            // Additional logic based on status
            if (in_array($validatedData['status_name'], ['Returned', 'Late Return', 'Completed', 'Rejected', 'Cancelled'])) {
                $form->is_closed = true;
                $form->closed_at = now();
                $form->closed_by = $adminId;

                // Create completed transaction record for finalized statuses
                if (!CompletedTransaction::where('request_id', $requestId)->exists()) {
                    CompletedTransaction::create([
                        'request_id' => $requestId,
                        'official_receipt_no' => $form->official_receipt_no,
                        'official_receipt_url' => $form->official_receipt_url,
                        'official_receipt_public_id' => $form->official_receipt_public_id
                    ]);
                }
            }

            $form->save();

            // Send email notification if status changed to Late
            if ($validatedData['status_name'] === 'Late') {
                $this->sendLatePenaltyEmail($form);
            }

            // Recalculate approved fee after status change
            $form->load(['requestedFacilities', 'requestedEquipment', 'requisitionFees']);
            $approvedFee = $this->calculateApprovedFee($form);
            $form->approved_fee = $approvedFee;
            $form->save();

            \Log::info('Status updated successfully', [
                'request_id' => $requestId,
                'old_status' => $form->getOriginal('status_id'),
                'new_status' => $form->status_id,
                'is_late' => $form->is_late,
                'late_penalty_fee' => $form->late_penalty_fee,
                'admin_id' => $adminId
            ]);

            return response()->json([
                'message' => 'Status updated successfully',
                'new_status' => $validatedData['status_name'],
                'status_id' => $status->status_id,
                'color_code' => $status->color_code,
                'is_late' => $form->is_late,
                'late_penalty_fee' => $form->late_penalty_fee,
                'updated_approved_fee' => $approvedFee
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('Status update validation failed', [
                'request_id' => $requestId,
                'errors' => $e->errors(),
                'input_data' => $request->all()
            ]);

            return response()->json([
                'error' => 'Validation failed',
                'details' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Failed to update status', [
                'request_id' => $requestId,
                'admin_id' => auth()->id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'Failed to update status',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    private function sendLatePenaltyEmail($form)
    {
        try {
            $userName = $form->first_name . ' ' . $form->last_name;
            $userEmail = $form->email;

            $emailData = [
                'first_name' => $form->first_name,
                'last_name' => $form->last_name,
                'penalty_fee' => $form->late_penalty_fee
            ];

            \Log::debug('Sending late penalty email', [
                'recipient' => $userEmail,
                'request_id' => $form->request_id,
                'penalty_fee' => $form->late_penalty_fee
            ]);

            \Mail::send('emails.booking-late', $emailData, function ($message) use ($userEmail, $userName) {
                $message->to($userEmail, $userName)
                    ->subject('Late Penalty Notice - Central Philippine University');
            });

            \Log::debug('Late penalty email sent successfully', [
                'recipient' => $userEmail,
                'request_id' => $form->request_id
            ]);
        } catch (\Exception $emailError) {
            \Log::error('Failed to send late penalty email', [
                'request_id' => $form->request_id,
                'error' => $emailError->getMessage(),
                'recipient' => $form->email,
                'trace' => $emailError->getTraceAsString()
            ]);
        }
    }
    // Calculate & Finalize fees //

    // Add better error logging to the calculateBaseFees method
private function calculateBaseFees($form)
{
    try {
        \Log::debug('Calculating base fees', [
            'request_id' => $form->request_id,
            'facilities_count' => $form->requestedFacilities->count(),
            'equipment_count' => $form->requestedEquipment->count(),
            'all_day' => $form->all_day ?? false
        ]);

        // Calculate duration in hours based on all_day flag
        $durationInHours = $this->calculateDurationHours($form);

        // Calculate facility fees with rate_type logic
        $facilityFees = $form->requestedFacilities->sum(function ($facility) use ($form, $durationInHours) {
            if ($facility->is_waived) {
                return 0;
            }

            $fee = $facility->facility->base_fee;

            // Check if rate_type is "Per Hour" and calculate based on duration
            if ($facility->facility->rate_type === 'Per Hour') {
                try {
                    $total = $fee * $durationInHours;

                    \Log::debug('Per Hour facility calculation', [
                        'facility_id' => $facility->facility_id,
                        'base_fee' => $fee,
                        'duration_hours' => $durationInHours,
                        'all_day' => $form->all_day ?? false,
                        'total' => $total
                    ]);

                    return $total;
                } catch (\Exception $e) {
                    \Log::error('Error calculating per hour facility fee', [
                        'facility_id' => $facility->facility_id,
                        'error' => $e->getMessage()
                    ]);
                    return $fee; // Fallback to base fee
                }
            }

            // For "Per Event" or any other rate type, return the base fee
            return $fee;
        });

        // Calculate equipment fees with rate_type logic
        $equipmentFees = $form->requestedEquipment->sum(function ($equipment) use ($form, $durationInHours) {
            if ($equipment->is_waived) {
                return 0;
            }

            $fee = $equipment->equipment->base_fee;

            // Check if rate_type is "Per Hour" and calculate based on duration
            if ($equipment->equipment->rate_type === 'Per Hour') {
                try {
                    $total = ($fee * $durationInHours) * $equipment->quantity;

                    \Log::debug('Per Hour equipment calculation', [
                        'equipment_id' => $equipment->equipment_id,
                        'base_fee' => $fee,
                        'quantity' => $equipment->quantity,
                        'duration_hours' => $durationInHours,
                        'all_day' => $form->all_day ?? false,
                        'total' => $total
                    ]);

                    return $total;
                } catch (\Exception $e) {
                    \Log::error('Error calculating per hour equipment fee', [
                        'equipment_id' => $equipment->equipment_id,
                        'error' => $e->getMessage()
                    ]);
                    return $fee * $equipment->quantity; // Fallback to base fee
                }
            }

            // For "Per Event" or any other rate type, return the base fee multiplied by quantity
            return $fee * $equipment->quantity;
        });

        $total = $facilityFees + $equipmentFees;

        \Log::debug('Base fees calculation completed', [
            'request_id' => $form->request_id,
            'facility_fees' => $facilityFees,
            'equipment_fees' => $equipmentFees,
            'total_base_fees' => $total,
            'duration_hours' => $durationInHours,
            'all_day' => $form->all_day ?? false
        ]);

        return $total;
    } catch (\Exception $e) {
        \Log::error('Error in calculateBaseFees', [
            'request_id' => $form->request_id ?? 'unknown',
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        return 0; // Return 0 on error to prevent calculation issues
    }
}

/**
 * Helper method to calculate duration in hours based on all_day flag
 */
private function calculateDurationHours($form)
{
    if ($form->all_day) {
        // For all-day events: count days × 8 hours per day
        $startDate = Carbon::parse($form->start_date);
        $endDate = Carbon::parse($form->end_date);
        $days = $startDate->diffInDays($endDate) + 1; // +1 to include both start and end days
        $hours = $days * 8; // Standard 8-hour day
        
        \Log::debug('All-day duration calculation', [
            'start_date' => $form->start_date,
            'end_date' => $form->end_date,
            'days' => $days,
            'hours' => $hours
        ]);
        
        return $hours;
    } else {
        // For time-based events: calculate exact hours
        try {
            $startDateTime = Carbon::parse($form->start_date . ' ' . $form->start_time);
            $endDateTime = Carbon::parse($form->end_date . ' ' . $form->end_time);
            $hours = $startDateTime->diffInHours($endDateTime);
            
            // Ensure minimum 1 hour
            $hours = max(1, $hours);
            
            \Log::debug('Time-based duration calculation', [
                'start' => $form->start_date . ' ' . $form->start_time,
                'end' => $form->end_date . ' ' . $form->end_time,
                'hours' => $hours
            ]);
            
            return $hours;
        } catch (\Exception $e) {
            \Log::error('Error calculating time-based duration', [
                'error' => $e->getMessage(),
                'start_date' => $form->start_date,
                'start_time' => $form->start_time,
                'end_date' => $form->end_date,
                'end_time' => $form->end_time
            ]);
            return 1; // Fallback to 1 hour
        }
    }
}

private function calculateTentativeFee($requestId)
{
    $form = RequisitionForm::with(['requestedFacilities.facility', 'requestedEquipment.equipment'])
        ->findOrFail($requestId);

    $waivers = session()->get('pending_waivers', [])[$requestId] ?? [];
    
    // Calculate duration in hours based on all_day flag
    $durationInHours = $this->calculateDurationHours($form);

    // Calculate facility fees with rate type and all-day support
    $facilityFees = $form->requestedFacilities->reduce(function ($carry, $facility) use ($waivers, $form, $durationInHours) {
        $isWaived = $waivers['facility'][$facility->requested_facility_id] ?? $facility->is_waived;
        
        if ($isWaived) {
            return $carry + 0;
        }

        $fee = $facility->facility->base_fee;

        // Apply hourly rate if applicable
        if ($facility->facility->rate_type === 'Per Hour') {
            return $carry + ($fee * $durationInHours);
        }

        // Per Event rate
        return $carry + $fee;
    }, 0);

    // Calculate equipment fees with rate type, quantity, and all-day support
    $equipmentFees = $form->requestedEquipment->reduce(function ($carry, $equipment) use ($waivers, $form, $durationInHours) {
        $isWaived = $waivers['equipment'][$equipment->requested_equipment_id] ?? $equipment->is_waived;
        
        if ($isWaived) {
            return $carry + 0;
        }

        $fee = $equipment->equipment->base_fee;
        $quantity = $equipment->quantity;

        // Apply hourly rate if applicable
        if ($equipment->equipment->rate_type === 'Per Hour') {
            return $carry + (($fee * $durationInHours) * $quantity);
        }

        // Per Event rate
        return $carry + ($fee * $quantity);
    }, 0);

    // Add late penalty if applicable
    $latePenalty = $form->is_late ? $form->late_penalty_fee : 0;

    $total = $facilityFees + $equipmentFees + $latePenalty;

    \Log::debug('Tentative fee calculated', [
        'request_id' => $requestId,
        'facility_fees' => $facilityFees,
        'equipment_fees' => $equipmentFees,
        'late_penalty' => $latePenalty,
        'duration_hours' => $durationInHours,
        'all_day' => $form->all_day ?? false,
        'total' => $total
    ]);

    return $total;
}

    private function calculateApprovedFee($form)
    {
        $baseFees = $this->calculateBaseFees($form);
        $additionalFees = $this->calculateAdditionalFees($form);
        $discounts = $this->calculateTotalDiscounts($form, $baseFees + $additionalFees);

        $approvedFee = $baseFees + $additionalFees - $discounts;

        if ($form->is_late) {
            $approvedFee += $form->late_penalty_fee;
        }

        // Ensure fee doesn't go negative
        return max(0, $approvedFee);
    }

    private function calculateAdditionalFees($form)
    {
        // Sum only positive fee amounts (additional fees)
        return $form->requisitionFees->sum(function ($fee) {
            return max(0, (float) $fee->fee_amount);
        });
    }

    private function calculateTotalDiscounts($form, $subtotal)
    {
        $totalDiscount = 0;

        foreach ($form->requisitionFees as $fee) {
            $discountAmount = (float) $fee->discount_amount;

            if ($discountAmount > 0) {
                if ($fee->discount_type === 'Percentage') {
                    // Calculate percentage discount based on subtotal
                    $percentageDiscount = ($discountAmount / 100) * $subtotal;
                    $totalDiscount += $percentageDiscount;
                } else {
                    // Fixed discount
                    $totalDiscount += $discountAmount;
                }
            }
        }

        return $totalDiscount;
    }

    public function markAsScheduled(Request $request, $requestId)
    {
        try {
            \Log::debug('Mark as scheduled request received', [
                'request_id' => $requestId,
                'admin_id' => auth()->id(),
                'official_receipt_num' => $request->official_receipt_num
            ]);

            $validatedData = $request->validate([
                'official_receipt_num' => 'required|string|max:50|unique:requisition_forms,official_receipt_num',
                'calendar_title' => 'sometimes|string|max:50|nullable',
                'calendar_description' => 'sometimes|string|max:100|nullable',
            ]);

            $adminId = auth()->id();
            if (!$adminId) {
                return response()->json(['error' => 'Admin not authenticated'], 401);
            }

            $form = RequisitionForm::with([
                'requestedFacilities.facility',
                'requestedEquipment.equipment',
                'requisitionFees',
                'purpose',
                'formStatus'
            ])->findOrFail($requestId);

            // Update form with official receipt number and status
            $scheduledStatus = FormStatus::where('status_name', 'Scheduled')->first();
            if (!$scheduledStatus) {
                throw new \Exception('Scheduled status not found');
            }

            $form->official_receipt_num = $validatedData['official_receipt_num'];
            $form->status_id = $scheduledStatus->status_id;

            if (!empty($validatedData['calendar_title'])) {
                $form->calendar_title = $validatedData['calendar_title'];
            }

            if (!empty($validatedData['calendar_description'])) {
                $form->calendar_description = $validatedData['calendar_description'];
            }

            $form->save();

            // Send confirmation email
            $this->sendScheduledConfirmationEmail($form);

            \Log::info('Form marked as scheduled successfully', [
                'request_id' => $requestId,
                'official_receipt_num' => $form->official_receipt_num,
                'admin_id' => $adminId
            ]);

            return response()->json([
                'message' => 'Form marked as scheduled successfully',
                'official_receipt_num' => $form->official_receipt_num,
                'new_status' => 'Scheduled'
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('Mark as scheduled validation failed', [
                'request_id' => $requestId,
                'errors' => $e->errors()
            ]);

            return response()->json([
                'error' => 'Validation failed',
                'details' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Failed to mark form as scheduled', [
                'request_id' => $requestId,
                'admin_id' => auth()->id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'Failed to mark form as scheduled',
                'details' => $e->getMessage()
            ], 500);
        }
    }

private function sendScheduledConfirmationEmail($form)
{
    try {
        $userName = $form->first_name . ' ' . $form->last_name;
        $userEmail = $form->email;

        // Format schedule based on all_day flag
        if ($form->all_day) {
            $formattedStartDate = Carbon::parse($form->start_date)->format('F j, Y');
            $formattedEndDate = Carbon::parse($form->end_date)->format('F j, Y');
            $formattedStartTime = 'All Day';
            $formattedEndTime = 'All Day';
            $formattedSchedule = $form->start_date === $form->end_date
                ? $formattedStartDate . ' (All Day)'
                : $formattedStartDate . ' - ' . $formattedEndDate . ' (All Day)';
        } else {
            $startDateTime = Carbon::parse($form->start_date . ' ' . $form->start_time);
            $endDateTime = Carbon::parse($form->end_date . ' ' . $form->end_time);
            $formattedStartDate = $startDateTime->format('F j, Y');
            $formattedEndDate = $endDateTime->format('F j, Y');
            $formattedStartTime = $startDateTime->format('g:i A');
            $formattedEndTime = $endDateTime->format('g:i A');
            $formattedSchedule = $form->start_date === $form->end_date
                ? $formattedStartDate . ' ' . $formattedStartTime . ' - ' . $formattedEndTime
                : $formattedStartDate . ' ' . $formattedStartTime . ' - ' . $formattedEndDate . ' ' . $formattedEndTime;
        }

        $emailData = [
            'user_name' => $userName,
            'request_id' => $form->request_id,
            'official_receipt_num' => $form->official_receipt_num,
            'purpose' => $form->purpose->purpose_name ?? 'N/A',
            // Raw values
            'start_date' => $form->start_date,
            'start_time' => $form->start_time,
            'end_date' => $form->end_date,
            'end_time' => $form->end_time,
            'all_day' => $form->all_day,
            // Formatted values for display
            'formatted_start_date' => $formattedStartDate,
            'formatted_end_date' => $formattedEndDate,
            'formatted_start_time' => $formattedStartTime,
            'formatted_end_time' => $formattedEndTime,
            'formatted_schedule' => $formattedSchedule,
            'approved_fee' => number_format($form->approved_fee, 2),
            'is_multi_day' => $form->start_date !== $form->end_date
        ];

        \Log::debug('Scheduled confirmation email data', [
            'request_id' => $form->request_id,
            'all_day' => $form->all_day,
            'formatted_schedule' => $formattedSchedule
        ]);

        // Use view() instead of loading the file directly
        \Mail::send('emails.booking-scheduled', $emailData, function ($message) use ($userEmail, $userName) {
            $message->to($userEmail, $userName)
                ->subject('Your Booking Has Been Scheduled – Official Receipt Generated');
        });

        \Log::debug('Scheduled confirmation email sent successfully', [
            'recipient' => $userEmail,
            'request_id' => $form->request_id,
            'official_receipt_num' => $form->official_receipt_num
        ]);
    } catch (\Exception $emailError) {
        \Log::error('Failed to send scheduled confirmation email', [
            'request_id' => $form->request_id,
            'error' => $emailError->getMessage(),
            'recipient' => $form->email
        ]);
    }
}
  

public function generateOfficialReceipt($requestId)
{
    try {
        \Log::debug('=== GENERATE OFFICIAL RECEIPT CALLED ===', [
            'request_id' => $requestId,
            'full_url' => request()->fullUrl(),
            'method' => request()->method()
        ]);

        $form = RequisitionForm::with([
            'requestedFacilities.facility',
            'requestedEquipment.equipment',
            'purpose',
            'requisitionFees',
            'formStatus',
            'requisitionApprovals.approvedBy'
        ])->findOrFail($requestId);

        // Check if official receipt number exists
        if (empty($form->official_receipt_num)) {
            abort(404, 'Official receipt not generated yet');
        }

        // Calculate total fee
        $totalFee = $form->approved_fee;

        // Format schedule based on all_day flag
        if ($form->all_day) {
            $startDateFormatted = Carbon::parse($form->start_date)->format('F j, Y');
            $endDateFormatted = Carbon::parse($form->end_date)->format('F j, Y');
            
            if ($form->start_date === $form->end_date) {
                $scheduleString = $startDateFormatted . ' (All Day)';
                $startSchedule = $startDateFormatted . ' (All Day)';
                $endSchedule = $endDateFormatted . ' (All Day)';
            } else {
                $scheduleString = $startDateFormatted . ' — ' . $endDateFormatted . ' (All Day)';
                $startSchedule = $startDateFormatted . ' (All Day)';
                $endSchedule = $endDateFormatted . ' (All Day)';
            }
        } else {
            $startDateTime = Carbon::parse($form->start_date . ' ' . $form->start_time);
            $endDateTime = Carbon::parse($form->end_date . ' ' . $form->end_time);
            
            $startDateFormatted = $startDateTime->format('F j, Y');
            $endDateFormatted = $endDateTime->format('F j, Y');
            $startTimeFormatted = $startDateTime->format('g:i A');
            $endTimeFormatted = $endDateTime->format('g:i A');
            
            if ($form->start_date === $form->end_date) {
                $scheduleString = $startDateFormatted . ' — ' . $startTimeFormatted . ' to ' . $endTimeFormatted;
                $startSchedule = $startDateFormatted . ' — ' . $startTimeFormatted;
                $endSchedule = $endDateFormatted . ' — ' . $endTimeFormatted;
            } else {
                $scheduleString = $startDateFormatted . ' ' . $startTimeFormatted . ' — ' . 
                                $endDateFormatted . ' ' . $endTimeFormatted;
                $startSchedule = $startDateFormatted . ' — ' . $startTimeFormatted;
                $endSchedule = $endDateFormatted . ' — ' . $endTimeFormatted;
            }
        }

        // Get all admins who approved this request with their approval dates
        $approvingAdmins = $form->requisitionApprovals
            ->whereNotNull('approved_by')
            ->map(function ($approval) {
                return [
                    'admin' => $approval->approvedBy,
                    'date_approved' => $approval->date_updated ? Carbon::parse($approval->date_updated)->format('M j, Y') : 'N/A'
                ];
            })
            ->filter(function ($item) {
                return !is_null($item['admin']);
            })
            ->unique(function ($item) {
                return $item['admin']->admin_id;
            });

        // Prepare receipt data
        $receiptData = [
            'official_receipt_num' => $form->official_receipt_num,
            'user_name' => $form->first_name . ' ' . $form->last_name,
            'user_email' => $form->email,
            'organization_name' => $form->organization_name,
            'contact_number' => $form->contact_number,
            'request_id' => $form->request_id,
            'facility_name' => $form->requestedFacilities->first()->facility->facility_name ?? 'N/A',
            'purpose' => $form->purpose->purpose_name,
            'num_participants' => $form->num_participants,
            'total_fee' => $totalFee,
            'issued_date' => $form->updated_at->format('F j, Y'),
            // Raw values
            'start_date' => $form->start_date,
            'end_date' => $form->end_date,
            'start_time' => $form->start_time,
            'end_time' => $form->end_time,
            'all_day' => $form->all_day,
            // Formatted values
            'formatted_start_date' => $startDateFormatted ?? null,
            'formatted_end_date' => $endDateFormatted ?? null,
            'formatted_start_time' => $form->all_day ? 'All Day' : ($startTimeFormatted ?? null),
            'formatted_end_time' => $form->all_day ? 'All Day' : ($endTimeFormatted ?? null),
            'schedule' => $scheduleString,
            'start_schedule' => $startSchedule,
            'end_schedule' => $endSchedule,
            'fee_breakdown' => $this->getFeeBreakdown($form),
            'approving_admins' => $approvingAdmins->map(function ($item) {
                return [
                    'name' => $item['admin']->first_name . ' ' . $item['admin']->last_name,
                    'title' => $item['admin']->title ?? 'Administrator',
                    'signature_url' => $item['admin']->signature_url,
                    'date_approved' => $item['date_approved']
                ];
            })->toArray(),
            'is_multi_day' => $form->start_date !== $form->end_date
        ];

        \Log::debug('Official receipt data prepared', [
            'request_id' => $requestId,
            'all_day' => $form->all_day,
            'schedule' => $scheduleString,
            'approving_admins_count' => count($receiptData['approving_admins'])
        ]);

        return view('public.official-receipt', compact('receiptData'));
    } catch (\Exception $e) {
        \Log::error('Failed to generate official receipt', [
            'request_id' => $requestId,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);

        abort(404, 'Receipt not found');
    }
}

    private function getFeeBreakdown($form)
    {
        $breakdown = [];

        // Add facility fees
        foreach ($form->requestedFacilities as $facility) {
            if (!$facility->is_waived) {
                $breakdown[] = [
                    'description' => $facility->facility->facility_name . ' Rental',
                    'amount' => $facility->facility->base_fee
                ];
            }
        }

        // Add equipment fees
        foreach ($form->requestedEquipment as $equipment) {
            if (!$equipment->is_waived) {
                $breakdown[] = [
                    'description' => $equipment->equipment->equipment_name . ' Rental' .
                        ($equipment->quantity > 1 ? ' (×' . $equipment->quantity . ')' : ''),
                    'amount' => $equipment->equipment->base_fee * $equipment->quantity
                ];
            }
        }

        // Add additional fees
        foreach ($form->requisitionFees as $fee) {
            if ($fee->fee_amount > 0) {
                $breakdown[] = [
                    'description' => $fee->label,
                    'amount' => $fee->fee_amount
                ];
            }
        }

        // Add late penalty if applicable
        if ($form->is_late && $form->late_penalty_fee > 0) {
            $breakdown[] = [
                'description' => 'Late Penalty Fee',
                'amount' => $form->late_penalty_fee
            ];
        }

        return $breakdown;
    }

    public function updateCalendarInfo(Request $request, $requestId)
    {
        try {
            \Log::debug('Updating calendar info', [
                'request_id' => $requestId,
                'admin_id' => auth()->id(),
                'input_data' => $request->all()
            ]);

            $validatedData = $request->validate([
                'calendar_title' => 'sometimes|string|max:50|nullable',
                'calendar_description' => 'sometimes|string|max:100|nullable',
            ]);

            $adminId = auth()->id();
            if (!$adminId) {
                return response()->json(['error' => 'Admin not authenticated'], 401);
            }

            $form = RequisitionForm::findOrFail($requestId);

            // Update only the provided fields
            if (array_key_exists('calendar_title', $validatedData)) {
                $form->calendar_title = $validatedData['calendar_title'];
            }

            if (array_key_exists('calendar_description', $validatedData)) {
                $form->calendar_description = $validatedData['calendar_description'];
            }

            $form->save();

            \Log::info('Calendar info updated successfully', [
                'request_id' => $requestId,
                'calendar_title' => $form->calendar_title,
                'calendar_description' => $form->calendar_description,
                'admin_id' => $adminId
            ]);

            return response()->json([
                'message' => 'Calendar information updated successfully',
                'calendar_title' => $form->calendar_title,
                'calendar_description' => $form->calendar_description
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('Calendar info update validation failed', [
                'request_id' => $requestId,
                'errors' => $e->errors()
            ]);

            return response()->json([
                'error' => 'Validation failed',
                'details' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Failed to update calendar info', [
                'request_id' => $requestId,
                'admin_id' => auth()->id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'Failed to update calendar information',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    // Completed Transactions // 


public function completedRequests()
{
    /* Documentation:

        - this method gets all completed requisition forms based on these form_statuses (PK: status_id, Model: FormStatus): 'Returned' (5), 'Late Return' (6), 'Completed' (7), 'Rejected' (9), and 'Cancelled' (10). Use the status_name to pluck the status_id.

        - This method should return this json response as in the pendingRequests() method, but with the different status_id logic condition 
    */

    // Get status IDs for completed requests
    $includedStatuses = FormStatus::whereIn('status_name', [
        'Returned',
        'Late Return',
        'Completed',
        'Rejected',
        'Cancelled'
    ])->pluck('status_id');

    // Get completed forms with relationships
    $forms = RequisitionForm::whereIn('status_id', $includedStatuses)
        ->with([
            'formStatus',
            'requestedFacilities.facility',
            'requestedEquipment.equipment',
            'requisitionApprovals',
            'purpose',
            'finalizedBy',
            'closedBy'
        ])
        ->get()
        ->map(function ($form) {
            // Calculate tentative fee from facilities and equipment - FIXED: use reduce() instead of sum()
            $facilityFees = $form->requestedFacilities->reduce(function ($carry, $facility) {
                if ($facility->is_waived) {
                    return $carry + 0;
                }
                return $carry + $facility->facility->base_fee;
            }, 0);

            $equipmentFees = $form->requestedEquipment->reduce(function ($carry, $equipment) {
                if ($equipment->is_waived) {
                    return $carry + 0;
                }
                return $carry + ($equipment->equipment->base_fee * $equipment->quantity);
            }, 0);

            $totalTentativeFee = $facilityFees + $equipmentFees;
            if ($form->is_late) {
                $totalTentativeFee += $form->late_penalty_fee;
            }

            // Format schedule display based on all_day flag
            $formattedStartTime = $form->all_day ? 'All Day' : $form->start_time;
            $formattedEndTime = $form->all_day ? 'All Day' : $form->end_time;
            
            $formattedStartDateTime = $form->all_day 
                ? date('M j, Y', strtotime($form->start_date)) . ' (All Day)'
                : date('M j, Y', strtotime($form->start_date)) . ' ' . date('g:i A', strtotime($form->start_time));
            
            $formattedEndDateTime = $form->all_day
                ? date('M j, Y', strtotime($form->end_date)) . ' (All Day)'
                : date('M j, Y', strtotime($form->end_date)) . ' ' . date('g:i A', strtotime($form->end_time));

            // Return the same structure as pendingRequests()
            return [
                'request_id' => $form->request_id,
                'user_details' => [
                    'user_type' => $form->user_type,
                    'first_name' => $form->first_name,
                    'last_name' => $form->last_name,
                    'email' => $form->email,
                    'school_id' => $form->school_id,
                    'organization_name' => $form->organization_name,
                    'contact_number' => $form->contact_number
                ],
                'form_details' => [
                    'num_participants' => $form->num_participants,
                    'num_tables' => $form->num_tables,
                    'num_chairs' => $form->num_chairs,
                    'purpose' => $form->purpose->purpose_name,
                    'additional_requests' => $form->additional_requests,
                    'status' => [
                        'id' => $form->formStatus->status_id,
                        'name' => $form->formStatus->status_name,
                        'color' => $form->formStatus->color_code ?? $form->formStatus->color
                    ],
                    'calendar_info' => [
                        'title' => $form->calendar_title,
                        'description' => $form->calendar_description
                    ],
                    'official_receipt_num' => $form->official_receipt_num
                ],
                'schedule' => [
                    'start_date' => $form->start_date,
                    'end_date' => $form->end_date,
                    'start_time' => $form->start_time,
                    'end_time' => $form->end_time,
                    'all_day' => $form->all_day,
                    'formatted_start_time' => $formattedStartTime,
                    'formatted_end_time' => $formattedEndTime,
                    'formatted_start_datetime' => $formattedStartDateTime,
                    'formatted_end_datetime' => $formattedEndDateTime
                ],
                'requested_items' => [
                    'facilities' => $form->requestedFacilities->map(function ($facility) {
                        return [
                            'requested_facility_id' => $facility->requested_facility_id,
                            'facility_id' => $facility->facility_id,
                            'name' => $facility->facility->facility_name,
                            'fee' => $facility->facility->base_fee,
                            'rate_type' => $facility->facility->rate_type,
                            'is_waived' => $facility->is_waived
                        ];
                    })->values(),
                    'equipment' => $form->requestedEquipment->map(function ($equipment) {
                        return [
                            'requested_equipment_id' => $equipment->requested_equipment_id,
                            'equipment_id' => $equipment->equipment_id,
                            'name' => $equipment->equipment->equipment_name,
                            'quantity' => $equipment->quantity,
                            'fee' => $equipment->equipment->base_fee,
                            'rate_type' => $equipment->equipment->rate_type,
                            'is_waived' => $equipment->is_waived,
                            'total_fee' => $equipment->equipment->base_fee * $equipment->quantity
                        ];
                    })->values()
                ],
                'fees' => [
                    'tentative_fee' => $totalTentativeFee,
                    'approved_fee' => $form->approved_fee,
                    'late_penalty_fee' => $form->late_penalty_fee,
                    'is_late' => $form->is_late,
                    'breakdown' => [
                        'base_fees' => $facilityFees + $equipmentFees,
                        'late_penalty' => $form->is_late ? $form->late_penalty_fee : 0
                    ]
                ],
                'status_tracking' => [
                    'is_finalized' => $form->is_finalized,
                    'finalized_at' => $form->finalized_at,
                    'finalized_by' => $form->finalizedBy ? [
                        'id' => $form->finalizedBy->admin_id,
                        'name' => $form->finalizedBy->first_name . ' ' . $form->finalizedBy->last_name,
                        'role' => $form->finalizedBy->role->role_title ?? 'Administrator'
                    ] : null,
                    'is_closed' => $form->is_closed,
                    'closed_at' => $form->closed_at,
                    'closed_by' => $form->closedBy ? [
                        'id' => $form->closedBy->admin_id,
                        'name' => $form->closedBy->first_name . ' ' . $form->closedBy->last_name,
                        'role' => $form->closedBy->role->role_title ?? 'Administrator'
                    ] : null,
                    'returned_at' => $form->returned_at
                ],
                'documents' => [
                    'endorser' => $form->endorser,
                    'date_endorsed' => $form->date_endorsed,
                    'formal_letter' => [
                        'url' => $form->formal_letter_url,
                        'public_id' => $form->formal_letter_public_id
                    ],
                    'facility_layout' => [
                        'url' => $form->facility_layout_url,
                        'public_id' => $form->facility_layout_public_id
                    ],
                    'official_receipt' => [
                        'number' => $form->official_receipt_num,
                        'url' => $form->official_receipt_url,
                        'public_id' => $form->official_receipt_public_id
                    ],
                    'proof_of_payment' => [
                        'url' => $form->proof_of_payment_url,
                        'public_id' => $form->proof_of_payment_public_id
                    ]
                ],
                'approval_info' => [
                    'approval_count' => $form->requisitionApprovals->whereNotNull('approved_by')->count(),
                    'rejection_count' => $form->requisitionApprovals->whereNotNull('rejected_by')->count(),
                    'latest_action' => $form->requisitionApprovals()->latest('date_updated')->first()
                ],
                'access_code' => $form->access_code
            ];
        });

    return response()->json($forms);
}

    // Get form by access code (Requester side)
public function getFormByAccessCode($accessCode)
{
    try {
        $form = RequisitionForm::with([
            'formStatus:status_id,status_name,color_code',
            'requestedFacilities.facility:facility_id,facility_name,base_fee,rate_type',
            'requestedEquipment.equipment:equipment_id,equipment_name,base_fee,rate_type',
            'purpose:purpose_id,purpose_name',
            'requisitionFees'
        ])->where('access_code', $accessCode)->firstOrFail();

        // Calculate fees - FIXED: use reduce() instead of sum() with callback
        $facilityFees = $form->requestedFacilities->reduce(function ($carry, $facility) {
            if ($facility->is_waived) {
                return $carry + 0;
            }
            return $carry + $facility->facility->base_fee;
        }, 0);

        $equipmentFees = $form->requestedEquipment->reduce(function ($carry, $equipment) {
            if ($equipment->is_waived) {
                return $carry + 0;
            }
            return $carry + ($equipment->equipment->base_fee * $equipment->quantity);
        }, 0);

        $totalTentativeFee = $facilityFees + $equipmentFees;
        if ($form->is_late) {
            $totalTentativeFee += $form->late_penalty_fee;
        }

        // Calculate approved fee including requisition fees
        $approvedFee = $this->calculateApprovedFee($form);

        // Format schedule display based on all_day flag
        if ($form->all_day) {
            $formattedStartDate = Carbon::parse($form->start_date)->format('F j, Y');
            $formattedEndDate = Carbon::parse($form->end_date)->format('F j, Y');
            $formattedStartTime = 'All Day';
            $formattedEndTime = 'All Day';
            $formattedSchedule = $form->start_date === $form->end_date
                ? $formattedStartDate . ' (All Day)'
                : $formattedStartDate . ' — ' . $formattedEndDate . ' (All Day)';
        } else {
            $startDateTime = Carbon::parse($form->start_date . ' ' . $form->start_time);
            $endDateTime = Carbon::parse($form->end_date . ' ' . $form->end_time);
            $formattedStartDate = $startDateTime->format('F j, Y');
            $formattedEndDate = $endDateTime->format('F j, Y');
            $formattedStartTime = $startDateTime->format('g:i A');
            $formattedEndTime = $endDateTime->format('g:i A');
            $formattedSchedule = $form->start_date === $form->end_date
                ? $formattedStartDate . ' ' . $formattedStartTime . ' — ' . $formattedEndTime
                : $formattedStartDate . ' ' . $formattedStartTime . ' — ' . $formattedEndDate . ' ' . $formattedEndTime;
        }

        // Transform response
        $result = [
            'request_id' => $form->request_id,
            'user_type' => $form->user_type,
            'first_name' => $form->first_name,
            'last_name' => $form->last_name,
            'email' => $form->email,
            'organization_name' => $form->organization_name,
            'contact_number' => $form->contact_number,
            'access_code' => $form->access_code,
            'num_participants' => $form->num_participants,
            // Raw schedule values
            'start_date' => $form->start_date,
            'end_date' => $form->end_date,
            'start_time' => $form->start_time,
            'end_time' => $form->end_time,
            'all_day' => $form->all_day, // ADDED
            // Formatted schedule values for display
            'formatted_start_date' => $formattedStartDate,
            'formatted_end_date' => $formattedEndDate,
            'formatted_start_time' => $formattedStartTime,
            'formatted_end_time' => $formattedEndTime,
            'formatted_schedule' => $formattedSchedule,
            'calendar_title' => $form->calendar_title,
            'calendar_description' => $form->calendar_description,

            // Include fee data in facilities and equipment
            'requested_facilities' => $form->requestedFacilities->map(fn($rf) => [
                'requested_facility_id' => $rf->requested_facility_id,
                'facility_id' => $rf->facility_id,
                'facility_name' => $rf->facility->facility_name,
                'base_fee' => $rf->facility->base_fee,
                'rate_type' => $rf->facility->rate_type,
                'is_waived' => $rf->is_waived
            ])->values(),

            'requested_equipment' => $form->requestedEquipment->map(fn($re) => [
                'requested_equipment_id' => $re->requested_equipment_id,
                'equipment_id' => $re->equipment_id,
                'equipment_name' => $re->equipment->equipment_name,
                'base_fee' => $re->equipment->base_fee,
                'rate_type' => $re->equipment->rate_type,
                'quantity' => $re->quantity,
                'is_waived' => $re->is_waived,
                'total_fee' => $re->equipment->base_fee * $re->quantity
            ])->values(),

            'form_status' => $form->formStatus,
            'purpose' => $form->purpose,

            // Use the same fees structure as admin API
            'fees' => [
                'tentative_fee' => $totalTentativeFee,
                'approved_fee' => $approvedFee,
                'late_penalty_fee' => $form->late_penalty_fee,
                'is_late' => $form->is_late,
                'breakdown' => [ // ADDED for consistency
                    'base_fees' => $facilityFees + $equipmentFees,
                    'late_penalty' => $form->is_late ? $form->late_penalty_fee : 0
                ]
            ],

            // Keep total_fee for backward compatibility
            'total_fee' => $approvedFee,
            
            // Add multi-day flag
            'is_multi_day' => $form->start_date !== $form->end_date
        ];

        return response()->json($result);
    } catch (\Exception $e) {
        return response()->json([
            'error' => 'Form not found',
            'details' => $e->getMessage()
        ], 404);
    }
}
    public function uploadPaymentReceipt(Request $request, $requestId)
    {
        try {
            \Log::info('Payment receipt upload attempt', [
                'request_id' => $requestId,
                'has_receipt_url' => !empty($request->receipt_url),
                'has_public_id' => !empty($request->public_id)
            ]);

            $validatedData = $request->validate([
                'receipt_url' => 'required|url',
                'public_id' => 'required|string'
            ]);

            $form = RequisitionForm::findOrFail($requestId);

            // Check if form is in correct status for payment
            if ($form->status_id !== FormStatus::where('status_name', 'Awaiting Payment')->first()->status_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'This request is not awaiting payment.'
                ], 422);
            }

            // Update the form with receipt details
            $form->proof_of_payment_url = $validatedData['receipt_url'];
            $form->proof_of_payment_public_id = $validatedData['public_id'];
            $form->save();

            \Log::info('Payment receipt uploaded successfully', [
                'request_id' => $requestId,
                'receipt_url' => $validatedData['receipt_url']
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Receipt uploaded successfully'
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('Receipt upload validation failed', [
                'request_id' => $requestId,
                'errors' => $e->errors()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Failed to upload payment receipt', [
                'request_id' => $requestId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to upload receipt: ' . $e->getMessage()
            ], 500);
        }
    }

public function autoMarkLateForms()
{
    try {
        \Log::info('Starting automatic late form detection');

        // Get forms that are in Ongoing status and not already marked as late
        $ongoingStatus = FormStatus::where('status_name', 'Ongoing')->first();
        $lateStatus = FormStatus::where('status_name', 'Late')->first();

        if (!$ongoingStatus || !$lateStatus) {
            \Log::error('Required statuses not found');
            return response()->json([
                'error' => 'Required statuses not found',
                'processed' => 0,
                'marked_late' => 0
            ], 500);
        }

        // Use get() to retrieve actual model instances, not toBase() or similar
        $formsToMarkLate = RequisitionForm::where('status_id', $ongoingStatus->status_id)
            ->where('is_late', false)
            ->get(); // This returns a collection of RequisitionForm models

        $markedLateCount = 0;

        foreach ($formsToMarkLate as $form) {
            try {
                // Ensure $form is a RequisitionForm model
                if (!($form instanceof RequisitionForm)) {
                    \Log::warning('Form is not an instance of RequisitionForm', [
                        'request_id' => $form->request_id ?? 'unknown',
                        'type' => gettype($form)
                    ]);
                    continue;
                }

                // Calculate end datetime based on all_day flag
                if ($form->all_day) {
                    // For all-day events: end at 23:59:59 of end_date
                    $endDateTime = Carbon::parse($form->end_date . ' 23:59:59');
                    \Log::debug('All-day event end time', [
                        'request_id' => $form->request_id,
                        'end_date' => $form->end_date,
                        'end_time' => '23:59:59',
                        'parsed_end' => $endDateTime
                    ]);
                } else {
                    // For regular events: use end_date + end_time
                    $endDateTime = Carbon::parse($form->end_date . ' ' . $form->end_time);
                }

                // Calculate grace period (4 hours)
                $gracePeriodEnd = $endDateTime->copy()->addHours(4);

                // Check if grace period has passed
                if (now()->greaterThan($gracePeriodEnd)) {
                    \Log::info('Marking form as late automatically', [
                        'request_id' => $form->request_id,
                        'all_day' => $form->all_day,
                        'end_datetime' => $endDateTime,
                        'grace_period_end' => $gracePeriodEnd,
                        'current_time' => now()
                    ]);

                    // Set default penalty fee for automated detection
                    $defaultPenaltyFee = 500.00; // You can adjust this amount

                    // Update form to late status with penalty fee
                    $form->status_id = $lateStatus->status_id;
                    $form->is_late = true;
                    $form->late_penalty_fee = $defaultPenaltyFee;
                    $form->save(); // This should now work since $form is a RequisitionForm model

                    // Send AUTOMATED late penalty email
                    $this->sendAutoLatePenaltyEmail($form, $defaultPenaltyFee);

                    $markedLateCount++;

                    // Log the automatic action
                    \Log::info('Form automatically marked as late', [
                        'request_id' => $form->request_id,
                        'all_day' => $form->all_day,
                        'requester' => $form->first_name . ' ' . $form->last_name,
                        'original_end' => $endDateTime,
                        'grace_period_end' => $gracePeriodEnd,
                        'penalty_fee' => $defaultPenaltyFee,
                        'marked_late_at' => now()
                    ]);
                } else {
                    \Log::debug('Form still within grace period', [
                        'request_id' => $form->request_id,
                        'all_day' => $form->all_day,
                        'end_datetime' => $endDateTime,
                        'grace_period_end' => $gracePeriodEnd,
                        'remaining_minutes' => now()->diffInMinutes($gracePeriodEnd, false)
                    ]);
                }
            } catch (\Exception $e) {
                \Log::error('Error processing form for late marking', [
                    'request_id' => $form->request_id ?? 'unknown',
                    'all_day' => $form->all_day ?? false,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                continue;
            }
        }

        \Log::info('Automatic late form detection completed', [
            'processed' => $formsToMarkLate->count(),
            'marked_late' => $markedLateCount,
            'still_in_grace_period' => $formsToMarkLate->count() - $markedLateCount
        ]);

        return response()->json([
            'message' => 'Automatic late detection completed',
            'processed' => $formsToMarkLate->count(),
            'marked_late' => $markedLateCount
        ]);
    } catch (\Exception $e) {
        \Log::error('Failed to automatically mark late forms', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);

        return response()->json([
            'error' => 'Failed to automatically mark late forms',
            'details' => $e->getMessage(),
            'processed' => 0,
            'marked_late' => 0
        ], 500);
    }
}

private function sendAutoLatePenaltyEmail($form, $penaltyFee)
{
    try {
        $userName = $form->first_name . ' ' . $form->last_name;
        $userEmail = $form->email;

        // Calculate end datetime based on all_day flag
        if ($form->all_day) {
            // For all-day events: end at 23:59:59 of end_date
            $endDateTime = Carbon::parse($form->end_date . ' 23:59:59');
            $originalEndTimeFormatted = $endDateTime->format('F j, Y') . ' (All Day)';
            $scheduleType = 'All Day Event';
        } else {
            // For regular events: use end_date + end_time
            $endDateTime = Carbon::parse($form->end_date . ' ' . $form->end_time);
            $originalEndTimeFormatted = $endDateTime->format('F j, Y \a\t g:i A');
            $scheduleType = 'Timed Event';
        }

        $gracePeriodEnd = $endDateTime->copy()->addHours(4);

        // Format schedule for email display
        if ($form->all_day) {
            $startDateFormatted = Carbon::parse($form->start_date)->format('F j, Y');
            $endDateFormatted = Carbon::parse($form->end_date)->format('F j, Y');
            
            $fullSchedule = $form->start_date === $form->end_date
                ? $startDateFormatted . ' (All Day)'
                : $startDateFormatted . ' — ' . $endDateFormatted . ' (All Day)';
        } else {
            $startDateTime = Carbon::parse($form->start_date . ' ' . $form->start_time);
            $endDateTimeForDisplay = Carbon::parse($form->end_date . ' ' . $form->end_time);
            
            $fullSchedule = $form->start_date === $form->end_date
                ? $startDateTime->format('F j, Y \a\t g:i A') . ' — ' . $endDateTimeForDisplay->format('g:i A')
                : $startDateTime->format('F j, Y \a\t g:i A') . ' — ' . $endDateTimeForDisplay->format('F j, Y \a\t g:i A');
        }

        $emailData = [
            'first_name' => $form->first_name,
            'last_name' => $form->last_name,
            'request_id' => $form->request_id,
            'penalty_fee' => number_format($penaltyFee, 2),
            'original_end_time' => $originalEndTimeFormatted,
            'grace_period_end' => $gracePeriodEnd->format('F j, Y \a\t g:i A'),
            'detected_late_time' => now()->format('F j, Y \a\t g:i A'),
            // New fields for better email templates
            'all_day' => $form->all_day,
            'schedule_type' => $scheduleType,
            'start_date' => $form->start_date,
            'end_date' => $form->end_date,
            'start_time' => $form->all_day ? 'All Day' : $form->start_time,
            'end_time' => $form->all_day ? 'All Day' : $form->end_time,
            'formatted_start_date' => Carbon::parse($form->start_date)->format('F j, Y'),
            'formatted_end_date' => Carbon::parse($form->end_date)->format('F j, Y'),
            'formatted_start_time' => $form->all_day ? 'All Day' : Carbon::parse($form->start_time)->format('g:i A'),
            'formatted_end_time' => $form->all_day ? 'All Day' : Carbon::parse($form->end_time)->format('g:i A'),
            'full_schedule' => $fullSchedule,
            'purpose' => $form->purpose->purpose_name ?? 'N/A',
            'num_participants' => $form->num_participants,
            'access_code' => $form->access_code
        ];

        \Log::debug('Sending automated late penalty email', [
            'recipient' => $userEmail,
            'request_id' => $form->request_id,
            'all_day' => $form->all_day,
            'penalty_fee' => $penaltyFee,
            'schedule' => $fullSchedule
        ]);

        \Mail::send('emails.booking-late-auto', $emailData, function ($message) use ($userEmail, $userName) {
            $message->to($userEmail, $userName)
                ->subject('Automatic Late Penalty Notice - Central Philippine University');
        });

        \Log::debug('Automated late penalty email sent successfully', [
            'recipient' => $userEmail,
            'request_id' => $form->request_id,
            'all_day' => $form->all_day
        ]);
    } catch (\Exception $emailError) {
        \Log::error('Failed to send automated late penalty email', [
            'request_id' => $form->request_id,
            'all_day' => $form->all_day ?? false,
            'error' => $emailError->getMessage(),
            'recipient' => $form->email,
            'trace' => $emailError->getTraceAsString()
        ]);
    }
}

public function autoMarkOngoingForms()
{
    try {
        \Log::info('Starting automatic ongoing form detection');

        // Get forms that are in Scheduled status
        $scheduledStatus = FormStatus::where('status_name', 'Scheduled')->first();
        $ongoingStatus = FormStatus::where('status_name', 'Ongoing')->first();

        if (!$scheduledStatus || !$ongoingStatus) {
            \Log::error('Required statuses not found');
            return response()->json([
                'error' => 'Required statuses not found',
                'processed' => 0,
                'marked_ongoing' => 0
            ], 500);
        }

        $formsToMarkOngoing = RequisitionForm::where('status_id', $scheduledStatus->status_id)
            ->get();

        $markedOngoingCount = 0;

        foreach ($formsToMarkOngoing as $form) {
            try {
                // Calculate start datetime based on all_day flag
                if ($form->all_day) {
                    // For all-day events: start at 00:00:00 of start_date
                    $startDateTime = Carbon::parse($form->start_date . ' 00:00:00');
                    \Log::debug('All-day event start time', [
                        'request_id' => $form->request_id,
                        'start_date' => $form->start_date,
                        'start_time' => '00:00:00',
                        'parsed_start' => $startDateTime
                    ]);
                } else {
                    // For regular events: use start_date + start_time
                    $startDateTime = Carbon::parse($form->start_date . ' ' . $form->start_time);
                }

                // Check if start time has begun (current time is equal to or after start time)
                if (now()->greaterThanOrEqualTo($startDateTime)) {
                    \Log::info('Marking form as ongoing automatically', [
                        'request_id' => $form->request_id,
                        'all_day' => $form->all_day,
                        'start_datetime' => $startDateTime,
                        'current_time' => now()
                    ]);

                    // Update form to ongoing status
                    $form->status_id = $ongoingStatus->status_id;
                    $form->save();

                    // Optional: Send notification email for status change
                    $this->sendOngoingStatusEmail($form);

                    $markedOngoingCount++;

                    // Log the automatic action
                    \Log::info('Form automatically marked as ongoing', [
                        'request_id' => $form->request_id,
                        'all_day' => $form->all_day,
                        'requester' => $form->first_name . ' ' . $form->last_name,
                        'original_start' => $startDateTime,
                        'marked_ongoing_at' => now()
                    ]);
                } else {
                    \Log::debug('Form not yet ready for ongoing status', [
                        'request_id' => $form->request_id,
                        'all_day' => $form->all_day,
                        'start_datetime' => $startDateTime,
                        'minutes_until_start' => now()->diffInMinutes($startDateTime, false)
                    ]);
                }
            } catch (\Exception $e) {
                \Log::error('Error processing form for ongoing marking', [
                    'request_id' => $form->request_id,
                    'all_day' => $form->all_day ?? false,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                continue;
            }
        }

        \Log::info('Automatic ongoing form detection completed', [
            'processed' => $formsToMarkOngoing->count(),
            'marked_ongoing' => $markedOngoingCount,
            'not_yet_started' => $formsToMarkOngoing->count() - $markedOngoingCount
        ]);

        return response()->json([
            'message' => 'Automatic ongoing detection completed',
            'processed' => $formsToMarkOngoing->count(),
            'marked_ongoing' => $markedOngoingCount
        ]);
    } catch (\Exception $e) {
        \Log::error('Failed to automatically mark ongoing forms', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);

        return response()->json([
            'error' => 'Failed to automatically mark ongoing forms',
            'details' => $e->getMessage(),
            'processed' => 0,
            'marked_ongoing' => 0
        ], 500);
    }
}

    // Email notification method for ongoing status
private function sendOngoingStatusEmail($form)
{
    try {
        $userName = $form->first_name . ' ' . $form->last_name;
        $userEmail = $form->email;

        // Format schedule based on all_day flag
        if ($form->all_day) {
            $startDateTime = Carbon::parse($form->start_date . ' 00:00:00');
            $endDateTime = Carbon::parse($form->end_date . ' 23:59:59');
            
            $formattedStartTime = $startDateTime->format('F j, Y') . ' (All Day)';
            $formattedEndTime = $endDateTime->format('F j, Y') . ' (All Day)';
            
            $fullSchedule = $form->start_date === $form->end_date
                ? $formattedStartTime
                : $startDateTime->format('F j, Y') . ' — ' . $endDateTime->format('F j, Y') . ' (All Day)';
                
            $scheduleType = 'All Day Event';
        } else {
            $startDateTime = Carbon::parse($form->start_date . ' ' . $form->start_time);
            $endDateTime = Carbon::parse($form->end_date . ' ' . $form->end_time);
            
            $formattedStartTime = $startDateTime->format('F j, Y \a\t g:i A');
            $formattedEndTime = $endDateTime->format('F j, Y \a\t g:i A');
            
            $fullSchedule = $form->start_date === $form->end_date
                ? $startDateTime->format('F j, Y \a\t g:i A') . ' — ' . $endDateTime->format('g:i A')
                : $startDateTime->format('F j, Y \a\t g:i A') . ' — ' . $endDateTime->format('F j, Y \a\t g:i A');
                
            $scheduleType = 'Timed Event';
        }

        // Get facility names
        $facilityNames = $form->requestedFacilities->map(function ($facility) {
            return $facility->facility->facility_name;
        })->filter()->implode(', ');

        // Get equipment names with quantities
        $equipmentList = $form->requestedEquipment->map(function ($equipment) {
            $name = $equipment->equipment->equipment_name ?? 'Unknown Equipment';
            $quantity = $equipment->quantity > 1 ? " (×{$equipment->quantity})" : '';
            return $name . $quantity;
        })->filter()->implode(', ');

        $emailData = [
            'first_name' => $form->first_name,
            'last_name' => $form->last_name,
            'request_id' => $form->request_id,
            // Raw values
            'start_date' => $form->start_date,
            'end_date' => $form->end_date,
            'start_time' => $form->start_time,
            'end_time' => $form->end_time,
            'all_day' => $form->all_day,
            // Formatted values
            'formatted_start_time' => $formattedStartTime,
            'formatted_end_time' => $formattedEndTime,
            'full_schedule' => $fullSchedule,
            'schedule_type' => $scheduleType,
            'facilities' => $facilityNames ?: 'No facilities booked',
            'equipment' => $equipmentList ?: 'No equipment booked',
            'purpose' => $form->purpose->purpose_name ?? 'N/A',
            'num_participants' => $form->num_participants,
            'access_code' => $form->access_code,
            'calendar_title' => $form->calendar_title ?? 'Booking #' . $form->request_id,
            'calendar_description' => $form->calendar_description,
            'is_multi_day' => $form->start_date !== $form->end_date
        ];

        \Log::debug('Sending ongoing status email', [
            'recipient' => $userEmail,
            'request_id' => $form->request_id,
            'all_day' => $form->all_day,
            'schedule' => $fullSchedule
        ]);

        \Mail::send('emails.booking-ongoing', $emailData, function ($message) use ($userEmail, $userName) {
            $message->to($userEmail, $userName)
                ->subject('Your Booking is Now Ongoing - Central Philippine University');
        });

        \Log::debug('Ongoing status email sent successfully', [
            'recipient' => $userEmail,
            'request_id' => $form->request_id,
            'all_day' => $form->all_day
        ]);
    } catch (\Exception $emailError) {
        \Log::error('Failed to send ongoing status email', [
            'request_id' => $form->request_id,
            'all_day' => $form->all_day ?? false,
            'error' => $emailError->getMessage(),
            'recipient' => $form->email,
            'trace' => $emailError->getTraceAsString()
        ]);
    }
}

    public function autoUpdateAllStatuses()
    {
        try {
            \Log::info('Starting automatic status updates for all forms');

            // Run both automated methods
            $ongoingResult = $this->autoMarkOngoingForms();
            $lateResult = $this->autoMarkLateForms();

            return response()->json([
                'message' => 'Automatic status updates completed',
                'ongoing_forms' => json_decode($ongoingResult->getContent(), true),
                'late_forms' => json_decode($lateResult->getContent(), true)
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed to run automatic status updates', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'error' => 'Failed to run automatic status updates',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    /**
 * Common JSON response structure
 */
private function jsonResponse($success, $message, $data = [], $status = 200)
{
    return response()->json([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ], $status);
}


}
