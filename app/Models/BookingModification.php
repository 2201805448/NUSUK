<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BookingModification extends Model
{
    protected $table = 'booking_modifications';
    protected $primaryKey = 'modification_id';

    protected $fillable = [
        'booking_id',
        'request_type',
        'request_data',
        'status',
        'admin_notes',
    ];

    protected $casts = [
        'request_data' => 'array',
    ];

    public function booking()
    {
        return $this->belongsTo(Booking::class, 'booking_id', 'booking_id');
    }
}
