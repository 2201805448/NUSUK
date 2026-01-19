# Supervisor Assignment API Fix

## Overview
This document details the changes made to resolve a 404 error for the supervisor assignment route and to implement the `assignSupervisor` logic in the backend.

## Changes

### 1. Routes (`routes/api.php`)
- **Added POST Route**: Added a new POST route definition for assigning a supervisor to a group.
- **Removed Duplicate Routes**: Removed duplicate/incorrect `PUT` routes for `assign-supervisor`.

```php
// New Route Definition
Route::post('/groups/{group}/supervisor', [\App\Http\Controllers\Api\GroupController::class, 'assignSupervisor']);
```

### 2. Controller (`app/Http/Controllers/Api/GroupController.php`)
- **Updated `assignSupervisor` Method**:
    - Changed method signature to use Route Model Binding (`GroupTrip $group`).
    - Updated validation to check for `supervisor_id` existence in the `users` table.
    - Implemented logic to update the `supervisor_id` on the group.
    - Returns a JSON response with the updated group and loaded supervisor relationship.

```php
public function assignSupervisor(Request $request, GroupTrip $group)
{
    $validated = $request->validate([
        'supervisor_id' => 'required|exists:users,user_id'
    ]);

    $group->update([
        'supervisor_id' => $validated['supervisor_id']
    ]);

    return response()->json([
        'message' => 'Supervisor assigned successfully',
        'group' => $group->load('supervisor')
    ]);
}
```

## Verification
- **Endpoint**: `POST /api/groups/{id}/supervisor`
- **Payload**:
  ```json
  {
      "supervisor_id": 123
  }
  ```
- **Expected Response**:
  - Status: 200 OK
  - Body:
    ```json
    {
        "message": "Supervisor assigned successfully",
        "group": {
            ...
            "supervisor": { ... }
        }
    }
    ```
