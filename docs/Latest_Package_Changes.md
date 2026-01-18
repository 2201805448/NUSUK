# Latest Package Management Backend Changes

This document outlines the recent updates made to `Package.php` and `PackageController.php` to enhance package management capabilities, specifically regarding accommodation linking, data casting, and API response structures.

## 1. Model Updates: `app/Models/Package.php`

### Fillable Attributes
The `$fillable` array has been updated to include fields necessary for hotel linking and service definitions:
- `accommodation_id`: Foreign key linking to the `Accommodation` model.
- `room_type`: String field for specifying the room type.
- `services`: JSON field for storing package features/services.
- `is_active`: Boolean field for package status.

### Attribute Casting
The `$casts` array ensures data types are correctly handled between the database and the application:
```php
protected $casts = [
    'services' => 'array',       // Automatically converts JSON to array
    'is_active' => 'boolean',    // Ensures explicit true/false values
    'price' => 'decimal:2',      // Maintains price precision
    'duration_days' => 'integer'
];
```

### Relationships
A `belongsTo` relationship has been defined to link the package to an accommodation:
```php
public function accommodation()
{
    return $this->belongsTo(Accommodation::class, 'accommodation_id', 'accommodation_id');
}
```

---

## 2. Controller Updates: `app/Http/Controllers/Api/PackageController.php`

### `index(Request $request)`
- **Eager Loading**: Now includes `accommodation` data in the list results (`Package::with('accommodation')`).
- **Filtering**: Supports filtering by `is_active` status using boolean validation.

### `show($id)`
- **Eager Loading**: Fetches the package with its associated `accommodation` details to provide context (e.g., hotel name) in the API response.

### `store(Request $request)`
- **Validation**:
    - `accommodation_id`: Required and must exist in the `accommodations` table.
    - `room_type`: Required string.
    - `price`, `duration_days`: Validated as numeric/integer.
- **Creation**: Explicitly maps request inputs to model attributes, handling `is_active` defaulting to `true`.

### `update(Request $request, $id)`
- **Validation**: Uses `sometimes` rules for partial updates.
- **Data Handling**:
    - **Services**: Checks for `services` explicitly.
    - **Is Active**: converts `is_active` input using `filter_var(..., FILTER_VALIDATE_BOOLEAN)` to correctly handle string representations of booleans (e.g., "true", "1").
    - **Relational Data**: Updates `accommodation_id` and `room_type` if provided.
- **Response**: Returns the updated package with fresh `accommodation` data loaded.
