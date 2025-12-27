# Accommodation Management API

This document details the Accommodation (Hotel) Management API endpoints. These endpoints are strictly protected and require the `ADMIN` role.

### Authentication & Authorization
- **Middleware**: `auth:sanctum`, `role:ADMIN`

---

## 1. Accommodations (Hotels)

### List Accommodations
- **URL**: `/api/accommodations`
- **Method**: `GET`

### Create Accommodation
- **URL**: `/api/accommodations`
- **Method**: `POST`
- **Body Parameters**:
  | Parameter | Type | Required | Description |
  |-----------|------|----------|-------------|
  | `name` | string | Yes | Hotel Name |
  | `city` | string | Yes | Makkah, Madinah, etc. |
  | `address` | string | No | Full address |
  | `star_rating` | int | No | 1-5 |

### Show Accommodation
- **URL**: `/api/accommodations/{id}`
- **Method**: `GET`

### Update Accommodation
- **URL**: `/api/accommodations/{id}`
- **Method**: `PUT`

### Delete Accommodation
- **URL**: `/api/accommodations/{id}`
- **Method**: `DELETE`

---

## 2. Rooms

### List Rooms
- **URL**: `/api/rooms`
- **Method**: `GET`
- **Query Parameters**:
  - `accommodation_id`: Filter by hotel.

### Create Room
- **URL**: `/api/rooms`
- **Method**: `POST`
- **Body Parameters**:
  | Parameter | Type | Required | Description |
  |-----------|------|----------|-------------|
  | `accommodation_id`| int | Yes | Hotel ID |
  | `room_type` | string | Yes | Single, Double, Suite |
  | `price_per_night` | number | Yes | Price |
  | `capacity` | int | Yes | Number of beds |

### Show, Update, Delete
Standard resource endpoints:
- `GET /api/rooms/{id}`
- `PUT /api/rooms/{id}`
- `DELETE /api/rooms/{id}`
