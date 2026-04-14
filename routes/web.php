<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RequisitionFormController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\EquipmentController;
use App\Http\Controllers\FacilityController;
use App\Http\Controllers\FeedbackController;
use App\Http\Controllers\AdminApprovalController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/
Route::middleware('web')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Home Page
    |--------------------------------------------------------------------------
    */
    Route::get('/', function () {
        return view('public.index');
    });

    /*
    |--------------------------------------------------------------------------
    | Public Routes
    |--------------------------------------------------------------------------
    */
    // Catalogs
    Route::view('/booking-catalog', 'public.booking-catalog');
Route::get('/csrf-token', function() {
    return response()->json([
        'csrf_token' => csrf_token()
    ]);
})->middleware('web');

    // About Pages
    Route::view('/about-equipment', 'public.about-equipment');
    Route::view('/about-services', 'public.about-services');
    Route::view('/about-facilities', 'public.about-facilities');
    Route::view('/about-personnel', 'public.about-personnel');

    // User Pages
    Route::view('/home', 'public.index');
    Route::view('/events-calendar', 'public.events-calendar');
    Route::view('/reservation-form', 'public.reservation-form');
    Route::view('/user-feedback', 'public.user-feedback');
    Route::view('/your-bookings', 'public.your-bookings');
    Route::view('/user-payment', 'public.user-payment');
    Route::view('/policies', 'public.policies');
    Route::view('/inquiries', 'public.inquiries');

    // Official Receipt
    Route::view('/official-receipt', 'public.official-receipt')->name('official-receipt.test');
    Route::get('/official-receipt/{requestId}', [AdminApprovalController::class, 'generateOfficialReceipt'])
        ->name('official-receipt.generate');

    /*
    |--------------------------------------------------------------------------
    | Authentication Routes
    |--------------------------------------------------------------------------
    */
    Route::get('/login', function () {
        return view('admin.admin-login');  // Point to your actual admin login file
    })->name('login');

    /*
    |--------------------------------------------------------------------------
    | Requisition Form Routes (AJAX/Form submissions)
    |--------------------------------------------------------------------------
    */
    Route::prefix('requisition')->group(function () {
        Route::post('/save-user-info', [RequisitionFormController::class, 'saveUserInfo']);
        Route::post('/add-item', [RequisitionFormController::class, 'addToForm']);
        Route::post('/remove-item', [RequisitionFormController::class, 'removeFromForm']);
        Route::get('/get-items', [RequisitionFormController::class, 'getItems']);
        Route::get('/calculate-fees', [RequisitionFormController::class, 'calculateFees']);
        Route::post('/check-availability', [RequisitionFormController::class, 'checkAvailability']);
        Route::post('/temp-upload', [RequisitionFormController::class, 'tempUpload']);
        Route::post('/submit', [RequisitionFormController::class, 'submitForm']);
        Route::post('/clear-session', [RequisitionFormController::class, 'clearSession']);
    });

    /*
    |--------------------------------------------------------------------------
    | Admin Routes (All preserve original URLs)
    |--------------------------------------------------------------------------
    */
    // Dashboard & Profile
    Route::get('/admin/profile/{adminId}', function ($adminId) {
        return view('admin.admin-profile', ['adminId' => $adminId]);
    });
    Route::view('/admin/dashboard', 'admin.dashboard');
    Route::view('/admin/signatory/dashboard', 'admin.signatory-dashboard');

    // Admin Management
    Route::view('/admin/admin-roles', 'admin.admin-roles');
    Route::view('/admin/login', 'admin.admin-login');
    Route::view('/admin/admin-page-template', 'admin.admin-page-template');
    Route::get('/admin-roles', [AdminController::class, 'adminRoles'])->name('admin.roles');

    // Calendar
    Route::view('/admin/reservations', 'admin.reservations');
    Route::view('/admin/calendar', 'admin.calendar');
    Route::view('/admin/reservations', 'admin.reservations');

    // Pending Requests
    Route::view('/admin/pending-requests', 'admin.pending-requests');

    // Asset Tracking
    Route::view('/admin/asset-tracking', 'admin.asset-tracking');

    // User Feedback
    Route::view('/admin/user-feedback', 'admin.user-feedback');

    // Test calendar event new version
    Route::view('/admin/calendarv2', 'admin.admin-calendar');

    // Equipment Management
    Route::view('/admin/add-equipment', 'admin.add-equipment');
    Route::view('/admin/manage-equipment', 'admin.manage-equipment');
    Route::get('/admin/edit-equipment', [EquipmentController::class, 'edit'])->name('admin.edit-equipment');
    Route::view('/admin/scan-equipment', 'admin.scan-equipment');

    // Facility Management
    Route::view('/admin/add-facility', 'admin.add-facility');
    Route::view('/admin/manage-facilities', 'admin.manage-facilities');
    Route::get('/admin/edit-facility', [FacilityController::class, 'edit'])->name('admin.edit-facility');

    // Request Management
    Route::view('/admin/manage-requests', 'admin.manage-requests');
    Route::get('/admin/requisition/{requestId}', function ($requestId) {
        return view('admin.request-view', ['requestId' => $requestId]);
    });

    Route::get('/admin/form-review/{requestId}', function ($requestId) {
        return view('admin.form-review', ['requestId' => $requestId]);
    });

    // Feedback Management
    Route::get('/admin/feedback-data', [FeedbackController::class, 'getFeedbackData'])->name('admin.feedback.data');
    Route::get('/admin/feedback-stats', [FeedbackController::class, 'getFeedbackStats'])->name('admin.feedback.stats');

    // Archives
    Route::get('/admin/archives', function () {
        return view('admin.archives');
    })->name('admin.archives');
});

Route::get('/test-email', function () {

    Mail::raw('This is a test email from Laravel SMTP.', function ($message) {
        $message->to('yourtestemail@gmail.com')
            ->subject('Laravel SMTP Test');
    });

    return 'Email sent (check inbox or spam).';
});