<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Pilgrim extends Model
{
    use HasFactory;

    protected $primaryKey = 'pilgrim_id';

    protected $fillable = [
        'user_id',
        'passport_name',
        'passport_number',
        'passport_img',
        'visa_img',
        'nationality',
        'date_of_birth',
        'gender',
        'emergency_call',
        'notes',
    ];

    // علاقة: كل Pilgrim تابع لـ User واحد
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function groupMembers()
    {
        return $this->hasMany(GroupMember::class, 'pilgrim_id', 'pilgrim_id');
    }

    public function roomAssignments()
    {
        return $this->hasMany(RoomAssignment::class, 'pilgrim_id', 'pilgrim_id');
    }

    public function latestAttendance()
    {
        return $this->hasOne(AttendanceTracking::class, 'pilgrim_id')->latestOfMany('attendance_id');
    }
}