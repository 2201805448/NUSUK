<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\BookingModification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminBookingController extends Controller
{
    /**
     * List bookings with filters.
     */
    public function index(Request $request)
    {
        $query = Booking::with(['user', 'package', 'trip']);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Default to showing latest first
        $query->orderBy('booking_date', 'desc');

        $bookings = $query->paginate(15);

        return response()->json($bookings);
    }

    /**
     * Show booking details.
     */
    public function show($id)
    {
        $booking = Booking::with(['user', 'package', 'trip', 'payments', 'modifications'])->findOrFail($id);
        return response()->json($booking);
    }

    /**
     * Approve or Reject a booking.
     */
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:CONFIRMED,REJECTED',
            'admin_reply' => 'nullable|string'
        ]);

        $booking = Booking::findOrFail($id);

        $booking->status = $request->status;
        if ($request->has('admin_reply')) {
            $booking->admin_reply = $request->admin_reply;
        }
        $booking->save();

        return response()->json([
            'message' => 'Booking status updated successfully',
            'booking' => $booking
        ]);
    }

    /**
     * List booking modifications (pending by default).
     */
    public function indexModifications(Request $request)
    {
        $query = BookingModification::with('booking.user');

        if ($request->has('status')) {
            $query->where('status', $request->status);
        } else {
            // Default to pending if not specified, or show all? 
            // Usually admins want to see Pending first.
            $query->where('status', 'PENDING');
        }

        $query->orderBy('created_at', 'desc');

        $modifications = $query->paginate(15);

        return response()->json($modifications);
    }

    /**
     * Approve or Reject a modification request.
     */
    public function updateModificationStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:APPROVED,REJECTED',
            'admin_notes' => 'nullable|string'
        ]);

        DB::beginTransaction();
        try {
            $modification = BookingModification::findOrFail($id);
            $modification->status = $request->status;
            if ($request->has('admin_notes')) {
                $modification->admin_notes = $request->admin_notes;
            }
            $modification->save();

            // Apply logic if Approved
            if ($request->status === 'APPROVED') {
                $booking = $modification->booking;

                if ($modification->request_type === 'CANCELLATION') {
                    $booking->status = 'CANCELLED';
                    $booking->save();
                }
                // Handle other types like CHANGE_DATE if needed
                // elseif ($modification->request_type === 'CHANGE_DATE') { ... }
            }

            DB::commit();

            return response()->json([
                'message' => 'Modification request updated successfully',
                'modification' => $modification
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Error updating modification', 'error' => $e->getMessage()], 500);
        }
    }
}
