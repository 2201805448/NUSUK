# Pilgrim Affairs & Field Services Management API

This document provides a reference for the API endpoints related to managing groups, supervisors, housing, and pilgrim services within the Nusuk platform.

## 1. Group Management

### Create Group
Creates a new group within a specific trip.
- **Endpoint**: `POST /api/trips/{trip_id}/groups`
- **Description**: Create a new group for a trip.
- **Required Parameters**:
  - `group_code` (string): Unique code for the group.

### View Group Details
Retrieves detailed information about a specific group, including its members.
- **Endpoint**: `GET /api/groups/{id}`
- **Description**: Get group details.
- **Required Parameters**: None (ID in URL).

### Modify Group Data
Updates the information of an existing group.
- **Endpoint**: `PUT /api/groups/{id}`
- **Description**: Update group status or code.
- **Required Parameters**:
  - `group_code` (string, optional)
  - `status` (string, optional): e.g., `CREATED`, `ACTIVE`, `FINISHED`.

### Add Pilgrim to Group
Adds a user (pilgrim) to a group. Accounts for creating a pilgrim profile if one doesn't exist for the user.
- **Endpoint**: `POST /api/groups/{id}/members`
- **Description**: Add a member to the group.
- **Required Parameters**:
  - `user_id` (integer): The ID of the user to add as a pilgrim.

### Transfer Pilgrim Between Groups
Moves a pilgrim from their current group to another group.
- **Endpoint**: `POST /api/groups/{id}/transfer`
- **Description**: Transfer a pilgrim to a different group.
- **Required Parameters**:
  - `pilgrim_id` (integer): ID of the pilgrim to transfer.
  - `new_group_id` (integer): ID of the destination group.

### Remove Pilgrim from Group
Removes a pilgrim from a group (sets status to REMOVED).
- **Endpoint**: `POST /api/groups/{id}/remove`
- **Description**: Remove a member from the group.
- **Required Parameters**:
  - `pilgrim_id` (integer): ID of the pilgrim to remove.

---

## 2. Supervisor Assignment

### Assign Supervisor to Group
Assigns a supervisor to a specialized group.
- **Endpoint**: `PUT /api/groups/{id}/assign-supervisor`
- **Description**: Assign a user with SUPERVISOR role to the group.
- **Required Parameters**:
  - `supervisor_id` (integer): User ID of the supervisor.

### Modify Supervisor Assignment
Updates the assigned supervisor for a group (replaces the existing one).
- **Endpoint**: `PUT /api/groups/{id}/assign-supervisor`
- **Description**: Overwrite the current supervisor with a new one.
- **Required Parameters**:
  - `supervisor_id` (integer): User ID of the new supervisor.

### Display Assigned Supervisors List
Retrieves a list of groups with their assigned supervisors.
- **Endpoint**: `GET /api/trips/{trip_id}/groups` (or `GET /api/groups`)
- **Description**: List groups to see assigned supervisors.
- **Required Parameters**: None.

### Unassign Supervisor from Group
Removes the currently assigned supervisor from a group.
- **Endpoint**: `PUT /api/groups/{id}/unassign-supervisor`
- **Description**: Unlink the supervisor from the group.
- **Required Parameters**: None.

---

## 3. Housing Management

### Monitoring Housing Data
Retrieves the housing distribution for a trip, showing hotels, rooms, and current occupants.
- **Endpoint**: `GET /api/trips/{trip_id}/housing`
- **Description**: Monitor room occupancy and assignments.
- **Required Parameters**: None.

### Assign Rooms to Pilgrims
Assigns a pilgrim to a specific room in an accommodation.
- **Endpoint**: `POST /api/room-assignments`
- **Description**: Create a room assignment record.
- **Required Parameters**:
  - `pilgrim_id` (integer)
  - `accommodation_id` (integer)
  - `room_id` (integer)
  - `check_in` (date/datetime): e.g., `2025-01-01 14:00:00`
  - `check_out` (date/datetime)
  - `status` (string): `CONFIRMED` or `PENDING`.

### Modifying Housing Data
Updates an existing room assignment (e.g., moving a pilgrim to a different room).
- **Endpoint**: `PUT /api/room-assignments/{assignment_id}`
- **Description**: Modify room assignment details.
- **Required Parameters** (at least one):
  - `room_id` (integer, optional)
  - `accommodation_id` (integer, optional)
  - `check_in` (date/datetime, optional)
  - `check_out` (date/datetime, optional)
  - `status` (string, optional)

---

## 4. Pilgrim Services

### Display Pilgrim Card
Displays the digital card for the authenticated pilgrim, including personal info, group details, and housing.
- **Endpoint**: `GET /api/pilgrim/card`
- **Description**: Retrieve the logged-in pilgrim's digital ID card.
- **Required Parameters**: None (Requires User Authentication Token).
