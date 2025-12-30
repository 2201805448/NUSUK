# Support Operations & Communication Features

This document provides a walkthrough of the 16 support operations and communication features implemented in the NUSUK system.

---

## 1. Send Message

**Endpoint:** `POST /api/messages`

Allows users to send direct messages to other users or support staff within the system.

---

## 2. View Notifications

**Endpoint:** `GET /api/notifications`

Retrieves a list of all notifications for the authenticated user, including system alerts, booking updates, and trip reminders.

---

## 3. View Announcements

**Endpoint:** `GET /api/announcements`

Displays official announcements published by administrators, such as important updates, schedule changes, or general information for pilgrims.

---

## 4. View Religious Content and Ritual-Related Information

**Endpoint:** `GET /api/religious-content`

Provides access to religious guidance, ritual instructions, prayers, and educational content related to Umrah practices.

---

## 5. View Groups List

**Endpoint:** `GET /api/groups`

Lists all pilgrim groups, allowing supervisors and administrators to view group compositions, assigned supervisors, and member counts.

---

## 6. Communicate via Trip Chat

**Endpoint:** `GET/POST /api/trips/{trip_id}/chat`

Enables real-time communication between pilgrims and trip supervisors through a dedicated trip chat channel.

---

## 7. Create Support Ticket

**Endpoint:** `POST /api/support-tickets`

Allows users to create a new support ticket for issues, complaints, or requests that require attention from the support team.

---

## 8. View Support Tickets

**Endpoint:** `GET /api/support-tickets`

Retrieves a list of all support tickets created by the user (for pilgrims) or all tickets in the system (for administrators/support staff).

---

## 9. Reply to Tickets

**Endpoint:** `POST /api/support-tickets/{ticket_id}/reply`

Enables support staff or users to add replies to an existing support ticket, facilitating ongoing communication until resolution.

---

## 10. Transfer Tickets to Competent Authority

**Endpoint:** `POST /api/support-tickets/{ticket_id}/transfer`

Allows support staff to escalate or transfer a ticket to a different department or authority better suited to handle the issue.

---

## 11. Close Ticket

**Endpoint:** `PUT /api/support-tickets/{ticket_id}/close`

Marks a support ticket as closed/resolved, ending the support request lifecycle.

---

## 12. Ticket Status Notifications

**Automatic System Feature**

Users receive automatic notifications when their support ticket status changes (e.g., assigned, in progress, resolved, closed).

---

## 13. View Hotel Reviews

**Endpoint:** `GET /api/trips/{trip_id}/hotel-reviews`

Displays anonymous reviews and ratings for hotels associated with a specific trip, helping pilgrims understand accommodation quality.

---

## 14. View Trip Reviews

**Endpoint:** `GET /api/trips/{trip_id}/reviews`

Shows reviews and feedback submitted by pilgrims who have completed a trip, providing insights into the overall trip experience.

---

## 15. View General Statistics

**Endpoint:** `GET /api/reports/statistics`

Provides administrators with aggregate statistics including total bookings, active trips, pilgrim counts, and system usage metrics.

---

## 16. Export Reports

**Endpoint:** `GET /api/reports/trips/export?format={pdf|excel|csv}`

Allows administrators to export trip reports in PDF, Excel, or CSV format for offline analysis and record-keeping.

---

## Summary

| # | Feature | Endpoint |
|---|---------|----------|
| 1 | Send Message | `POST /api/messages` |
| 2 | View Notifications | `GET /api/notifications` |
| 3 | View Announcements | `GET /api/announcements` |
| 4 | View Religious Content | `GET /api/religious-content` |
| 5 | View Groups List | `GET /api/groups` |
| 6 | Trip Chat | `GET/POST /api/trips/{trip_id}/chat` |
| 7 | Create Support Ticket | `POST /api/support-tickets` |
| 8 | View Support Tickets | `GET /api/support-tickets` |
| 9 | Reply to Tickets | `POST /api/support-tickets/{ticket_id}/reply` |
| 10 | Transfer Tickets | `POST /api/support-tickets/{ticket_id}/transfer` |
| 11 | Close Ticket | `PUT /api/support-tickets/{ticket_id}/close` |
| 12 | Ticket Status Notifications | Automatic |
| 13 | View Hotel Reviews | `GET /api/trips/{trip_id}/hotel-reviews` |
| 14 | View Trip Reviews | `GET /api/trips/{trip_id}/reviews` |
| 15 | View General Statistics | `GET /api/reports/statistics` |
| 16 | Export Reports | `GET /api/reports/trips/export` |
