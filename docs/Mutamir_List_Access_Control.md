# Mutamir List Access Control Update

**Date:** 2026-01-22
**Feature:** Display Mutamir List per Group

## Change Summary
The functionality to display the list of Mutamirs (Pilgrims) within a specific group has been restricted to **Admin (Manager)** users only. Supervisors can no longer access this data.

## Technical Details

### Route Modification
- **File:** `routes/api.php`
- **Endpoint:** `GET /groups/{id}/pilgrims`
- **Middleware:** Moved to the `role:ADMIN` middleware group.
- **Previous State:** Accessible by `ADMIN` and `SUPERVISOR`.
- **New State:** Accessible by `ADMIN` only.

### Controller Cleanup
- **File:** `app/Http/Controllers/Api/GroupController.php`
- **Method:** `listPilgrims($id)`
- **Change:** Removed commented-out authorization logic to rely solely on route-level middleware protection.

## Verification
- **Test Script:** `test_manager_groups_permissions.php`
- **Results:**
    - **Supervisor:** Requesting `/groups/{id}/pilgrims` returns `403 Forbidden`.
    - **Admin:** Requesting `/groups/{id}/pilgrims` returns `200 OK` with the list of pilgrims.
