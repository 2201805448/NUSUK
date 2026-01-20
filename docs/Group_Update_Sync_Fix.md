# Group Update API Fix

## Overview
The `GroupController` has been updated to support syncing group members (pilgrims) during the group update process (`PUT /api/groups/{id}`).

## Changes
- **File**: `app/Http/Controllers/Api/GroupController.php`
- **Method**: `update`

### New Functionality
- Accepts `pilgrim_ids` (array of User IDs) in the request body.
- Validates the IDs against the `users` table.
- Automatically finds or creates `Pilgrim` records for the given users.
- Syncs the group membership to match the provided list (adding new ones, removing missing ones).

## Request Example
**Endpoint**: `PUT /api/groups/{id}`

**Body**:
```json
{
    "group_code": "GRP-123-UPDATED",
    "group_status": "ACTIVE",
    "pilgrim_ids": [1, 2, 5]
}
```

## Response
Returns the updated group object, including the `pilgrims` relationship.
