<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Accommodation;
use Illuminate\Validation\Rule;

class AccommodationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $accommodations = Accommodation::all();
        return response()->json($accommodations);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'hotel_name' => 'required|string|max:150',
            'city' => 'required|string|max:100',
            'room_type' => 'required|string|max:50',
            'capacity' => 'required|integer|min:1',
            'notes' => 'nullable|string',
        ]);

        $accommodation = Accommodation::create($request->all());

        return response()->json([
            'message' => 'Accommodation created successfully',
            'accommodation' => $accommodation
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $accommodation = Accommodation::findOrFail($id);
        return response()->json($accommodation);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $accommodation = Accommodation::findOrFail($id);

        $request->validate([
            'hotel_name' => 'sometimes|string|max:150',
            'city' => 'sometimes|string|max:100',
            'room_type' => 'sometimes|string|max:50',
            'capacity' => 'sometimes|integer|min:1',
            'notes' => 'nullable|string',
        ]);

        $accommodation->update($request->all());

        return response()->json([
            'message' => 'Accommodation updated successfully',
            'accommodation' => $accommodation
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $accommodation = Accommodation::findOrFail($id);

        // Optional: Check if used in any room assignments or trips before deleting?
        // simple delete for now, DB constraints should handle restrict/cascade

        $accommodation->delete();

        return response()->json([
            'message' => 'Accommodation deleted successfully'
        ]);
    }
}
