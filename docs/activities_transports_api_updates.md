# Activities & Transports API Updates

**Date:** January 17, 2026  
**Version:** 1.0

## Overview

This document details the recent updates made to the Activities and Transports API endpoints to enable independent resource creation without requiring a trip association.

---

## Changes Made

### 1. Activities Route Fix

**Problem:** The frontend was receiving a `MethodNotAllowedHttpException` when trying to POST to `/api/activities` because the route only supported GET and HEAD methods.

**Solution:** Removed the `->except(['store'])` restriction from the activities API resource route.

#### File Modified
- `routes/api.php`

#### Before
```php
Route::apiResource('activities', \App\Http\Controllers\Api\ActivityController::class)->except(['store']);
```

#### After
```php
Route::apiResource('activities', \App\Http\Controllers\Api\ActivityController::class);
```

#### Available Endpoints
| Method | Endpoint | Action |
|--------|----------|--------|
| GET | `/api/activities` | List all activities |
| POST | `/api/activities` | Create a new activity |
| GET | `/api/activities/{id}` | Show a specific activity |
| PUT/PATCH | `/api/activities/{id}` | Update an activity |
| DELETE | `/api/activities/{id}` | Delete an activity |

> [!NOTE]
> Activities can also be created under a specific trip using `POST /api/trips/{id}/activities` via `TripController@addActivity`.

---

### 2. Transports - Make trip_id Nullable

**Problem:** Transports could only be created when associated with a trip (`trip_id` was required).

**Solution:** Made the `trip_id` field nullable in both the database and validation rules.

#### Files Modified/Created

| File | Change |
|------|--------|
| `database/migrations/2026_01_17_121925_make_trip_id_nullable_in_transports_table.php` | New migration to make `trip_id` nullable |
| `app/Http/Controllers/Api/TransportController.php` | Updated validation rule |

#### Migration Details
The migration:
1. Drops the existing foreign key constraint on `trip_id`
2. Modifies `trip_id` column to be nullable
3. Re-adds the foreign key constraint with nullable support

#### Validation Change

**Before:**
```php
'trip_id' => 'required|exists:trips,trip_id',
```

**After:**
```php
'trip_id' => 'nullable|exists:trips,trip_id',
```

#### Available Endpoints
| Method | Endpoint | Action |
|--------|----------|--------|
| GET | `/api/transports` | List all transports (filterable by `?trip_id=`) |
| POST | `/api/transports` | Create a new transport (trip_id optional) |
| GET | `/api/transports/{id}` | Show a specific transport |
| PUT/PATCH | `/api/transports/{id}` | Update a transport |
| DELETE | `/api/transports/{id}` | Delete a transport |

> [!TIP]
> Transports can be created independently and later associated with a trip, or created directly under a trip using `POST /api/trips/{id}/transports`.

---

## API Request Examples

### Create Activity (Independent)

```http
POST /api/activities
Content-Type: application/json
Authorization: Bearer {token}

{
    "name": "City Tour",
    "description": "Guided tour of historical sites",
    "location": "Makkah",
    "activity_date": "2026-02-15",
    "status": "scheduled"
}
```

### Create Transport (Without Trip)

```http
POST /api/transports
Content-Type: application/json
Authorization: Bearer {token}

{
    "transport_type": "Bus",
    "route_from": "Jeddah Airport",
    "route_to": "Makkah Hotel",
    "departure_time": "2026-02-15T10:00:00",
    "arrival_time": "2026-02-15T11:30:00",
    "notes": "Airport pickup service"
}
```

### Create Transport (With Trip)

```http
POST /api/transports
Content-Type: application/json
Authorization: Bearer {token}

{
    "trip_id": 1,
    "transport_type": "Bus",
    "route_from": "Makkah",
    "route_to": "Madinah",
    "departure_time": "2026-02-20T08:00:00"
}
```

---

## Authorization

Both endpoints require authentication via Sanctum and appropriate role authorization:

| Resource | Required Roles |
|----------|---------------|
| Activities | `ADMIN` or `SUPERVISOR` |
| Transports | `ADMIN` only |

---

## Post-Update Commands

After deploying these changes, run the following commands:

```bash
# Run the migration
php artisan migrate

# Clear route cache
php artisan route:clear

# Clear config cache (if cached)
php artisan config:clear
```
