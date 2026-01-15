# Room and Accommodation Updates

This document describes the recent updates made to the Room and Accommodation functionality.

---

## Room Updates

### 1. Migration Update

**File:** `database/migrations/2025_12_27_000001_create_rooms_table.php`

Added a new `capacity` column to the `rooms` table:

```php
$table->integer('capacity')->nullable();
```

This field stores the maximum number of occupants for each room.

---

### 2. Room Model Update

**File:** `app/Models/Room.php`

Added `capacity` to the `$fillable` array to allow mass assignment:

```php
protected $fillable = [
    'accommodation_id',
    'room_number',
    'floor',
    'room_type',
    'capacity',  // NEW
    'status',
    'notes',
];
```

---

### 3. Price Field Addition (2026-01-15)

**Migration:** `database/migrations/2026_01_15_110000_add_price_to_rooms_table.php`

Added a new `price` column to the `rooms` table:

```php
$table->decimal('price', 10, 2)->nullable()->after('capacity');
```

This field stores the room price as a decimal value with 2 decimal places.

**Model Update:** Added `price` to the `$fillable` array:

```php
protected $fillable = [
    'accommodation_id',
    'room_number',
    'floor',
    'room_type',
    'capacity',
    'price',    // NEW
    'status',
    'notes',
];
```

**Controller Update:** Added validation for `price` in both `store` and `update` functions:

```php
'price' => 'nullable|numeric|min:0',
```

---

### 4. RoomController Update

**File:** `app/Http/Controllers/Api/RoomController.php`

Added validation for `capacity` in the `store` function:

```php
$request->validate([
    'accommodation_id' => 'required|exists:accommodations,accommodation_id',
    'room_number' => [...],
    'floor' => 'nullable|integer',
    'room_type' => 'nullable|string|max:50',
    'capacity' => 'nullable|integer|min:1',  // NEW
    'status' => 'in:AVAILABLE,OCCUPIED,MAINTENANCE,CLEANING',
    'notes' => 'nullable|string',
]);
```

---

## Accommodation Updates

### Migration Update

**File:** `database/migrations/2025_12_22_193204_create_accommodations_table.php`

Added three new fields to the `accommodations` table:

```php
$table->integer('start')->nullable();        // Star rating (1-5)
$table->string('phone', 50)->nullable();     // Contact phone
$table->string('email', 150)->nullable();    // Contact email
```

---

### Accommodation Model Update

**File:** `app/Models/Accommodation.php`

Added the new fields to the `$fillable` array:

```php
protected $fillable = [
    'hotel_name',
    'city',
    'room_type',
    'capacity',
    'notes',
    'start',   // NEW - Star rating
    'phone',   // NEW - Contact phone
    'email',   // NEW - Contact email
];
```

---

### AccommodationController Update

**File:** `app/Http/Controllers/Api/AccommodationController.php`

Added validation for the new fields in both `store` and `update` functions:

```php
$request->validate([
    // ... existing validations ...
    'start' => 'nullable|integer|min:1|max:5',  // NEW - Star rating 1-5
    'phone' => 'nullable|string|max:50',        // NEW
    'email' => 'nullable|email|max:150',        // NEW
]);
```

---

## API Usage Examples

### Create Room with Capacity

```http
POST /api/rooms
Content-Type: application/json

{
    "accommodation_id": 1,
    "room_number": "101",
    "floor": 1,
    "room_type": "Double",
    "capacity": 2,
    "price": 150.00,
    "status": "AVAILABLE"
}
```

### Create Accommodation with New Fields

```http
POST /api/accommodations
Content-Type: application/json

{
    "hotel_name": "Grand Hotel",
    "city": "Makkah",
    "room_type": "Suite",
    "capacity": 100,
    "start": 5,
    "phone": "+966-123-456789",
    "email": "info@grandhotel.com"
}
```

---

## Migration Notes

> **Important:** If the tables already exist in the database, you need to either:
> 1. Run `php artisan migrate:fresh --seed` (drops all tables and re-runs migrations)
> 2. Create separate migrations to add the new columns to existing tables
