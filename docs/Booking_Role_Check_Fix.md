# Booking Controller Role Check Fix

**Date:** 2026-01-19
**Component:** Backend - Booking System

## Overview
This document details the fix applied to the `BookingController` to resolve an "Access denied" error encountered by users with the 'Pilgrim' role involved in the booking process.

## Issue Description
Users with the role 'Pilgrim' were receiving a 403 Forbidden ("Access denied") error when attempting to create a booking (`POST /api/bookings`).
Investigation revealed that the backend was strictly comparing the user's role without sanitizing it for potential whitespace (e.g., "Pilgrim " vs "Pilgrim"). While the `RoleMiddleware` handled this correctly using `trim()`, the `BookingController`'s internal check did not.

## Changes Made

### File: `app/Http/Controllers/Api/BookingController.php`

**Method:** `store`

Updated the role verification logic to include `trim()`, ensuring robust comparison regardless of trailing or leading spaces in the database value.

#### Before:
```php
if (Str::lower($request->user()->role) !== 'pilgrim') {
    return response()->json(['message' => 'Access denied'], 403);
}
```

#### After:
```php
if (Str::lower(trim($request->user()->role)) !== 'pilgrim') {
    return response()->json(['message' => 'Access denied'], 403);
}
```

## Impact
- **Reliability:** The booking process is now more resilient to minor data inconsistencies regarding user roles.
- **Consistency:** The logic now aligns with the robust role checking mechanism found in the application's middleware.
