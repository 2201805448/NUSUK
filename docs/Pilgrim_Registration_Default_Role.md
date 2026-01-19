# Pilgrim Registration Default Role Update

**Date:** 2026-01-19
**Component:** Authentication / User Registration

## Summary
This update modifies the user registration process to automatically assign the **'Pilgrim'** role to all new users by default. This change ensures that any public registration through the API results in a user with 'Pilgrim' access privileges, facilitating the booking flow for end-users.

## Modified Files

### [AuthController.php](file:///c:/Users/admin/Downloads/NUSUK/app/Http/Controllers/Api/AuthController.php)
*Location: `app/Http/Controllers/Api/AuthController.php`*

#### Changes Implemented:
1.  **Validation Rule Removed**:
    *   Removed `role` from the request validation rules. The API no longer expects or accepts a `role` parameter during public registration.
    
    ```php
    // Before
    'role' => 'required|in:Admin,Supervisor,Pilgrim,Support',

    // After
    // (Line removed)
    ```

2.  **Default Role Assignment**:
    *   Hardcoded the `role` field to `'Pilgrim'` in the `User::create` method.
    *   Ensures consistent Role casing (Title Case 'Pilgrim') as required by the application's authorization logic.

    ```php
    // Before
    'role' => $request->role,

    // After
    'role' => 'Pilgrim',
    ```

## Impact
*   **New Users**: Any user registering via the `/api/register` endpoint will immediately have the 'Pilgrim' role.
*   **Security**: Prevents users from self-assigning privileged roles (like Admin or Supervisor) during registration.
*   **Booking Flow**: Allows new users to immediately access Pilgrim-specific features, such as 'Continue as Guest' or standard booking flows, without manual database intervention.

## Database Observations
*   During verification, it was noted that **User ID 2** did not exist in the database.
*   **Action**: Development/Testing users should register a new account to generate a valid Pilgrim user for testing purposes.
