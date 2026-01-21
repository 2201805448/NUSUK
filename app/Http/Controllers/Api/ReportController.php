<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Trip;
use App\Models\Booking;
use Illuminate\Support\Facades\Response;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\TripsExport;

use App\Models\Payment;

class ReportController extends Controller
{
    /**
     * Get Sales and Revenue Report
     */
    public function revenueReport(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $startDate = $request->start_date;
        $endDate = $request->end_date;

        // Query Payments
        // We consider 'revenue' as PAID payments.
        // We can also list other transactions for 'Sales Operations' view (e.g. including pending?), but usually revenue report focuses on accepted money.
        // Let's include everything but group/filter by status.

        $query = Payment::with(['booking.user'])
            ->whereDate('payment_date', '>=', $startDate)
            ->whereDate('payment_date', '<=', $endDate);

        $payments = $query->orderBy('payment_date', 'desc')->get();

        // Calculate Summary
        $totalRevenue = $payments->where('payment_status', 'PAID')->sum('amount');
        $totalTransactions = $payments->count();
        $successfulTransactions = $payments->where('payment_status', 'PAID')->count();
        $failedTransactions = $payments->where('payment_status', 'FAILED')->count();
        $pendingTransactions = $payments->where('payment_status', 'PENDING')->count();

        // Format Records
        $records = $payments->map(function ($payment) {
            return [
                'payment_id' => $payment->payment_id,
                'booking_ref' => $payment->booking->booking_ref ?? 'N/A',
                'booking_id' => $payment->booking_id,
                'user_name' => $payment->booking->user->full_name ?? 'N/A',
                'amount' => $payment->amount,
                'payment_date' => $payment->payment_date,
                'pay_method' => $payment->pay_method,
                'status' => $payment->payment_status,
            ];
        });

        return response()->json([
            'meta' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'generated_at' => now()->toDateTimeString(),
            ],
            'summary' => [
                'total_revenue' => $totalRevenue,
                'total_transactions' => $totalTransactions,
                'successful_transactions' => $successfulTransactions,
                'failed_transactions' => $failedTransactions,
                'pending_transactions' => $pendingTransactions,
            ],
            'records' => $records
        ]);
    }

    /**
     * Get Booking Report
     */
    public function bookingReport(Request $request)
    {
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'status' => 'nullable|string',
            'trip_id' => 'nullable|exists:trips,trip_id',
        ]);

        $query = Booking::with(['user', 'trip', 'attendees']);

        // Apply Filters
        if ($request->has('start_date') && $request->start_date) {
            $query->whereDate('booking_date', '>=', $request->start_date);
        }
        if ($request->has('end_date') && $request->end_date) {
            $query->whereDate('booking_date', '<=', $request->end_date);
        }
        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }
        if ($request->has('trip_id') && $request->trip_id) {
            $query->where('trip_id', $request->trip_id);
        }

        $bookings = $query->orderBy('booking_date', 'desc')->get();

        // Aggregated Data
        $totalBookings = $bookings->count();
        $totalPax = $bookings->sum(function ($booking) {
            return $booking->attendees->count();
        });
        $totalValue = $bookings->sum('total_price');

        // Status Breakdown
        $statusBreakdown = $bookings->groupBy('status')->map->count();

        // Format Records
        $records = $bookings->map(function ($booking) {
            return [
                'booking_id' => $booking->booking_id,
                'booking_ref' => $booking->booking_ref,
                'booking_date' => $booking->booking_date,
                'trip_name' => $booking->trip->trip_name ?? 'N/A',
                'user_name' => $booking->user->full_name ?? 'N/A',
                'pax_count' => $booking->attendees->count(),
                'total_price' => $booking->total_price,
                'status' => $booking->status,
            ];
        });

        return response()->json([
            'meta' => [
                'filters' => $request->only(['start_date', 'end_date', 'status', 'trip_id']),
                'generated_at' => now()->toDateTimeString(),
            ],
            'summary' => [
                'total_bookings' => $totalBookings,
                'total_pax' => $totalPax,
                'total_value' => $totalValue,
                'status_breakdown' => $statusBreakdown,
            ],
            'records' => $records
        ]);
    }

    public function exportTrips(Request $request)
    {
        $request->validate([
            'format' => 'required|in:pdf,excel,csv',
            'status' => 'nullable|string',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date',
        ]);

        $query = Trip::query()->with('package');

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        if ($request->has('date_from')) {
            $query->whereDate('start_date', '>=', $request->date_from);
        }
        if ($request->has('date_to')) {
            $query->whereDate('start_date', '<=', $request->date_to);
        }

        $trips = $query->get();

        $format = $request->input('format');

        if ($format === 'pdf') {
            $pdf = Pdf::loadView('reports.trips_pdf', ['trips' => $trips]);
            return $pdf->download('trips_report.pdf');
        } elseif ($format === 'excel') {
            return Excel::download(new TripsExport($trips), 'trips_report.xlsx');
        } elseif ($format === 'csv') {
            $csvFileName = 'trips_report.csv';
            $headers = [
                "Content-type" => "text/csv",
                "Content-Disposition" => "attachment; filename=$csvFileName",
                "Pragma" => "no-cache",
                "Cache-Control" => "must-revalidate, post-check=0, pre-check=0",
                "Expires" => "0"
            ];

            $callback = function () use ($trips) {
                $file = fopen('php://output', 'w');
                fputcsv($file, ['ID', 'Trip Name', 'Status', 'Start Date', 'End Date', 'Package Name', 'Capacity']);

                foreach ($trips as $trip) {
                    fputcsv($file, [
                        $trip->getKey(), // يفضل استخدام هذه الطريقة لجلب المعرف الأساسي مهما كان اسمه
                        $trip->trip_name,
                        $trip->status,
                        $trip->start_date,
                        $trip->end_date,
                        $trip->package->package_name ?? 'N/A',
                        $trip->capacity
                    ]);
                }
                fclose($file);
            };

            return Response::stream($callback, 200, $headers);
        }
    }
}
