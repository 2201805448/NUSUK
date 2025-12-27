# Transport Management API

This document details the Transport Management API endpoints. These endpoints are strictly protected and require the `ADMIN` role.

### Authentication & Authorization
- **Middleware**: `auth:sanctum`, `role:ADMIN`
- **Headers Required**:
  - `Authorization: Bearer <token>`
  - `Accept: application/json`

---

## 1. Transports

### List Transports
Retrieve a list of transports. Can be filtered by `trip_id`.

- **URL**: `/api/transports`
- **Method**: `GET`
- **Query Parameters**:
  | Parameter | Type | Required | Description |
  |-----------|------|----------|-------------|
  | `trip_id` | int | No | Filter by Trip ID |

- **Success Response (200 OK)**:
  ```json
  [
    {
      "transport_id": 1,
      "trip_id": 10,
      "transport_type": "Bus",
      "route_from": "Jeddah",
      "route_to": "Makkah",
      ...
    }
  ]
  ```

### Create Transport
Add a new transport to a trip.

- **URL**: `/api/transports`
- **Method**: `POST`
- **Body Parameters**:
  | Parameter | Type | Required | Description |
  |-----------|------|----------|-------------|
  | `trip_id` | int | Yes | Trip ID |
  | `transport_type` | string | Yes | Bus, Train, etc. |
  | `departure_time` | datetime | Yes | YYYY-MM-DD HH:MM:SS |
  | `route_id` | int | No | Pre-defined Route ID |
  | `driver_id` | int | No | Driver ID |
  | `route_from` | string | Cond* | Required if route_id is missing |
  | `route_to` | string | Cond* | Required if route_id is missing |
  
- **Success Response (201 Created)**:
  ```json
  {
    "message": "Transport added successfully",
    "transport": { ... }
  }
  ```

### Get Transport Details
- **URL**: `/api/transports/{id}`
- **Method**: `GET`

### Update Transport
- **URL**: `/api/transports/{id}`
- **Method**: `PUT/PATCH`
- **Body Parameters**: Same as Create (optional fields).

### Delete Transport
- **URL**: `/api/transports/{id}`
- **Method**: `DELETE`

---

## 2. Drivers

### CRUD Operations for Drivers
Standard resource routes.

- **List**: `GET /api/drivers`
- **Create**: `POST /api/drivers`
  - Body: `name`, `license_number`, `phone_number`
- **Show**: `GET /api/drivers/{id}`
- **Update**: `PUT /api/drivers/{id}`
- **Delete**: `DELETE /api/drivers/{id}`

---

## 3. Transport Routes

### CRUD Operations for Transport Routes
Standard resource routes.

- **List**: `GET /api/routes`
- **Create**: `POST /api/routes`
  - Body: `start_location`, `end_location`, `distance_km`, `estimated_duration_minutes`
- **Show**: `GET /api/routes/{id}`
- **Update**: `PUT /api/routes/{id}`
- **Delete**: `DELETE /api/routes/{id}`
