# Package Management API

This document details the Package Management API endpoints for Admins.

### Authentication & Authorization
- **Middleware**: `auth:sanctum`, `role:ADMIN`

---

## Umrah Packages

### Create Package
Define a new Umrah package foundation.

- **URL**: `/api/packages`
- **Method**: `POST`
- **Body Parameters**:
  | Parameter | Type | Required | Description |
  |-----------|------|----------|-------------|
  | `package_name` | string | Yes | Name of the package |
  | `price` | number | Yes | Base Price |
  | `duration_days` | int | Yes | Duration in days |
  | `description` | string | No | Details |
  | `is_active` | boolean | No | Default true |

- **Success Response (201 Created)**:
  ```json
  {
    "message": "Package created successfully",
    "package": { ... }
  }
  ```

### Update Package
- **URL**: `/api/packages/{id}`
- **Method**: `PUT`
- **Body Parameters**: Same as Create (optional).

### Delete Package
- **URL**: `/api/packages/{id}`
- **Method**: `DELETE`

> **Note**: This controller currently does not implement `index` (List) or `show` (Get Details) methods for direct Package access. Packages are typically accessed via Trip relations or future implementation.
