<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transport extends Model
{
    protected $primaryKey = 'transport_id';

    protected $fillable = [
        'trip_id',
        'transport_type',
        'route_from',
        'route_to',
        'departure_time',
        'notes',
    ];

    public $timestamps = false;

    public function trip()
    {
        return $this->belongsTo(Trip::class, 'trip_id', 'trip_id');
    }
}