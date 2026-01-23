# Attendance Tracking Restriction

## Overview
This document details the changes made to restrict the "Attendance Tracking for Trips" feature so that it is exclusively available to the **Supervisor** role.

## Changes Implemented

### 1. API Route Restriction
The API endpoints responsible for recording attendance and viewing attendance reports have been moved from the shared `ADMIN,SUPERVISOR` middleware group to the `SUPERVISOR`-only middleware group in `routes/api.php`.

**Affected Routes:**
- `POST /api/pilgrims/{id}/attendance` - Record attendance (Arrival, Departure, etc.)
- `GET /api/trips/{id}/attendance-reports` - View attendance reports for a trip

### 2. Access Control Verification
- **Admins**: Are now **denied** access to these endpoints (Returns `403 Forbidden`).
- **Pilgrims**: Are **denied** access to these endpoints (Returns `403 Forbidden`).
- **Supervisors**: Are **granted** access to record attendance and view reports (Returns `200 OK` or `201 Created`).

## Verification
A dedicated test script `test_attendance_tracking.php` was updated and executed to verify these permissions. The script explicitly tests:
1.  Admin attempts to record attendance -> Fails as expected.
2.  Pilgrim attempts to record attendance -> Fails as expected.
3.  Supervisor attempts to record attendance -> Succeeds as expected.

## related Files
- `routes/api.php`
- `app/Http/Controllers/Api/AttendanceController.php`
- `test_attendance_tracking.php`
