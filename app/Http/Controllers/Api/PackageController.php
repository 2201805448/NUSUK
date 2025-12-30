<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Package;

class PackageController extends Controller
{
    // List all packages
    public function index(Request $request)
    {
        $query = Package::query();

        // Optional: Filter by active status
        if ($request->has('is_active')) {
            $query->where('is_active', filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN));
        }

        return response()->json($query->get());
    }

    // Get specific package details
    public function show($id)
    {
        $package = Package::findOrFail($id);
        return response()->json($package);
    }

    // Store a new package
    public function store(Request $request)
    {
        $request->validate([
            'package_name' => 'required|string|max:150',
            'price' => 'required|numeric|min:0',
            'duration_days' => 'required|integer|min:1',
            'description' => 'nullable|string',
            'services' => 'nullable|string',
            'mod_policy' => 'nullable|string',
            'cancel_policy' => 'nullable|string',
            'is_active' => 'boolean'
        ]);

        $package = Package::create([
            'package_name' => $request->package_name,
            'price' => $request->price,
            'duration_days' => $request->duration_days,
            'description' => $request->description,
            'services' => $request->services,
            'mod_policy' => $request->mod_policy,
            'cancel_policy' => $request->cancel_policy,
            'is_active' => $request->is_active ?? true,
        ]);

        return response()->json([
            'message' => 'Package created successfully',
            'package' => $package
        ], 201);
    }

    // Update an existing package
    public function update(Request $request, $id)
    {
        $package = Package::findOrFail($id);

        $request->validate([
            'package_name' => 'sometimes|string|max:150',
            'price' => 'sometimes|numeric|min:0',
            'duration_days' => 'sometimes|integer|min:1',
            'description' => 'nullable|string',
            'services' => 'nullable|string',
            'mod_policy' => 'nullable|string',
            'cancel_policy' => 'nullable|string',
            'is_active' => 'boolean'
        ]);

        $package->update($request->all());

        return response()->json([
            'message' => 'Package updated successfully',
            'package' => $package
        ]);
    }

    // Delete a package
    public function destroy($id)
    {
        $package = Package::findOrFail($id);
        $package->delete();

        return response()->json([
            'message' => 'Package deleted successfully'
        ]);
    }

    /**
     * Get reviews for trips associated with this package.
     * Returns anonymous reviews.
     */
    public function getTripReviews($id)
    {
        $package = Package::findOrFail($id);

        // Get all trip IDs for this package
        $tripIds = \App\Models\Trip::where('package_id', $id)->pluck('trip_id');

        // Find evaluations where type='TRIP' and target_id IN $tripIds and internal_only=0
        $reviews = \App\Models\Evaluation::where('type', 'TRIP')
            ->whereIn('target_id', $tripIds)
            ->where('internal_only', 0)
            ->select('score', 'concern_text', 'created_at') // Anonymous
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'package_name' => $package->package_name,
            'reviews' => $reviews
        ]);
    }
}
