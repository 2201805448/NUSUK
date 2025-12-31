# Project Feature Documentation

This document outlines the key features implemented in the Pilgrim Affairs and Field Services module, detailing their purpose, user roles, and API endpoints.

## 1. Review Pilgrim Documents
**Description**: Allows supervisors and administrators to view documents uploaded by pilgrims (e.g., passports, visas).
**Actors**: Admin, Supervisor
**Endpoints**:
- List all pilgrims with documents: `GET /api/pilgrims/documents`
- View specific pilgrim documents: `GET /api/pilgrims/{id}/documents`

## 2. View Booking History
**Description**: Allows users to view their past and current booking records.
**Actors**: Pilgrim (User), Admin
**Endpoints**:
- List Bookings: `GET /api/bookings`
- View Booking Details: `GET /api/bookings/{id}`

## 3. View Previous Activity Log
**Description**: Provides a log of activities and movements related to a trip or pilgrim.
**Actors**: Pilgrim, Admin
**Endpoints**:
- List Activity Logs: `GET /api/activity-log`
- View Trip Specific Activity: `GET /api/activity-log/trips/{trip_id}`

## 4. View Trip Schedule
**Description**: Allows pilgrims to view the full timeline and schedule of their trip.
**Actors**: Pilgrim
**Endpoints**:
- Full Schedule: `GET /api/trips/{trip_id}/schedule`
- Today's Schedule: `GET /api/trips/{trip_id}/schedule/today`

## 5. View Accommodation Details
**Description**: Allows pilgrims to see details of their assigned accommodation.
**Actors**: Pilgrim
**Endpoints**:
- My Accommodations: `GET /api/my-accommodations`
- Current Accommodation: `GET /api/my-accommodations/current`
- For Specific Trip: `GET /api/trips/{trip_id}/my-accommodations`

## 6. View Housing Data
**Description**: Provides a broader view of housing assignments for a trip, typically for monitoring purposes.
**Actors**: Admin, Supervisor, Pilgrim (Housing View)
**Endpoints**:
- Housing Data Overview: `GET /api/trips/{id}/housing` (`AccommodationController`)
- Pilgrim Housing View: `GET /api/trips/{trip_id}/my-housing` (`PilgrimAccommodationController`)

## 7. Download Trip Documents
**Description**: Enables pilgrims to download important trip documents (e.g., guides, tickets).
**Actors**: Pilgrim
**Endpoints**:
- List Documents: `GET /api/trips/{trip_id}/documents`
- Download Document: `GET /api/trips/{trip_id}/documents/{document_id}/download`

## 8. View Pilgrims List
**Description**: Allows supervisors to view lists of pilgrims assigned to their groups.
**Actors**: Supervisor, Admin
**Endpoints**:
- My Pilgrims (All Groups): `GET /api/my-pilgrims`
- Pilgrims in Group: `GET /api/groups/{id}/pilgrims`
- General Group List: `GET /api/groups`

## 9. View Pilgrim Notes
**Description**: Allows supervisors to view notes and feedback submitted by pilgrims.
**Actors**: Supervisor, Admin
**Endpoints**:
- List Notes: `GET /api/pilgrim-notes`
- View Specific Note: `GET /api/pilgrim-notes/{note_id}`
- Respond to Note: `POST /api/pilgrim-notes/{note_id}/respond`

## 10. Link Accommodation to Groups
**Description**: Enables linking specific accommodation/hotels to pilgrim groups.
**Actors**: Admin, Supervisor
**Endpoints**:
- Link Accommodation: `POST /api/groups/{group_id}/accommodations`
- Bulk Link: `POST /api/group-accommodations/bulk-link`
- Unlink: `DELETE /api/groups/{group_id}/accommodations/{accommodation_id}`

## 11. Create Announcement
**Description**: Allows administrators to create new announcements (General, Trip, Package, Offer).
**Actors**: Admin
**Inputs**: Title, Content, Type, Priority, Related ID (optional), Start Date.
**Endpoints**:
- Create: `POST /api/announcements`

## 12. Edit Announcement
**Description**: Allows administrators to modify existing announcements.
**Actors**: Admin
**Endpoints**:
- Update: `PUT /api/announcements/{id}`

## 13. View Announcement Details
**Description**: Retrieves full details of an announcement, including any related trip or package data.
**Actors**: Admin, User (All Authenticated)
**Endpoints**:
- View Details: `GET /api/announcements/{id}`

## 14. Delete Announcement
**Description**: Removes an announcement from the system.
**Actors**: Admin
**Endpoints**:
- Delete: `DELETE /api/announcements/{id}`
