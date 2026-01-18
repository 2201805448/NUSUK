# Booking Access Denied Fix

**Date**: 2026-01-18
**Component**: Booking System

## Issue Description
Pilgrim users were receiving an "Access denied. Booking is strictly reserved for Pilgrim accounts" error when attempting to create a booking, even though they were logged in with the correct role.

## Root Cause
The role check in `BookingController@store` was performed using a strict case-sensitive comparison (`=== 'pilgrim'`). If the user's role in the database was stored as "Pilgrim" (capitalized) or any other variation, the check would fail.

Additionally, the `RoleMiddleware` logic was also strictly case-sensitive.

## Changes Implemented

### 1. BookingController (`app/Http/Controllers/Api/BookingController.php`)
- **Case-Insensitive Check**: Updated the role verification logic to convert the user's role to lowercase before comparison using `Str::lower()`.
- **Debug Logging**: Added `Log::info()` to record the User ID and Role for every booking attempt. This aids in verify exactly what the application sees during the request.

```php
// Before
if (Auth::user()->role !== 'pilgrim') { ... }

// After
$user = Auth::user();
\Illuminate\Support\Facades\Log::info("Booking Attempt: User ID {$user->user_id}, Role: {$user->role}");

if (Str::lower($user->role) !== 'pilgrim') { ... }
```

### 2. RoleMiddleware (`app/Http/Middleware/RoleMiddleware.php`)
- **Robustness**: Updated the middleware to convert both the user's role and the list of allowed roles to lowercase. This ensures consistent behavior across the application for any route using this middleware.

```php
// Before
if (!in_array($user->role, $roles)) { ... }

// After
if (!in_array(strtolower($user->role), array_map('strtolower', $roles))) { ... }
```

## Verification
- Confirmed that `RoleMiddleware` was not the blocker for the booking route (it is not applied to `POST /bookings`).
- The fix in the controller ensures that roles like "Pilgrim", "PILGRIM", and "pilgrim" are all treated as valid.
