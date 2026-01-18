# Package Management Backend Updates

This document details the backend updates made to the Package Management system to support hotel linking and correct field naming, ensuring synchronization with the Frontend.

## 1. Database Changes
### Migration: `add_accommodation_details_to_packages_table`
- **New Columns**:
  - `accommodation_id` (Foreign Key): Links the package to a specific accommodation in the `accommodations` table. Nullable, `onDelete('set null')`.
  - `room_type` (String): Specifies the type of room included in the package. Nullable.

## 2. Model Updates
### `App\Models\Package`
- **Fillable Attributes**: Added `accommodation_id` and `room_type` to the `$fillable` array to allow mass assignment.
- **Relationships**: Defined a `belongsTo` relationship with the `Accommodation` model.
  ```php
  public function accommodation()
  {
      return $this->belongsTo(Accommodation::class, 'accommodation_id', 'accommodation_id');
  }
  ```

## 3. Controller Updates
### `App\Http\Controllers\Api\PackageController`
- **Store Method (`store`)**:
  - Added validation for `accommodation_id`: `required|exists:accommodations,accommodation_id`.
  - Added validation for `room_type`: `required|string`.
  - Included these fields in the `Package::create` call.
- **Update Method (`update`)**:
  - Added validation for `accommodation_id`: `sometimes|exists:accommodations,accommodation_id`.
  - Added validation for `room_type`: `sometimes|string`.
- **Show Method (`show`)**:
  - Updated to eager load the accommodation details using `with('accommodation')`.
  ```php
  $package = Package::with('accommodation')->findOrFail($id);
  ```

## Summary
These changes ensure that packages can be correctly linked to hotels (accommodations) and that the room type is stored. The API now returns the accommodation details when fetching a single package, allowing the frontend to display the hotel name and location.
