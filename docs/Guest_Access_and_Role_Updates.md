# Guest Access and Role Standardization Updates

**Date:** 2026-01-19
**See also:** `routes/api.php`, `app/Models/User.php`, `app/Http/Controllers/Api/AuthController.php`

## 1. Guest Access (Public Routes)

To support the "Continue as Guest" feature on the frontend, several API endpoints have been moved from the authenticated scope to the public scope. This allows users to browse packages and view hotel details without needing to log in (no Bearer Token required).

### Modified Routes (`routes/api.php`)

The following `GET` routes are now **Public**:

#### Packages
- `GET /packages` (List all packages)
- `GET /packages/{id}` (View package details)
- `GET /packages/{id}/reviews` (View package reviews)
- `GET /trips/{id}/hotel-reviews` (View hotel reviews for a specific trip)

#### Accommodations & Rooms
- `GET /accommodations` (List hotels)
- `GET /accommodations/{id}` (View hotel details)
- `GET /rooms` (List rooms)
- `GET /rooms/{id}` (View room details)

---

## 2. Role Standardization

To ensure consistency across the application (Backend, Database, and Frontend Navbar), user roles have been standardized to strict Title Case strings.

### Key Changes

#### User Model (`app/Models/User.php`)
- **Mutator (`setRoleAttribute`)**: Automatically converts any role value to Title Case before saving to the database.
  - Example: `admin` becomes `Admin`.
- **Accessor (`getRoleAttribute`)**: Ensures that any role retrieved from the database is returned in Title Case.
  - Benefit: This guarantees that legacy data (e.g., `admin` or `ADMIN`) is always sent to the Frontend as `Admin`, ensuring permission checks work correctly.

#### Auth Controller (`app/Http/Controllers/Api/AuthController.php`)
- **Registration Validation**: The `role` field validation in the `register` method has been updated to strictly accept the following values:
  - `Admin`
  - `Supervisor`
  - `Pilgrim`
  - `Support`
- The `USER` role has been removed from the validation list as it is no longer used.

## Impact
- **Guest Users**: Can now browse the catalog and hotels freely.
- **Frontend Stability**: Role-based UI elements (Navbar, Buttons) will function reliably due to consistent role casing from the API.
