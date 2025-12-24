<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BookingAttendee extends Model
{
    protected $table = 'booking_attendees';
    protected $primaryKey = 'attendee_id';

    public $timestamps = false; // ما عندكش timestamps

    protected $fillable = [
        'booking_id',
        'pilgrim_id',
        'guest_name',
        'passport_num',
        'guest_age',
        'ticket_price',
    ];

    // علاقات مهمة
    public function booking()
    {
        return $this->belongsTo(Booking::class, 'booking_id', 'booking_id');
    }

    public function pilgrim()
    {
        return $this->belongsTo(Pilgrim::class, 'pilgrim_id', 'pilgrim_id');
    }
}