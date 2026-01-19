# Group Creation Logic Documentation

## Overview
This document outlines the recent backend changes made to support the creation of groups and the linking of pilgrims via a new API endpoint.

## Changes

### 1. Routes (`routes/api.php`)
- **[NEW]** `POST /groups`
    - **Controller**: `App\Http\Controllers\Api\GroupController`
    - **Method**: `storeGroup`
    - **Description**: Allows creating a group by providing a name, trip ID, and a list of pilgrim (user) IDs. This route maps `name` to the database field `group_code` and automatically creates or finds Pilgrim profiles for the provided User IDs before linking them to the group.

### 2. Models
#### `App\Models\GroupTrip.php`
- **[NEW Relationship]** `pilgrims()`
    - Defines a `belongsToMany` relationship with the `Pilgrim` model via the `group_members` pivot table.
    - Allows using sync/attach methods to manage group members easily.

### 3. Controllers
#### `App\Http\Controllers\Api\GroupController.php`
- **[NEW Method]** `storeGroup(Request $request)`
    - **Validation**:
        - `name`: Required string (mapped to `group_code`).
        - `trip_id`: Required, must exist in `trips`.
        - `pilgrim_ids`: Required array of **User IDs**.
    - **Logic**:
        - Creates a new `GroupTrip` record.
        - Iterates through `pilgrim_ids`, checks if a `User` exists, and ensures a corresponding `Pilgrim` record exists (creating one if missing).
        - Syncs the list of Pilgrim IDs to the group using the `pilgrims()` relationship.
    - **Response**: Returns the created group with loaded relationships (`trip`, `pilgrims.user`) and a success message.

## Usage Example
**Request:**
```json
POST /api/groups
Content-Type: application/json
Authorization: Bearer <token>

{
    "name": "Mecca Group A",
    "trip_id": 10,
    "pilgrim_ids": [1, 2, 5]
}
```

**Response (201 Created):**
```json
{
    "message": "Group created successfully!",
    "group": {
        "group_id": 101,
        "group_code": "Mecca Group A",
        "trip_id": 10,
        "pilgrims": [...]
    }
}
```
