<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Driver;

class DriverController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return response()->json(Driver::all());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:150',
            'license_number' => 'required|string|max:50|unique:drivers',
            'phone_number' => 'required|string|max:30',
            'status' => 'in:ACTIVE,INACTIVE'
        ]);

        $driver = Driver::create($request->all());

        return response()->json([
            'message' => 'Driver created successfully',
            'driver' => $driver
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $driver = Driver::findOrFail($id);
        return response()->json($driver);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $driver = Driver::findOrFail($id);

        $request->validate([
            'name' => 'sometimes|string|max:150',
            'license_number' => 'sometimes|string|max:50|unique:drivers,license_number,' . $id . ',driver_id',
            'phone_number' => 'sometimes|string|max:30',
            'status' => 'in:ACTIVE,INACTIVE'
        ]);

        $driver->update($request->all());

        return response()->json([
            'message' => 'Driver updated successfully',
            'driver' => $driver
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $driver = Driver::findOrFail($id);

        // Transports will set driver_id to null due to DB constraint
        $driver->delete();

        return response()->json([
            'message' => 'Driver deleted successfully'
        ]);
    }
}
