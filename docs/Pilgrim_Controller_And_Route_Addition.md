# Pilgrim Controller and Route Addition

## Overview
To resolve a 404 NotFoundHttpException when accessing `/api/pilgrims`, a new controller and route were added to the API.

## Changes

### 1. New Controller
**File:** `app/Http/Controllers/Api/PilgrimController.php`

A new controller `PilgrimController` was created with a single `index` method.
- **Method:** `index()`
- **Responsibility:** Returns a collection of all pilgrims using the `Pilgrim::all()` model method.
- **Return Type:** JSON array of pilgrim objects.

### 2. New Route
**File:** `routes/api.php`

A new GET route was defined to expose the pilgrim data.
- **Endpoint:** `/api/pilgrims`
- **Method:** `GET`
- **Controller:** `App\Http\Controllers\Api\PilgrimController`
- **Action:** `index`
- **Middleware:** Placed within the `auth:sanctum` group, specifically in the Admin/Supervisor section (commented as `// All Pilgrims (Admin/Supervisor)`).

## Verification
The route was verified using `php artisan route:list`.
- **URI:** `api/pilgrims`
- **Name:** No name assigned (default)
- **Action:** `App\Http\Controllers\Api\PilgrimController@index`
- **Middleware:** `api`, `auth:sanctum`

## Impact
This change enables the frontend and other consumers to fetch a list of all pilgrims from the system, resolving the previous 404 error.
