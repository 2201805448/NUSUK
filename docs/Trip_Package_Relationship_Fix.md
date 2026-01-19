# Trip Package Relationship Fix

**Date:** January 19, 2026
**Author:** Antigravity (AI Assistant)

## Issue Description
An exception was encountered when attempting to retrieve booking history or create a booking:
`Call to undefined relationship [package] on model [App\Models\Trip].`

This error occurred in `BookingController.php` where the code attempted to eager load the `package` relationship on the `Trip` model (e.g., `$trip->package` or `Trip::with('package')`), but the relationship was not defined in the `Trip` model.

## Changes Made

### 1. `app/Models/Trip.php`

- **Added Relationship Method**: Defined the `package()` relationship method to link the `Trip` model to the `Package` model.

```php
    // 🔗 العلاقة مع Package
    public function package()
    {
        return $this->belongsTo(Package::class, 'package_id', 'package_id');
    }
```

## Reason for Change
The `BookingController` relies on accessing package details via the trip (since a booking is essentially for a trip which is linked to a package). Without this relationship definition in the `Trip` model, Laravel's Eloquent ORM could not resolve the relationship, leading to the runtime exception.

This change ensures that:
1.  `BookingController` can successfully execute queries like `Trip::with('package')->findOrFail($id)`.
2.  Package details (like price) can be retrieved when creating a booking (`$trip->package->price`).
3.  Booking history can display package information related to the trip.
