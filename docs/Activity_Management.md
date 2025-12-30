# Activity Management Functions

This document details the functions available for managing program activities (visits).

## 1. Add Activity
**Description**: Add a new activity or visit to a specific trip program.

- **Endpoint**: `POST /api/trips/{id}/activities`
- **Controller**: `TripController@addActivity`
- **Parameters**:
  - `activity_type` (string, required): Type of activity (e.g., VISIT, LECTURE).
  - `location` (string, required): Location name.
  - `activity_date` (date, required): YYYY-MM-DD.
  - `activity_time` (time, required): HH:MM.
  - `status` (string, optional): SCHEDULED (default).

## 2. Edit Activity
**Description**: Modify the details of an existing activity in the approved program.

- **Endpoint**: `PUT /api/activities/{id}`
- **Controller**: `ActivityController@update`
- **Parameters**:
  - `location` (string, optional): Update location.
  - `activity_time` (time, optional): Update time.
  - `activity_date` (date, optional): Update date.
  - `status` (string, optional): Update status. Allowed values: `SCHEDULED`, `IN_PROGRESS`, `DONE`, `CANCELLED`.
  
**Note**: This endpoint is available to **Supervisors** and **Admins** to enable real-time tracking of trip activities.

## 3. Delete Activity
**Description**: Remove an activity or visit from the program (e.g., if cancelled).

- **Endpoint**: `DELETE /api/activities/{id}`
- **Controller**: `ActivityController@destroy`
- **Response**: Returns 200 OK on success.

---
**Verification**:
These functions have been verified via `test_add_activity.php` and `test_activity_modification.php`.
