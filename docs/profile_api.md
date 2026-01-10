# User Profile API

API endpoints for managing user profile information.

## Authentication

All endpoints require authentication via Bearer token (Sanctum).

```
Authorization: Bearer {token}
```

---

## Endpoints

### Get User Profile

Retrieves the authenticated user's profile including pilgrim details and recent booking history.

- **URL**: `/api/user/profile`
- **Method**: `GET`
- **Auth Required**: Yes

#### Success Response (200)

```json
{
  "user": {
    "user_id": 1,
    "full_name": "John Doe",
    "email": "john@example.com",
    "phone_number": "+1234567890",
    "role": "PILGRIM",
    "account_status": "ACTIVE",
    "pilgrim": {
      "pilgrim_id": 1,
      "passport_number": "AB123456",
      "nationality": "US"
    }
  },
  "services_history": [
    {
      "booking_id": 1,
      "booking_date": "2026-01-10",
      "status": "CONFIRMED",
      "package": { "name": "Premium Umrah" },
      "trip": { "trip_id": 5, "start_date": "2026-02-01" }
    }
  ]
}
```

---

### Update User Profile

Updates the authenticated user's profile information.

- **URL**: `/api/user/profile`
- **Method**: `PUT`
- **Auth Required**: Yes

#### Request Body

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `full_name` | string | No | User's full name (max 150 chars) |
| `email` | string | No | Valid email address (must be unique) |
| `phone_number` | string | No | Phone number (max 30 chars) |

> **Note**: Fields `role` and `account_status` cannot be modified through this endpoint.

#### Example Request

```json
{
  "full_name": "John Updated",
  "phone_number": "+9876543210"
}
```

#### Success Response (200)

```json
{
  "message": "Profile updated successfully",
  "user": {
    "user_id": 1,
    "full_name": "John Updated",
    "email": "john@example.com",
    "phone_number": "+9876543210",
    "role": "PILGRIM",
    "account_status": "ACTIVE"
  }
}
```

#### Validation Errors (422)

```json
{
  "message": "The email has already been taken.",
  "errors": {
    "email": ["The email has already been taken."]
  }
}
```

---

## Error Responses

| Code | Description |
|------|-------------|
| 401 | Unauthorized - Invalid or missing token |
| 422 | Validation error - Invalid input data |
| 500 | Server error |
