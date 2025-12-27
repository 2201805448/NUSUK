<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Transport;
use App\Models\Trip;

class TransportController extends Controller
{
    /**
     * Display a listing of transports.
     * Filterable by trip_id.
     */
    public function index(Request $request)
    {
        $query = Transport::query();

        if ($request->has('trip_id')) {
            $query->where('trip_id', $request->trip_id);
        }

        return response()->json($query->get());
    }

    /**
     * Store a newly created transport record for a trip.
     */
    public function store(Request $request)
    {
        $request->validate([
            'trip_id' => 'required|exists:trips,trip_id',
            'driver_id' => 'nullable|exists:drivers,driver_id',
            'route_id' => 'nullable|exists:transport_routes,id', // Added
            'transport_type' => 'required|string|max:50',

            // If route_id is missing, these are required. If present, they are optional (auto-filled if missing).
            'route_from' => 'required_without:route_id|string|max:100',
            'route_to' => 'required_without:route_id|string|max:100',

            'departure_time' => 'required|date',
            'arrival_time' => 'nullable|date|after:departure_time', // Added
            'notes' => 'nullable|string',
        ]);

        $data = $request->all();

        // Auto-fill from route if linked and fields are missing
        if ($request->filled('route_id') && (!$request->filled('route_from') || !$request->filled('route_to'))) {
            $route = \App\Models\TransportRoute::find($request->route_id);
            if ($route) {
                if (!$request->filled('route_from'))
                    $data['route_from'] = $route->start_location;
                if (!$request->filled('route_to'))
                    $data['route_to'] = $route->end_location;
            }
        }

        $transport = Transport::create($data);

        return response()->json([
            'message' => 'Transport added successfully',
            'transport' => $transport
        ], 201);
    }

    /**
     * Display the specified transport.
     */
    public function show($id)
    {
        $transport = Transport::with(['trip', 'driver', 'route'])->findOrFail($id);
        return response()->json($transport);
    }

    /**
     * Update the specified transport in storage.
     */
    public function update(Request $request, $id)
    {
        $transport = Transport::findOrFail($id);

        $request->validate([
            'trip_id' => 'sometimes|exists:trips,trip_id',
            'driver_id' => 'nullable|exists:drivers,driver_id',
            'route_id' => 'nullable|exists:transport_routes,id',
            'transport_type' => 'sometimes|string|max:50',
            'route_from' => 'nullable|string|max:100',
            'route_to' => 'nullable|string|max:100',
            'departure_time' => 'sometimes|date',
            'arrival_time' => 'nullable|date|after:departure_time',
            'notes' => 'nullable|string',
        ]);

        $data = $request->except(['route_from', 'route_to']); // Handle conditional logic for route fields

        // Handle route auto-fill logic similar to store if route_id changes or is present
        if ($request->has('route_id') && $request->route_id) {
            $route = \App\Models\TransportRoute::find($request->route_id);
            if ($route) {
                if (!$request->filled('route_from'))
                    $data['route_from'] = $route->start_location;
                if (!$request->filled('route_to'))
                    $data['route_to'] = $route->end_location;
            }
        } else {
            // If manual fields are provided, use them
            if ($request->filled('route_from'))
                $data['route_from'] = $request->route_from;
            if ($request->filled('route_to'))
                $data['route_to'] = $request->route_to;
        }

        // If simply updating fields without changing route logic significantly, standard update works but need to be careful with nulls if not provided. 
        // Actually, simplest approach:

        $transport->fill($request->all());

        // Re-apply route logic if route_id is being changed or if specific route fields requested. 
        // If the user sends route_id, we might want to refresh from/to if they aren't sent.
        if ($request->has('route_id') && $request->route_id) {
            $route = \App\Models\TransportRoute::find($request->route_id);
            if ($route) {
                if (!$request->filled('route_from'))
                    $transport->route_from = $route->start_location;
                if (!$request->filled('route_to'))
                    $transport->route_to = $route->end_location;
            }
        }

        $transport->save();

        return response()->json([
            'message' => 'Transport updated successfully',
            'transport' => $transport
        ]);
    }

    /**
     * Remove the specified transport.
     */
    public function destroy($id)
    {
        $transport = Transport::findOrFail($id);
        $transport->delete();

        return response()->json([
            'message' => 'Transport deleted successfully'
        ]);
    }
}
