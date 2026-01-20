# Pilgrim Role Validation Update

**Date:** 2026-01-20
**Component:** AdminController (`app/Http/Controllers/Api/AdminController.php`)

## Overview

The validation rules and logic for user creation and updates have been standardized to strictly accept and use the uppercase string `'PILGRIM'` for the pilgrim role. This aligns the API validation with the database schema and resolves validation errors when the frontend sends `'PILGRIM'`.

## Changes

### 1. Validation Rules
The `role` field validation in both `store` and `update` methods now explicitly checks for `'PILGRIM'` instead of `'Pilgrim'`.

```php
// Before
'role' => 'required|in:ADMIN,SUPERVISOR,SUPPORT,Pilgrim'

// After
'role' => 'required|in:ADMIN,SUPERVISOR,SUPPORT,PILGRIM'
```

### 2. Role Normalization
The legacy support that converts `'USER'` (often utilized by frontend dropdowns for 'معتمر') now normalizes it to `'PILGRIM'` (uppercase).

```php
// Before
if (strtoupper($request->role) === 'USER') {
    $request->merge(['role' => 'Pilgrim']);
}

// After
if (strtoupper($request->role) === 'USER') {
    $request->merge(['role' => 'PILGRIM']);
}
```

### 3. Pilgrim Record Creation
The conditional check to automatically create a `pilgrims` table record upon user creation has been updated to check for `'PILGRIM'`.

```php
// Before
if ($user->role === 'Pilgrim') { ... }

// After
if ($user->role === 'PILGRIM') { ... }
```

## Impact
- **Fixes:** Resolves the "The selected role is invalid" error when submitting `'PILGRIM'`.
- **Consistency:** Ensures consistency between the application layer and the database ENUM definition.
- **Legacy Support:** Preserves the behavior where selecting 'USER' creates a 'PILGRIM' account.
