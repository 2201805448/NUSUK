<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    protected $table = 'bookings';
    protected $primaryKey = 'booking_id';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'package_id',
        'trip_id',
        'booking_ref',
        'booking_date',
        'total_price',
        'pay_method',
        'status',
        'request_notes',
        'admin_reply',
    ];

    // Booking -> User (Many to One)
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    // Booking -> Package (Many to One)
    public function package()
    {
        return $this->belongsTo(Package::class, 'package_id', 'package_id');
    }

    // Booking -> Trip (Many to One)
    public function trip()
    {
        return $this->belongsTo(Trip::class, 'trip_id', 'trip_id');
    }


    public function payments()
    {
        return $this->hasMany(Payment::class, 'booking_id', 'booking_id');
    }
}