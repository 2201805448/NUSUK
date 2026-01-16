# Trip Accommodation Linking Update

**Date:** 2026-01-16  
**Feature:** Enhanced Trip-Hotel Linking API

---

## Overview

Modified the trip-accommodation linking functionality to use a dedicated endpoint with request body parameters instead of URL-based trip identification.

## Changes Made

### 1. API Route Addition

**File:** `routes/api.php`

Added a new dedicated endpoint for linking accommodations to trips:

```php
Route::post('/trip-accommodations', [TripController::class, 'addHotel']);
```

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/trip-accommodations` | Link a hotel to a trip |

---

### 2. Controller Method Update

**File:** `app/Http/Controllers/Api/TripController.php`

Modified the `addHotel` method to receive the `trip_id` from the request body instead of a URL parameter:

#### Before:
```php
public function addHotel(Request $request, $id)
{
    $trip = Trip::findOrFail($id);
    // ...
}
```

#### After:
```php
public function addHotel(Request $request)
{
    $tripId = $request->trip_id;
    $trip = Trip::findOrFail($tripId);
    // ...
}
```

---

## API Usage

### Link Hotel to Trip

```http
POST /api/trip-accommodations
Content-Type: application/json

{
    "trip_id": 1,
    "accommodation_id": 5
}
```

### Response (Success)

```json
{
    "message": "تم ربط الفندق بنجاح",
    "trip": {
        "trip_id": 1,
        "trip_name": "Hajj Trip 2026",
        "accommodations": [...]
    }
}
```

---

## Validation Rules

| Field | Rule | Description |
|-------|------|-------------|
| `trip_id` | required | Must be a valid trip ID |
| `accommodation_id` | required, exists | Must exist in accommodations table |
