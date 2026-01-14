<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Accommodation extends Model
{
    // اسم الجدول
    protected $table = 'accommodations';

    // اسم الـ Primary Key
    protected $primaryKey = 'accommodation_id';

    // Enable timestamps since the migration creates them
    public $timestamps = true;

    // الحقول المسموح بها في create()
    protected $fillable = [
        'hotel_name',
        'city',
        'room_type',
        'capacity',
        'notes',
        'start',
        'phone',
        'email',
    ];

    /*
     |-----------------------------
     | العلاقات
     |-----------------------------
     */

    // Accommodation -> RoomAssignments (one to many)
    public function roomAssignments()
    {
        return $this->hasMany(
            RoomAssignment::class,
            'accommodation_id',
            'accommodation_id'
        );
    }

    // Accommodation -> Trips (many to many)
    public function trips()
    {
        return $this->belongsToMany(Trip::class, 'trip_accommodations', 'accommodation_id', 'trip_id')
            ->withTimestamps();
    }

    // Accommodation -> Rooms (one to many)
    public function rooms()
    {
        return $this->hasMany(Room::class, 'accommodation_id', 'accommodation_id');
    }
}