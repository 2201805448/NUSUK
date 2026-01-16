# Trip Flight Fields Addition

**Date:** 2026-01-16  
**Feature:** Flight information fields for Trips

---

## Overview

Added three new columns to the `trips` table to support flight information required by the frontend. These fields allow storing airline and flight details associated with each trip.

## Changes Made

### 1. Database Migration

**File:** `database/migrations/2025_12_22_193436_create_trips_table.php`

Added the following columns:

```php
$table->string('flight_number')->nullable();
$table->string('airline')->nullable();
$table->string('route')->nullable();
```

| Column | Type | Nullable | Description |
|--------|------|----------|-------------|
| `flight_number` | string | Yes | Flight number (e.g., "SV123") |
| `airline` | string | Yes | Airline name (e.g., "Saudi Airlines") |
| `route` | string | Yes | Flight route (e.g., "JED → MED") |

---

### 2. Model Update

**File:** `app/Models/Trip.php`

Added the new fields to the `$fillable` array:

```php
protected $fillable = [
    'trip_name',
    'start_date',
    'end_date',
    'status',
    'capacity',
    'notes',
    'flight_number',  // NEW
    'airline',        // NEW
    'route',          // NEW
];
```

---

### 3. Controller Validation

**File:** `app/Http/Controllers/Api/TripController.php`

#### Store Method
Added validation rules for creating trips:

```php
'flight_number' => 'nullable|string|max:50',
'airline' => 'nullable|string|max:100',
'route' => 'nullable|string|max:200',
```

#### Update Method
Added validation rules for updating trips:

```php
'flight_number' => 'nullable|string|max:50',
'airline' => 'nullable|string|max:100',
'route' => 'nullable|string|max:200',
```

---

## API Usage

### Create Trip with Flight Info

```http
POST /api/trips
Content-Type: application/json

{
    "trip_name": "Hajj Trip 2026",
    "start_date": "2026-06-10",
    "end_date": "2026-06-25",
    "status": "PLANNED",
    "capacity": 50,
    "flight_number": "SV123",
    "airline": "Saudi Airlines",
    "route": "CAI → JED"
}
```

### Update Trip Flight Info

```http
PUT /api/trips/{id}
Content-Type: application/json

{
    "flight_number": "SV456",
    "airline": "Flynas",
    "route": "JED → MED"
}
```

---

## Migration Note

> [!IMPORTANT]
> If the database already has data in the `trips` table, create a new migration to add these columns:
> ```bash
> php artisan make:migration add_flight_fields_to_trips_table
> ```
> For a fresh installation, run:
> ```bash
> php artisan migrate:fresh
> ```
