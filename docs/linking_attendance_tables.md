# Linking Attendance Tables Documentation

**Date:** 2026-01-25
**Feature:** Pilgrim Attendance Tracking Link

## Overview
This document details the changes made to link the `AttendanceTracking` table with the `Pilgrim` model and update the API to return the latest attendance status. This ensures the frontend receives the correct UI state for pilgrim attendance.

## Modified Files

### 1. `app/Models/Pilgrim.php`
Added the `latestAttendance` relationship to fetch the most recent attendance record for a pilgrim.

```php
public function latestAttendance()
{
    // Fetches the latest attendance record based on 'attendance_id'
    return $this->hasOne(AttendanceTracking::class, 'pilgrim_id')->latestOfMany('attendance_id');
}
```

### 2. `app/Http/Controllers/Api/PilgrimController.php`
Updated the `index` method to eager load the `latestAttendance` relationship and wrap the response in a `pilgrims` key.

```php
public function index()
{
    // Eager load 'latestAttendance' to avoid N+1 queries and include status
    $pilgrims = Pilgrim::with(['latestAttendance'])->get();
    
    return response()->json([
        'pilgrims' => $pilgrims
    ]);
}
```

## API Response Structure
The `GET /api/pilgrims` endpoint now returns a JSON object with the following structure:

```json
{
    "pilgrims": [
        {
            "pilgrim_id": 1,
            "passport_name": "Example Pilgrim",
            "latest_attendance": {
                "attendance_id": 101,
                "pilgrim_id": 1,
                "status_type": "ARRIVAL",
                "timestamp": "2026-01-25 12:00:00",
                ...
            },
            ...
        },
        ...
    ]
}
```

## Verification
tested via `tests/final_test.php` which confirmed that:
1.  Pilgrims are correctly retrieved.
2.  The `latest_attendance` object is present and contains the correct data.
