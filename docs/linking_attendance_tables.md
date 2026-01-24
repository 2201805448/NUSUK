# Pilgrim Attendance Linking Documentation

**Last Updated:** 2026-01-25
**Feature:** Link Attendance Tracking to Pilgrim Profile

## 1. Overview
This feature connects the `pilgrims` table with `attendance_tracking` to allow the API to return the most recent attendance status for each pilgrim. This is critical for the frontend to display the correct real-time status (e.g., ARRIVAL, DEPARTURE).

## 2. Implementation Details

### Model: `App\Models\Pilgrim`
A new relationship `latestAttendance` was added. It uses `latestOfMany` to efficiently retrieve the single most recent record.

```php
public function latestAttendance()
{
    // Returns the latest attendance record based on the 'attendance_id' primary key.
    return $this->hasOne(AttendanceTracking::class, 'pilgrim_id')->latestOfMany('attendance_id');
}
```

### Controller: `App\Http\Controllers\Api\PilgrimController`
The `index` method was updated to eager load this relationship.

```php
public function index()
{
    // Eager loading ensures data is fetched in a minimum number of queries
    $pilgrims = Pilgrim::with(['latestAttendance'])->get();

    return response()->json([
        'pilgrims' => $pilgrims
    ]);
}
```

## 3. Data Verification

### Response Structure
The API response is a JSON object containing a list of pilgrims. Each pilgrim object includes a `latest_attendance` key.

- **If attendance exists:** `latest_attendance` is an object containing `attendance_id`, `status_type`, `timestamp`, etc.
- **If no attendance:** `latest_attendance` is `null`.

### Testing Verification
We verified the integrity of relevant database keys:
- `pilgrims.pilgrim_id`: `bigint(20) unsigned`
- `attendance_tracking.pilgrim_id`: `bigint(20) unsigned`

Tests confirmed that when a record exists in `attendance_tracking` with a matching `pilgrim_id`, it is correctly returned by the API.

## 4. Troubleshooting
If the field appears missing:
1.  Ensure the `attendance_tracking` record has the correct `pilgrim_id`.
2.  Ensure the `attendance_tracking` record has a valid `timestamp` and `attendance_id`.
3.  Check if the `Pilgrim` model has any global scopes or visibility settings (e.g., `$hidden` attributes) obscuring the output (none found in current review).
