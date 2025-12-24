<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AttendanceTracking extends Model
{
    protected $table = 'attendance_tracking';

    protected $primaryKey = 'attendance_id';

    public $timestamps = false;

    protected $fillable = [
        'pilgrim_id',
        'trip_id',
        'activity_id',
        'status_type',
        'timestamp',
        'supervisor_id',
        'supervisor_note',
    ];

    // علاقات (اختياري لكن مفيد)
    public function pilgrim()
    {
        return $this->belongsTo(Pilgrim::class, 'pilgrim_id', 'pilgrim_id');
    }

    public function trip()
    {
        return $this->belongsTo(Trip::class, 'trip_id', 'trip_id');
    }

    public function activity()
    {
        return $this->belongsTo(Activity::class, 'activity_id', 'activity_id');
    }

    public function supervisor()
    {
        return $this->belongsTo(User::class, 'supervisor_id', 'user_id');
    }
}