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
    - **Authorization**: Removed the explicit `Auth::user()->role !== 'ADMIN'` check. Access is now controlled solely by `Route::middleware('role:ADMIN,SUPERVISOR')`, allowing both Admins and Supervisors to assign/reassign if authorized. This resolves the 403 "Unauthorized access" error for Supervisors.
    - **Validation**: Enforced `'supervisor_id' => 'required|exists:users,user_id'` to ensure compatibility with the `users` table schema (`user_id` primary key).
    - **Model Binding**: Used implicit Route Model Binding (`GroupTrip $group`) where `$group` argument matches `{group}` route parameter.
    - **Response**: Returns JSON with `group` and loaded `supervisor`. The `User` model correctly serializes `user_id` instead of `id`.

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
