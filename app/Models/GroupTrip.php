<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GroupTrip extends Model
{
    protected $table = 'groups_trips';
    protected $primaryKey = 'group_id';

    protected $fillable = [
        'trip_id',
        'supervisor_id',
        'group_code',
        'group_status',
    ];

    public $timestamps = false;

    public function trip()
    {
        return $this->belongsTo(Trip::class, 'trip_id', 'trip_id');
    }

    public function supervisor()
    {
        return $this->belongsTo(User::class, 'supervisor_id', 'user_id');
    }

    public function members()
    {
        return $this->hasMany(GroupMember::class, 'group_id', 'group_id');
    }
}