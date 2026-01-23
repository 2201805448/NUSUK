# Supervisor Observation Access Update

**Date:** January 23, 2026
**Author:** Antigravity (Assistant)

## Overview
This document details the latest changes made to the API regarding the "recording observations on a pilgrim" functionality. The access control for this feature has been updated to strictly enforce Supervisor-only access, removing it from the general Admin scope.

## Changes Implemented

### 1. Route Reconfiguration
The API route for submitting pilgrim notes/observations has been moved from the shared Admin/Supervisor middleware group to a dedicated Supervisor-only group.

- **Endpoint:** `POST /api/pilgrims/{id}/notes`
- **Controller:** `SupervisorNoteController@store`
- **Previous Access:** `ADMIN`, `SUPERVISOR`
- **New Access:** `SUPERVISOR` (Strict)

### 2. Implication
- **Supervisors**: Can continue to record observations and notes about pilgrims assigned to them (or generally, depending on controller logic).
- **Administrators**: Can **NO LONGER** access this specific endpoint to record observations, unless their user account explicitly possesses the 'SUPERVISOR' role. This enforces the separation of duties requested.

## Technical Details

### Modified File: `routes/api.php`

```php
// ... previous configuration ...

// Shared Routes (Admin & Supervisor) logic...
// [Route removed from here]

// ...

// Supervisor Only Routes
Route::middleware('role:SUPERVISOR')->group(function () {
    // Supervisor Notes on Pilgrim (Restricted to Supervisor Only)
    Route::post('/pilgrims/{id}/notes', [\App\Http\Controllers\Api\SupervisorNoteController::class, 'store']);
});
```

## Verification
- **Code Inspection**: Verified that the route is nested within `Route::middleware('role:SUPERVISOR')`.
- **Target Role**: Confirmed that only requests from users with the `SUPERVISOR` role will bypass this middleware.
