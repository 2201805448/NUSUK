# ActivityController API Documentation

**Date:** January 17, 2026  
**Version:** 1.0

## Overview

This document details the implementation of the `ActivityController` which provides full CRUD functionality for managing activities in the system.

---

## Controller Location

```
app/Http/Controllers/Api/ActivityController.php
```

---

## Methods Implemented

### 1. `index()` - List All Activities

Returns a list of all activities in the system.

**Endpoint:** `GET /api/activities`

**Response:**
```json
[
    {
        "activity_id": 1,
        "trip_id": 5,
        "activity_type": "City Tour",
        "location": "Makkah",
        "activity_date": "2026-02-15",
        "activity_time": "09:00",
        "end_time": "12:00",
        "status": "SCHEDULED"
    }
]
```

---

### 2. `store()` - Create New Activity

Creates a new activity record in the database.

**Endpoint:** `POST /api/activities`

**Validation Rules:**

| Field | Rule | Description |
|-------|------|-------------|
| `trip_id` | required, exists:trips,trip_id | Must reference a valid trip |
| `activity_type` | required, string, max:100 | Type of activity |
| `location` | required, string, max:150 | Activity location |
| `activity_date` | required, date | Date of the activity |
| `activity_time` | required | Start time (HH:mm format) |
| `end_time` | nullable | Optional end time |
| `status` | nullable, string | Activity status (flexible, accepts any string or empty) |

> [!NOTE]
> The `status` validation was updated from a strict enum (`in:SCHEDULED,IN_PROGRESS,DONE,CANCELLED`) to a flexible `nullable|string` rule to accommodate various status values from the frontend.

**Request Example:**
```json
{
    "trip_id": 5,
    "activity_type": "Guided Tour",
    "location": "Madinah",
    "activity_date": "2026-02-20",
    "activity_time": "10:00",
    "end_time": "13:00",
    "status": "SCHEDULED"
}
```

**Success Response (201):**
```json
{
    "message": "Activity created successfully",
    "activity": {
        "activity_id": 2,
        "trip_id": 5,
        "activity_type": "Guided Tour",
        "location": "Madinah",
        "activity_date": "2026-02-20",
        "activity_time": "10:00",
        "end_time": "13:00",
        "status": "SCHEDULED"
    }
}
```

---

### 3. `show($id)` - Display Single Activity

Retrieves details of a specific activity.

**Endpoint:** `GET /api/activities/{id}`

**Response:**
```json
{
    "activity_id": 1,
    "trip_id": 5,
    "activity_type": "City Tour",
    "location": "Makkah",
    "activity_date": "2026-02-15",
    "activity_time": "09:00",
    "end_time": "12:00",
    "status": "SCHEDULED"
}
```

---

### 4. `update($id)` - Update Activity

Updates an existing activity record.

**Endpoint:** `PUT /api/activities/{id}`

**Validation Rules:**

| Field | Rule | Description |
|-------|------|-------------|
| `trip_id` | sometimes, exists:trips,trip_id | Optional trip reference update |
| `activity_type` | sometimes, string, max:100 | Activity type |
| `location` | sometimes, string, max:150 | Location update |
| `activity_date` | sometimes, date | Date update |
| `activity_time` | sometimes | Time update (flexible format) |
| `status` | in:SCHEDULED,IN_PROGRESS,DONE,CANCELLED | Status update |

> [!NOTE]
> The `activity_time` validation was relaxed from strict `date_format:H:i` to `sometimes` to allow flexible time input formats.

**Request Example:**
```json
{
    "status": "IN_PROGRESS",
    "location": "Updated Location"
}
```

**Success Response:**
```json
{
    "message": "Activity updated successfully",
    "activity": {
        "activity_id": 1,
        "trip_id": 5,
        "activity_type": "City Tour",
        "location": "Updated Location",
        "activity_date": "2026-02-15",
        "activity_time": "09:00",
        "end_time": "12:00",
        "status": "IN_PROGRESS"
    }
}
```

---

### 5. `destroy($id)` - Delete Activity

Removes an activity from the system.

**Endpoint:** `DELETE /api/activities/{id}`

**Success Response:**
```json
{
    "message": "Activity deleted successfully"
}
```

---

## Activity Status Values

| Status | Description |
|--------|-------------|
| `SCHEDULED` | Activity is planned but not started |
| `IN_PROGRESS` | Activity is currently ongoing |
| `DONE` | Activity has been completed |
| `CANCELLED` | Activity was cancelled |

---

## Authorization

All activity endpoints require:
- **Authentication:** Bearer token via Laravel Sanctum
- **Roles:** `ADMIN` or `SUPERVISOR`

---

## API Endpoints Summary

| Method | Endpoint | Action | Description |
|--------|----------|--------|-------------|
| GET | `/api/activities` | index | List all activities |
| POST | `/api/activities` | store | Create new activity |
| GET | `/api/activities/{id}` | show | Get single activity |
| PUT/PATCH | `/api/activities/{id}` | update | Update activity |
| DELETE | `/api/activities/{id}` | destroy | Delete activity |

---

## Related Routes

Activities can also be managed under specific trips:

| Method | Endpoint | Controller | Description |
|--------|----------|------------|-------------|
| POST | `/api/trips/{id}/activities` | TripController@addActivity | Add activity to a trip |
