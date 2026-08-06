<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\OfficeController;
use App\Http\Controllers\Api\AttendanceController;
use App\Http\Controllers\Api\PostController;
use App\Http\Controllers\Api\VerificationController;
use App\Http\Controllers\Api\ProfileController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Attendance API untuk aplikasi mobile
|
*/

// Public routes (tidak perlu auth)
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);

// Public: Posts
Route::get('/posts', [PostController::class, 'index']);
Route::get('/posts/{slug}', [PostController::class, 'show']);

// Public: Verification
Route::get('/verify/{code}', [VerificationController::class, 'verify']);

// Public: Prodies list (for registration form)
Route::get('/prodies', [ProfileController::class, 'prodies']);

// Protected routes (perlu auth:sanctum)
Route::middleware('auth:sanctum')->group(function () {
    // Auth
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);

    // Profile
    Route::get('/profile', [ProfileController::class, 'show']);
    Route::put('/profile', [ProfileController::class, 'update']);
    Route::post('/profile/photo', [ProfileController::class, 'updatePhoto']);

    // WhatsApp OTP (same logic as web)
    Route::prefix('whatsapp')->group(function () {
        Route::get('/otp-status', [\App\Http\Controllers\Api\WhatsAppController::class, 'checkOtpStatus']);
        Route::post('/send-otp', [\App\Http\Controllers\Api\WhatsAppController::class, 'sendOtp']);
        Route::post('/verify-otp', [\App\Http\Controllers\Api\WhatsAppController::class, 'verifyOtp']);
        Route::post('/delete', [\App\Http\Controllers\Api\WhatsAppController::class, 'delete']);
    });

    // Offices
    Route::get('/offices', [OfficeController::class, 'index']);

    // Attendance
    Route::prefix('attendance')->group(function () {
        Route::get('/today', [AttendanceController::class, 'today']);
        Route::post('/clock-in', [AttendanceController::class, 'clockIn']);
        Route::post('/clock-out', [AttendanceController::class, 'clockOut']);
        Route::post('/{id}/update-notes', [AttendanceController::class, 'updateNotes']);
        Route::get('/history', [AttendanceController::class, 'history']);
    });
});

