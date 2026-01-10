# Add User - Status Field Fix

**Date**: 2026-01-10  
**File Modified**: `app/Http/Controllers/Api/AdminController.php`

## Issue

A 422 (Unprocessable Content) error occurred when adding a new user because the `account_status` field was required by the backend but not present in the frontend "Add User" form.

## Solution

Modified the `store` method in `AdminController` to make `account_status` optional with a default value of `'ACTIVE'`.

### Changes Made

#### 1. Validation Rule (Line 63)

```diff
- 'account_status' => 'required|in:ACTIVE,INACTIVE,BLOCKED'
+ 'account_status' => 'sometimes|in:ACTIVE,INACTIVE,BLOCKED'
```

#### 2. User Creation (Line 72)

```diff
- 'account_status' => $request->account_status,
+ 'account_status' => $request->account_status ?? 'ACTIVE',
```

## Result

- The "Add User" form no longer requires the `account_status` field
- New users are automatically assigned `ACTIVE` status when not specified
- The 422 validation error is resolved
- Frontend forms work without modification

## API Endpoint

- **URL**: `POST /api/users`
- **Auth Required**: Yes (Admin only)

### Request Body

| Field | Type | Required | Default |
|-------|------|----------|---------|
| `full_name` | string | Yes | - |
| `email` | string | Yes | - |
| `phone_number` | string | Yes | - |
| `password` | string | Yes | - |
| `role` | string | Yes | - |
| `account_status` | string | **No** | `ACTIVE` |

### Valid Status Values

- `ACTIVE`
- `INACTIVE`
- `BLOCKED`
