<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\Admin;
use App\Models\RequisitionForm;
use Illuminate\Support\Carbon;
use App\Services\RequisitionFormatterService;
use App\Services\FeeCalculatorService;
use App\Services\ScheduleFormatterService;

class NotificationService
{

    protected $RequisitionFormatter;
    protected $FeeCalculator;
    protected $ScheduleFormatter;

    public function __construct(RequisitionFormatterService $requisitionFormatter, FeeCalculatorService $feeCalculator, ScheduleFormatterService $scheduleFormatter)
    {
        $this->RequisitionFormatter = $requisitionFormatter;
        $this->FeeCalculator = $feeCalculator;
        $this->ScheduleFormatter = $scheduleFormatter;
    }

    public static function notifyNewRequisition(RequisitionForm $requisition)
    {
        // Get all Head Admins (role_id 1), VPA (role_id 2), and Approving Officers (role_id 3)
        $admins = Admin::whereIn('role_id', [1, 2, 3])->get();

        $message = "New requisition submitted by {$requisition->first_name} {$requisition->last_name}";

        foreach ($admins as $admin) {
            Notification::create([
                'admin_id' => $admin->admin_id,
                'type' => 'new_requisition',
                'message' => $message,
                'request_id' => $requisition->request_id,
                'is_read' => false
            ]);
        }
    }

    public static function getUnreadCount($adminId)
    {
        return Notification::where('admin_id', $adminId)
            ->where('is_read', false)
            ->count();
    }

    public static function markAsRead($adminId, $notificationId = null)
    {
        if ($notificationId) {
            return Notification::where('admin_id', $adminId)
                ->where('notification_id', $notificationId)
                ->update(['is_read' => true]);
        }

        return Notification::where('admin_id', $adminId)
            ->where('is_read', false)
            ->update(['is_read' => true]);
    }


    /// Email Notifications //

    public function sendConfirmationEmail(RequisitionForm $requisitionForm)
    {
        try {
            $subject = 'CPU Booking System - Requisition Form Received';

            $emailData = [
                'first_name' => $requisitionForm->first_name,
                'last_name' => $requisitionForm->last_name,
                'access_code' => $requisitionForm->access_code,
            ];

            // Use the blade template instead of raw text
            \Mail::send('emails.booking-confirmation', $emailData, function ($message) use ($requisitionForm, $subject) {
                $message->to($requisitionForm->email)
                    ->subject($subject)
                    ->from(config('mail.from.address'), config('mail.from.name'));
            });

            \Log::info('Confirmation email sent to: ' . $requisitionForm->email);

        } catch (\Exception $e) {
            \Log::error('Failed to send confirmation email: ' . $e->getMessage());
            // Don't throw exception here - email failure shouldn't prevent form submission
        }
    }
    /**
     * Send approval request emails to all admins responsible for this requisition
     * 
     * Business Rule: Admins are considered approvers if:
     * - For facilities/equipment: They manage departments that match the resource's departments
     * - For services: They are directly assigned to manage the service
     */
    public function sendAdminApprovalEmails(RequisitionForm $requisitionForm)
    {
        \Log::info('=== STARTING ADMIN APPROVAL EMAILS ===', [
            'request_id' => $requisitionForm->request_id,
            'method' => __METHOD__
        ]);

        // Get the RequiredApprovalsController instance
        $approvalsController = app(\App\Http\Controllers\RequiredApprovalsController::class);

        // Get the list of admins to notify using the controller's business logic
        $response = $approvalsController->getAdminsToNotify($requisitionForm->request_id);
        $data = $response->getData();

        \Log::info('Admins to notify response:', [
            'has_data' => !is_null($data),
            'admins_count' => isset($data->admins) ? count($data->admins) : 0,
            'facilities_count' => $data->facilities_count ?? 0,
            'equipment_count' => $data->equipment_count ?? 0,
            'services_count' => $data->services_count ?? 0
        ]);

        if ($data && isset($data->admins) && count($data->admins) > 0) {

            // Process ALL admins (production mode)
            $adminsToProcess = $data->admins;

            \Log::info('📧 Processing ' . count($adminsToProcess) . ' admin(s) for email notifications based on department/resource matching.');

            foreach ($adminsToProcess as $adminData) {
                try {
                    // Skip if admin has no email address
                    if (empty($adminData->email)) {
                        \Log::warning('⚠ Skipping admin with no email address:', [
                            'admin_name' => $adminData->name ?? 'Unknown',
                            'admin_id' => $adminData->admin_id ?? 'N/A'
                        ]);
                        continue;
                    }

                    \Log::info('Processing admin:', [
                        'admin_name' => $adminData->name,
                        'admin_email' => $adminData->email,
                        'resource_count' => isset($adminData->resources) ? count($adminData->resources) : 0
                    ]);

                    // Build resources array for the template from the structured data
                    $resources = [];
                    $resourceTypes = [
                        'facility' => 'Facility',
                        'equipment' => 'Equipment',
                        'service' => 'Service'
                    ];

                    if (isset($adminData->resources) && is_array($adminData->resources)) {
                        foreach ($adminData->resources as $resource) {
                            // Handle both object and array formats
                            $resourceObj = is_object($resource) ? $resource : (object) $resource;

                            $type = $resourceObj->type ?? 'unknown';
                            $name = $resourceObj->name ?? 'Unknown';

                            $resources[] = [
                                'type' => $type,
                                'type_label' => $resourceTypes[$type] ?? ucfirst($type),
                                'name' => $name,
                                'id' => $resourceObj->id ?? null
                            ];
                        }
                    }

                    // Group resources by type for better display in email
                    $groupedResources = [
                        'facilities' => array_filter($resources, fn($r) => $r['type'] === 'facility'),
                        'equipment' => array_filter($resources, fn($r) => $r['type'] === 'equipment'),
                        'services' => array_filter($resources, fn($r) => $r['type'] === 'service')
                    ];

                    // Create a simple string list for fallback
                    $resourcesList = collect($resources)
                        ->map(fn($r) => $r['type_label'] . ': ' . $r['name'])
                        ->implode(', ');

                    // Get formatted schedule using ScheduleFormatter
                    $scheduleDisplay = $this->ScheduleFormatter->getDisplayString($requisitionForm);

                    // Email subject
                    $subject = 'CPU Booking System - Action Required: New Booking Request Needs Your Approval';

                    // Email data
                    $emailData = [
                        'admin_name' => $adminData->name,
                        'requester_name' => $requisitionForm->first_name . ' ' . $requisitionForm->last_name,
                        'requester_email' => $requisitionForm->email,
                        'request_id' => $requisitionForm->request_id,
                        'access_code' => $requisitionForm->access_code,
                        'resources' => $resources,
                        'grouped_resources' => $groupedResources,
                        'resources_list' => $resourcesList,
                        'schedule_display' => $scheduleDisplay,
                        'purpose' => $requisitionForm->purpose->purpose_name ?? 'N/A',
                        'participants' => $requisitionForm->num_participants,
                        'admin_link' => url("/admin/requisition/{$requisitionForm->request_id}"),
                        'has_facilities' => !empty($groupedResources['facilities']),
                        'has_equipment' => !empty($groupedResources['equipment']),
                        'has_services' => !empty($groupedResources['services']),
                        'facilities_count' => count($groupedResources['facilities']),
                        'equipment_count' => count($groupedResources['equipment']),
                        'services_count' => count($groupedResources['services']),
                        'total_resources' => count($resources),
                        'equipment_badge_color' => '#17a2b8',
                        'facility_badge_color' => '#28a745',
                        'service_badge_color' => '#ffc107'
                    ];

                    \Log::info('Sending approval email to admin:', [
                        'to' => $adminData->email,
                        'admin_name' => $adminData->name,
                        'resources_by_type' => [
                            'facilities' => $emailData['facilities_count'],
                            'equipment' => $emailData['equipment_count'],
                            'services' => $emailData['services_count']
                        ]
                    ]);

                    // Send to actual admin email
                    \Mail::send('emails.admin-approval-request', $emailData, function ($message) use ($subject, $adminData) {
                        $message->to($adminData->email, $adminData->name)
                            ->subject($subject)
                            ->from(config('mail.from.address'), config('mail.from.name'));
                    });

                    \Log::info('✓ Admin approval email sent successfully', [
                        'admin' => $adminData->name,
                        'email' => $adminData->email
                    ]);

                } catch (\Exception $e) {
                    \Log::error('✗ Failed to send admin approval email: ' . $e->getMessage(), [
                        'admin_name' => $adminData->name ?? 'Unknown',
                        'admin_email' => $adminData->email ?? 'unknown',
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                }
            }

        } else {
            \Log::warning('⚠ No admins found to notify for request #' . $requisitionForm->request_id, [
                'request_id' => $requisitionForm->request_id,
                'facilities_count' => $requisitionForm->requestedFacilities->count(),
                'equipment_count' => $requisitionForm->requestedEquipment->count(),
                'services_count' => $requisitionForm->requestedServices->count()
            ]);

            // Log why no admins were found (debugging info)
            $this->logNoAdminsFoundDebugInfo($requisitionForm);
        }

        \Log::info('=== FINISHED ADMIN APPROVAL EMAILS ===', [
            'request_id' => $requisitionForm->request_id,
            'total_admins_processed' => isset($adminsToProcess) ? count($adminsToProcess) : 0
        ]);
    }

    /**
     * Log debug information when no admins are found
     */
    private function logNoAdminsFoundDebugInfo(RequisitionForm $requisitionForm)
    {
        \Log::debug('Debug: No admins found - checking resource details', [
            'facilities' => $requisitionForm->requestedFacilities->map(function ($rf) {
                return [
                    'facility_id' => $rf->facility_id,
                    'facility_name' => $rf->facility->facility_name ?? 'Unknown',
                    'departments' => $rf->facility && $rf->facility->departments
                        ? $rf->facility->departments->pluck('department_name')
                        : []
                ];
            }),
            'equipment' => $requisitionForm->requestedEquipment->map(function ($re) {
                return [
                    'equipment_id' => $re->equipment_id,
                    'equipment_name' => $re->equipment->equipment_name ?? 'Unknown',
                    'departments' => $re->equipment && $re->equipment->departments
                        ? $re->equipment->departments->pluck('department_name')
                        : []
                ];
            }),
            'services' => $requisitionForm->requestedServices->map(function ($rs) {
                return [
                    'service_id' => $rs->service_id,
                    'service_name' => $rs->service->service_name ?? 'Unknown',
                    'admins' => $rs->service && $rs->service->admins
                        ? $rs->service->admins->map(fn($a) => $a->first_name . ' ' . $a->last_name)
                        : []
                ];
            })
        ]);
    }
    /**
     * Send approval email with booking details
     */
    public function sendApprovalEmail($form)
    {
        \Log::info('=== STARTING APPROVAL EMAIL ===', [
            'request_id' => $form->request_id,
            'method' => __METHOD__
        ]);

        try {
            $emailData = $this->buildApprovalEmailData($form);

            // Debug: Log the complete email data structure
            \Log::debug('Email data being sent:', [
                'request_id' => $form->request_id,
                'email' => $form->email,
                'user_name' => $emailData['user_name'] ?? 'not set',
                'user_first_name' => $emailData['user_first_name'] ?? 'not set',
                'user_last_name' => $emailData['user_last_name'] ?? 'not set',
                'access_code' => $emailData['access_code'] ?? 'not set',
                'approved_fee' => $emailData['approved_fee'] ?? 'not set',
                'formatted_fee' => $emailData['formatted_fee'] ?? 'not set',
                'due_date' => $emailData['due_date'] ?? 'not set',
                'schedule_display' => $emailData['schedule_display'] ?? 'not set',
                'purpose' => $emailData['purpose'] ?? 'not set',
                'resources_count' => isset($emailData['resources']) ? count($emailData['resources']) : 0,
                'payment_deadline_days' => $emailData['payment_deadline_days'] ?? 'not set',
                'payment_instructions' => isset($emailData['payment_instructions']) ? 'present' : 'not set'
            ]);

            // Debug: Log the raw form data for reference
            \Log::debug('Raw form data:', [
                'request_id' => $form->request_id,
                'first_name' => $form->first_name,
                'last_name' => $form->last_name,
                'email' => $form->email,
                'access_code' => $form->access_code,
                'tentative_fee' => $form->tentative_fee,
                'status_id' => $form->status_id,
                'purpose_id' => $form->purpose_id,
                'all_day' => $form->all_day,
                'start_date' => $form->start_date,
                'end_date' => $form->end_date,
                'start_time' => $form->start_time,
                'end_time' => $form->end_time
            ]);

            // Debug: Check if view exists
            $viewName = 'emails.booking-approved';
            if (!view()->exists($viewName)) {
                $errorMsg = 'Email view not found: ' . $viewName;
                \Log::error('❌ ' . $errorMsg, [
                    'view_name' => $viewName,
                    'view_paths' => config('view.paths')
                ]);
                throw new \Exception($errorMsg);
            }

            \Log::info('✅ View exists: ' . $viewName);

            // Debug: Log mail configuration (without sensitive data)
            \Log::debug('Mail configuration:', [
                'mailer' => config('mail.default'),
                'from_address' => config('mail.from.address'),
                'from_name' => config('mail.from.name'),
                'has_smtp_credentials' => !empty(config('mail.mailers.smtp.username')),
                'environment' => app()->environment()
            ]);

            // Attempt to send email
            \Log::info('📧 Attempting to send approval email to: ' . $form->email);

            \Mail::send($viewName, $emailData, function ($message) use ($form) {
                $userName = $form->first_name . ' ' . $form->last_name;

                \Log::debug('Building message:', [
                    'to_email' => $form->email,
                    'to_name' => $userName,
                    'subject' => 'Your Booking Request Has Been Approved – Payment Required',
                    'from' => config('mail.from.address')
                ]);

                $message->to($form->email, $userName)
                    ->subject('Your Booking Request Has Been Approved – Payment Required');
            });

            \Log::info('✅ Approval email sent successfully to: ' . $form->email . ' for request #' . $form->request_id);

        } catch (\Exception $e) {
            \Log::error('❌ Failed to send approval email for request #' . $form->request_id, [
                'error_message' => $e->getMessage(),
                'error_class' => get_class($e),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'error_trace' => $e->getTraceAsString(),
                'form_data' => [
                    'request_id' => $form->request_id,
                    'email' => $form->email,
                    'name' => ($form->first_name ?? '') . ' ' . ($form->last_name ?? '')
                ]
            ]);

            // Re-throw if you want to handle it upstream, or return false if you want to fail silently
            throw $e; // or return false;
        }

        \Log::info('=== FINISHED APPROVAL EMAIL ===', [
            'request_id' => $form->request_id
        ]);
    }
    /**
     * Build email data array for approval email
     */
    private function buildApprovalEmailData($form)
    {
        \Log::debug('Building approval email data for request #' . $form->request_id);

        try {
            $userName = $form->first_name . ' ' . $form->last_name;

            // Get schedule display string from ScheduleFormatter (for the main schedule display)
            $scheduleDisplay = $this->ScheduleFormatter->getDisplayString($form);

            // Get schedule data from ScheduleFormatter (for individual components if needed)
            $scheduleData = $this->ScheduleFormatter->forEmail($form);
            $baseSchedule = $this->ScheduleFormatter->getBaseSchedule($form);

            // Get fee summary from calculator
            $feeSummary = $this->FeeCalculator->getFeeSummary($form);

            // Extract facility and equipment breakdowns
            $facilitiesBreakdown = $feeSummary['breakdown']['facilities'] ?? [];
            $equipmentBreakdown = $feeSummary['breakdown']['equipment'] ?? [];

            $data = [
                'user_name' => $userName,
                'request_id' => $form->request_id,
                'approved_fee' => $feeSummary['approved_fee'] ?? 0,
                'base_fee' => $feeSummary['base_fee'] ?? 0,
                'additional_fees_total' => $feeSummary['additional_fees'] ?? 0,
                'discounts_total' => $feeSummary['discounts'] ?? 0,
                'late_penalty_fee' => $form->late_penalty_fee ?? 0,
                'payment_deadline' => now()->addDays(5)->format('F j, Y'),
                'access_code' => $form->access_code,

                // SCHEDULE INFORMATION - Using the unified format
                'schedule_display' => $scheduleDisplay, // Main schedule display for the template

                // Keep these for backward compatibility or if needed elsewhere
                'booking_duration' => $baseSchedule['duration_hours'] ?? 0,
                'booking_duration_text' => $baseSchedule['duration_text'] ?? '0 hours',
                'duration_details' => $feeSummary['duration'] ?? [],
                'schedule_start' => $scheduleData['start'] ?? '',
                'schedule_end' => $scheduleData['end'] ?? '',
                'is_all_day' => $form->all_day ?? false,

                // Resources and fee breakdowns
                'requested_facilities' => $this->getSimpleFacilitiesList($form),
                'requested_equipment' => $this->getSimpleEquipmentList($form),
                'facilities_breakdown' => $facilitiesBreakdown,
                'equipment_breakdown' => $equipmentBreakdown,
                'additional_fees' => $this->getAdditionalFeesList($form)
            ];

            \Log::debug('✅ Approval email data built successfully', [
                'request_id' => $form->request_id,
                'schedule_display' => $scheduleDisplay,
                'booking_duration' => $baseSchedule['duration_hours'] ?? 0,
                'facilities_count' => count($facilitiesBreakdown),
                'equipment_count' => count($equipmentBreakdown),
                'additional_fees_count' => count($data['additional_fees']),
                'approved_fee' => $data['approved_fee']
            ]);

            return $data;

        } catch (\Exception $e) {
            \Log::error('❌ Failed to build approval email data for request #' . $form->request_id, [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Return minimal data to prevent complete failure
            return [
                'user_name' => ($form->first_name ?? '') . ' ' . ($form->last_name ?? ''),
                'request_id' => $form->request_id ?? 'N/A',
                'approved_fee' => $form->tentative_fee ?? 0,
                'base_fee' => 0,
                'additional_fees_total' => 0,
                'discounts_total' => 0,
                'late_penalty_fee' => 0,
                'payment_deadline' => now()->addDays(5)->format('F j, Y'),
                'access_code' => $form->access_code ?? 'N/A',
                'schedule_display' => 'Schedule information unavailable',
                'booking_duration' => 0,
                'booking_duration_text' => '0 hours',
                'is_all_day' => false,
                'requested_facilities' => [],
                'requested_equipment' => [],
                'facilities_breakdown' => [],
                'equipment_breakdown' => [],
                'additional_fees' => []
            ];
        }
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


    public function sendLatePenaltyEmail($form)
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

    public function sendScheduledConfirmationEmail($form)
    {
        \Log::info('=== STARTING SCHEDULED CONFIRMATION EMAIL ===', [
            'request_id' => $form->request_id,
            'method' => __METHOD__
        ]);

        try {
            $userName = $form->first_name . ' ' . $form->last_name;
            $userEmail = $form->email;

            \Log::debug('Step 1: Basic data prepared', [
                'request_id' => $form->request_id,
                'user_name' => $userName,
                'user_email' => $userEmail,
                'official_receipt_num' => $form->official_receipt_num
            ]);

            // Check if ScheduleFormatter is available
            if (!isset($this->ScheduleFormatter)) {
                \Log::error('ScheduleFormatter service not injected', [
                    'request_id' => $form->request_id
                ]);
                throw new \Exception('ScheduleFormatter service not available');
            }

            \Log::debug('Step 2: Getting base schedule from ScheduleFormatter');

            try {
                $baseSchedule = $this->ScheduleFormatter->getBaseSchedule($form);
                \Log::debug('Base schedule retrieved', [
                    'request_id' => $form->request_id,
                    'base_schedule' => $baseSchedule
                ]);
            } catch (\Exception $scheduleError) {
                \Log::error('Failed to get base schedule', [
                    'request_id' => $form->request_id,
                    'error' => $scheduleError->getMessage(),
                    'trace' => $scheduleError->getTraceAsString()
                ]);
                throw $scheduleError;
            }

            \Log::debug('Step 3: Getting display string');

            try {
                $displayString = $this->ScheduleFormatter->getDisplayString($form);
                \Log::debug('Display string retrieved', [
                    'request_id' => $form->request_id,
                    'display_string' => $displayString
                ]);
            } catch (\Exception $displayError) {
                \Log::error('Failed to get display string', [
                    'request_id' => $form->request_id,
                    'error' => $displayError->getMessage(),
                    'trace' => $displayError->getTraceAsString()
                ]);
                throw $displayError;
            }

            // Check if purpose relationship exists
            \Log::debug('Step 4: Checking purpose relationship', [
                'request_id' => $form->request_id,
                'purpose_exists' => isset($form->purpose),
                'purpose_id' => $form->purpose_id,
                'purpose_name' => $form->purpose->purpose_name ?? 'N/A'
            ]);

            $purpose = 'N/A';
            try {
                $purpose = $form->purpose->purpose_name ?? 'N/A';
            } catch (\Exception $purposeError) {
                \Log::warning('Failed to get purpose name', [
                    'request_id' => $form->request_id,
                    'error' => $purposeError->getMessage()
                ]);
            }

            // Get formatted schedule from ScheduleFormatter
            $baseSchedule = $this->ScheduleFormatter->getBaseSchedule($form);
            $displayString = $this->ScheduleFormatter->getDisplayString($form);

            $emailData = [
                'user_name' => $userName,
                'request_id' => $form->request_id,
                'official_receipt_num' => $form->official_receipt_num,
                'purpose' => $form->purpose->purpose_name ?? 'N/A',
                // Raw values
                'start_date' => $baseSchedule['start_date'],
                'end_date' => $baseSchedule['end_date'],
                'all_day' => $baseSchedule['all_day'],
                // Formatted values from ScheduleFormatter
                'formatted_start_date' => $baseSchedule['formatted_start_date'],
                'formatted_end_date' => $baseSchedule['formatted_end_date'],
                'formatted_start_time' => $baseSchedule['formatted_start_time'],
                'formatted_end_time' => $baseSchedule['formatted_end_time'],
                'formatted_schedule' => $displayString,
                'approved_fee' => (float) $form->approved_fee, // Pass as float, NOT formatted
                'is_multi_day' => $baseSchedule['is_multi_day']
            ];

            \Log::debug('Step 5: Email data prepared', [
                'request_id' => $form->request_id,
                'email_data_keys' => array_keys($emailData),
                'formatted_schedule' => $emailData['formatted_schedule'],
                'approved_fee' => $emailData['approved_fee']
            ]);

            // Check if email view exists
            $viewName = 'emails.booking-scheduled';
            \Log::debug('Step 6: Checking if email view exists', [
                'view_name' => $viewName,
                'view_exists' => view()->exists($viewName),
                'view_paths' => config('view.paths')
            ]);

            if (!view()->exists($viewName)) {
                \Log::error('Email view not found', [
                    'view_name' => $viewName,
                    'view_paths' => config('view.paths')
                ]);
                throw new \Exception('Email view not found: ' . $viewName);
            }

            // Log mail configuration (without sensitive data)
            \Log::debug('Step 7: Mail configuration', [
                'mailer' => config('mail.default'),
                'from_address' => config('mail.from.address'),
                'from_name' => config('mail.from.name'),
                'has_smtp_credentials' => !empty(config('mail.mailers.smtp.username')),
                'environment' => app()->environment()
            ]);

            // Attempt to send email
            \Log::info('Step 8: Attempting to send scheduled confirmation email', [
                'recipient' => $userEmail,
                'request_id' => $form->request_id,
                'subject' => 'Your Booking Has Been Scheduled – Official Receipt Generated'
            ]);

            try {
                \Mail::send($viewName, $emailData, function ($message) use ($userEmail, $userName, $form) {
                    \Log::debug('Building message', [
                        'request_id' => $form->request_id,
                        'to_email' => $userEmail,
                        'to_name' => $userName,
                        'from' => config('mail.from.address')
                    ]);

                    $message->to($userEmail, $userName)
                        ->subject('Your Booking Has Been Scheduled – Official Receipt Generated');
                });

                \Log::info('✅ Scheduled confirmation email sent successfully', [
                    'recipient' => $userEmail,
                    'request_id' => $form->request_id,
                    'official_receipt_num' => $form->official_receipt_num
                ]);

            } catch (\Exception $mailError) {
                \Log::error('❌ Mail::send() failed', [
                    'request_id' => $form->request_id,
                    'error' => $mailError->getMessage(),
                    'error_class' => get_class($mailError),
                    'error_file' => $mailError->getFile(),
                    'error_line' => $mailError->getLine(),
                    'trace' => $mailError->getTraceAsString()
                ]);
                throw $mailError;
            }

        } catch (\Exception $emailError) {
            \Log::error('❌ Failed to send scheduled confirmation email', [
                'request_id' => $form->request_id,
                'error' => $emailError->getMessage(),
                'error_class' => get_class($emailError),
                'error_file' => $emailError->getFile(),
                'error_line' => $emailError->getLine(),
                'trace' => $emailError->getTraceAsString(),
                'recipient' => $form->email ?? 'unknown'
            ]);

            // Re-throw if you want it to be handled upstream, or comment out if you want it to fail silently
            // throw $emailError;
        }

        \Log::info('=== FINISHED SCHEDULED CONFIRMATION EMAIL ===', [
            'request_id' => $form->request_id
        ]);
    }
    // Email notification method for ongoing status
    public function sendOngoingStatusEmail($form)
    {
        try {
            $userName = $form->first_name . ' ' . $form->last_name;
            $userEmail = $form->email;

            // Get formatted schedule from ScheduleFormatter
            $baseSchedule = $this->ScheduleFormatter->getBaseSchedule($form);
            $displayString = $this->ScheduleFormatter->getDisplayString($form);
            $emailSchedule = $this->ScheduleFormatter->forEmail($form);

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
                // Raw values from base schedule
                'start_date' => $baseSchedule['start_date'],
                'end_date' => $baseSchedule['end_date'],
                'all_day' => $baseSchedule['all_day'],
                // Formatted values from ScheduleFormatter
                'formatted_start_time' => $emailSchedule['start'] ?? $displayString,
                'formatted_end_time' => $emailSchedule['end'] ?? '',
                'full_schedule' => $displayString,
                'schedule_type' => $baseSchedule['all_day'] ? 'All Day Event' : 'Timed Event',
                'facilities' => $facilityNames ?: 'No facilities booked',
                'equipment' => $equipmentList ?: 'No equipment booked',
                'purpose' => $form->purpose->purpose_name ?? 'N/A',
                'num_participants' => $form->num_participants,
                'access_code' => $form->access_code,
                'calendar_title' => $form->calendar_title ?? 'Booking #' . $form->request_id,
                'calendar_description' => $form->calendar_description,
                'is_multi_day' => $baseSchedule['is_multi_day']
            ];

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
                'recipient' => $form->email
            ]);
        }
    }

    public function sendAutoLatePenaltyEmail($form, $penaltyFee)
    {
        try {
            $userName = $form->first_name . ' ' . $form->last_name;
            $userEmail = $form->email;

            // Get formatted schedule from ScheduleFormatter
            $baseSchedule = $this->ScheduleFormatter->getBaseSchedule($form);
            $displayString = $this->ScheduleFormatter->getDisplayString($form);

            // Calculate end datetime for penalty logic
            if ($form->all_day) {
                $endDateTime = Carbon::parse($form->end_date . ' 23:59:59');
                $originalEndTimeFormatted = $endDateTime->format('F j, Y') . ' (All Day)';
            } else {
                $endDateTime = Carbon::parse($form->end_date . ' ' . $form->end_time);
                $originalEndTimeFormatted = $endDateTime->format('F j, Y \a\t g:i A');
            }

            $gracePeriodEnd = $endDateTime->copy()->addHours(4);

            $emailData = [
                'first_name' => $form->first_name,
                'last_name' => $form->last_name,
                'request_id' => $form->request_id,
                'penalty_fee' => number_format($penaltyFee, 2),
                'original_end_time' => $originalEndTimeFormatted,
                'grace_period_end' => $gracePeriodEnd->format('F j, Y \a\t g:i A'),
                'detected_late_time' => now()->format('F j, Y \a\t g:i A'),
                // Use ScheduleFormatter for all schedule data
                'all_day' => $baseSchedule['all_day'],
                'schedule_type' => $baseSchedule['all_day'] ? 'All Day Event' : 'Timed Event',
                'start_date' => $baseSchedule['start_date'],
                'end_date' => $baseSchedule['end_date'],
                'start_time' => $baseSchedule['all_day'] ? 'All Day' : $baseSchedule['start_time'],
                'end_time' => $baseSchedule['all_day'] ? 'All Day' : $baseSchedule['end_time'],
                'formatted_start_date' => $baseSchedule['formatted_start_date'],
                'formatted_end_date' => $baseSchedule['formatted_end_date'],
                'formatted_start_time' => $baseSchedule['formatted_start_time'],
                'formatted_end_time' => $baseSchedule['formatted_end_time'],
                'full_schedule' => $displayString,
                'purpose' => $form->purpose->purpose_name ?? 'N/A',
                'num_participants' => $form->num_participants,
                'access_code' => $form->access_code
            ];

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
                'recipient' => $form->email
            ]);
        }
    }
}