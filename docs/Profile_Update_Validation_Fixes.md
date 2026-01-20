# Profile Update Validation Fixes

**Date:** 2026-01-20
**Module:** User Profile & Pilgrim Management

## Overview
This document outlines the fixes applied to the Profile Update mechanism to resolve a `422 Unprocessable Content` error. The issue stemmed from case-sensitivity mismatches in validation rules and restricted mass assignment for unified profile fields on the `User` model.

## Issues Resolved

1.  **Gender Validation Failure (422 Error)**
    *   **Problem:** The `gender` field was being sent as uppercase "MALE" or "FEMALE" from the frontend (and required as such by the database/migration), but the backend validation rule strictly expected lowercase "male" or "female".
    *   **Fix:** Updated the validation rule in `ProfileController` to accept uppercase values.

2.  **Mass Assignment Restrictions**
    *   **Problem:** Unified profile fields (like `passport_number`, `nationality`, `gender`) were not included in the `$fillable` array of the `User` model. While these are primarily `Pilgrim` data, ensuring they are fillable on the `User` model prevents issues if the logic attempts to update attributes via the User instance or if the architecture shifts to store some duplicates on the User table.
    *   **Fix:** Added the following fields to the `User` model's `$fillable` property:
        *   `passport_name`
        *   `passport_number`
        *   `nationality`
        *   `gender`
        *   `date_of_birth`
        *   `emergency_call`

3.  **Nullable Fields Handling**
    *   **Problem:** Empty strings sent for fields like `passport_number` could potentially trigger validation errors if not explicitly handled as nullable strings.
    *   **Fix:** Confirmed and ensured that `passport_name`, `passport_number`, `nationality`, and `emergency_call` are validated as `nullable|string`.

## Technical Implementation Details

### 1. `app/Http/Controllers/Api/ProfileController.php`

**Validation Rules Updated:**
```php
'passport_name' => 'nullable|string|max:150',
'passport_number' => 'nullable|string|max:50',
'nationality' => 'nullable|string|max:100',
'gender' => 'nullable|string|in:MALE,FEMALE', // Changed from 'male,female' to 'MALE,FEMALE'
'date_of_birth' => 'nullable|date',
'emergency_call' => 'nullable|string|max:50',
```

### 2. `app/Models/User.php`

**Fillable Array Updated:**
```php
protected $fillable = [
    'full_name',
    'email',
    'phone_number',
    'password',
    'role',
    'account_status',
    // Added Unified Fields
    'passport_name',
    'passport_number',
    'nationality',
    'gender',
    'date_of_birth',
    'emergency_call',
];
```

## Verification
*   **Payload Test:** Sending `gender: "MALE"` now passes validation.
*   **Empty Values:** Sending `""` for `passport_number` is accepted without error.
*   **Data Consistency:** Profile data is correctly routed to the update logic, and mass assignment on the User model is enabled for these fields, supporting the intended update flow.
