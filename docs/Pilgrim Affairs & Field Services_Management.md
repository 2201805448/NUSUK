# Pilgrim Affairs & Field Services Management API

This document provides a reference for the API endpoints related to managing groups, supervisors, housing, and pilgrim services within the Nusuk platform.

## 1. Group Management

### Create Group
Creates a new group within a specific trip.
- **Endpoint**: `POST /api/trips/{trip_id}/groups`
- **Description**: Create a new group for a trip.
- **Required Parameters**:
  - `group_code` (string): Unique code for the group.

### View Group Details
Retrieves detailed information about a specific group, including its members.
- **Endpoint**: `GET /api/groups/{id}`
- **Description**: Get group details.
- **Required Parameters**: None (ID in URL).

### Modify Group Data
Updates the information of an existing group.
- **Endpoint**: `PUT /api/groups/{id}`
- **Description**: Update group status or code.
- **Required Parameters**:
  - `group_code` (string, optional)
  - `status` (string, optional): e.g., `CREATED`, `ACTIVE`, `FINISHED`.

### Add Pilgrim to Group
Adds a user (pilgrim) to a group. Accounts for creating a pilgrim profile if one doesn't exist for the user.
- **Endpoint**: `POST /api/groups/{id}/members`
- **Description**: Add a member to the group.
- **Required Parameters**:
  - `user_id` (integer): The ID of the user to add as a pilgrim.

### Transfer Pilgrim Between Groups
Moves a pilgrim from their current group to another group.
- **Endpoint**: `POST /api/groups/{id}/transfer`
- **Description**: Transfer a pilgrim to a different group.
- **Required Parameters**:
  - `pilgrim_id` (integer): ID of the pilgrim to transfer.
  - `new_group_id` (integer): ID of the destination group.

### Remove Pilgrim from Group
Removes a pilgrim from a group (sets status to REMOVED).
- **Endpoint**: `POST /api/groups/{id}/remove`
- **Description**: Remove a member from the group.
- **Required Parameters**:
  - `pilgrim_id` (integer): ID of the pilgrim to remove.

---

## 2. Supervisor Assignment

### Assign Supervisor to Group
Assigns a supervisor to a specialized group.
- **Endpoint**: `PUT /api/groups/{id}/assign-supervisor`
- **Description**: Assign a user with SUPERVISOR role to the group.
- **Required Parameters**:
  - `supervisor_id` (integer): User ID of the supervisor.

### Modify Supervisor Assignment
Updates the assigned supervisor for a group (replaces the existing one).
- **Endpoint**: `PUT /api/groups/{id}/assign-supervisor`
- **Description**: Overwrite the current supervisor with a new one.
- **Required Parameters**:
  - `supervisor_id` (integer): User ID of the new supervisor.

### Display Assigned Supervisors List
Retrieves a list of groups with their assigned supervisors.
- **Endpoint**: `GET /api/trips/{trip_id}/groups` (or `GET /api/groups`)
- **Description**: List groups to see assigned supervisors.
- **Required Parameters**: None.

### Unassign Supervisor from Group
Removes the currently assigned supervisor from a group.
- **Endpoint**: `PUT /api/groups/{id}/unassign-supervisor`
- **Description**: Unlink the supervisor from the group.
- **Required Parameters**: None.

---

## 3. Housing Management

### Monitoring Housing Data
Retrieves the housing distribution for a trip, showing hotels, rooms, and current occupants.
- **Endpoint**: `GET /api/trips/{trip_id}/housing`
- **Description**: Monitor room occupancy and assignments.
- **Required Parameters**: None.

### Assign Rooms to Pilgrims
Assigns a pilgrim to a specific room in an accommodation.
- **Endpoint**: `POST /api/room-assignments`
- **Description**: Create a room assignment record.
- **Required Parameters**:
  - `pilgrim_id` (integer)
  - `accommodation_id` (integer)
  - `room_id` (integer)
  - `check_in` (date/datetime): e.g., `2025-01-01 14:00:00`
  - `check_out` (date/datetime)
  - `status` (string): `CONFIRMED` or `PENDING`.

### Modifying Housing Data
Updates an existing room assignment (e.g., moving a pilgrim to a different room).
- **Endpoint**: `PUT /api/room-assignments/{assignment_id}`
- **Description**: Modify room assignment details.
- **Required Parameters** (at least one):
  - `room_id` (integer, optional)
  - `accommodation_id` (integer, optional)
  - `check_in` (date/datetime, optional)
  - `check_out` (date/datetime, optional)
  - `status` (string, optional)

---

## 4. Pilgrim Services

### Display Pilgrim Card
Displays the digital card for the authenticated pilgrim, including personal info, group details, and housing.
- **Endpoint**: `GET /api/pilgrim/card`
- **Description**: Retrieve the logged-in pilgrim's digital ID card.
- **Required Parameters**: None (Requires User Authentication Token).

### Review Pilgrim Documents (List)
Allows supervisors and management to view documents uploaded by pilgrims.
- **Endpoint**: `GET /api/pilgrims/documents`
- **Description**: List pilgrims with their documents (passport, visa, personal data).
- **Access**: ADMIN, SUPERVISOR
- **Permission Logic**:
  - **ADMIN**: Can view all pilgrims, optionally filtered by trip or group.
  - **SUPERVISOR**: Can only view pilgrims in groups they supervise.
- **Optional Query Parameters**:
  - `trip_id` (integer): Filter by trip.
  - `group_id` (integer): Filter by group.

### Review Pilgrim Documents (Details)
View detailed documents for a specific pilgrim.
- **Endpoint**: `GET /api/pilgrims/{pilgrim_id}/documents`
- **Description**: Get complete document and personal data for a specific pilgrim.
- **Access**: ADMIN, SUPERVISOR
- **Permission Logic**:
  - **ADMIN**: Can view any pilgrim's documents.
  - **SUPERVISOR**: Can only view documents for pilgrims in their supervised groups.
- **Response Includes**:
  - User info (full_name, email, phone_number)
  - Documents (passport_name, passport_number, passport_img, visa_img)
  - Personal data (nationality, date_of_birth, gender, emergency_call, notes)
  - Group info (group_code, trip_name, join_date)

### View Previous Activity Log
Allows pilgrims to view the log of trips and activities they have participated in.
- **Endpoint**: `GET /api/activity-log`
- **Description**: Retrieve all trips and activities the pilgrim has participated in.
- **Access**: Authenticated Pilgrim
- **Response Includes**:
  - Summary (total trips, past/current/upcoming counts, activity counts)
  - Activity log with trips, packages, hotels, and activities

### View Trip Activity Details
View detailed activity log for a specific trip.
- **Endpoint**: `GET /api/activity-log/trips/{trip_id}`
- **Description**: Get detailed program and activities for a specific trip.
- **Access**: Authenticated Pilgrim (must have participated in the trip)
- **Response Includes**:
  - Trip details (name, dates, package, hotels, transports)
  - Group info
  - Activities summary and program organized by date

### View Trip Schedule (Full Timeline)
View the complete timeline of a trip including transportation, visits, and daily activities.
- **Endpoint**: `GET /api/trips/{trip_id}/schedule`
- **Description**: Get the full trip schedule with unified timeline and daily breakdown.
- **Access**: Authenticated Pilgrim (must be registered for the trip)
- **Response Includes**:
  - Trip and package details
  - Group and supervisor info
  - Accommodations list
  - Transportation schedules with driver info
  - Activities summary
  - Unified timeline (activities + transports sorted by datetime)
  - Daily schedule (events grouped by date)
  - Trip updates/announcements

### View Today's Schedule
View only the current day's schedule for a trip.
- **Endpoint**: `GET /api/trips/{trip_id}/schedule/today`
- **Description**: Get today's activities and transports only.
- **Access**: Authenticated Pilgrim (must be registered for the trip)
- **Response Includes**:
  - Today's date and day name
  - Activities count and transports count
  - Sorted timeline of today's events

---

## 5. Accommodation Details

### View All Accommodations
View all accommodation assignments for the authenticated pilgrim.
- **Endpoint**: `GET /api/my-accommodations`
- **Description**: List all hotels and rooms assigned to the pilgrim.
- **Access**: Authenticated Pilgrim
- **Response Includes**:
  - Summary (total, current, upcoming, past)
  - Current accommodation details
  - All accommodations with hotel, room, and stay information

### View Current Accommodation
View only the currently active accommodation.
- **Endpoint**: `GET /api/my-accommodations/current`
- **Description**: Get the pilgrim's current hotel and room details.
- **Access**: Authenticated Pilgrim
- **Response Includes**:
  - Hotel name, city, room type
  - Room number and floor
  - Check-in/out dates, duration, days remaining

### View Trip Accommodations
View accommodations for a specific trip.
- **Endpoint**: `GET /api/trips/{trip_id}/my-accommodations`
- **Description**: Get all hotels in the trip with pilgrim's assignments.
- **Access**: Authenticated Pilgrim (must be registered for the trip)
- **Response Includes**:
  - Trip details
  - All accommodations in the trip
  - Pilgrim's room assignments for each hotel

### View Housing Data with Group Information
View housing data assigned within a trip, including group association.
- **Endpoint**: `GET /api/trips/{trip_id}/my-housing`
- **Description**: Get housing data with place of residence, room, dates, and group info.
- **Access**: Authenticated Pilgrim (must be registered for the trip)
- **Response Includes**:
  - Trip information (name, dates, status)
  - Group details (code, status, supervisor contact)
  - Housing summary (current, upcoming, past)
  - Current housing with:
    - Place of residence (hotel name, city)
    - Room number and floor
    - Check-in/out dates with duration
  - All housing assignments

---

## 6. Trip Documents

### List Trip Documents
View all documents available for a trip.
- **Endpoint**: `GET /api/trips/{trip_id}/documents`
- **Description**: List all public documents for the trip.
- **Access**: Authenticated Pilgrim (must be registered for the trip)
- **Response Includes**:
  - Total documents count
  - Documents grouped by type (PROGRAM, INSTRUCTIONS, MAP, GUIDE, etc.)
  - Document details (title, description, file info)

### View Document Details
Get details about a specific document.
- **Endpoint**: `GET /api/trips/{trip_id}/documents/{document_id}`
- **Description**: Get document details including download URL.
- **Access**: Authenticated Pilgrim (must be registered for the trip)

### Download Document
Download a trip document.
- **Endpoint**: `GET /api/trips/{trip_id}/documents/{document_id}/download`
- **Description**: Download the actual document file.
- **Access**: Authenticated Pilgrim (must be registered for the trip)
- **Document Types Available**:
  - PROGRAM - Trip program/schedule
  - INSTRUCTIONS - Travel instructions
  - VISA - Visa documents
  - TICKET - Travel tickets
  - MAP - Location maps
  - GUIDE - Religious/travel guides
  - OTHER - Other official files

### Upload Document (Admin/Supervisor)
- **Endpoint**: `POST /api/trips/{trip_id}/documents`
- **Description**: Upload a new document for the trip.
- **Access**: ADMIN or SUPERVISOR
- **Parameters**:
  - `title` (Required): Document title
  - `description` (Optional): Document description
  - `document_type` (Required): Type of document
  - `file` (Required): The file to upload (max 10MB)
  - `is_public` (Optional): Whether visible to pilgrims (default: true)

---

## 7. Supervisor Pilgrims Management

### List All Pilgrims (Supervisor)
View all pilgrims in groups supervised by the current supervisor.
- **Endpoint**: `GET /api/my-pilgrims`
- **Description**: List all pilgrims across all supervised groups.
- **Access**: ADMIN or SUPERVISOR
- **Parameters**:
  - `trip_id` (Optional): Filter by specific trip
- **Response Includes**:
  - Summary (total groups, total pilgrims, active pilgrims)
  - Pilgrim details (name, contact, passport info, nationality, group)

### List Group Pilgrims
View pilgrims in a specific group.
- **Endpoint**: `GET /api/groups/{id}/pilgrims`
- **Description**: List pilgrims in a specific group with detailed information.
- **Access**: ADMIN or Supervisor of the group
- **Response Includes**:
  - Group and trip details
  - Summary (total, active, removed pilgrims)
  - Pilgrim details:
    - Personal info (name, email, phone)
    - Passport details (name, number)
    - Nationality, gender, date of birth
    - Emergency contact
    - Join date and status

### Submit Pilgrim Note (Pilgrim)
Submit feedback, suggestions, or complaints about the trip.
- **Endpoint**: `POST /api/my-notes`
- **Description**: Submit a note about the trip or services.
- **Access**: Authenticated Pilgrim
- **Parameters**:
  - `trip_id` (Required): ID of the trip
  - `note_type` (Required): FEEDBACK, SUGGESTION, COMPLAINT, REQUEST, OBSERVATION, OTHER
  - `note_text` (Required): The note content (min 10 chars)
  - `category` (Optional): ACCOMMODATION, TRANSPORT, FOOD, SCHEDULE, SERVICE, STAFF, GENERAL
  - `priority` (Optional): LOW, MEDIUM, HIGH

### View My Notes (Pilgrim)
View notes submitted by the pilgrim.
- **Endpoint**: `GET /api/my-notes`
- **Access**: Authenticated Pilgrim
- **Parameters**:
  - `trip_id` (Optional): Filter by trip
  - `status` (Optional): Filter by status

### View Pilgrim Notes (Supervisor/Admin)
View all notes from pilgrims for follow-up and observation tracking.
- **Endpoint**: `GET /api/pilgrim-notes`
- **Description**: View notes from pilgrims in supervised groups.
- **Access**: ADMIN or SUPERVISOR
- **Parameters**:
  - `trip_id`, `group_id`, `status`, `note_type`, `category`, `priority` (all optional filters)
- **Response Includes**:
  - Summary (total, pending, reviewed, resolved)
  - Analysis by category, type, priority, status
  - Notes with pilgrim and trip details

### View Single Pilgrim Note
- **Endpoint**: `GET /api/pilgrim-notes/{note_id}`
- **Access**: ADMIN or Supervisor of the group

### Respond to Pilgrim Note
- **Endpoint**: `POST /api/pilgrim-notes/{note_id}/respond`
- **Access**: ADMIN or Supervisor of the group
- **Parameters**:
  - `status` (Required): REVIEWED, RESOLVED, DISMISSED
  - `response` (Optional): Response text to the pilgrim

---

## 8. Group Accommodation Management

### View Group Accommodations
List all accommodations linked to a group.
- **Endpoint**: `GET /api/groups/{group_id}/accommodations`
- **Access**: ADMIN or SUPERVISOR
- **Response**: List of accommodations with check-in/out dates

### Link Accommodation to Group
Link a hotel to a specific group within a trip.
- **Endpoint**: `POST /api/groups/{group_id}/accommodations`
- **Access**: ADMIN or SUPERVISOR
- **Parameters**:
  - `accommodation_id` (Required): ID of the accommodation
  - `check_in_date` (Optional): Check-in date for the group
  - `check_out_date` (Optional): Check-out date for the group
  - `notes` (Optional): Assignment notes

### Update Group Accommodation
Update check-in/out dates or notes for an accommodation assignment.
- **Endpoint**: `PUT /api/groups/{group_id}/accommodations/{accommodation_id}`
- **Access**: ADMIN or SUPERVISOR
- **Parameters**: Same as link (dates and notes)

### Unlink Accommodation from Group
Remove the link between an accommodation and a group.
- **Endpoint**: `DELETE /api/groups/{group_id}/accommodations/{accommodation_id}`
- **Access**: ADMIN or SUPERVISOR

### Bulk Link Accommodation
Link one accommodation to multiple groups at once.
- **Endpoint**: `POST /api/group-accommodations/bulk-link`
- **Access**: ADMIN or SUPERVISOR
- **Parameters**:
  - `accommodation_id` (Required): ID of the accommodation
  - `group_ids` (Required): Array of group IDs
  - `check_in_date`, `check_out_date`, `notes` (Optional)
