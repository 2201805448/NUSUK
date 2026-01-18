# Booking Permissions - Pilgrim Role Restriction

**Date:** January 18, 2026  
**File Modified:** `app/Http/Controllers/Api/BookingController.php`

---

## Overview

Added role-based access control to the booking creation endpoint to ensure only users with the `pilgrim` role can create bookings.

## Change Details

### Modified Method: `store()`

Added a role validation check at the beginning of the `store` method:

```php
// Only pilgrims can create bookings
if (Auth::user()->role !== 'pilgrim') {
    return response()->json([
        'message' => 'Access denied. Booking is strictly reserved for Pilgrim accounts.'
    ], 403);
}
```

### Behavior

| User Role | Result |
|-----------|--------|
| `pilgrim` | ✅ Allowed to create bookings |
| `admin` | ❌ Blocked (403 Forbidden) |
| `staff` | ❌ Blocked (403 Forbidden) |
| `support` | ❌ Blocked (403 Forbidden) |

---

## Why This Matters

1. **Report Accuracy:** Prevents administrative accounts from appearing in Pilgrim Count reports
2. **Data Integrity:** Ensures `total_price` and hotel assignments are linked only to legitimate pilgrim accounts
3. **Backend Security:** Blocks unauthorized booking attempts even if someone bypasses the frontend

---

## API Response (When Blocked)

**Status Code:** `403 Forbidden`

```json
{
    "message": "Access denied. Booking is strictly reserved for Pilgrim accounts."
}
```
