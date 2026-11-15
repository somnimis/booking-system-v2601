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
    })->name('home.index');

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

    Route::view('/booking-catalog', 'public.booking-catalog')->name('booking-catalog');
    Route::get('/csrf-token', function () {
        return response()->json([
            'csrf_token' => csrf_token()
        ]);
    })->middleware('web')->name('csrf-token');

    Route::view('/home', 'public.index')->name('home');
    Route::view('/events-calendar', 'public.events-calendar')->name('events-calendar');
    Route::get('/reservation-form', \App\Livewire\ReservationForm::class)->name('reservation.form');
    Route::view('/user-feedback', 'public.user-feedback')->name('user-feedback');
    Route::view('/your-bookings', 'public.your-bookings')->name('your-bookings');
    Route::view('/user-payment', 'public.user-payment')->name('user-payment');
    Route::view('/policies', 'public.policies')->name('policies');
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

    Route::prefix('requisition')->name('requisition.')->group(function () {
        Route::post('/save-user-info', [RequisitionFormController::class, 'saveUserInfo'])->name('save-user-info');
        Route::post('/add-item', [RequisitionFormController::class, 'addToForm'])->name('add-item');
        Route::post('/remove-item', [RequisitionFormController::class, 'removeFromForm'])->name('remove-item');
        Route::get('/get-items', [RequisitionFormController::class, 'getItems'])->name('get-items');
        Route::get('/calculate-fees', [RequisitionFormController::class, 'calculateFees'])->name('calculate-fees');
        Route::post('/check-availability', [CheckAvailabilityController::class, 'checkAvailability'])->name('check-availability');
        Route::post('/temp-upload', [RequisitionFormController::class, 'tempUpload'])->name('temp-upload');
        Route::post('/submit', [RequisitionFormController::class, 'submitForm'])->name('submit');
        Route::post('/clear-session', [RequisitionFormController::class, 'clearSession'])->name('clear-session');
    });

    /*
    |--------------------------------------------------------------------------
    | Protected Admin Routes (Using Middleware)
    |--------------------------------------------------------------------------
    */
    Route::middleware([RequireAdminAuth::class])->prefix('admin')->name('admin.')->group(function () {

        // Dashboard
        Route::get('/dashboard', function () {
            return view('admin.dashboard');
        })->name('dashboard');

        // Signatory Dashboard
        Route::get('/signatory/dashboard', function () {
            return view('admin.signatory-dashboard');
        })->name('signatory.dashboard');

        // Admin Profile
        Route::get('/profile/{adminId}', function ($adminId) {
            return view('admin.admin-profile', ['adminId' => $adminId]);
        })->name('profile');

        // Admin Management Pages
        Route::get('/admin-roles', function () {
            return view('admin.admin-roles');
        })->name('admin-roles');

        Route::get('/departments', function () {
            return view('admin.departments');
        })->name('departments');

        Route::get('/services', function () {
            return view('admin.services');
        })->name('services');

        Route::get('/purposes', function () {
            return view('admin.purposes');
        })->name('purposes');

        // Requisition & Request Management
        Route::get('/reservations/create', function () {
            return view('admin.create-reservation');
        })->name('reservations.create');

        Route::get('/pending-requests', function () {
            return view('admin.pending-requests');
        })->name('pending-requests');

        Route::get('/actionable-requests', function () {
            return view('admin.actionable-requests');
        })->name('actionable-requests');

        Route::get('/requisition/{requestId}', function ($requestId) {
            return view('admin.request-view', ['requestId' => $requestId]);
        })->name('requisition.view');

        Route::get('/requisition/{requestId}/financials', function ($requestId) {
            return view('admin.financials-edit', ['requestId' => $requestId]);
        })->name('requisition.financials');

        Route::get('/form-review/{requestId}', function ($requestId) {
            return view('admin.form-review', ['requestId' => $requestId]);
        })->name('form-review');

        Route::get('/transactions', function () {
            return view('admin.transactions');
        })->name('transactions');

        // Facility Management
        Route::get('/add-facility', function () {
            return view('admin.add-facility');
        })->name('add-facility');

        Route::get('/manage-facilities', function () {
            return view('admin.manage-facilities');
        })->name('manage-facilities');

        Route::get('/edit-facility', [FacilityController::class, 'edit'])->name('edit-facility');

        // Equipment Management
        Route::get('/add-equipment', function () {
            return view('admin.add-equipment');
        })->name('add-equipment');

        Route::get('/manage-equipment', function () {
            return view('admin.manage-equipment');
        })->name('manage-equipment');

        Route::get('/edit-equipment', [EquipmentController::class, 'edit'])->name('edit-equipment');

        Route::get('/scan-equipment', function () {
            return view('admin.scan-equipment');
        })->name('scan-equipment');

        Route::get('/asset-tracking', function () {
            return view('admin.asset-tracking');
        })->name('asset-tracking');

        // Calendar & Feedback
        Route::get('/calendar', function () {
            return view('admin.admin-calendar');
        })->name('calendar');

        Route::get('/user-feedback', function () {
            return view('admin.user-feedback');
        })->name('user-feedback');

        // Feedback APIs
        Route::get('/feedback-data', [FeedbackController::class, 'getFeedbackData'])->name('feedback.data');
        Route::get('/feedback-stats', [FeedbackController::class, 'getFeedbackStats'])->name('feedback.stats');

    });

});