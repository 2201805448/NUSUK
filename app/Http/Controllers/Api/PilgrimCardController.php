<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Pilgrim;
use App\Models\RoomAssignment;
use Illuminate\Support\Facades\Auth;

class PilgrimCardController extends Controller
{
    /**
     * Display the digital card for the authenticated pilgrim.
     */
    public function show()
    {
        $user = Auth::user();

        // 1. Find Pilgrim Profile
        $pilgrim = Pilgrim::where('user_id', $user->user_id)->first();

        if (!$pilgrim) {
            return response()->json(['message' => 'Pilgrim profile not found.'], 404);
        }

        // 2. Load Relationships (Active Group & Housing)
        $pilgrim->load([
            'groupMembers' => function ($q) {
                // Assuming we want the latest active group? Or all?
                // Usually a pilgrim is in one active trip at a time.
                $q->where('member_status', 'ACTIVE')->with('groupTrip.trip', 'groupTrip.supervisor');
            },
            'roomAssignments' => function ($q) {
                // Active room assignment
                $q->whereIn('status', ['CONFIRMED', 'PENDING'])
                    ->where('check_out', '>=', now())
                    ->with('accommodation', 'room');
            }
        ]);

        // 3. Extract Data for Card
        $groupData = null;
        $activeMember = $pilgrim->groupMembers->first();
        if ($activeMember && $activeMember->groupTrip) {
            $groupData = [
                'group_code' => $activeMember->groupTrip->group_code,
                'trip_name' => $activeMember->groupTrip->trip->trip_name ?? 'N/A',
                'supervisor' => $activeMember->groupTrip->supervisor->full_name ?? 'Unassigned',
                'supervisor_phone' => $activeMember->groupTrip->supervisor->phone_number ?? 'N/A'
            ];
        }

        $housingData = null;
        $activeAssignment = $pilgrim->roomAssignments->sortByDesc('check_in')->first();
        if ($activeAssignment) {
            $housingData = [
                'hotel_name' => $activeAssignment->accommodation->hotel_name ?? 'N/A',
                'room_number' => $activeAssignment->room->room_number ?? 'N/A',
                'floor' => $activeAssignment->room->floor ?? '-',
                'check_out' => $activeAssignment->check_out
            ];
        }

        // 4. Generate QR Code Content (Simple unique string for verification)
        // Format: P-{id}-U-{uid}-{timestamp}
        $qrContent = "NUSUK-PILGRIM-" . $pilgrim->pilgrim_id . "-" . $user->user_id;

        return response()->json([
            'card' => [
                'full_name' => $user->full_name,
                'passport_number' => $pilgrim->passport_number,
                'nationality' => $pilgrim->nationality,
                'nusuk_id' => $pilgrim->nusuk_id ?? 'PENDING', // If nusuk_id existed
                'group' => $groupData,
                'housing' => $housingData,
                'qr_code_content' => $qrContent
            ]
        ]);
    }
}
