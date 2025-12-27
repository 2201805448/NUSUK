<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Trip extends Model
{
    protected $primaryKey = 'trip_id';

    protected $fillable = [
        'package_id',
        'trip_name',
        'start_date',
        'end_date',
        'status',
        'capacity',
        'notes',
    ];

    public $timestamps = false;

    // 🔗 العلاقة مع Package
    public function package()
    {
        return $this->belongsTo(Package::class, 'package_id', 'package_id');
    }

    // 🔗 العلاقة مع Bookings
    public function bookings()
    {
        return $this->hasMany(Booking::class, 'trip_id', 'trip_id');
    }

    // 🔗 العلاقة مع Accommodations (Hotels)
    public function accommodations()
    {
        return $this->belongsToMany(Accommodation::class, 'trip_accommodations', 'trip_id', 'accommodation_id')
            ->withTimestamps();
    }

    // 🔗 العلاقة مع Transports
    public function transports()
    {
        return $this->hasMany(Transport::class, 'trip_id', 'trip_id');
    }
}