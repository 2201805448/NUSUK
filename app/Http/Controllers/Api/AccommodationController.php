<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Accommodation;
use Illuminate\Validation\Rule;

class AccommodationController extends Controller
{
    /**
     * عرض قائمة الفنادق
     */
    public function index()
    {
        $accommodations = Accommodation::all();
        return response()->json($accommodations);
    }

    /**
     * إضافة فندق جديد (تم التعديل لضمان حفظ النجوم والهاتف والإيميل)
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'hotel_name' => 'required|string|max:150',
            'city' => 'required|string|max:100',
            'room_type' => 'required|string|max:50',
            'capacity' => 'required|integer|min:1',
            'notes' => 'nullable|string',
            'start' => 'nullable|integer|min:1|max:5',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:150',
        ]);

        // الحفظ اليدوي المباشر لحل مشكلة الـ Null
        $accommodation = new Accommodation();
        $accommodation->hotel_name = $validated['hotel_name'];
        $accommodation->city = $validated['city'];
        $accommodation->room_type = $validated['room_type'];
        $accommodation->capacity = $validated['capacity'];
        $accommodation->notes = $validated['notes'] ?? null;
        $accommodation->start = $validated['start'] ?? null;
        $accommodation->phone = $validated['phone'] ?? null;
        $accommodation->email = $validated['email'] ?? null;
        $accommodation->save();

        return response()->json([
            'message' => 'Accommodation created successfully',
            'accommodation' => $accommodation
        ], 201);
    }

    /**
     * عرض بيانات فندق معين
     */
    public function show($id)
    {
        $accommodation = Accommodation::findOrFail($id);
        return response()->json($accommodation);
    }

    /**
     * تحديث بيانات الفندق
     */
    public function update(Request $request, $id)
    {
        $accommodation = Accommodation::findOrFail($id);

        $validated = $request->validate([
            'hotel_name' => 'sometimes|string|max:150',
            'city' => 'sometimes|string|max:100',
            'room_type' => 'sometimes|string|max:50',
            'capacity' => 'sometimes|integer|min:1',
            'notes' => 'nullable|string',
            'start' => 'nullable|integer|min:1|max:5',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:150',
        ]);

        // استخدام المصفوفة المفلترة لضمان الحفظ الصحيح
        $accommodation->update($validated);

        return response()->json([
            'message' => 'Accommodation updated successfully',
            'accommodation' => $accommodation
        ]);
    }

    /**
     * حذف الفندق
     */
    public function destroy($id)
    {
        $accommodation = Accommodation::findOrFail($id);
        $accommodation->delete();

        return response()->json([
            'message' => 'Accommodation deleted successfully'
        ]);
    }

    /**
     * جلب بيانات التسكين لرحلة معينة (للمشرفين والمسؤولين)
     */
    public function getHousingData(Request $request, $trip_id)
    {
        $trip = \App\Models\Trip::findOrFail($trip_id);

        if (!auth()->user() || !in_array(auth()->user()->role, ['ADMIN', 'SUPERVISOR'])) {
            return response()->json(['message' => 'Unauthorized access.'], 403);
        }

        $hotels = $trip->accommodations()->with([
            'rooms.roomAssignments' => function ($q) {
                $q->whereIn('status', ['CONFIRMED', 'PENDING']);
            },
            'rooms.roomAssignments.pilgrim.user'
        ])->get();

        $data = $hotels->map(function ($hotel) {
            return [
                'accommodation_id' => $hotel->accommodation_id,
                'hotel_name' => $hotel->hotel_name,
                'city' => $hotel->city,
                'rooms' => $hotel->rooms->map(function ($room) {
                    $occupants = $room->roomAssignments->count();
                    return [
                        'room_id' => $room->id,
                        'room_number' => $room->room_number,
                        'floor' => $room->floor,
                        'room_type' => $room->room_type,
                        'status' => $room->status,
                        'current_occupants' => $occupants,
                        'pilgrims' => $room->roomAssignments->map(function ($assign) {
                            return [
                                'pilgrim_id' => $assign->pilgrim_id,
                                'name' => $assign->pilgrim->user->full_name ?? 'Unknown',
                                'status' => $assign->status
                            ];
                        })
                    ];
                })
            ];
        });

        return response()->json([
            'trip_id' => $trip->trip_id,
            'trip_name' => $trip->trip_name,
            'housing' => $data
        ]);
    }
}