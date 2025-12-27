<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    protected $fillable = [
        'accommodation_id',
        'room_number',
        'floor',
        'room_type',
        'status',
        'notes',
    ];

    public function accommodation()
    {
        return $this->belongsTo(Accommodation::class, 'accommodation_id', 'accommodation_id');
    }
}
