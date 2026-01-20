# Pilgrim Card Supervisor Phone Update

**Date:** 2026-01-21
**Component:** `PilgrimCardController`
**Related Feature:** Digital Pilgrim Card / Physical Badge

## Overview
This update addresses the requirement to include the supervisor's phone number on the Pilgrim's Smart Card (physical badge). This is a critical safety feature allowing anyone to contact the supervisor immediately in case of emergencies involving the pilgrim.

## Changes

### `app/Http/Controllers/Api/PilgrimCardController.php`

Modified the `groupData` array construction within the `show` method.

**Before:**
```php
$groupData = [
    'group_code' => $activeMember->groupTrip->group_code,
    'trip_name' => $activeMember->groupTrip->trip->trip_name ?? 'N/A',
    'supervisor' => $activeMember->groupTrip->supervisor->full_name ?? 'Unassigned'
];
```

**After:**
```php
$groupData = [
    'group_code' => $activeMember->groupTrip->group_code,
    'trip_name' => $activeMember->groupTrip->trip->trip_name ?? 'N/A',
    'supervisor' => $activeMember->groupTrip->supervisor->full_name ?? 'Unassigned',
    'supervisor_phone' => $activeMember->groupTrip->supervisor->phone_number ?? 'N/A' // Added field
];
```

## Technical Details
- **Field Source:** The phone number is retrieved from the `User` model relationship: `$activeMember->groupTrip->supervisor->phone_number`.
- **Fallback:** defaults to `'N/A'` if the phone number is not available.
- **Model Check:** Confirmed that the `User` model uses `phone_number` as the column name, not `phone`.

## Impact
- **Frontend/Mobile App:** The API response for the digital card now includes `supervisor_phone` inside the `group` object.
- **Physical Badge:** Printing systems or views generating the physical badge can now access and display this vital contact information.
