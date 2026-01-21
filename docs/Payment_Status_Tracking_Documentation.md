# Payment Status Tracking Documentation

## Overview
This document outlines the changes made to implement the **Payment Status Tracking** feature, which allows administrators to view the payment status (Paid, Unpaid, Overdue) for each Umrah pilgrim within a booking.

## Changes Implemented

### 1. Database Model Updates
**File:** `app/Models/Booking.php`

- **Added `attendees()` relationship:**
  - This `hasMany` relationship allows the application to retrieve all pilgrims/guests associated with a specific booking.
- **Added `modifications()` relationship:**
  - This `hasMany` relationship was added to resolve an "undefined relationship" error when eager loading booking details.

### 2. Admin API Updates
**File:** `app/Http/Controllers/Api/AdminBookingController.php`

- **Updated `show($id)` Method:**
  - The method now eager loads `attendees.pilgrim` to fetch pilgrim details alongside the booking.
  - **Payment Status Logic:** A new logic was introduced to calculate the payment status dynamically:
    - **PAID:** logic: `Total Payments (Confirmed) >= Total Booking Price`
    - **OVERDUE:** logic: `Status is UNPAID` AND `Booking Date > 3 days ago`
    - **UNPAID:** logic: `Status is not PAID` AND `Booking Date <= 3 days ago`
  - The calculated `payment_status` is now attached to the main `booking` object and also injected into each `attendee` object in the response.

## API Response Structure
The `GET /api/admin/bookings/{id}` endpoint response now includes:

```json
{
    "booking_id": 123,
    "booking_ref": "BK-SAMPLE",
    "total_price": 1000.00,
    "payment_status": "PAID", // New Field
    "attendees": [
        {
            "attendee_id": 1,
            "guest_name": "John Doe",
            "payment_status": "PAID" // New Field (Matches Booking Status)
        }
    ],
    ...
}
```

## Verification
- Validated using a custom test script `test_admin_booking_payment_status.php`.
- Verified scenarios for **Paid**, **Unpaid**, **Overdue**, and **Partial Payment**.
