# My Bookings Route Addition

**Date:** 2026-01-19
**Author:** Antigravity (Assistant)

## Overview
This document details the changes made to the backend to resolve a 404 error encountered by the frontend when accessing the `api/my-bookings` route. The corresponding route and controller method were missing and have now been added.

## Changes

### 1. API Route
**File:** `routes/api.php`

Added a new GET route `my-bookings` within the `auth:sanctum` middleware group to ensure only authenticated users (Pilgrims) can access it.

```php
// In routes/api.php

Route::middleware('auth:sanctum')->group(function () {
    // ...
    // Bookings
    Route::get('/my-bookings', [\App\Http\Controllers\Api\BookingController::class, 'myBookings']);
    // ...
});
```

### 2. Controller Method
**File:** `app/Http/Controllers/Api/BookingController.php`

Added the `myBookings` method to the `BookingController` class. This method currently serves as an alias to the `index` method, which is already designed to return bookings for the authenticated user.

```php
// In app/Http/Controllers/Api/BookingController.php

/**
 * Get bookings for the logged-in pilgrim (Alias for index or specific implementation)
 */
public function myBookings(Request $request)
{
    return $this->index($request);
}
```

## Verification
- The `api/my-bookings` route is now registered and points to the correct controller method.
- The `myBookings` method reuses the `index` logic to return the authenticated user's bookings, ensuring consistency and resolving the 404 error.
