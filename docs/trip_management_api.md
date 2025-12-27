# Trip Management API

This document details the Trip Management API endpoints for Admins. Trips are specific scheduled instances of Packages.

### Authentication & Authorization
- **Middleware**: `auth:sanctum`, `role:ADMIN`

---

## 1. Trips

### List Trips
- **URL**: `/api/trips`
- **Method**: `GET`
- **Query Parameters**:
  - `package_id`: Filter by Package.
  - `status`: PLANNED, ONGOING, COMPLETED, CANCELLED.

### Create Trip
Schedule a new trip.

- **URL**: `/api/trips`
- **Method**: `POST`
- **Body Parameters**:
  | Parameter | Type | Required | Description |
  |-----------|------|----------|-------------|
  | `package_id` | int | Yes | Linked Package |
  | `trip_name` | string | Yes | Specific Name (e.g. "Ramadan Group A") |
  | `start_date` | date | Yes | YYYY-MM-DD |
  | `end_date` | date | Yes | YYYY-MM-DD |
  | `status` | string | No | Default PLANNED |
  | `max_capacity` | int | No | Max pilgrims |

### Get Trip Details
- **URL**: `/api/trips/{id}`
- **Method**: `GET`
- **Returns**: Trip details including linked hotels, activities, and transports.

---

## 2. Trip Logistics (Hotels & Activities)

### Add Hotel to Trip
Assign a hotel to a trip for a specific duration.

- **URL**: `/api/trips/{id}/hotels`
- **Method**: `POST`
- **Body Parameters**:
  | Parameter | Type | Required | Description |
  |-----------|------|----------|-------------|
  | `accommodation_id`| int | Yes | Hotel ID |
  | `check_in_date` | date | Yes | YYYY-MM-DD |
  | `check_out_date` | date | Yes | YYYY-MM-DD |

### Remove Hotel from Trip
- **URL**: `/api/trips/{id}/hotels/{accommodation_id}`
- **Method**: `DELETE`

### Add Activity to Trip
- **URL**: `/api/trips/{id}/activities`
- **Method**: `POST`
- **Body Parameters**:
  | Parameter | Type | Required | Description |
  |-----------|------|----------|-------------|
  | `activity_name` | string | Yes | Name (e.g., Ziyarat) |
  | `description` | string | No | Details |
  | `activity_date` | date | Yes | YYYY-MM-DD |
  | `time` | time | No | HH:MM |

### Update Activity
Modify an existing activity's details.

- **URL**: `/api/activities/{id}`
- **Method**: `PUT` or `PATCH`
- **Body Parameters**: Same as Add Activity (optional fields).

### Delete Activity
- **URL**: `/api/activities/{id}`
- **Method**: `DELETE`
