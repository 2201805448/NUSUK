# Pilgrim Permissions & Access Fixes Documentation

**Date:** 2026-01-19

## Overview
This document details the critical backend updates made to resolve "403 Forbidden" errors encountered by Pilgrim users when accessing Bookings, Rooms, and Accommodations. The issues were primarily caused by case-sensitive role comparisons in middleware and controllers.

## Changes Implemented

### 1. Middleware (`app/Http/Middleware/RoleMiddleware.php`)
**Issue:**  
The middleware was performing strictly case-sensitive comparisons (e.g., matching "pilgrim" against "Pilgrim"), causing valid users with slightly different role casing to be denied access.

**Fix:**  
Updated the logic to standardize both the user's role and the allowed roles to lowercase and trimmed whitespace before comparison.

```php
// Old Logic
if (!in_array($request->user()->role, $roles)) { ... }

// New Logic
$userRole = Str::lower(trim($user->role));
$allowedRoles = array_map(fn($r) => Str::lower(trim($r)), $roles);

if (!in_array($userRole, $allowedRoles)) {
    return response()->json(['message' => 'Unauthorized. Access denied.'], 403);
}
```

### 2. Booking Controller (`app/Http/Controllers/Api/BookingController.php`)
**Issue:**  
The `store` method (create booking) had a hardcoded check for the "pilgrim" role that was also case-sensitive.

**Fix:**  
Applied the same `Str::lower()` and `trim()` normalization to the role check in the `store` method.

```php
// Updated Check
if (Str::lower(trim($user->role)) !== 'pilgrim') {
    return response()->json([
        'message' => 'Access denied. Booking is strictly reserved for Pilgrim accounts.'
    ], 403);
}
```

### 3. API Routes (`routes/api.php`)
**Verification:**  
Verified that the routes for **Accommodations** and **Rooms** are defined outside of the strict `role:ADMIN` or `role:SUPERVISOR` groups. They are placed within the general `auth:sanctum` group, which inherently allows Pilgrim access (as they are authenticated users).

- `GET /accommodations` -> Accessible to all authenticated users.
- `GET /rooms` -> Accessible to all authenticated users.

## Impact
- **Pilgrims** can now successfully create bookings without receiving a 403 error.
- **Pilgrims** can now view lists of Hotels (Accommodations) and Rooms, allowing dropdowns and selection UIs on the frontend to populate correctly.
- The application is more robust against minor inconsistencies in data entry regarding user roles (e.g., "Pilgrim " vs "pilgrim").
