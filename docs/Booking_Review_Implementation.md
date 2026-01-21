# Booking Review and Approval Implementation

## Overview
This document details the implementation of the Admin Booking Review and Approval system, allowing administrators to manage pilgrim bookings, approve or reject them, and handle modification requests.

## Changes

### 1. Database Schema
- **File**: `database/migrations/2025_12_22_193525_create_bookings_table.php`
- **Change**: Updated the `status` enum column in the `bookings` table to include `'REJECTED'`.
  ```php
  $table->enum('status', ['PENDING', 'CONFIRMED', 'CANCELLED', 'REJECTED'])->default('PENDING');
  ```

### 2. New Controller
- **File**: `app/Http/Controllers/Api/AdminBookingController.php`
- **Purpose**: dedicated controller for admin booking operations to keep `BookingController` focused on Pilgrim actions.
- **Key Methods**:
    - `index(Request $request)`: Retrieve paginated list of bookings. Supports filtering by `status` and defaults to showing latest bookings first.
    - `show($id)`: Retrieve detailed information for a specific booking, including relations (User, Package, Trip, Payments, Modifications).
    - `updateStatus(Request $request, $id)`: Update booking status to `CONFIRMED` or `REJECTED`. Accepts an optional `admin_reply`.
    - `indexModifications(Request $request)`: Retrieve paginated list of booking modification requests (e.g., Cancellation, Change Date).
    - `updateModificationStatus(Request $request, $id)`: Approve or Reject a modification request.
        - **Logic**: If a `CANCELLATION` request is `APPROVED`, the associated Booking status is automatically updated to `CANCELLED`.

### 3. API Routes
- **File**: `routes/api.php`
- **Location**: Inside the `admin` prefix group protected by `auth:sanctum`.
- **Endpoints**:
    - `GET /api/admin/bookings` - List all bookings.
    - `GET /api/admin/bookings/{id}` - View booking details.
    - `PUT /api/admin/bookings/{id}/status` - Approve/Reject booking.
    - `GET /api/admin/booking-modifications` - List modification requests.
    - `PUT /api/admin/booking-modifications/{id}/status` - Approve/Reject modification.

## How to Test
1.  **List Bookings**: `GET /api/admin/bookings?status=PENDING`
2.  **Approve/Reject**:
    - Method: `PUT /api/admin/bookings/{id}/status`
    - Body: `{ "status": "REJECTED", "admin_reply": "Booking rejected due to invalid documents." }`
3.  **Process Cancellation**:
    - Pilgrim requests cancellation via `BookingController`.
    - Admin views request via `GET /api/admin/booking-modifications`.
    - Admin approves via `PUT /api/admin/booking-modifications/{id}/status` with `{ "status": "APPROVED" }`.
    - Check that Booking status becomes `CANCELLED`.
