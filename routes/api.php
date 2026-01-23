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

// Public Routes (Packages & Accommodations)
Route::get('/packages', [\App\Http\Controllers\Api\PackageController::class, 'index']);
Route::get('/packages/{id}', [\App\Http\Controllers\Api\PackageController::class, 'show']);
Route::get('/packages/{id}/reviews', [\App\Http\Controllers\Api\PackageController::class, 'getTripReviews']);
Route::get('/trips/{id}/hotel-reviews', [\App\Http\Controllers\Api\TripController::class, 'getHotelReviews']);

Route::get('/accommodations', [\App\Http\Controllers\Api\AccommodationController::class, 'index']);
Route::get('/accommodations/{id}', [\App\Http\Controllers\Api\AccommodationController::class, 'show']);
Route::get('/rooms', [\App\Http\Controllers\Api\RoomController::class, 'index']);
Route::get('/rooms/{id}', [\App\Http\Controllers\Api\RoomController::class, 'show']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/password/change', [AuthController::class, 'changePassword']);
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    // Profile
    Route::get('/user/profile', [\App\Http\Controllers\Api\ProfileController::class, 'me']);
    Route::put('/user/profile', [\App\Http\Controllers\Api\ProfileController::class, 'update']);
    Route::get('/pilgrim-card', [\App\Http\Controllers\Api\PilgrimCardController::class, 'show']);

    // Messaging
    Route::post('/messages', [\App\Http\Controllers\Api\MessageController::class, 'store']);
    Route::get('/messages', [\App\Http\Controllers\Api\MessageController::class, 'index']);
    Route::get('/messages/{user_id}', [\App\Http\Controllers\Api\MessageController::class, 'show']);

    // Display Notifications (User)
    Route::get('/notifications', [\App\Http\Controllers\Api\NotificationController::class, 'index']);

    Route::put('/notifications/{id}/read', [\App\Http\Controllers\Api\NotificationController::class, 'markAsRead']);

    // Advertisements / Announcements
    Route::get('/announcements', [\App\Http\Controllers\Api\AnnouncementController::class, 'index']);
    Route::post('/announcements', [\App\Http\Controllers\Api\AnnouncementController::class, 'store']);
    Route::put('/announcements/{id}', [\App\Http\Controllers\Api\AnnouncementController::class, 'update']);
    Route::get('/announcements/{id}', [\App\Http\Controllers\Api\AnnouncementController::class, 'show']);
    Route::delete('/announcements/{id}', [\App\Http\Controllers\Api\AnnouncementController::class, 'destroy']);

    // Religious Content (Guides & Prayer Times)
    Route::get('/guides', [\App\Http\Controllers\Api\GuideController::class, 'index']);
    Route::get('/guides/{id}', [\App\Http\Controllers\Api\GuideController::class, 'show']);
    Route::post('/guides', [\App\Http\Controllers\Api\GuideController::class, 'store']); // Admin create
    Route::get('/prayer-times', [\App\Http\Controllers\Api\PrayerTimeController::class, 'index']);

    // Support Tickets
    Route::get('/support/tickets', [\App\Http\Controllers\Api\SupportTicketController::class, 'index']);
    Route::post('/support/tickets', [\App\Http\Controllers\Api\SupportTicketController::class, 'store']);
    Route::get('/support/tickets/{id}', [\App\Http\Controllers\Api\SupportTicketController::class, 'show']);
    Route::post('/support/tickets/{id}/reply', [\App\Http\Controllers\Api\SupportTicketController::class, 'reply']);
    Route::post('/support/tickets/{id}/transfer', [\App\Http\Controllers\Api\SupportTicketController::class, 'transfer']);
    Route::post('/support/tickets/{id}/close', [\App\Http\Controllers\Api\SupportTicketController::class, 'close']);

    // Bookings
    Route::get('/my-bookings', [\App\Http\Controllers\Api\BookingController::class, 'myBookings']);
    Route::get('/bookings', [\App\Http\Controllers\Api\BookingController::class, 'index']);
    Route::get('/bookings/{id}', [\App\Http\Controllers\Api\BookingController::class, 'show']);
    Route::post('/bookings', [\App\Http\Controllers\Api\BookingController::class, 'store']);
    Route::post('/bookings/{id}/request-modification', [\App\Http\Controllers\Api\BookingController::class, 'requestModification']);
    Route::post('/bookings/{id}/request-cancellation', [\App\Http\Controllers\Api\BookingController::class, 'requestCancellation']);

    // Activity Log (Pilgrim's trip and activity history)
    Route::get('/activity-log', [\App\Http\Controllers\Api\ActivityLogController::class, 'index']);
    Route::get('/activity-log/trips/{trip_id}', [\App\Http\Controllers\Api\ActivityLogController::class, 'show']);

    // Trip Schedule (Full timeline view for pilgrims)
    Route::get('/trips/{trip_id}/schedule', [\App\Http\Controllers\Api\TripScheduleController::class, 'show']);
    Route::get('/trips/{trip_id}/schedule/today', [\App\Http\Controllers\Api\TripScheduleController::class, 'today']);

    // Pilgrim Accommodation Details
    Route::get('/my-accommodations', [\App\Http\Controllers\Api\PilgrimAccommodationController::class, 'index']);
    Route::get('/my-accommodations/current', [\App\Http\Controllers\Api\PilgrimAccommodationController::class, 'current']);
    Route::get('/trips/{trip_id}/my-accommodations', [\App\Http\Controllers\Api\PilgrimAccommodationController::class, 'forTrip']);
    Route::get('/trips/{trip_id}/my-housing', [\App\Http\Controllers\Api\PilgrimAccommodationController::class, 'housing']);

    // Trip Documents (Download for Pilgrims)
    Route::get('/trips/{trip_id}/documents', [\App\Http\Controllers\Api\TripDocumentController::class, 'index']);
    Route::get('/trips/{trip_id}/documents/{document_id}', [\App\Http\Controllers\Api\TripDocumentController::class, 'show']);
    Route::get('/trips/{trip_id}/documents/{document_id}/download', [\App\Http\Controllers\Api\TripDocumentController::class, 'download']);

    // Pilgrim Notes (Submissions by Pilgrims)
    Route::post('/my-notes', [\App\Http\Controllers\Api\PilgrimNoteController::class, 'store']);
    Route::get('/my-notes', [\App\Http\Controllers\Api\PilgrimNoteController::class, 'myNotes']);

    // Common Routes for Admin and Supervisor
    Route::middleware('role:ADMIN,SUPERVISOR')->group(function () {
        Route::get('/trips', [\App\Http\Controllers\Api\TripController::class, 'index']);
        Route::get('/trips/{id}', [\App\Http\Controllers\Api\TripController::class, 'show']);

        // Route::get('/groups', [\App\Http\Controllers\Api\GroupController::class, 'index']); // MOVED TO ADMIN
        // Route::post('/trips/{id}/groups', [\App\Http\Controllers\Api\GroupController::class, 'store']); // MOVED TO ADMIN
        // Route::get('/trips/{id}/groups', [\App\Http\Controllers\Api\GroupController::class, 'index']); // MOVED TO ADMIN

        // All Pilgrims (Admin/Supervisor)
        Route::get('/pilgrims', [\App\Http\Controllers\Api\PilgrimController::class, 'index']);

        // Pilgrims List (for Supervisor viewing their pilgrims) - MOVED TO ADMIN
        Route::get('/my-pilgrims', [\App\Http\Controllers\Api\GroupController::class, 'listAllPilgrims']);
        Route::get('/groups/{id}/pilgrims', [\App\Http\Controllers\Api\GroupController::class, 'listPilgrims']);

        // View Pilgrim Notes (for Supervisor/Admin)
        Route::get('/pilgrim-notes', [\App\Http\Controllers\Api\PilgrimNoteController::class, 'index']);
        Route::get('/pilgrim-notes/{note_id}', [\App\Http\Controllers\Api\PilgrimNoteController::class, 'show']);
        Route::post('/pilgrim-notes/{note_id}/respond', [\App\Http\Controllers\Api\PilgrimNoteController::class, 'respond']);

        // Group Accommodations (Link accommodation to groups)
        // Group Accommodations (Link accommodation to groups) - MOVED TO ADMIN
        // Route::get('/groups/{group_id}/accommodations', ...);
        // Route::post('/groups/{group_id}/accommodations', ...);
        // Route::put('/groups/{group_id}/accommodations/{accommodation_id}', ...);
        // Route::delete('/groups/{group_id}/accommodations/{accommodation_id}', ...);
        // Route::post('/group-accommodations/bulk-link', ...);

        // Trip Documents (Upload)
        Route::post('/trips/{trip_id}/documents', [\App\Http\Controllers\Api\TripDocumentController::class, 'store']);
        Route::delete('/trips/{trip_id}/documents/{document_id}', [\App\Http\Controllers\Api\TripDocumentController::class, 'destroy']);
        Route::post('/trip-accommodations', [\App\Http\Controllers\Api\TripController::class, 'addHotel']);
        // Trip Chat
        Route::get('/trips/{id}/chat', [\App\Http\Controllers\Api\TripChatController::class, 'index']);
        Route::post('/trips/{id}/chat', [\App\Http\Controllers\Api\TripChatController::class, 'store']);

        // Hotel Reviews (Shared/Auth) - Placing here or in generic auth section? 
        // User requested "enable pilgrim to view". Pilgrim is Authed User. 
        // So generic auth group is better. But let's check placement.
        // The previous attempt was logic: inside auth group.
        // Actually, store is /trips/{id}/groups, implying nested resource.
        // My index method handles ?trip_id=... but Route::get('/trips/{id}/groups') passes id.
        // Wait, Route::get('/trips/{id}/groups', [GroupController::class, 'index']) passes $id as FIRST argument?
        // If I define it as get('/trips/{id}/groups'), Laravel passes $id.
        // My index(Request $request) expects request. If I add $id param: index(Request $request, $id = null).
        // Let's modify api.php to point to a new method OR use a query param on a generic endpoint.
        // Cleaner: Route::get('/groups', [..,'index']); WITH ?trip_id=...

        // Route::post('/groups', [\App\Http\Controllers\Api\GroupController::class, 'storeGroup']); // MOVED TO ADMIN
        // Route::get('/groups', [\App\Http\Controllers\Api\GroupController::class, 'index']); // MOVED TO ADMIN
        // Route::get('/groups/{id}', [\App\Http\Controllers\Api\GroupController::class, 'show']); // MOVED TO ADMIN
        // Route::put('/groups/{id}', [\App\Http\Controllers\Api\GroupController::class, 'update']); // MOVED TO ADMIN
        // Route::post('/groups/{id}/members', [\App\Http\Controllers\Api\GroupController::class, 'addMember']); // MOVED TO ADMIN
        // Route::post('/groups/{id}/transfer', [\App\Http\Controllers\Api\GroupController::class, 'transferMember']); // MOVED TO ADMIN
        // Route::post('/groups/{id}/remove', [\App\Http\Controllers\Api\GroupController::class, 'removeMember']); // MOVED TO ADMIN
        // Route::post('/groups/{group}/supervisor', [\App\Http\Controllers\Api\GroupController::class, 'assignSupervisor']); // MOVED TO ADMIN
        // Route::put('/groups/{id}/unassign-supervisor', [\App\Http\Controllers\Api\GroupController::class, 'unassignSupervisor']); // MOVED TO ADMIN

        // Monitoring
        Route::get('/trips/{id}/housing', [\App\Http\Controllers\Api\AccommodationController::class, 'getHousingData']);

        // Room Assignment
        Route::post('/room-assignments', [\App\Http\Controllers\Api\RoomAssignmentController::class, 'store']);
        Route::put('/room-assignments/{id}', [\App\Http\Controllers\Api\RoomAssignmentController::class, 'update']);

        // Activity Management (Shared)
        Route::apiResource('activities', \App\Http\Controllers\Api\ActivityController::class);

        // Pilgrim Notes - MOVED TO ADMIN ONLY
        // Route::post('/pilgrims/{id}/notes', [\App\Http\Controllers\Api\SupervisorNoteController::class, 'store']);



        // Notifications
        Route::post('/notifications/general', [\App\Http\Controllers\Api\NotificationController::class, 'sendGeneral']);
        Route::post('/trips/{id}/notifications', [\App\Http\Controllers\Api\NotificationController::class, 'sendTrip']);
        // Route::post('/groups/{id}/notifications', [\App\Http\Controllers\Api\NotificationController::class, 'sendGroup']); // MOVED TO ADMIN

        // Trip Updates (Feed)
        Route::post('/trips/{id}/updates', [\App\Http\Controllers\Api\TripUpdateController::class, 'store']);
        Route::get('/trips/{id}/updates', [\App\Http\Controllers\Api\TripUpdateController::class, 'index']);

        // Pilgrim Documents Review
        Route::get('/pilgrims/documents', [\App\Http\Controllers\Api\PilgrimDocumentController::class, 'index']);
        Route::get('/pilgrims/{id}/documents', [\App\Http\Controllers\Api\PilgrimDocumentController::class, 'show']);
        Route::put('/pilgrims/{id}/documents', [\App\Http\Controllers\Api\PilgrimDocumentController::class, 'update']); // Authorization logic is also in controller

    });

    // Admin Dashboard Routes - No Prefix, Role Restricted
    Route::middleware('role:ADMIN')->group(function () {
        Route::get('/stats', [\App\Http\Controllers\Api\AdminController::class, 'stats']);
        Route::get('/general-stats', [\App\Http\Controllers\Api\AdminController::class, 'generalStats']);
        Route::get('/users', [\App\Http\Controllers\Api\AdminController::class, 'users']);
        Route::post('/users', [\App\Http\Controllers\Api\AdminController::class, 'store']);
        Route::get('/users/{id}', [\App\Http\Controllers\Api\AdminController::class, 'show']);
        Route::put('/users/{id}', [\App\Http\Controllers\Api\AdminController::class, 'update']);
        Route::delete('/users/{id}', [\App\Http\Controllers\Api\AdminController::class, 'destroy']);
        Route::patch('/users/{id}/status', [\App\Http\Controllers\Api\AdminController::class, 'updateUserStatus']);

        // Reports
        Route::get('/reports/trips', [\App\Http\Controllers\Api\AdminController::class, 'tripReports']);
        Route::get('/reports/trips/export', [\App\Http\Controllers\Api\ReportController::class, 'exportTrips']);
        Route::get('/reports/revenue', [\App\Http\Controllers\Api\ReportController::class, 'revenueReport']);
        Route::get('/reports/bookings', [\App\Http\Controllers\Api\ReportController::class, 'bookingReport']);

        // Package Management
        Route::post('/packages', [\App\Http\Controllers\Api\PackageController::class, 'store']);
        Route::put('/packages/{id}', [\App\Http\Controllers\Api\PackageController::class, 'update']);
        Route::delete('/packages/{id}', [\App\Http\Controllers\Api\PackageController::class, 'destroy']);

        // Accommodation Management
        Route::apiResource('accommodations', \App\Http\Controllers\Api\AccommodationController::class)->except(['index', 'show']);
        Route::apiResource('rooms', \App\Http\Controllers\Api\RoomController::class)->except(['index', 'show']);

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
        // Route::apiResource('activities', \App\Http\Controllers\Api\ActivityController::class); // Moved to shared group
        // Booking Review & Approval
        Route::get('/bookings', [\App\Http\Controllers\Api\AdminBookingController::class, 'index']);
        Route::get('/bookings/{id}', [\App\Http\Controllers\Api\AdminBookingController::class, 'show']);
        Route::put('/bookings/{id}/status', [\App\Http\Controllers\Api\AdminBookingController::class, 'updateStatus']);

        Route::get('/booking-modifications', [\App\Http\Controllers\Api\AdminBookingController::class, 'indexModifications']);
        Route::put('/booking-modifications/{id}/status', [\App\Http\Controllers\Api\AdminBookingController::class, 'updateModificationStatus']);

        // Group Management (Exclusively Manager/Admin)
        Route::get('/groups', [\App\Http\Controllers\Api\GroupController::class, 'index']);
        Route::post('/groups', [\App\Http\Controllers\Api\GroupController::class, 'storeGroup']);
        Route::get('/groups/{id}', [\App\Http\Controllers\Api\GroupController::class, 'show']);
        Route::put('/groups/{id}', [\App\Http\Controllers\Api\GroupController::class, 'update']);
        Route::post('/trips/{id}/groups', [\App\Http\Controllers\Api\GroupController::class, 'store']);
        Route::get('/trips/{id}/groups', [\App\Http\Controllers\Api\GroupController::class, 'index']);
        Route::delete('/groups/{id}', [\App\Http\Controllers\Api\GroupController::class, 'destroy']);

        // Group Members Management
        Route::post('/groups/{id}/members', [\App\Http\Controllers\Api\GroupController::class, 'addMember']);
        Route::post('/groups/{id}/transfer', [\App\Http\Controllers\Api\GroupController::class, 'transferMember']);
        Route::post('/groups/{id}/remove', [\App\Http\Controllers\Api\GroupController::class, 'removeMember']);

        // Supervisor Management
        Route::post('/groups/{group}/supervisor', [\App\Http\Controllers\Api\GroupController::class, 'assignSupervisor']);
        Route::put('/groups/{id}/unassign-supervisor', [\App\Http\Controllers\Api\GroupController::class, 'unassignSupervisor']);

        // Listings
        // Group Accommodations
        Route::get('/groups/{group_id}/accommodations', [\App\Http\Controllers\Api\GroupAccommodationController::class, 'index']);
        Route::post('/groups/{group_id}/accommodations', [\App\Http\Controllers\Api\GroupAccommodationController::class, 'link']);
        Route::put('/groups/{group_id}/accommodations/{accommodation_id}', [\App\Http\Controllers\Api\GroupAccommodationController::class, 'update']);
        Route::delete('/groups/{group_id}/accommodations/{accommodation_id}', [\App\Http\Controllers\Api\GroupAccommodationController::class, 'unlink']);
        Route::post('/group-accommodations/bulk-link', [\App\Http\Controllers\Api\GroupAccommodationController::class, 'bulkLink']);

        // Group Notifications
        Route::post('/groups/{id}/notifications', [\App\Http\Controllers\Api\NotificationController::class, 'sendGroup']);


    });

    // Supervisor Only Routes
    Route::middleware('role:SUPERVISOR')->group(function () {
        // Supervisor Notes on Pilgrim (Restricted to Supervisor Only)
        Route::post('/pilgrims/{id}/notes', [\App\Http\Controllers\Api\SupervisorNoteController::class, 'store']);

        // Attendance Tracking (Restricted to Supervisor Only)
        Route::post('/pilgrims/{id}/attendance', [\App\Http\Controllers\Api\AttendanceController::class, 'store']);
        Route::get('/trips/{id}/attendance-reports', [\App\Http\Controllers\Api\AttendanceController::class, 'getTripReports']);
    });
});
