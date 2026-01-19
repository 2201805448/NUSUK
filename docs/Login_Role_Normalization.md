# Login API Role Normalization

**Date:** January 19, 2026  
**Purpose:** Ensure consistent role naming in API responses for frontend compatibility

---

## Summary

Updated the login API to return normalized role names with proper title case (e.g., `Pilgrim` instead of `PILGRIM` or `pilgrim`).

---

## Changes Made

### File Modified: `app/Http/Controllers/Api/AuthController.php`

Added role normalization logic in the `login` method:

```php
// Normalize role to proper title case for frontend consistency
$roleMap = [
    'admin' => 'Admin',
    'supervisor' => 'Supervisor',
    'pilgrim' => 'Pilgrim',
    'support' => 'Support',
];
$normalizedRole = $roleMap[strtolower(trim($user->role))] ?? ucfirst(strtolower($user->role));
```

---

## Role Mapping

| Database Value | API Response |
|----------------|--------------|
| `PILGRIM`, `pilgrim`, `Pilgrim` | `Pilgrim` |
| `ADMIN`, `admin`, `Admin` | `Admin` |
| `SUPERVISOR`, `supervisor` | `Supervisor` |
| `SUPPORT`, `support` | `Support` |

---

## Login Response Example

```json
{
    "message": "Login successful",
    "user": { ... },
    "role": "Pilgrim",
    "token": "1|abc123...",
    "redirect_url": "/dashboard"
}
```

---

## Booking Routes Access

The `/bookings` routes are configured in `routes/api.php` within the `auth:sanctum` middleware group **without any role restriction**, meaning:

- ✅ All authenticated users (including Pilgrims) can access booking endpoints
- ✅ The `RoleMiddleware` uses case-insensitive comparison (`Str::lower()`)

### Available Booking Endpoints for Pilgrims

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/bookings` | List user bookings |
| GET | `/api/bookings/{id}` | View booking details |
| POST | `/api/bookings` | Create new booking |
| POST | `/api/bookings/{id}/request-modification` | Request modification |
| POST | `/api/bookings/{id}/request-cancellation` | Request cancellation |

---

## Related Files

- `app/Http/Controllers/Api/AuthController.php` - Login method updated
- `app/Http/Middleware/RoleMiddleware.php` - Case-insensitive role comparison
- `routes/api.php` - Booking routes configuration
