<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\PasswordResetController;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login'])->name('login');

// Password Reset
Route::post('/password/send-code', [PasswordResetController::class, 'sendResetCode']);
Route::post('/password/verify-code', [PasswordResetController::class, 'verifyResetCode']);
Route::post('/password/reset', [PasswordResetController::class, 'resetPassword']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/password/change', [AuthController::class, 'changePassword']);
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    // Profile
    Route::get('/profile', [\App\Http\Controllers\Api\ProfileController::class, 'me']);
    Route::put('/profile', [\App\Http\Controllers\Api\ProfileController::class, 'update']);

    // Packages (Available to all authenticated users)
    Route::get('/packages', [\App\Http\Controllers\Api\PackageController::class, 'index']);
    Route::get('/packages/{id}', [\App\Http\Controllers\Api\PackageController::class, 'show']);

    // Bookings
    Route::post('/bookings', [\App\Http\Controllers\Api\BookingController::class, 'store']);
    Route::post('/bookings/{id}/request-modification', [\App\Http\Controllers\Api\BookingController::class, 'requestModification']);
    Route::post('/bookings/{id}/request-cancellation', [\App\Http\Controllers\Api\BookingController::class, 'requestCancellation']);

    // Common Routes for Admin and Supervisor
    Route::middleware('role:ADMIN,SUPERVISOR')->group(function () {
        Route::get('/trips', [\App\Http\Controllers\Api\TripController::class, 'index']);
        Route::get('/trips/{id}', [\App\Http\Controllers\Api\TripController::class, 'show']);
    });

    // Admin Dashboard Routes - No Prefix, Role Restricted
    Route::middleware('role:ADMIN')->group(function () {
        Route::get('/stats', [\App\Http\Controllers\Api\AdminController::class, 'stats']);
        Route::get('/users', [\App\Http\Controllers\Api\AdminController::class, 'users']);
        Route::post('/users', [\App\Http\Controllers\Api\AdminController::class, 'store']);
        Route::get('/users/{id}', [\App\Http\Controllers\Api\AdminController::class, 'show']);
        Route::put('/users/{id}', [\App\Http\Controllers\Api\AdminController::class, 'update']);
        Route::delete('/users/{id}', [\App\Http\Controllers\Api\AdminController::class, 'destroy']);
        Route::patch('/users/{id}/status', [\App\Http\Controllers\Api\AdminController::class, 'updateUserStatus']);

        // Reports
        Route::get('/reports/trips', [\App\Http\Controllers\Api\AdminController::class, 'tripReports']);

        // Package Management
        Route::post('/packages', [\App\Http\Controllers\Api\PackageController::class, 'store']);
        Route::put('/packages/{id}', [\App\Http\Controllers\Api\PackageController::class, 'update']);
        Route::delete('/packages/{id}', [\App\Http\Controllers\Api\PackageController::class, 'destroy']);

        // Accommodation Management
        Route::apiResource('accommodations', \App\Http\Controllers\Api\AccommodationController::class);
        Route::apiResource('rooms', \App\Http\Controllers\Api\RoomController::class);

        // Transportation Management
        Route::apiResource('transports', \App\Http\Controllers\Api\TransportController::class);
        Route::apiResource('drivers', \App\Http\Controllers\Api\DriverController::class);
        Route::apiResource('routes', \App\Http\Controllers\Api\TransportRouteController::class); // Added

        // Trip Management (including Hotels in Trips)
        // Route::get('/trips', ...) moved to shared group
        Route::post('/trips', [\App\Http\Controllers\Api\TripController::class, 'store']); // Added store route
        // Route::get('/trips/{id}', ...) moved to shared group
        Route::put('/trips/{id}', [\App\Http\Controllers\Api\TripController::class, 'update']); // Added update route
        Route::patch('/trips/{id}/cancel', [\App\Http\Controllers\Api\TripController::class, 'cancel']); // Added cancel route
        Route::post('/trips/{id}/hotels', [\App\Http\Controllers\Api\TripController::class, 'addHotel']);
        Route::delete('/trips/{id}/hotels/{accommodation_id}', [\App\Http\Controllers\Api\TripController::class, 'removeHotel']);

        // Activity Management in Trips
        Route::post('/trips/{id}/activities', [\App\Http\Controllers\Api\TripController::class, 'addActivity']);
        Route::post('/trips/{id}/transports', [\App\Http\Controllers\Api\TripController::class, 'addTransport']); // Added for stages
        Route::apiResource('activities', \App\Http\Controllers\Api\ActivityController::class); // Added for update/delete
    });
});
