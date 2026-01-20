# User Role Standardization Update
**Date:** 2026-01-20

## Overview
This update addresses the inconsistency in user roles by standardizing `USER` to `Pilgrim` across the system. This ensures that all pilgrims are treated consistently by features like the Digital Card and Group Management.

## Changes Applied

### 1. Database Migration
- **Migration File:** `2026_01_20_142100_update_user_roles_to_pilgrim.php`
- **Action:** Updated all existing user records with the role `USER` (case-insensitive) to `Pilgrim`.

### 2. Admin Controller (`AdminController.php`)
- **Store & Update Methods:** 
    - Added logic to automatically intercept and map the `USER` role to `Pilgrim` before validation.
    - Updated validation rules to explicitly allow `Pilgrim` and removed `USER`.
    - **New Allowed Roles:** `ADMIN`, `SUPERVISOR`, `SUPPORT`, `Pilgrim`.

### 3. User Model (`User.php`)
- **Mutator Update:** The `setRoleAttribute` mutator now automatically converts any assignment of `USER` (or `user`) to `Pilgrim`.

## Impact
- **Admin Dashboard:** Creating a user with the generic 'User' role (if still present in UI dropdowns) will now correctly save them as 'Pilgrim'.
- **Data Consistency:** All backend logic relying on the 'Pilgrim' role will now correctly find these users.
