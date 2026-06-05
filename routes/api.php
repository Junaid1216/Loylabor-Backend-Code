<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\DistrictController;
use App\Http\Controllers\Api\TechnicianController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Home Services Mobile API (prefix: /api)
|--------------------------------------------------------------------------
| Local base URL example:
| http://localhost/homeservices-12Mar2026/api
*/

Route::get('/', function () {
    return response()->json([
        'success' => true,
        'message' => 'Home Services API is running.',
        'base_url' => url('/api'),
        'public_endpoints' => [
            'POST /api/register',
            'POST /api/verify-otp',
            'POST /api/login',
            'GET  /api/districts',
            'POST /api/forgot-password',
            'POST /api/reset-password',
            'GET  /api/technicians',
        ],
        'auth_header' => 'Authorization: Bearer {token}',
    ]);
});

// Public
Route::post('/register', [AuthController::class, 'register']);
Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);
Route::post('/login', [AuthController::class, 'login']);
Route::get('/districts', [DistrictController::class, 'index']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);
Route::get('/technicians', [TechnicianController::class, 'getTechnicians']);

// Protected
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/profile', [AuthController::class, 'profile']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // Technician
    Route::post('/technician/submit-verification', [TechnicianController::class, 'submitVerification']);
    Route::post('/technician/activate-subscription', [TechnicianController::class, 'activateSubscription']);
    Route::get('/technician/status', [TechnicianController::class, 'status']);
    Route::post('/technician/availability', [TechnicianController::class, 'updateAvailability']);
    Route::post('/technician/availability/toggle', [TechnicianController::class, 'toggleDayAvailability']);

    // Bookings
    Route::post('/bookings', [BookingController::class, 'bookTechnician']);
    Route::get('/bookings/my', [BookingController::class, 'myBookings']);
    Route::get('/bookings/requests', [BookingController::class, 'getBookingRequests']);
    Route::get('/bookings/{bookingId}', [BookingController::class, 'getBookingDetails']);
    Route::post('/bookings/{bookingId}/accept', [BookingController::class, 'acceptBooking']);
    Route::post('/bookings/{bookingId}/reject', [BookingController::class, 'rejectBooking']);
    Route::post('/bookings/{bookingId}/cancel', [BookingController::class, 'cancelBooking']);
    Route::post('/bookings/{bookingId}/complete', [BookingController::class, 'completeBooking']);
});

// Keep API 404 inside api group (avoid web.php fallback when method/URL is wrong)
Route::fallback(function (Request $request) {
    return response()->json([
        'success' => false,
        'message' => 'API endpoint not found. Check URL and HTTP method (GET/POST).',
        'path' => $request->path(),
        'method' => $request->method(),
        'hint' => str_starts_with($request->path(), 'api/register')
            ? 'Use POST /api/register (browser GET will not work).'
            : null,
    ], 404);
});