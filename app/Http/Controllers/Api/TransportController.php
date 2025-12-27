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
