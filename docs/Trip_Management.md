# Trip Management API Documentation

This document outlines the API endpoints for managing Trip lifecycles, stages, and reporting.

## 1. Create Trip
**Description**: Schedule a new trip linked to a package.

- **Endpoint**: `POST /api/trips`
- **Authorization**: Admin
- **Parameters**:
  - `package_id` (Required, Integer): ID of the linked package.
  - `trip_name` (Required, String): Unique name for the trip.
  - `start_date` (Required, Date): YYYY-MM-DD.
  - `end_date` (Required, Date): YYYY-MM-DD.
  - `status` (Optional, String): Default 'PLANNED'.
  - `capacity` (Optional, Integer).
  - `notes` (Optional, String).

## 2. Define Trip Stages
**Description**: Define the timeline of the trip by adding transport legs and activity visits.

### A. Add Transport Stage
- **Endpoint**: `POST /api/trips/{id}/transports`
- **Authorization**: Admin
- **Parameters**:
  - `transport_type` (Required, String): e.g., 'Bus', 'Train'.
  - `route_from` (Required, String): Origin.
  - `route_to` (Required, String): Destination.
  - `departure_time` (Required, DateTime): YYYY-MM-DD HH:MM.
  - `arrival_time` (Optional, DateTime): YYYY-MM-DD HH:MM.

### B. Add Activity Stage
- **Endpoint**: `POST /api/trips/{id}/activities`
- **Authorization**: Admin
- **Parameters**:
  - `activity_type` (Required, String): e.g., 'Ziyarat'.
  - `location` (Required, String).
  - `activity_date` (Required, Date): YYYY-MM-DD.
  - `activity_time` (Required, Time): HH:MM (Start Time).
  - `end_time` (Optional, Time): HH:MM (End Time).

## 3. Update Trip Data
**Description**: Modify details of an existing trip or its stages.

### A. Update Trip Details
- **Endpoint**: `PUT /api/trips/{id}`
- **Authorization**: Admin
- **Parameters** (All Optional):
  - `trip_name`, `start_date`, `end_date`, `status`, `notes`.

### B. Update Stage Details
- **Transport**: `PUT /api/transports/{id}`
- **Activity**: `PUT /api/activities/{id}`

## 4. Cancel Trip
**Description**: Mark a trip as cancelled.

- **Endpoint**: `PATCH /api/trips/{id}/cancel`
- **Authorization**: Admin
- **Parameters**: None.

## 5. Display Visits & Activities (Supervisor View)
**Description**: View the full approved program of a trip, including all stages.

- **Endpoint**: `GET /api/trips/{id}`
- **Authorization**: Admin, Supervisor
- **Response Structure**:
  - Trip Details
  - `transports`: List of transport stages.
  - `activities`: List of activity stages.

## 6. Trip Status Reports
**Description**: proper reporting on trip statuses within time periods.

- **Endpoint**: `GET /api/reports/trips`
- **Authorization**: Admin
- **Query Parameters**:
  - `status` (Optional): Filter by 'PLANNED', 'ONGOING', 'COMPLETED', 'CANCELLED'.
  - `date_from` (Optional, Date): Trips starting on/after.
  - `date_to` (Optional, Date): Trips starting on/before.
