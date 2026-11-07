<?php

use App\Http\Controllers\AdminActionsController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RequisitionFormController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\EquipmentController;
use App\Http\Controllers\FacilityController;
use App\Http\Controllers\FeedbackController;
use App\Http\Controllers\UserRequisitionController;
use App\Http\Controllers\CheckAvailabilityController;
use App\Http\Controllers\AdminApprovalController;
use App\Http\Middleware\RequireAdminAuth;
use Illuminate\Http\Request;

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

    Route::get('/pick-a-venue', function () {
        return view('public.pick-a-venue');
    })->name('pick-a-venue');

    Route::get('/equipment-details/{id}', function ($id) {
        return view('public.equipment-details');
    })->name('equipment.details');

    Route::get('/facility/{facilityId}', function ($facilityId) {
        return view('public.facility-details', ['facilityId' => $facilityId]);
    })->name('facility.details');

    Route::view('/booking-catalog', 'public.booking-catalog');
    Route::get('/csrf-token', function () {
        return response()->json([
            'csrf_token' => csrf_token()
        ]);
    })->middleware('web');

    Route::view('/home', 'public.index')->name('home');
    Route::view('/events-calendar', 'public.events-calendar');
    Route::get('/reservation-form', \App\Livewire\ReservationForm::class)->name('reservation.form');
    Route::view('/user-feedback', 'public.user-feedback');
    Route::view('/your-bookings', 'public.your-bookings');
    Route::view('/user-payment', 'public.user-payment');
    Route::view('/policies', 'public.policies');
    Route::view('/inquiries', 'public.inquiries');
    Route::view('/user-guide', 'public.user-guide')->name('user-guide');
    Route::view('/privacy-policy', 'public.privacy-policy')->name('privacy-policy');
    Route::view('/terms-of-use', 'public.terms-of-use')->name('terms-of-use');
    Route::view('/contact-support', 'public.contact-support')->name('contact-support');

    Route::get('/official-receipt/{requestId}', [AdminActionsController::class, 'generateOfficialReceipt'])
        ->name('official-receipt.generate');
    Route::view('/official-receipt', 'public.official-receipt')
        ->name('official-receipt');

    Route::get('/verify', [UserRequisitionController::class, 'showVerifyPage'])->name('verify.page');
    Route::post('/verify', [UserRequisitionController::class, 'verifyPermit'])->name('verify.submit');

    Route::get('/admin/login', function () {
        return view('admin.admin-login');
    })->name('login');

    Route::prefix('requisition')->group(function () {
        Route::post('/save-user-info', [RequisitionFormController::class, 'saveUserInfo']);
        Route::post('/add-item', [RequisitionFormController::class, 'addToForm']);
        Route::post('/remove-item', [RequisitionFormController::class, 'removeFromForm']);
        Route::get('/get-items', [RequisitionFormController::class, 'getItems']);
        Route::get('/calculate-fees', [RequisitionFormController::class, 'calculateFees']);
        Route::post('/check-availability', [CheckAvailabilityController::class, 'checkAvailability']);
        Route::post('/temp-upload', [RequisitionFormController::class, 'tempUpload']);
        Route::post('/submit', [RequisitionFormController::class, 'submitForm'])->name('requisition.submit');
        Route::post('/clear-session', [RequisitionFormController::class, 'clearSession']);
    });

    /*
    |--------------------------------------------------------------------------
    | Protected Admin Routes (Using Middleware)
    |--------------------------------------------------------------------------
    */

    // Apply the middleware to all routes inside this group
    Route::middleware([RequireAdminAuth::class])->group(function () {

        // Dashboard
        Route::get('/admin/dashboard', function () {
            return view('admin.dashboard');
        });

        // Signatory Dashboard
        Route::get('/admin/signatory/dashboard', function () {
            return view('admin.signatory-dashboard');
        });

        // Admin Profile
        Route::get('/admin/profile/{adminId}', function ($adminId) {
            return view('admin.admin-profile', ['adminId' => $adminId]);
        });

        // Admin Management Pages
        Route::get('/admin/admin-roles', function () {
            return view('admin.admin-roles');
        });

        Route::get('/admin/departments', function () {
            return view('admin.departments');
        });

        Route::get('/admin/services', function () {
            return view('admin.services');
        });

        Route::get('/admin/purposes', function () {
            return view('admin.purposes');
        });

        // Requisition & Request Management
        Route::get('/admin/reservations/create', function () {
            return view('admin.create-reservation');
        });

        Route::get('/admin/pending-requests', function () {
            return view('admin.pending-requests');
        });

        Route::get('/admin/actionable-requests', function () {
            return view('admin.actionable-requests');
        });

        Route::get('/admin/requisition/{requestId}', function ($requestId) {
            return view('admin.request-view', ['requestId' => $requestId]);
        });

        Route::get('/admin/requisition/{requestId}/financials', function ($requestId) {
            return view('admin.financials-edit', ['requestId' => $requestId]);
        });

        Route::get('/admin/form-review/{requestId}', function ($requestId) {
            return view('admin.form-review', ['requestId' => $requestId]);
        });

        Route::get('/admin/archives', function () {
            return view('admin.archives');
        })->name('admin.archives');

        // Facility Management
        Route::get('/admin/add-facility', function () {
            return view('admin.add-facility');
        });

        Route::get('/admin/manage-facilities', function () {
            return view('admin.manage-facilities');
        });

        Route::get('/admin/edit-facility', [FacilityController::class, 'edit'])->name('admin.edit-facility');

        // Equipment Management
        Route::get('/admin/add-equipment', function () {
            return view('admin.add-equipment');
        });

        Route::get('/admin/manage-equipment', function () {
            return view('admin.manage-equipment');
        });

        Route::get('/admin/edit-equipment', [EquipmentController::class, 'edit'])->name('admin.edit-equipment');

        Route::get('/admin/scan-equipment', function () {
            return view('admin.scan-equipment');
        });

        Route::get('/admin/asset-tracking', function () {
            return view('admin.asset-tracking');
        });

        // Calendar & Feedback
        Route::get('/admin/calendar', function () {
            return view('admin.admin-calendar');
        });

        Route::get('/admin/user-feedback', function () {
            return view('admin.user-feedback');
        });

        // Feedback APIs
        Route::get('/admin/feedback-data', [FeedbackController::class, 'getFeedbackData'])->name('admin.feedback.data');
        Route::get('/admin/feedback-stats', [FeedbackController::class, 'getFeedbackStats'])->name('admin.feedback.stats');

    });

});