<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\TransportRoute;

class TransportRouteController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return response()->json(TransportRoute::all());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'route_name' => 'required|string|max:150',
            'start_location' => 'required|string|max:100',
            'end_location' => 'required|string|max:100',
            'distance_km' => 'nullable|numeric|min:0',
            'estimated_duration_mins' => 'nullable|integer|min:0',
        ]);

        $route = TransportRoute::create($request->all());

        return response()->json([
            'message' => 'Route created successfully',
            'route' => $route
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $route = TransportRoute::findOrFail($id);
        return response()->json($route);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $route = TransportRoute::findOrFail($id);

        $request->validate([
            'route_name' => 'sometimes|string|max:150',
            'start_location' => 'sometimes|string|max:100',
            'end_location' => 'sometimes|string|max:100',
            'distance_km' => 'nullable|numeric|min:0',
            'estimated_duration_mins' => 'nullable|integer|min:0',
        ]);

        $route->update($request->all());

        return response()->json([
            'message' => 'Route updated successfully',
            'route' => $route
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $route = TransportRoute::findOrFail($id);
        $route->delete();

        return response()->json([
            'message' => 'Route deleted successfully'
        ]);
    }
}
