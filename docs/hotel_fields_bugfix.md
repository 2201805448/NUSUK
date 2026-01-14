# Hotel Fields Bug Fix

**Date:** 2026-01-15  
**Status:** Resolved ✅

## Issue

When adding a hotel via the API, the following fields were not being saved to the database:
- `start` (star rating)
- `phone` (phone number)
- `email` (email address)

## Root Cause

The `Accommodation` model had a mismatch between its configuration and the database schema:

| Component | Configuration |
|-----------|---------------|
| **Migration** | Creates `created_at` and `updated_at` columns with `$table->timestamps()` |
| **Model** | Had `$timestamps = false` |

This mismatch caused issues with how Laravel handles model saves, preventing some fields from being persisted correctly.

## Fix Applied

**File:** `app/Models/Accommodation.php`

```diff
-    // لأن الجدول ما فيهش created_at / updated_at
-    public $timestamps = false;
+    // Enable timestamps since the migration creates them
+    public $timestamps = true;
```

## Verification

After applying the fix, all fields are now saved correctly:

```
hotel_name: Test Hotel
start: 4
phone: +966987654321
email: test@hotel.com
```

## Related Files

- [Accommodation.php](file:///c:/Users/admin/Downloads/NUSUK/app/Models/Accommodation.php) - Model file (fixed)
- [AccommodationController.php](file:///c:/Users/admin/Downloads/NUSUK/app/Http/Controllers/Api/AccommodationController.php) - Controller (no changes needed)
- [create_accommodations_table.php](file:///c:/Users/admin/Downloads/NUSUK/database/migrations/2025_12_22_193204_create_accommodations_table.php) - Migration (no changes needed)
