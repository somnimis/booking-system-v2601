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
 * PRODUCTION BEHAVIOR: This function sends emails to ALL admins who manage resources 
 * in this request. Each admin receives an email listing only the resources they manage.
 * 
 * TESTING MODE: Currently limited to send only to the first 1-2 admins to avoid 
 * overwhelming the system during development.
 */
public function sendAdminApprovalEmails(RequisitionForm $requisitionForm)
{
    \Log::info('=== STARTING ADMIN APPROVAL EMAILS ===', [
        'request_id' => $requisitionForm->request_id,
        'method' => __METHOD__
    ]);

    // For testing - only send to your email
    $testEmail = 'hunniegwyn98@gmail.com';

    // Get the RequiredApprovalsController instance
    $approvalsController = app(\App\Http\Controllers\RequiredApprovalsController::class);

    // Get the list of admins to notify
    $response = $approvalsController->getAdminsToNotify($requisitionForm->request_id);
    $data = $response->getData();

    \Log::info('Admins to notify response:', [
        'has_data' => !is_null($data),
        'admins_count' => isset($data->admins) ? count($data->admins) : 0,
        'full_response' => $data
    ]);

    if ($data && isset($data->admins) && count($data->admins) > 0) {
        
        // ========== TESTING LIMIT: Only process first 2 admins ==========
        // In production, remove this line to notify ALL admins
        $adminsToProcess = array_slice($data->admins, 0, 2); // Limit to first 2 admins for testing
        
        \Log::info('🔧 TEST MODE: Limiting to ' . count($adminsToProcess) . ' admin(s) out of ' . count($data->admins) . ' total. Remove this limit in production to notify ALL admins.');
        
        foreach ($adminsToProcess as $adminData) {
            try {
                \Log::info('Processing admin:', [
                    'admin_name' => $adminData->name,
                    'admin_email' => $adminData->email ?? 'no email',
                    'resource_count' => count($adminData->resources)
                ]);

                // Build resources array for the template - now using structured data
                $resources = [];
                foreach ($adminData->resources as $resource) {
                    $resources[] = [
                        'type' => $resource->type,
                        'name' => $resource->name
                    ];
                }

                // Also keep a simple string list for fallback if needed
                $resourcesList = collect($resources)
                    ->map(fn($r) => ucfirst($r['type']) . ': ' . $r['name'])
                    ->implode(', ');

                // Get formatted schedule using ScheduleFormatter
                $scheduleDisplay = $this->ScheduleFormatter->getDisplayString($requisitionForm);

                // Email subject
                $subject = 'CPU Booking System - Action Required: New Booking Request Needs Approval';

                // Email data - now passing structured resources array
                $emailData = [
                    'admin_name' => $adminData->name,
                    'requester_name' => $requisitionForm->first_name . ' ' . $requisitionForm->last_name,
                    'requester_email' => $requisitionForm->email,
                    'request_id' => $requisitionForm->request_id,
                    'access_code' => $requisitionForm->access_code,
                    'resources' => $resources, // This is now an array of objects with type/name
                    'resources_list' => $resourcesList, // Keep this for backward compatibility
                    'schedule_display' => $scheduleDisplay,
                    'purpose' => $requisitionForm->purpose->purpose_name ?? 'N/A',
                    'participants' => $requisitionForm->num_participants,
                    'admin_link' => url("/admin/requisition/{$requisitionForm->request_id}"),
                    'is_test' => true,
                    // Add equipment badge color style
                    'equipment_badge_color' => '#17a2b8'
                ];

                \Log::info('Attempting to send email with data:', [
                    'to' => $testEmail,
                    'subject' => $subject,
                    'template' => 'emails.admin-approval-request',
                    'has_admin_link' => isset($emailData['admin_link']),
                    'resources_structure' => 'array of objects' // Log the new structure
                ]);

                // TEMPORARY: Send to your email instead of actual admin email
                $mailSent = \Mail::send('emails.admin-approval-request', $emailData, function ($message) use ($subject, $testEmail, $adminData) {
                    $message->to($testEmail)
                        ->subject('[TEST - For ' . $adminData->name . '] ' . $subject)
                        ->from(config('mail.from.address'), config('mail.from.name'));
                });

                \Log::info('✓ Test admin approval email sent successfully to: ' . $testEmail . ' (originally for: ' . $adminData->name . ')');

            } catch (\Exception $e) {
                \Log::error('✗ Failed to send admin approval email: ' . $e->getMessage(), [
                    'admin_name' => $adminData->name,
                    'error_class' => get_class($e),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString()
                ]);
            }
        }
        
        // Log a warning if there were more admins that were skipped
        if (count($data->admins) > 2) {
            \Log::warning('⚠ TEST MODE: Skipped ' . (count($data->admins) - 2) . ' admin(s). In production, these would also receive notifications.');
        }
        
    } else {
        \Log::warning('⚠ No admins found to notify for request #' . $requisitionForm->request_id);
        
        // For testing, let's try to send a test email even if no admins are found
        try {
            \Log::info('Sending test email to verify mail configuration...');
            
            \Mail::raw('This is a test email to verify mail configuration is working.', function ($message) use ($testEmail) {
                $message->to($testEmail)
                    ->subject('Test Email - Mail Configuration Check')
                    ->from(config('mail.from.address'), config('mail.from.name'));
            });
            
            \Log::info('✓ Test email sent successfully');
        } catch (\Exception $e) {
            \Log::error('✗ Test email failed: ' . $e->getMessage());
        }
    }
    
    \Log::info('=== FINISHED ADMIN APPROVAL EMAILS ===');
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