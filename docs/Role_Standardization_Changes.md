# Role Standardization and 'Incomplete Profile' Fix

## Overview
This document details the changes made to standardize user roles to 'Pilgrim' and resolve the issue where new users created by Admins were missing their `pilgrims` profile record, causing "Incomplete Profile" errors in Digital Card generation.

## Problem
- Admin "Add User" form was assigning the role 'USER'.
- The system expects 'Pilgrim' for end-users.
- Creating a user with role 'Pilgrim' (or 'USER') did not automatically create the corresponding record in the `pilgrims` table.
- Without a `pilgrims` record, features like Digital Card and Group Management would fail.

## Changes Implemented

### 1. Database Role Standardization
A migration was run to:
- Convert all existing users with role 'USER' (case-insensitive) to 'Pilgrim'.
- Ensure all 'Pilgrim' roles are stored in Title Case.
- **Backfill Missing Records**: For every user with role 'Pilgrim' who did not have a corresponding record in the `pilgrims` table, a new record was created with the following placeholder data:
    - `passport_name`: User's `full_name`
    - `passport_number`: "PENDING"
    - `nationality`: "Unknown"

### 2. Admin Controller Updates (`AdminController.php`)
The `store` method was updated to:
- **Enforce Role**: If 'USER' is selected, it is automatically converted to 'Pilgrim'.
- **Auto-Link Profile**: When a new user with role 'Pilgrim' is created, the system now automatically creates a linked record in the `pilgrims` table with the same placeholder data as the backfill process.

## Verification
- **Existing Users**: All 'USER' accounts are now 'Pilgrim' and have a valid `pilgrims` entry.
- **New Users**: Creating a user via Admin panel properly sets up the full profile structure immediately.

## Next Steps
- Verify on the frontend that the Digital Card now loads correctly for previously affected users.
