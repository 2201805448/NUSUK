<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class TripsExport implements FromCollection, WithHeadings, WithMapping
{
    protected $trips;

    public function __construct($trips)
    {
        $this->trips = $trips;
    }

    public function collection()
    {
        return $this->trips;
    }

    public function headings(): array
    {
        return [
            'ID',
            'Trip Name',
            'Status',
            'Start Date',
            'End Date',
            'Package Name',
            'Capacity',
        ];
    }

    public function map($trip): array
    {
        return [
            $trip->trip_id,
            $trip->trip_name,
            $trip->status,
            $trip->start_date,
            $trip->end_date,
            $trip->package->package_name ?? 'N/A',
            $trip->capacity,
        ];
    }
}
