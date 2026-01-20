# Profile Image Upload Fix

**Date:** 2026-01-20
**Module:** User Profile / API

## Issue Description
Users were experiencing an issue where `passport_img` and `visa_img` were being saved as `null` in the database during profile updates, despite the text fields working correctly.

## Root Cause Analysis
Upon inspection of `App\Http\Controllers\Api\ProfileController.php`, several required components for file handling were missing:
1.  **Validation Rules:** The validation array did not account for file inputs for `passport_img` and `visa_img`.
2.  **File Retrieval:** The controller was not checking for or retrieving files using `$request->file()`.
3.  **Storage Logic:** There was no logic to store the uploaded files on the server or save their generated paths to the database.

## Applied Solution
The `update` method in `App\Http\Controllers\Api\ProfileController.php` was updated with the following changes:

### 1. Updated Validation Rules
Added validation rules to ensure the uploaded files are images, strictly of types `jpg`, `png`, or `jpeg`, and do not exceed 2048KB (2MB).

```php
'passport_img' => 'nullable|image|mimes:jpg,png,jpeg|max:2048',
'visa_img' => 'nullable|image|mimes:jpg,png,jpeg|max:2048',
```

### 2. Implemented File Storage Logic
Added logic to:
-   Check if the request contains `passport_img` or `visa_img`.
-   Store the files in the `public` disk under the `pilgrims/passports` and `pilgrims/visas` directories respectively.
-   Add the storage paths to the `$pilgrimData` array to be saved in the database.

```php
// Handle File Uploads
if ($request->hasFile('passport_img')) {
    $path = $request->file('passport_img')->store('pilgrims/passports', 'public');
    $pilgrimData['passport_img'] = $path;
}

if ($request->hasFile('visa_img')) {
    $path = $request->file('visa_img')->store('pilgrims/visas', 'public');
    $pilgrimData['visa_img'] = $path;
}
```

### 3. Updated Dependency Injection
Updated the closure for the database transaction to include `$request` so that file methods could be accessed within the transaction scope.

```php
DB::transaction(function () use ($user, $validated, $request) { ... }
```

## Verification
-   **Filesystem**: Confirmed `config/filesystems.php` has the `public` disk configured correctly.
-   **Syntax**: Verified PHP syntax validity for the modified controller.

## Requirement for Frontend
The frontend must ensure the form is submitted with encryption type `multipart/form-data` for the files to be correctly detected by the server.
