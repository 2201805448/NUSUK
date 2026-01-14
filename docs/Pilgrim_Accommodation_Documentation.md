# Pilgrim Accommodation Feature Documentation

## Overview

This document describes the **Pilgrim Accommodation** functionality in the NUSUK system. It details how pilgrims can view their accommodation information, the permissions model, and the security controls that ensure each pilgrim only sees their own data.

---

## Table of Contents

1. [Feature Summary](#feature-summary)
2. [API Endpoints](#api-endpoints)
3. [Permission Model](#permission-model)
4. [Data Access Controls](#data-access-controls)
5. [Available Fields](#available-fields)
6. [Response Examples](#response-examples)

---

## Feature Summary

The Pilgrim Accommodation feature allows authenticated pilgrims to:

- ✅ View **all** their accommodation assignments (past, current, and upcoming)
- ✅ View their **current** active accommodation
- ✅ View accommodations for a **specific trip** they are registered for
- ✅ View detailed **housing data** including room assignments and group information

### Key Principle: Data Isolation

> **Each pilgrim can only access their own accommodation data.** The system enforces strict data isolation by filtering all queries through the authenticated user's pilgrim profile.

---

## API Endpoints

| Endpoint | Method | Description |
|----------|--------|-------------|
| `/api/my-accommodations` | GET | View all pilgrim's accommodation assignments |
| `/api/my-accommodations/current` | GET | View current active accommodation only |
| `/api/trips/{trip_id}/my-accommodations` | GET | View accommodations for a specific trip |
| `/api/trips/{trip_id}/my-housing` | GET | View detailed housing data for a specific trip |

### Authentication

All endpoints require authentication via **Bearer Token** (Sanctum).

```http
Authorization: Bearer {your_token}
```

---

## Permission Model

### Role-Based Access

| Actor | Access Level | Description |
|-------|--------------|-------------|
| **Pilgrim** | Own Data Only | Can view only their own accommodation assignments |
| **Supervisor** | Group Data | Can view housing data for pilgrims in their assigned groups |
| **Admin** | Full Access | Can view and manage all accommodation data |

### Pilgrim Permissions Breakdown

```
┌─────────────────────────────────────────────────────────────────┐
│                    PILGRIM PERMISSIONS                          │
├─────────────────────────────────────────────────────────────────┤
│ ✅ CAN VIEW:                                                    │
│    • Their own room assignments                                 │
│    • Hotels where they are accommodated                         │
│    • Check-in/check-out dates                                   │
│    • Room details (number, floor, type)                         │
│    • Hotel contact info (phone, email, stars)                   │
│    • Trip accommodations (only for trips they're registered)    │
│                                                                 │
│ ❌ CANNOT VIEW:                                                 │
│    • Other pilgrims' accommodations                             │
│    • Trips they are not registered for                          │
│    • Administrative accommodation management                    │
│    • Room assignments of other pilgrims                         │
└─────────────────────────────────────────────────────────────────┘
```

---

## Data Access Controls

### How Security is Enforced

The system enforces data isolation at multiple levels:

#### 1. User → Pilgrim Profile Linkage

```php
// Each request verifies the authenticated user has a pilgrim profile
$pilgrim = Pilgrim::where('user_id', Auth::user()->user_id)->first();

if (!$pilgrim) {
    return response()->json(['message' => 'Pilgrim profile not found.'], 404);
}
```

#### 2. Pilgrim-Specific Data Filtering

```php
// All queries are filtered by the authenticated pilgrim's ID
$assignments = RoomAssignment::where('pilgrim_id', $pilgrim->pilgrim_id)->get();
```

#### 3. Trip Registration Verification

```php
// For trip-specific endpoints, verify pilgrim is registered
$membership = GroupMember::where('pilgrim_id', $pilgrim->pilgrim_id)
    ->whereHas('groupTrip', fn($q) => $q->where('trip_id', $trip_id))
    ->first();

if (!$membership) {
    return response()->json(['message' => 'You are not registered for this trip.'], 403);
}
```

### Security Response Codes

| Code | Meaning | When Returned |
|------|---------|---------------|
| **200** | Success | Pilgrim has valid access |
| **401** | Unauthorized | No authentication token or invalid token |
| **403** | Forbidden | Pilgrim not registered for requested trip |
| **404** | Not Found | User doesn't have a pilgrim profile |

---

## Available Fields

### Hotel/Accommodation Data

All accommodation responses now include the **complete set of available fields**:

| Field | Type | Description |
|-------|------|-------------|
| `accommodation_id` | integer | Unique hotel identifier |
| `hotel_name` | string | Name of the hotel |
| `city` | string | City where hotel is located |
| `room_type` | string | Type of room (e.g., Single, Double, Suite) |
| `capacity` | integer | Maximum capacity |
| `notes` | string | Additional notes or address |
| `stars` | integer | Hotel star rating (1-5) |
| `phone` | string | Hotel contact phone number |
| `email` | string | Hotel contact email address |

### Room Assignment Data

| Field | Type | Description |
|-------|------|-------------|
| `assignment_id` | integer | Unique assignment identifier |
| `room_number` | string | Room number |
| `floor` | integer | Floor number |
| `room_type` | string | Room type |
| `check_in` | datetime | Check-in date and time |
| `check_out` | datetime | Check-out date and time |
| `status` | string | Assignment status (CONFIRMED, PENDING, CANCELLED) |

---

## Response Examples

### GET /api/my-accommodations

```json
{
    "message": "Accommodation details retrieved successfully.",
    "summary": {
        "total_accommodations": 2,
        "current": 1,
        "upcoming": 1,
        "past": 0
    },
    "current_accommodation": {
        "assignment_id": 1,
        "status": "CONFIRMED",
        "status_category": "current",
        "hotel": {
            "accommodation_id": 5,
            "hotel_name": "Makkah Grand Hotel",
            "city": "Makkah",
            "room_type": "Double",
            "capacity": 100,
            "notes": "Near Haram",
            "stars": 5,
            "phone": "+966-12-555-0100",
            "email": "reservations@makkahgrand.com"
        },
        "room": {
            "room_id": 12,
            "room_number": "405",
            "floor": 4,
            "room_type": "Double"
        },
        "stay": {
            "check_in": "2026-01-10T14:00:00",
            "check_out": "2026-01-15T12:00:00",
            "duration_nights": 5,
            "check_in_day": "Saturday",
            "check_out_day": "Thursday"
        }
    },
    "accommodations": [...]
}
```

### GET /api/trips/{trip_id}/my-housing (403 Response)

When a pilgrim tries to access a trip they are not registered for:

```json
{
    "message": "You are not registered for this trip."
}
```
**HTTP Status: 403 Forbidden**

---

## Data Flow Diagram

```
┌──────────────┐     ┌─────────────────┐     ┌──────────────────┐
│   Pilgrim    │────▶│  Authentication │────▶│  Verify Pilgrim  │
│   Request    │     │   (Sanctum)     │     │     Profile      │
└──────────────┘     └─────────────────┘     └────────┬─────────┘
                                                      │
                                                      ▼
                     ┌─────────────────┐     ┌──────────────────┐
                     │  Return Own     │◀────│  Filter by       │
                     │  Data Only      │     │  pilgrim_id      │
                     └─────────────────┘     └──────────────────┘
```

---

## Recent Updates (January 2026)

### Added Fields to Pilgrim View

The following fields were added to all pilgrim accommodation endpoints to provide complete hotel information:

- **`stars`** - Hotel star rating
- **`phone`** - Hotel contact phone
- **`email`** - Hotel contact email
- **`capacity`** - Hotel/room capacity (now consistent across all methods)
- **`room_type`** - Added to housing endpoint for consistency

These fields allow pilgrims to access the full details of their accommodation, including contact information for the hotels they are staying at.

---

## Files Modified

| File | Changes |
|------|---------|
| [`PilgrimAccommodationController.php`](file:///c:/Users/admin/Downloads/NUSUK/app/Http/Controllers/Api/PilgrimAccommodationController.php) | Added `stars`, `phone`, `email` fields to all response methods |

---

## Related Documentation

- [Project Feature Documentation](./Project_Feature_Documentation.md)
- [API Routes](../routes/api.php)
