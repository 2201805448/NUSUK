<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transport extends Model
{
    protected $primaryKey = 'transport_id';

    protected $fillable = [
        'trip_id',
        'driver_id', // Added
        'route_id', // Added
        'transport_type',
        'route_from',
        'route_to',
        'departure_time',
        'arrival_time', // Added
        'notes',
    ];

    public $timestamps = false;

    public function trip()
    {
        return $this->belongsTo(Trip::class, 'trip_id', 'trip_id');
    }

    public function driver()
    {
        return $this->belongsTo(Driver::class, 'driver_id', 'driver_id');
    }

    public function route()
    {
        return $this->belongsTo(TransportRoute::class, 'route_id', 'id');
    }
}