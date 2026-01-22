# Pilgrim Management & List Display Features

**Date:** 2026-01-22
**Focus:** Pilgrim List Visibility and Access Control

## Overview
This document summarizes the latest implementation regarding the display of pilgrim lists for Supervisors and Managers (Admins), ensuring correct data visibility and access control.

## 1. Supervisor View: Consolidated Pilgrim List
Supervisors now have the ability to view a consolidated list of all pilgrims assigned to the groups they supervise.

### Key Features
- **Consolidated Data:** Fetches pilgrims from all groups assigned to the logged-in supervisor.
- **Attributes Displayed:**
  - Pilgrim Name
  - Passport Number
  - Group Name
  - Contact Information
  - Status
  - Actions (View/Edit)

### Implementation Details
- **Logic:** The system retrieves all groups where the user is assigned as a supervisor, then aggregates the pilgrims from these groups.
- **Access Control:** Restricted to users with the `SUPERVISOR` role.

## 2. Manager View: Group-Specific Pilgrim List
Managers (Admins) retain the ability to view the full list of pilgrims within any specific group.

### Access Control Update
- **Refinement:** The endpoint `GET /groups/{id}/pilgrims` is now strictly restricted to **Admin (Manager)** users.
- **Reasoning:** Supervisors view pilgrims via the consolidated view, not by querying arbitrary group IDs (unless explicitly authorized for that specific group, but current logic favors the consolidated view for supervisors).

### Route Configuration
- **Endpoint:** `api/groups/{id}/pilgrims`
- **Middleware:** `auth:sanctum`, `role:ADMIN`

## 3. Verification & Testing
The following scripts and methods were used to verify the changes:

- **Script:** `test_pilgrim_lists.php`
- **Scenarios Verified:**
  1. **Supervisor Access:** Confirmed supervisors see only their assigned pilgrims.
  2. **Manager Access:** Confirmed managers can access pilgrims of any group.
  3. **Unauthorized Access:** Verified that unauthorized roles (or supervisors accessing unassigned groups via the admin endpoint) are blocked.

## 4. Role Standardization Reference
Recent changes also standardized the `USER` role to `PILGRIM` across the system to ensure consistency in authentication and authorization logic used these features.
