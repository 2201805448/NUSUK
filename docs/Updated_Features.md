# Project Updated Features & Permissions

This document consolidates the recent changes and functional specifications for various system features, highlighting the responsible Actors and their permissions.

## 1. Review Pilgrim Documents
**Description**: Functionality to view and manage documents uploaded by pilgrims (Passport, Visa, Personal Data).
- **Admin**: Has **Full Authority**. Can view and update documents for any pilgrim.
- **Supervisor**: Has **Read-Only Access**. Can only view documents for pilgrims within their assigned groups. Cannot modify documents.

## 2. Hotel Reviews
**Description**: Allows pilgrims to view reviews for hotels associated with their trips.
- **Pilgrim**: Can view anonymous reviews for hotels in their trip.
- **System**: Ensures reviews are displayed anonymously.

## 3. Trip Logistics Management
**Description**: comprehensive management of trip-related logistical data.
- **Admin**:
    - **Hotels**: Add and associate approved hotels with trips.
    - **Rooms**: Manage room data and availability for hotels.
    - **Transport**: Add transportation details (e.g., buses).
    - **Drivers**: Define drivers for internal transport.
    - **Routes**: Define transport routes between locations.
    - **Movement Times**: Set schedules for movements.

## 4. Umrah Package Management
**Description**: Management of Umrah packages offered.
- **Admin**:
    - **Add Package**: Create new packages with price, duration, and services.
    - **Edit Package**: Update existing package details.
    - **Delete Package**: Remove packages no longer available.

## 5. User Management
**Description**: Administration of system users.
- **Admin**: Can Add, Edit, Update Status, and Delete users.

## 6. User Profile
**Description**: Personal profile management.
- **Authenticated User**: Can view their own profile, including basic info (Name, Email, Role).
- **Pilgrim**: Profile includes passport details, nationality, and booking history (last 5 bookings).

## 7. Reports
**Description**: Exporting system data.
- **Admin**: Can export trip reports in PDF and Excel formats.

## 8. Pilgrim Services
**Description**: Field services for pilgrims.
- **Supervisor**:
    - View assigned groups and pilgrims.
    - View pilgrim notes and feedback.
    - Manage attendance (if applicable).
