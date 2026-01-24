# Attendance Route Update

## Overview
This document details the changes made to `routes/api.php` to support attendance tracking by supervisors.

## Changes

### `routes/api.php`

1.  **Import Statement**:
    Added the import for `AttendanceController`:
    ```php
    use App\Http\Controllers\Api\AttendanceController;
    ```

2.  **Route Definition**:
    Added the following route within the `auth:sanctum` middleware group:
    ```php
    Route::post('attendance/{pilgrim_id}', [AttendanceController::class, 'store']);
    ```

    *   **Method**: `POST`
    *   **URI**: `attendance/{pilgrim_id}`
    *   **Controller Method**: `AttendanceController@store`
    *   **Middleware**: `auth:sanctum` (Ensures `Auth::id()` is available)

## Reason for Change
The `store` method in `AttendanceController` requires an authenticated user to correctly capture the `supervisor_id` using `Auth::id()`. Placing this route inside the `auth:sanctum` middleware group ensures that the request is authenticated before reaching the controller.
