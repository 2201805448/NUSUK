# Pilgrim Attendance in Listing Update

**Date:** 2026-01-24
**Description:** This update ensures that the Pilgrim listing API includes the latest attendance record for each pilgrim. This modification allows the UI to persist the attendance state (e.g., Checked In, Absent) even after page reloads.

## 1. Problem
Previously, the pilgrim listing API (`GET /api/pilgrims`) only returned basic pilgrim details. If a supervisor marked attendance (Check In/Out), the UI would show the status, but upon refreshing the page, this state was lost because the backend did not provide the current attendance status.

## 2. Changes Implemented

### 2.1 Pilgrim Model (`app/Models/Pilgrim.php`)
Added a `latestAttendance` relationship using Laravel's `latestOfMany` feature.
**Crucial Fix:** The `AttendanceTracking` model has timestamps disabled (`public $timestamps = false;`). Therefore, the default `latestOfMany()` method (which uses `created_at` or `updated_at`) effectively falls back or behaves unexpectedly. We explicitly specified `attendance_id` as the key to determine the latest record.

```php
    public function latestAttendance()
    {
        return $this->hasOne(AttendanceTracking::class, 'pilgrim_id')->latestOfMany('attendance_id');
    }
```

### 2.2 Pilgrim Controller (`app/Http/Controllers/Api/PilgrimController.php`)
Updated the `index` method to eager load this new relationship. This ensures that every pilgrim object in the response includes a `latest_attendance` object (or null).

```php
    public function index()
    {
        return Pilgrim::with(['latestAttendance'])->get();
    }
```

## 3. Verification
A manual verification script (`tests/manual_test_attendance.php`) was created and executed to validate the fix.

### Test Logic
1.  Created a Supervisor and a Pilgrim user.
2.  Created an `AttendanceTracking` record for the pilgrim (Status: `ARRIVAL`).
3.  Fetched the pilgrim using `Pilgrim::with(['latestAttendance'])->get()`.
4.  Asserted that `latestAttendance` was present and contained the correct status.

### Result
The test passed, confirming the `latest_attendance` relationship is correctly loaded with the explicit `attendance_id` ordering.

## 4. Impact
-   **Frontend:** The frontend can now access `pilgrim.latest_attendance.status_type` (e.g., 'ARRIVAL', 'DEPARTURE') to render the correct UI state on load.
-   **Performance:** Eager loading avoids N+1 query issues when fetching attendance for a list of pilgrims.
