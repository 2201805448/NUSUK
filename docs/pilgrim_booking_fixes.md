# Pilgrim Booking Permissions Fix

## Overview
This update addresses the "Access denied" error encountered by Pilgrim users when attempting to create a booking via the `/api/bookings` endpoint.

## Changes

### 1. BookingController Update
- **File:** `app/Http/Controllers/Api/BookingController.php`
- **Method:** `store`
- **Changes:**
    - Added detailed logging to capture the authenticated user's ID and Role during booking attempts.
    - Updated the "Access denied" response to include the actual role detected by the backend. This aids in immediate debugging if the role is mismatched (e.g., casing issues or 'Pilgrim' vs 'User').
    - Refined the role check variable extraction checking `Str::lower(trim($request->user()->role))` to stricter compare against `'pilgrim'`.

### 2. Route Verification
- **File:** `routes/api.php`
- **Status:** Verified that `POST /bookings` is within the `auth:sanctum` middleware group but *outside* of restrictive role-based middleware groups (like `role:ADMIN`). This confirms route-level permissions are correct.

## Debugging
If users still face issues, check `storage/logs/laravel.log` for "Booking Attempt" entries or review the 403 response message which now states "Your role is: [Role]".
