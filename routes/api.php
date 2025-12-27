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

    // Admin Dashboard Routes - No Prefix, Role Restricted
    Route::middleware('role:ADMIN')->group(function () {
        Route::get('/stats', [\App\Http\Controllers\Api\AdminController::class, 'stats']);
        Route::get('/users', [\App\Http\Controllers\Api\AdminController::class, 'users']);
        Route::post('/users', [\App\Http\Controllers\Api\AdminController::class, 'store']);
        Route::get('/users/{id}', [\App\Http\Controllers\Api\AdminController::class, 'show']);
        Route::put('/users/{id}', [\App\Http\Controllers\Api\AdminController::class, 'update']);
        Route::delete('/users/{id}', [\App\Http\Controllers\Api\AdminController::class, 'destroy']);
        Route::patch('/users/{id}/status', [\App\Http\Controllers\Api\AdminController::class, 'updateUserStatus']);

        // Package Management
        Route::post('/packages', [\App\Http\Controllers\Api\PackageController::class, 'store']);
        Route::put('/packages/{id}', [\App\Http\Controllers\Api\PackageController::class, 'update']);
        Route::delete('/packages/{id}', [\App\Http\Controllers\Api\PackageController::class, 'destroy']);

        // Accommodation Management
        Route::apiResource('accommodations', \App\Http\Controllers\Api\AccommodationController::class);
        Route::apiResource('rooms', \App\Http\Controllers\Api\RoomController::class);

        // Trip Management (including Hotels in Trips)
        Route::get('/trips', [\App\Http\Controllers\Api\TripController::class, 'index']);
        Route::post('/trips', [\App\Http\Controllers\Api\TripController::class, 'store']); // Added store route
        Route::get('/trips/{id}', [\App\Http\Controllers\Api\TripController::class, 'show']);
        Route::post('/trips/{id}/hotels', [\App\Http\Controllers\Api\TripController::class, 'addHotel']);
        Route::delete('/trips/{id}/hotels/{accommodation_id}', [\App\Http\Controllers\Api\TripController::class, 'removeHotel']);
    });
});
