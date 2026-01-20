# Profile Update Synchronization Fix

## Overview
This update resolves the synchronization issue where profile updates were only reflecting in the `users` table and not the `pilgrims` table.

## Changes Noted
1.  **Dual-Table Update**: The `ProfileController@update` method now updates both the `User` model and the `Pilgrim` model within a single database transaction.
2.  **`updateOrCreate` Logic**: Uses `updateOrCreate` linked by `user_id` to ensure a `pilgrims` record is created if it doesn't exist (e.g., for users created before this logic was in place).
3.  **Unified Validation**: Added validation rules for pilgrim-specific fields:
    - `passport_name`
    - `passport_number`
    - `nationality`
    - `gender`
    - `date_of_birth`
    - `emergency_call`
4.  **Complete Response**: The API response now includes the `user` object with the `pilgrim` relationship loaded, allowing the frontend to display updated details immediately.

## API Endpoint
**PUT** `/api/user/profile`

### Request Body Example
```json
{
    "full_name": "Yazen Al-Sharif",
    "phone_number": "966500000000",
    "passport_number": "A12345678",
    "gender": "male",
    "nationality": "Saudi",
    "emergency_call": "966555555555"
}
```

### Response Example
```json
{
    "message": "Profile updated successfully",
    "user": {
        "user_id": 1,
        "full_name": "Yazen Al-Sharif",
        "email": "yazen@example.com",
        "role": "Pilgrim",
        "pilgrim": {
            "id": 5,
            "user_id": 1,
            "passport_number": "A12345678",
            "gender": "male",
            "nationality": "Saudi",
            ...
        }
    }
}
```
