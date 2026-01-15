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

class ReportController extends Controller
{
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
