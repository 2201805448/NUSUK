<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RoomAssignment extends Model
{
    protected $table = 'room_assignments';
    protected $primaryKey = 'assignment_id';
    public $timestamps = false;

    protected $fillable = [
        'pilgrim_id',
        'accommodation_id',
        'check_in',
        'check_out',
        'status',
    ];

    /**
     * RoomAssignment -> Accommodation
     */
    public function accommodation()
    {
        return $this->belongsTo(
            Accommodation::class,
            'accommodation_id',
            'accommodation_id'
        );
    }

    /**
     * RoomAssignment -> Pilgrim
     */
    public function pilgrim()
    {
        return $this->belongsTo(
            Pilgrim::class,
            'pilgrim_id',
            'pilgrim_id'
        );
    }
}