// routes/api.php
<?php
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\TechnicianController;
use App\Http\Controllers\Api\DistrictController;
use Illuminate\Support\Facades\Route;


// Public
Route::post('/register', [AuthController::class, 'register']);
Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);
Route::post('/login', [AuthController::class, 'login']);
Route::get('/districts', [DistrictController::class, 'index']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);

// Protected
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/profile', [AuthController::class, 'profile']);
    Route::post('/logout', [AuthController::class, 'logout']);
    
    // Technician only routes
    Route::post('/technician/submit-verification', [TechnicianController::class, 'submitVerification']);
    Route::post('/technician/activate-subscription', [TechnicianController::class, 'activateSubscription']);
    Route::get('/technician/status', [TechnicianController::class, 'status']);
});