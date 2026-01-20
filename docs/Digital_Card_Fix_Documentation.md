# Digital Card Loading Fix
**Date:** 2026-01-21

## Issue
The Digital Card was not loading on the frontend.

## Investigation & Fixes

### 1. Route Definition
- **Issue:** The route was defined as `/pilgrim/card` in `routes/api.php`, but the requirement was `/pilgrim-card`.
- **Fix:** Updated `routes/api.php` to use the correct URI.
  ```php
  Route::get('/pilgrim-card', [\App\Http\Controllers\Api\PilgrimCardController::class, 'show']);
  ```
  This route is properly placed inside the `auth:sanctum` middleware group to ensure the user is authenticated.

### 2. User Identification
- **Verification:** Checked `app/Models/User.php` to ensure the `user_id` is correctly set as the primary key, as the controller uses `Auth::user()->user_id`.
- **Result:** Confirmed that `protected $primaryKey = 'user_id';` is present in the `User` model.

## Conclusion
The backend is now correctly configured to serve the Digital Card at `/api/pilgrim-card`.
