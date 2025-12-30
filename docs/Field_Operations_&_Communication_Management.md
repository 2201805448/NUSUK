# Field Operations & Communication Management

This document outlines the API endpoints and functionalities implemented for managing field operations, pilgrim tracking, and communication by Supervisors and Admins.

## 1. Updating Activity/Visit Status
Enable supervisors to update the status of the visit or activity during the trip.

- **Endpoint**: `PUT /api/activities/{id}`
- **Role**: Supervisor, Admin
- **Parameters**:
  - `status` (string, required): New status. Allowed values: `SCHEDULED`, `IN_PROGRESS`, `DONE`, `CANCELLED`.
  - `location`, `activity_date`, `activity_time` (optional): Can also be updated.

## 2. Recording Notes on a Pilgrim
Enable the supervisor to record notes related to a specific pilgrim during the trip (organizational, behavioral, etc.).

- **Endpoint**: `POST /api/pilgrims/{id}/notes`
- **Role**: Supervisor, Admin
- **Parameters**:
  - `trip_id` (integer, required): ID of the trip.
  - `note_type` (string, required): e.g., 'BEHAVIORAL', 'ORGANIZATIONAL', 'GENERAL'.
  - `note_text` (string, required): Content of the note.

## 3. Arrival and Departure Status (Attendance)
Enable the supervisor to record arrival and departure statuses of pilgrims at different stages or activities.

- **Endpoint**: `POST /api/pilgrims/{id}/attendance`
- **Role**: Supervisor, Admin
- **Parameters**:
  - `trip_id` (integer, required).
  - `activity_id` (integer, optional): If linked to specific activity.
  - `status_type` (string, required): `ARRIVAL` or `DEPARTURE`.
  - `supervisor_note` (string, optional).

## 4. Displaying Trip Attendance Reports
Enable the supervisor to view reports related to pilgrim attendance during the trip.

- **Endpoint**: `GET /api/trips/{id}/attendance-reports`
- **Role**: Supervisor, Admin
- **Parameters**: None (Trip ID in URL).
- **Response**: List of attendance records including pilgrim details, status, and timestamp.

## 5. Sending Notifications
Enable sending of notifications to target users according to permissions.

### General Notification (Broadcast)
- **Endpoint**: `POST /api/notifications/general`
- **Role**: Admin
- **Parameters**:
  - `title` (string, required).
  - `message` (string, required).

### Trip/Group Notification (Targeted)
- **Endpoints**:
  - `POST /api/trips/{id}/notifications` (All pilgrims in a trip)
  - `POST /api/groups/{id}/notifications` (All pilgrims in a group)
- **Role**: Supervisor, Admin
- **Parameters**:
  - `title` (string, required).
  - `message` (string, required).

## 6. Sending Trip Updates (Feed)
Enable the supervisor to send updates related to the trip (e.g., schedule changes) which notifies pilgrims and appears in a feed.

### Post Update
- **Endpoint**: `POST /api/trips/{id}/updates`
- **Role**: Supervisor, Admin
- **Parameters**:
  - `title` (string, required).
  - `message` (string, required).
- **Effect**: Creates a persistent update record and sends a notification to all trip members.

### View Updates
- **Endpoint**: `GET /api/trips/{id}/updates`
- **Role**: User (Pilgrim), Supervisor, Admin
- **Response**: List of updates for the trip, ordered by date.
