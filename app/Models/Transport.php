<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transport extends Model
{
    protected $primaryKey = 'transport_id';

    protected $fillable = [
        'trip_id',
        'driver_id',
        'route_id',
        'transport_type',
        'route_from',
        'route_to',
        'departure_time',
        'arrival_time',
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

    /**
     * Get the starting location (route_from references transport_routes.id)
     */
    public function routeFrom()
    {
        return $this->belongsTo(TransportRoute::class, 'route_from', 'id');
    }

    /**
     * Get the destination location (route_to references transport_routes.id)
     */
    public function routeTo()
    {
        return $this->belongsTo(TransportRoute::class, 'route_to', 'id');
    }
}