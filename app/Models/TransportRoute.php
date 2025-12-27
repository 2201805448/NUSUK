<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransportRoute extends Model
{
    protected $fillable = [
        'route_name',
        'start_location',
        'end_location',
        'distance_km',
        'estimated_duration_mins',
    ];

    public function transports()
    {
        return $this->hasMany(Transport::class, 'route_id', 'id');
    }
}
