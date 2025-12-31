# Booking and Package Management API Documentation

This document outlines the API endpoints for Pilgrims to manage their packages and bookings.

## 1. Available Packages Display
**Description**: View a list of available Umrah packages.

- **Endpoint**: `GET /api/packages`
- **Method**: `GET`
- **Authorization**: Authenticated Users (Pilgrim, Admin, Supervisor)
- **Parameters**:
  - `is_active` (Optional, Boolean/String): Set to `1` or `true` to filter only active packages.

## 2. Package Details Display
**Description**: View the full details of a specific package (Accommodation, Transport, etc. context implied via Package description or linked Trip details).

- **Endpoint**: `GET /api/packages/{id}`
- **Method**: `GET`
- **Authorization**: Authenticated Users
- **Parameters**:
  - `id` (Required, Integer): ID of the package in the URL path.

## 3. View Booking History
**Description**: View all bookings for the authenticated pilgrim, including current and past bookings with status and basic data.

- **Endpoint**: `GET /api/bookings`
- **Method**: `GET`
- **Authorization**: Authenticated Users (Pilgrim)
- **Parameters**:
  - `status` (Optional, String): Filter by booking status (`PENDING`, `CONFIRMED`, `CANCELLED`).
- **Response Includes**:
  - `total_count`: Total number of bookings
  - `current_count`: Number of current/active bookings
  - `past_count`: Number of past/cancelled bookings
  - `bookings`: Array of booking objects with package and trip details

## 4. View Booking Details
**Description**: View detailed information about a specific booking.

- **Endpoint**: `GET /api/bookings/{id}`
- **Method**: `GET`
- **Authorization**: Authenticated Users (Pilgrim - Booking Owner)
- **Parameters**:
  - `id` (Required, Integer): ID of the booking in the URL path.
- **Response Includes**:
  - Booking details (ref, date, status, price, payment method)
  - Package details (name, duration, price, services)
  - Trip details (name, dates, status)
  - Payment history

## 5. Booking Execution
**Description**: Execute a booking process for a specific trip.

- **Endpoint**: `POST /api/bookings`
- **Method**: `POST`
- **Authorization**: Pilgrim
- **Parameters**:
  - `trip_id` (Required, Integer): ID of the trip being booked.
  - `pay_method` (Optional, String): Payment method (e.g., 'Credit Card').
  - `request_notes` (Optional, String): Any special requests.

## 6. Booking Modification Request
**Description**: Request a modification to booking data (e.g., companions, duration), subject to management approval.

- **Endpoint**: `POST /api/bookings/{id}/request-modification`
- **Method**: `POST`
- **Authorization**: Pilgrim (Booking Owner)
- **Parameters**:
  - `request_type` (Required, String): Type of modification (e.g., 'CHANGE_COMPANIONS').
  - `request_data` (Required, JSON/Array): Details of the change (e.g., `{'add_guest': 'Name'}`).

## 7. Booking Cancellation Request
**Description**: Request to cancel a booking, subject to management approval.

- **Endpoint**: `POST /api/bookings/{id}/request-cancellation`
- **Method**: `POST`
- **Authorization**: Pilgrim (Booking Owner)
- **Parameters**:
  - `reason` (Optional, String): Reason for the cancellation request.
