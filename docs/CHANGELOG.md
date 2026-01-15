# Changelog - Latest Updates

This document summarizes the most recent changes made to the NUSUK system.

---

## 2026-01-15

### Room Price Field Addition
Added a new `price` field to the Room entity to track room pricing.

**Files Modified:**
- [create_rooms_table.php](file:///c:/Users/admin/Downloads/NUSUK/database/migrations/2026_01_15_110000_add_price_to_rooms_table.php) - Added `price` column (decimal with 2 decimal places)
- [Room.php](file:///c:/Users/admin/Downloads/NUSUK/app/Models/Room.php) - Added `price` to `$fillable` array
- [RoomController.php](file:///c:/Users/admin/Downloads/NUSUK/app/Http/Controllers/Api/RoomController.php) - Added validation for `price` in `store` and `update` functions

**API Usage:**
```json
{
    "room_number": "101",
    "price": 150.00
}
```

---

### Hotel Data Storage Fix
Fixed an issue where hotel fields (star rating, phone, email) were not being saved.

**Root Cause:** The `Accommodation` model had `$timestamps = false` but the migration created timestamp columns.

**Fix Applied:**
- [Accommodation.php](file:///c:/Users/admin/Downloads/NUSUK/app/Models/Accommodation.php) - Changed `$timestamps` from `false` to `true`

**Status:** ✅ Resolved

---

## 2026-01-14

### Pilgrim Accommodation Details Enhancement
Updated the Pilgrim Accommodation Controller to allow pilgrims to view all available accommodation fields.

**Files Modified:**
- [PilgrimAccommodationController.php](file:///c:/Users/admin/Downloads/NUSUK/app/Http/Controllers/Api/PilgrimAccommodationController.php) - Enhanced view function to expose all relevant Accommodation model fields

---

## 2026-01-13

### Room Capacity Field Addition
Added a new `capacity` field to the Room entity to track maximum occupancy.

**Files Modified:**
- [create_rooms_table.php](file:///c:/Users/admin/Downloads/NUSUK/database/migrations/2025_12_27_000001_create_rooms_table.php) - Added `capacity` column
- [Room.php](file:///c:/Users/admin/Downloads/NUSUK/app/Models/Room.php) - Added `capacity` to `$fillable` array
- [RoomController.php](file:///c:/Users/admin/Downloads/NUSUK/app/Http/Controllers/Api/RoomController.php) - Added validation for `capacity` in `store` function

**API Usage:**
```json
{
    "room_number": "101",
    "capacity": 2
}
```

---

## Summary of Recent Enhancements

| Date | Feature | Status |
|------|---------|--------|
| 2026-01-15 | Room Price Field | ✅ Complete |
| 2026-01-15 | Hotel Fields Bug Fix | ✅ Resolved |
| 2026-01-14 | Pilgrim Accommodation Details | ✅ Complete |
| 2026-01-13 | Room Capacity Field | ✅ Complete |

---

## Related Documentation

- [Room_and_Accommodation_Updates.md](file:///c:/Users/admin/Downloads/NUSUK/docs/Room_and_Accommodation_Updates.md) - Detailed room and accommodation documentation
- [hotel_fields_bugfix.md](file:///c:/Users/admin/Downloads/NUSUK/docs/hotel_fields_bugfix.md) - Hotel fields bug fix details
- [Pilgrim_Accommodation_Documentation.md](file:///c:/Users/admin/Downloads/NUSUK/docs/Pilgrim_Accommodation_Documentation.md) - Pilgrim accommodation features
