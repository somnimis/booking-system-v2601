<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RequisitionFormController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\EquipmentController;
use App\Http\Controllers\FacilityController;
use App\Http\Controllers\FeedbackController;
use App\Http\Controllers\AdminApprovalController;
use Laravel\Sanctum\PersonalAccessToken;
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

    // Venue Picker Page - shows all parent facilities
    Route::get('/pick-a-venue', function () {
        return view('public.pick-a-venue');
    })->name('pick-a-venue');

    Route::get('/equipment-details/{id}', function ($id) {
        return view('public.equipment-details');
    })->name('equipment.details');

    // Facility Details Page
    Route::get('/facility/{facilityId}', function ($facilityId) {
        return view('public.facility-details', ['facilityId' => $facilityId]);
    })->name('facility.details');

    // Catalogs
    Route::view('/booking-catalog', 'public.booking-catalog');
    Route::get('/csrf-token', function () {
        return response()->json([
            'csrf_token' => csrf_token()
        ]);
    })->middleware('web');

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
    Route::get('/admin/login', function () {
        return view('admin.admin-login');
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
| Protected Admin Routes (Simple token-based auth)
|--------------------------------------------------------------------------
*/

// Helper function to authenticate from token
function authenticateFromRequest($request)
{
    $token = null;

    // Check URL query parameter first (for initial redirect after login)
    if ($token = $request->query('token')) {
        // Token found in URL
    }
    // Check cookie
    elseif ($token = $request->cookie('admin_token')) {
        // Token found in cookie
    }
    // Check session
    elseif ($token = $request->session()->get('admin_token')) {
        // Token found in session
    }
    // Check Authorization header (for API calls within page)
    elseif ($token = $request->bearerToken()) {
        // Token found in header
    }

    if ($token) {
        $accessToken = PersonalAccessToken::findToken($token);
        if ($accessToken && $accessToken->tokenable_type === 'App\Models\Admin') {
            auth('sanctum')->setUser($accessToken->tokenable);

            // Store token in cookie for future requests if it came from URL
            if ($request->query('token')) {
                cookie()->queue('admin_token', $token, 60 * 24 * 30);
                $request->session()->put('admin_token', $token);
            }

            return true;
        }
    }

    return false;
}

// Create a middleware-like function for admin routes
function requireAdminAuth($request, $callback)
{
    if (!authenticateFromRequest($request)) {
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }
        return redirect('/admin/login');
    }
    return $callback();
}

/*
|--------------------------------------------------------------------------
| Admin Dashboard & Core Pages
|--------------------------------------------------------------------------
*/

// Dashboard
Route::get('/admin/dashboard', function (Request $request) {
    return requireAdminAuth($request, function () use ($request) {
        return view('admin.dashboard');
    });
});

// Signatory Dashboard
Route::get('/admin/signatory/dashboard', function (Request $request) {
    return requireAdminAuth($request, function () use ($request) {
        return view('admin.signatory-dashboard');
    });
});

// Admin Profile
Route::get('/admin/profile/{adminId}', function (Request $request, $adminId) {
    return requireAdminAuth($request, function () use ($request, $adminId) {
        return view('admin.admin-profile', ['adminId' => $adminId]);
    });
});

/*
|--------------------------------------------------------------------------
| Admin Management Pages
|--------------------------------------------------------------------------
*/

// Admin Roles & Management
Route::get('/admin/admin-roles', function (Request $request) {
    return requireAdminAuth($request, function () use ($request) {
        return view('admin.admin-roles');
    });
});

// Departments
Route::get('/admin/departments', function (Request $request) {
    return requireAdminAuth($request, function () use ($request) {
        return view('admin.departments');
    });
});

// Services
Route::get('/admin/services', function (Request $request) {
    return requireAdminAuth($request, function () use ($request) {
        return view('admin.services');
    });
});

// Purposes
Route::get('/admin/purposes', function (Request $request) {
    return requireAdminAuth($request, function () use ($request) {
        return view('admin.purposes');
    });
});

/*
|--------------------------------------------------------------------------
| Requisition & Request Management
|--------------------------------------------------------------------------
*/

// Create Reservation
Route::get('/admin/reservations/create', function (Request $request) {
    return requireAdminAuth($request, function () use ($request) {
        return view('admin.create-reservation');
    });
});

// Reservations List
Route::get('/admin/reservations', function (Request $request) {
    return requireAdminAuth($request, function () use ($request) {
        return view('admin.reservations');
    });
});

// Pending Requests
Route::get('/admin/pending-requests', function (Request $request) {
    return requireAdminAuth($request, function () use ($request) {
        return view('admin.pending-requests');
    });
});

// Manage Requests
Route::get('/admin/manage-requests', function (Request $request) {
    return requireAdminAuth($request, function () use ($request) {
        return view('admin.manage-requests');
    });
});

// Requisition View
Route::get('/admin/requisition/{requestId}', function (Request $request, $requestId) {
    return requireAdminAuth($request, function () use ($request, $requestId) {
        return view('admin.request-view', ['requestId' => $requestId]);
    });
});

// Financials Edit
Route::get('/admin/requisition/{requestId}/financials', function (Request $request, $requestId) {
    return requireAdminAuth($request, function () use ($request, $requestId) {
        return view('admin.financials-edit', ['requestId' => $requestId]);
    });
});

// Form Review
Route::get('/admin/form-review/{requestId}', function (Request $request, $requestId) {
    return requireAdminAuth($request, function () use ($request, $requestId) {
        return view('admin.form-review', ['requestId' => $requestId]);
    });
});

// Archives
Route::get('/admin/archives', function (Request $request) {
    return requireAdminAuth($request, function () use ($request) {
        return view('admin.archives');
    });
})->name('admin.archives');

/*
|--------------------------------------------------------------------------
| Facility Management
|--------------------------------------------------------------------------
*/

// Add Facility
Route::get('/admin/add-facility', function (Request $request) {
    return requireAdminAuth($request, function () use ($request) {
        return view('admin.add-facility');
    });
});

// Manage Facilities
Route::get('/admin/manage-facilities', function (Request $request) {
    return requireAdminAuth($request, function () use ($request) {
        return view('admin.manage-facilities');
    });
});

// Edit Facility
Route::get('/admin/edit-facility', [FacilityController::class, 'edit'])->name('admin.edit-facility');

/*
|--------------------------------------------------------------------------
| Equipment Management
|--------------------------------------------------------------------------
*/

// Add Equipment
Route::get('/admin/add-equipment', function (Request $request) {
    return requireAdminAuth($request, function () use ($request) {
        return view('admin.add-equipment');
    });
});

// Manage Equipment
Route::get('/admin/manage-equipment', function (Request $request) {
    return requireAdminAuth($request, function () use ($request) {
        return view('admin.manage-equipment');
    });
});

// Edit Equipment
Route::get('/admin/edit-equipment', [EquipmentController::class, 'edit'])->name('admin.edit-equipment');

// Scan Equipment (Equipment Tracker)
Route::get('/admin/scan-equipment', function (Request $request) {
    return requireAdminAuth($request, function () use ($request) {
        return view('admin.scan-equipment');
    });
});

// Asset Tracking
Route::get('/admin/asset-tracking', function (Request $request) {
    return requireAdminAuth($request, function () use ($request) {
        return view('admin.asset-tracking');
    });
});

/*
|--------------------------------------------------------------------------
| Calendar & Feedback
|--------------------------------------------------------------------------
*/

// Calendar
Route::get('/admin/calendar', function (Request $request) {
    return requireAdminAuth($request, function () use ($request) {
        return view('admin.calendar');
    });
});

// Calendar v2
Route::get('/admin/calendarv2', function (Request $request) {
    return requireAdminAuth($request, function () use ($request) {
        return view('admin.admin-calendar');
    });
});

// User Feedback
Route::get('/admin/user-feedback', function (Request $request) {
    return requireAdminAuth($request, function () use ($request) {
        return view('admin.user-feedback');
    });
});

// Feedback Data API
Route::get('/admin/feedback-data', [FeedbackController::class, 'getFeedbackData'])->name('admin.feedback.data');

// Feedback Stats API
Route::get('/admin/feedback-stats', [FeedbackController::class, 'getFeedbackStats'])->name('admin.feedback.stats');

});
