# Booking Reports Documentation

## Overview
This document details the implementation of the **Booking Reports** feature, which allows administrators to generate comprehensive reports on system bookings based on specific criteria such as date range, status, and associated trip.

## Changes Implemented

### 1. API Changes
**Endpoint:** `GET /api/reports/bookings`

- **Access Level:** Admin only (protected by `auth:sanctum` and role middleware).
- **Parameters:**
  - `start_date` (Optional, Date): Filter bookings on or after this date.
  - `end_date` (Optional, Date): Filter bookings on or before this date.
  - `status` (Optional, String): Filter by booking status (e.g., `CONFIRMED`, `PENDING`, `CANCELLED`).
  - `trip_id` (Optional, Integer): Filter by a specific Trip ID.

### 2. Controller Logic
**File:** `app/Http/Controllers/Api/ReportController.php`

A new method `bookingReport` was added:

1.  **Filtering:** Applies dynamic `where` clauses based on provided parameters.
2.  **Eager Loading:** Loads `user`, `trip`, and `attendees` relationships to provide full context.
3.  **Aggregation:**
    - **Total Bookings:** Count of filtered booking records.
    - **Total Pax:** Sum of `attendees` count across all filtered bookings.
    - **Total Value:** Sum of `total_price`.
    - **Status Breakdown:** A grouped count of bookings by status (e.g., `{'CONFIRMED': 15, 'PENDING': 3}`).
4.  **Response:** Returns a JSON object with `meta` (filters used), `summary` (aggregated stats), and `records` (detailed list).

### 3. Response Structure

The API returns a structured JSON response:

```json
{
    "meta": {
        "filters": {
            "start_date": "2026-02-01",
            "status": "CONFIRMED"
        },
        "generated_at": "2026-01-22 12:05:00"
    },
    "summary": {
        "total_bookings": 10,
        "total_pax": 25,
        "total_value": 25000.00,
        "status_breakdown": {
            "CONFIRMED": 10
        }
    },
    "records": [
        {
            "booking_id": 202,
            "booking_ref": "BR-98765",
            "booking_date": "2026-02-10",
            "trip_name": "Umrah FEB 2026",
            "user_name": "Pilgrim Name",
            "pax_count": 3,
            "total_price": 3000.00,
            "status": "CONFIRMED"
        },
        ...
    ]
}
```

## Verification
- Validated using `test_booking_report.php`.
- Key verification points:
    - **Trip Filtering:** Confirmed that filtering by a specific Trip ID returns only bookings for that trip.
    - **Status Filtering:** Confirmed that filtering by status excludes other statuses.
    - **Date Filtering:** Confirmed date range inclusion/exclusion.
    - **Aggregation:** Verified that "Total Pax" correctly sums the number of attendees from the `booking_attendees` table.
