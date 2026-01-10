# User Management API

This document details the User Management API endpoints. These endpoints are strictly protected and require the `ADMIN` role.

### Authentication & Authorization
- **Middleware**: `auth:sanctum`, `role:ADMIN`
- **Headers Required**:
  - `Authorization: Bearer <token>`
  - `Accept: application/json`

---

## User Profile

### 1. Get My Profile
Retrieve the authenticated user's profile, including pilgrim details and service history.

- **URL**: `/api/user/profile`
- **Method**: `GET`
- **Success Response (200 OK)**:
  ```json
  {
      "user": {
          "user_id": 1,
          "full_name": "My Name",
          "email": "my@email.com",
          "pilgrim": { ... } // null if not pilgrim
      },
      "services_history": [
          {
              "booking_id": 10,
              "booking_date": "...",
              "status": "CONFIRMED",
              "trip": { "trip_name": "Umrah 2024" }
          }
      ]
  }
  ```

---

## Dashboard Statistics

### 2. Get Dashboard Stats
Retrieve high-level metrics for the dashboard widgets.

- **URL**: `/api/stats`
- **Method**: `GET`
- **Success Response (200 OK)**:
  ```json
  {
    "total_users": 150,
    "total_pilgrims": 120,
    "total_bookings": 45,
    "total_trips": 12,
    "active_users": 145,
    "pending_users": 5
  }
  ```

---

## User Management

### 2. List All Users
Retrieve a paginated list of users with optional filters.

- **URL**: `/api/users`
- **Method**: `GET`
- **Query Parameters**:
  | Parameter | Type | Required | Description |
  |-----------|------|----------|-------------|
  | `page` | int | No | Page number (default 1) |
  | `role` | string | No | Filter by role (ADMIN, USER, etc.) |
  | `status` | string | No | Filter by status (ACTIVE, BLOCKED) |
  | `search` | string | No | Search by name or email |

- **Success Response (200 OK)**:
  ```json
  {
    "current_page": 1,
    "data": [
      {
        "user_id": 1,
        "full_name": "John Doe",
        "email": "john@example.com",
        "role": "USER",
        "account_status": "ACTIVE",
        "created_at": "2024-01-01T00:00:00.000000Z"
      }
    ],
    "total": 50,
    "per_page": 10
  }
  ```

### 3. Create User
Create a new user directly from the admin panel.

- **URL**: `/api/users`
- **Method**: `POST`
- **Body Parameters**:
  | Parameter | Type | Required | Description |
  |-----------|------|----------|-------------|
  | `full_name` | string | Yes | Full Name |
  | `email` | string | Yes | Email Address |
  | `phone_number` | string | Yes | Phone Number |
  | `password` | string | Yes | Password (min 8 chars) |
  | `role` | string | Yes | ADMIN, USER, SUPERVISOR, SUPPORT, PILGRIM |
  | `account_status` | string | Yes | ACTIVE, INACTIVE, BLOCKED |

- **Success Response (201 Created)**:
  ```json
  {
    "message": "User created successfully",
    "user": { ... }
  }
  ```

### 4. Get User Details
Retrieve detailed information for a specific user.

- **URL**: `/api/users/{id}`
- **Method**: `GET`
- **Success Response (200 OK)**:
  ```json
  {
    "user_id": 1,
    "full_name": "John Doe",
    ...
  }
  ```

### 5. Update User
Update specific fields of a user.

- **URL**: `/api/users/{id}`
- **Method**: `PUT`
- **Body Parameters**:
  | Parameter | Type | Required | Description |
  |-----------|------|----------|-------------|
  | `full_name` | string | No | Update Name |
  | `email` | string | No | Update Email |
  | `role` | string | No | Update Role |
  | `password` | string | No | Update Password |
  | `account_status` | string | No | Update Status |

- **Success Response (200 OK)**:
  ```json
  {
    "message": "User updated successfully",
    "user": { ... }
  }
  ```

### 6. Delete User
Permanently remove a user from the system.

- **URL**: `/api/users/{id}`
- **Method**: `DELETE`
- **Success Response (200 OK)**:
  ```json
  {
    "message": "User deleted successfully"
  }
  ```

### 7. Update User Status (Quick Action)
Quickly block or activate a user account.

- **URL**: `/api/users/{id}/status`
- **Method**: `PATCH`
- **Body Parameters**:
  | Parameter | Type | Required | Description |
  |-----------|------|----------|-------------|
  | `status` | string | Yes | ACTIVE, INACTIVE, or BLOCKED |

- **Success Response (200 OK)**:
  ```json
  {
    "message": "User status updated to BLOCKED",
    "user": { ... }
  }
  ```
