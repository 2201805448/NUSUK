# Latest Changes - January 15, 2026

This document summarizes all code changes made on January 15, 2026.

---

## Summary

| Change | Files Affected | Status |
|--------|----------------|--------|
| Protected Visibility Fixes | 5 Controllers | ✅ Complete |
| TripController addHotel Refactor | TripController.php | ✅ Complete |
| TripChat Syntax Error Fix | TripChatController.php | ✅ Complete |
| Room Price Field Addition | Room.php, RoomController.php, Migration | ✅ Complete |
| Hotel Data Storage Fix | Accommodation.php | ✅ Complete |

---

## 1. Protected Visibility Fixes

Fixed "Member has protected visibility" errors across multiple controllers by using proper accessor methods.

### Files Modified

| File | Change |
|------|--------|
| [TripChatController.php](file:///c:/Users/admin/Downloads/NUSUK/app/Http/Controllers/Api/TripChatController.php) | Changed `$request->content` → `$request->input('content')` |
| [SupportTicketController.php](file:///c:/Users/admin/Downloads/NUSUK/app/Http/Controllers/Api/SupportTicketController.php) | Changed `$user->user_id` → `$user->getAttribute('user_id')` |
| [ReportController.php](file:///c:/Users/admin/Downloads/NUSUK/app/Http/Controllers/Api/ReportController.php) | Changed `$trip->trip_id` → `$trip->getKey()` |
| [MessageController.php](file:///c:/Users/admin/Downloads/NUSUK/app/Http/Controllers/Api/MessageController.php) | Changed property access to `getAttribute()` calls |

### Technical Solution
- **`$request->input('key')`** - Access request input data
- **`$model->getAttribute('key')`** - Explicit attribute accessor for Eloquent models
- **`$model->getKey()`** - Gets primary key value

---

## 2. TripController addHotel Refactor

Simplified the `addHotel()` method by removing logic for creating new accommodations.

### Before
- Accepted accommodation data to create new hotel records
- Handled room types and capacities
- Complex validation for multiple fields

### After
- Only accepts `accommodation_id` to link existing hotels
- Validates that the accommodation exists
- Prevents duplicate hotel-trip linkage

### Code Example
```php
// New simplified validation
$request->validate([
    'accommodation_id' => 'required|exists:accommodations,accommodation_id',
]);

// Link hotel to trip (prevents duplicates)
if (!$trip->accommodations()->where('trip_accommodations.accommodation_id', $request->accommodation_id)->exists()) {
    $trip->accommodations()->attach($request->accommodation_id);
}
```

**File:** [TripController.php](file:///c:/Users/admin/Downloads/NUSUK/app/Http/Controllers/Api/TripController.php)

---

## 3. TripChat Syntax Error Fix

Fixed a syntax error in `TripChatController.php` at line 51.

| Issue | Fix |
|-------|-----|
| `$request->''content''` (invalid quotes) | `$request->input('content')` |

**File:** [TripChatController.php](file:///c:/Users/admin/Downloads/NUSUK/app/Http/Controllers/Api/TripChatController.php)

---

## 4. Room Price Field Addition

Added a new `price` field to the Room entity.

### Changes
- **Migration:** Added `price` column (decimal, 10,2)
- **Model:** Added `price` to `$fillable` array
- **Controller:** Added validation for `price` in store/update

### API Usage
```json
POST /api/rooms
{
    "room_number": "101",
    "price": 150.00,
    "capacity": 2
}
```

---

## 5. Hotel Data Storage Fix

Fixed hotel fields (star rating, phone, email) not being saved.

**Root Cause:** Model had `$timestamps = false` but migration created timestamp columns.

**Fix:** Changed `$timestamps` from `false` to `true` in [Accommodation.php](file:///c:/Users/admin/Downloads/NUSUK/app/Models/Accommodation.php)

---

## Related Documentation

- [protected_visibility_fixes.md](file:///c:/Users/admin/Downloads/NUSUK/docs/protected_visibility_fixes.md)
- [CHANGELOG.md](file:///c:/Users/admin/Downloads/NUSUK/docs/CHANGELOG.md)
- [Room_and_Accommodation_Updates.md](file:///c:/Users/admin/Downloads/NUSUK/docs/Room_and_Accommodation_Updates.md)
