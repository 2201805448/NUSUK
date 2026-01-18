# Latest Backend Updates

This document summarizes the most recent changes made to the backend system, covering Package Management, Booking Restrictions, and Trip Details.

## 1. Package Management
**Objective**: Enable linking packages to specific hotels and define room types.

### Database & Models
- **Table `packages`**:
  - Added `accommodation_id` (Foreign Key -> `accommodations`).
  - Added `room_type` (String).
- **Model `Package`**:
  - Added `accommodation_id` and `room_type` to `$fillable`.
  - Defined `accommodation()` relationship (`belongsTo`).

### API (`PackageController`)
- **`index` & `show`**:
  - Eager load `accommodation` data to return hotel details (name, location) with the package.
- **`store` & `update`**:
  - Added validation: `accommodation_id` (exists), `room_type` (string).
  - Included new fields in creation/update logic.

## 2. Booking System
**Objective**: Ensure only appropriate users can create bookings.

### API (`BookingController`)
- **`store` Method**:
  - Added a strict role check: Only users with the `pilgrim` role can create a booking.
  - Returns `403 Forbidden` for other roles (e.g., admin, stuff).

## 3. Trip Management
**Objective**: Capture flight and travel details for trips.

### Database & Models
- **Table `trips`**:
  - Added `flight_number` (String).
  - Added `airline` (String).
  - Added `route` (String).
- **Model `Trip`**:
  - Added the above fields to `$fillable` for mass assignment.
