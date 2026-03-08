<?php

use App\Http\Controllers\EquipmentItemController;
use App\Http\Controllers\RequiredApprovalsController;
use App\Http\Controllers\UserRequisitionController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

use App\Http\Controllers\AdminAuthController;
use App\Http\Controllers\AdminApprovalController;
use App\Http\Controllers\AdminActionsController;
use App\Http\Controllers\RequisitionFormController;
use App\Http\Controllers\FacilityController;
use App\Http\Controllers\EquipmentController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\FeedbackController;
use App\Http\Controllers\ScannerController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\AdminFacilityController;
use App\Http\Controllers\Dropdowns\FacilityCategoryController;
use App\Http\Controllers\Dropdowns\FacilitySubcategoryController;
use App\Http\Controllers\Dropdowns\EquipmentCategoryController;
use App\Http\Controllers\Dropdowns\DepartmentController;
use App\Http\Controllers\Dropdowns\AvailabilityStatusController;
use App\Http\Controllers\FormStatusController;
use App\Http\Controllers\Dropdowns\ConditionController;
use App\Http\Controllers\Dropdowns\RequisitionPurposeController;
use App\Http\Controllers\CalendarEventsController;
use App\Http\Controllers\ExtraServicesController;
use App\Http\Controllers\ReservationListingsController;
use Illuminate\Support\Facades\Log;

// ==================== PUBLIC ROUTES ==================== //

Route::post('/log-client-error', function (Request $request) {
    // Log the JSON payload into laravel.log
    Log::error('Client error:', $request->all());

    return response()->json(['status' => 'logged']);
});

// ---------------- Authentication ---------------- //
Route::post('/admin/login', [AdminAuthController::class, 'login'])->middleware('throttle:5,1');
Route::post('/login', function (Request $request) {
    $credentials = $request->validate([
        'email' => 'required|email',
        'password' => 'required',
    ]);

    if (!Auth::attempt($credentials)) {
        return response()->json(['message' => 'Invalid credentials'], 401);
    }

    $user = Auth::user();
    $token = $user->createToken('api-token')->plainTextToken;

    return response()->json([
        'token' => $token,
        'user' => $user,
    ]);
});

Route::get('/admins/departments', [AdminController::class, 'getAdminDepartments']);

// ---------------- Booking Listings ---------------- //
Route::get('/equipment', [EquipmentController::class, 'publicIndex']);
Route::get('/facilities', [FacilityController::class, 'publicIndex']);

// Public catalog endpoints
Route::prefix('facilities')->group(function () {
    Route::get('/venues', [FacilityCategoryController::class, 'getVenues']);
    Route::get('/rooms', [FacilityCategoryController::class, 'getRooms']);
    Route::get('/buildings', [FacilityCategoryController::class, 'getBuildings']);
    Route::get('/{id}', [FacilityCategoryController::class, 'show']);
});

Route::prefix('facility-categories')->group(function () {
    Route::get('/venues', [FacilityCategoryController::class, 'getVenueCategories']);
    Route::get('/rooms', [FacilityCategoryController::class, 'getRoomCategories']);
});

// ---------------- Calendar Events ---------------- //
Route::get('/requisition-forms/calendar-events', [CalendarEventsController::class, 'getCalendarEvents']);
Route::get('/admin/requisition-forms/calendar-events', [CalendarEventsController::class, 'getCalendarEvents'])
    ->middleware('auth:admin');
Route::get('/calendar-events/all', [CalendarEventsController::class, 'getAllForCalendar']);
Route::get('/calendar-events', [CalendarEventsController::class, 'index']);
Route::post('/calendar-events', [CalendarEventsController::class, 'store']);
Route::delete('/calendar-events/{id}', [CalendarEventsController::class, 'destroy']);
Route::get('/calendar-events/types', [CalendarEventsController::class, 'getEventTypes']);

// ---------------- Lookup Tables ---------------- //
Route::get('/admin-role', [AdminController::class, 'adminRoles']);
Route::get('/departments', [DepartmentController::class, 'index']);
Route::get('/availability-statuses', [AvailabilityStatusController::class, 'index']);
Route::get('/form-statuses', [FormStatusController::class, 'index']);
Route::get('/conditions', [ConditionController::class, 'index']);
Route::get('/equipment/{id}', [EquipmentController::class, 'show']);
Route::get('/admin/facilities/{id}', [FacilityController::class, 'show']);
Route::get('/equipment-categories', [EquipmentCategoryController::class, 'index']);
Route::get('/equipment-items', [EquipmentItemController::class, 'index']);
Route::get('/facility-categories', [FacilityCategoryController::class, 'index']);
Route::get('/facility-categories/index', [FacilityCategoryController::class, 'indexWithSubcategories']);
Route::get('/facility-subcategories/{category}', [FacilitySubcategoryController::class, 'index']);
Route::get('/requisition-purposes', [RequisitionPurposeController::class, 'index']);

// ---------------- Extra Services ---------------- //
Route::get('/extra-services', [ExtraServicesController::class, 'index']);
Route::post('/extra-services', [ExtraServicesController::class, 'store']);
Route::put('/extra-services/{service_id}', [ExtraServicesController::class, 'update']);
Route::post('/extra-services/assign', [ExtraServicesController::class, 'assignService'])
    ->middleware('auth:sanctum');
Route::get('/admin-services/{adminId?}', [ExtraServicesController::class, 'getAdminServices']);
Route::delete('/admin-services/{adminServiceId}', [ExtraServicesController::class, 'unassignService']);
// ---------------- Requisition Forms (public) ---------------- //
Route::prefix('requisition')->middleware(['web'])->group(function () {
    Route::post('/save-request-info', [RequisitionFormController::class, 'saveRequestInfo']);
    Route::post('/add-item', [RequisitionFormController::class, 'addToForm']);
    Route::post('/remove-item', [RequisitionFormController::class, 'removeFromForm']);
    Route::get('/get-items', [RequisitionFormController::class, 'getItems']);
    Route::get('/calculate-fees', [RequisitionFormController::class, 'calculateFees']);
    Route::post('/check-availability', [RequisitionFormController::class, 'checkAvailability']);
    Route::post('/temp-upload', [RequisitionFormController::class, 'tempUpload']);
    Route::post('/submit', [RequisitionFormController::class, 'submitForm']);
    Route::post('/clear-session', [RequisitionFormController::class, 'clearSession']);
});

// ---------------- Requester Routes (public) ---------------- //
Route::prefix('requester')->middleware(['web'])->group(function () {
    Route::get('/form/{accessCode}', [UserRequisitionController::class, 'getFormByAccessCode']);
    Route::get('/{requestId}/receipt', [AdminApprovalController::class, 'getOfficialReceipt']);
});

// ---------------- User Actions (public) ---------------- //
Route::post('/feedback', [FeedbackController::class, 'store'])->middleware(['web']);
Route::post('/requester/requisition/{requestId}/cancel', [UserRequisitionController::class, 'cancelRequestPublic'])->middleware(['web']);
Route::post('/requester/requisition/{requestId}/upload-receipt', [UserRequisitionController::class, 'uploadPaymentReceipt'])->middleware(['web']);


// ---------------- Scanner Routes ---------------- //
Route::prefix('scanner')->group(function () {
    Route::post('/scan', [ScannerController::class, 'scan']);
    Route::post('/borrow', [ScannerController::class, 'borrow']);
    Route::post('/return', [ScannerController::class, 'return']);
    Route::put('/update-item/{itemId}', [ScannerController::class, 'updateItem']);
});

// ---------------- Barcode Generation ---------------- //
Route::post('/admin/generate-barcode', function (Request $request) {
    try {
        $equipmentId = $request->input('equipment_id');
        $itemId = $request->input('item_id');

        $barcodeValue = \App\Services\BarcodeService::generateEquipmentBarcode($equipmentId, $itemId);

        return response()->json([
            'status' => 'success',
            'barcode' => $barcodeValue
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => 'Failed to generate barcode: ' . $e->getMessage()
        ], 500);
    }
});

// ==================== PROTECTED ROUTES (auth:sanctum) ==================== //

Route::middleware('auth:sanctum')->group(function () {

    // ---------------- Admin Management ---------------- //
    Route::get('/admins', [AdminController::class, 'getAllAdmins']);
    Route::get('/admins/{admin}/edit', [AdminController::class, 'getAdminForEdit']);
    Route::get('/admins/{admin}', [AdminController::class, 'getAdminInfo']);
    Route::post('/admins', [AdminController::class, 'store']);
    Route::delete('/admins/{admin}', [AdminController::class, 'deleteAdmin']);
    Route::put('/admins/{admin}', [AdminController::class, 'update']);
    Route::post('/admin/update/{admin}', [AdminController::class, 'update']);
    Route::post('/admin/update-photo', [AdminController::class, 'updatePhoto']);
    Route::post('/admin/update-photo-records', [AdminController::class, 'updatePhotoRecords']);
    Route::post('/admin/delete-cloudinary-image', [AdminController::class, 'deleteCloudinaryImage']);

    // ---------------- Admin Profile & Notifications ---------------- //
    Route::get('/admin/profile', function (Request $request) {
        $user = $request->user();
        $user->load(['role', 'departments']);
        return response()->json($user);
    });
    Route::get('/admin/notifications', [NotificationController::class, 'getNotifications']);
    Route::post('/admin/notifications/mark-read/{notificationId?}', [NotificationController::class, 'markAsRead']);
    Route::post('/admin/notifications/mark-all-read', [NotificationController::class, 'markAllAsRead']);
    Route::get('/feedback', [FeedbackController::class, 'index']);
    Route::post('/admin/notifications/requisition/{requisitionId}/mark-as-read', [NotificationController::class, 'markRequisitionAsRead']);

    // ---------------- Equipment Management ---------------- //
    Route::post('admin/equipment', [EquipmentController::class, 'store']);
    Route::put('admin/equipment/{equipmentId}', [EquipmentController::class, 'update']);
    Route::delete('/admin/equipment/{equipmentId}', [EquipmentController::class, 'destroy']);

    // Equipment Images
    Route::post('/admin/upload', [EquipmentController::class, 'uploadImage']);
    Route::post('/admin/bulk-upload', [EquipmentController::class, 'uploadMultipleImages']);
    Route::delete('/admin/equipment/{equipmentId}/images/{imageId}', [EquipmentController::class, 'deleteImage']);
    Route::post('/admin/reorder', [EquipmentController::class, 'reorderImages']);
    Route::post('/admin/equipment/{equipmentId}/images/save', [EquipmentController::class, 'saveImageReference']);

    // Equipment Items
    Route::get('admin/equipment/{equipmentId}/items', [EquipmentController::class, 'getItems']);
    Route::post('admin/equipment/{equipmentId}/items', [EquipmentController::class, 'storeItem']);
    Route::put('admin/equipment/{equipmentId}/items/{itemId}', [EquipmentController::class, 'updateItem']);
    Route::delete('admin/equipment/{equipmentId}/items/{itemId}', [EquipmentController::class, 'deleteItem']);

    // ---------------- Facility Management ---------------- //
    Route::post('admin/add-facility', [FacilityController::class, 'store']);
    Route::put('admin/facilities/{facilityId}', [FacilityController::class, 'update']);
    Route::delete('/admin/facilities/{facilityId}', [FacilityController::class, 'destroy']);
    Route::get('facilities/get-categories', [FacilityController::class, 'create']);

    // Facility Images
    Route::post('/admin/upload', [FacilityController::class, 'uploadImage']);
    Route::post('/admin/bulk-upload', [FacilityController::class, 'uploadMultipleImages']);
    Route::delete('/admin/facilities/{facilityId}/images/{imageId}', [FacilityController::class, 'deleteImage']);
    Route::post('/admin/reorder', [FacilityController::class, 'reorderImages']);
    Route::post('/admin/facilities/{facilityId}/images/save', [FacilityController::class, 'saveImageReference']);

    // ---------------- Admin-Facility assignment routes ---------------- //
    Route::prefix('admin-facilities')->group(function () {
        Route::post('/', [AdminFacilityController::class, 'store']);
        Route::post('/batch', [AdminFacilityController::class, 'storeMultiple']);
        Route::get('/admin/{adminId}', [AdminFacilityController::class, 'getAdminFacilities']);
        Route::get('/facility/{facilityId}', [AdminFacilityController::class, 'getFacilityAdmins']);
        Route::delete('/{id}', [AdminFacilityController::class, 'destroy']);
    });

    Route::middleware(['auth:sanctum'])->group(function () {
        Route::get('/requisitions/{requestId}/approval-status', [RequiredApprovalsController::class, 'getApprovalStatus']);
        Route::get('/requisitions/{requestId}/approval-progress', [RequiredApprovalsController::class, 'getApprovalProgress']);
        Route::get('/requisitions/{requestId}/can-approve', [RequiredApprovalsController::class, 'canAdminApprove']);
    });

    // ---------------- Requisition Management ---------------- //

    Route::get('/admin/requisition-forms', [ReservationListingsController::class, 'pendingRequests']); // not used

    Route::get('/admin/pending-requests', [ReservationListingsController::class, 'paginatedPendingRequests']);
    Route::get('/admin/ongoing-requests', [ReservationListingsController::class, 'paginatedOngoingRequests']);
    Route::get('/admin/pending-requests-count', [ReservationListingsController::class, 'getPendingCount']);
    Route::get('/admin/requisition-forms/{requestId}', [ReservationListingsController::class, 'getRequisitionFormById']); // single form
    Route::put('admin/requisition-forms/{requestId}/calendar-info', [CalendarEventsController::class, 'updateCalendarInfo']);
    Route::get('/admin/completed-requests', [ReservationListingsController::class, 'completedRequests']);
    Route::get('/admin/archives', [ReservationListingsController::class, 'getArchivedRequisitions']);
    Route::post('/admin/requisition/{requestId}/mark-scheduled', [AdminActionsController::class, 'markAsScheduled']); // UPDATED
    Route::get('/admin/requisition/{requestId}/approval-history', [ReservationListingsController::class, 'getApprovalHistory']);
    Route::get('/admin/requisition/{requestId}/equipment-status', [AdminApprovalController::class, 'getEquipmentStatus']);

    // Form Management
    Route::prefix('admin/requisition')->group(function () {

        // Make manual reservation
        Route::post('create', [AdminActionsController::class, 'createReservation']);
        // Fees & Payments
        Route::post('/{requestId}/fee', [AdminActionsController::class, 'addFee']);
        Route::post('/{requestId}/discount', [AdminActionsController::class, 'addDiscount']);
        Route::post('/{requestId}/late-penalty', [AdminActionsController::class, 'addLatePenalty']);
        Route::post('/{requestId}/remove-late-penalty', [AdminActionsController::class, 'removeLatePenalty']);
        Route::delete('/{requestId}/fee/{feeId}', [AdminActionsController::class, 'removeFee']);
        Route::get('/{requestId}/fees', [ReservationListingsController::class, 'getRequisitionFees']); // UPDATED
        Route::post('/{requestId}/waive', [AdminActionsController::class, 'waiveItems']);

        // Status Management
        Route::post('/{requestId}/update-status', [AdminActionsController::class, 'updateStatus']); // UPDATED
        Route::post('/{requestId}/approve', [AdminApprovalController::class, 'approveRequest']);
        Route::post('/{requestId}/reject', [AdminActionsController::class, 'rejectRequest']);
        Route::post('{requestId}/cancel', [AdminActionsController::class, 'cancelForm']);
        Route::post('/{requestId}/finalize', [AdminActionsController::class, 'finalizeForm']);
        Route::post('/{requestId}/close', [AdminActionsController::class, 'closeForm']);
        Route::post('/{requestId}/mark-returned', [AdminActionsController::class, 'markReturned']);

        // Automatic status update routes
        Route::post('/admin/auto-mark-ongoing', [AdminApprovalController::class, 'autoMarkOngoingForms']);
        Route::post('/admin/auto-mark-late', [AdminApprovalController::class, 'autoMarkLateForms']);
        Route::post('/admin/auto-update-all', [AdminApprovalController::class, 'autoUpdateAllStatuses']);

        // Comments
        Route::post('/{requestId}/comment', [AdminActionsController::class, 'addComment']);
        Route::get('/{requestId}/comments', [AdminActionsController::class, 'getComments']);

        // Receipt
        Route::get('/{requestId}/receipt', [AdminApprovalController::class, 'getOfficialReceipt']);
    });

    // ---------------- Cloudinary Management ---------------- //
    Route::post('/admin/cloudinary/delete', function (Request $request) {
        // ... (unchanged)
    });

    // ---------------- Logout ---------------- //
    Route::post('/admin/logout', [AdminAuthController::class, 'logout']);
});