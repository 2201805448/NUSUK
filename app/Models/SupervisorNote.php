<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SupervisorNote extends Model
{
    protected $table = 'supervisor_notes';
    protected $primaryKey = 'note_id';

    public $timestamps = false; // عندك created_at فقط

    protected $fillable = [
        'pilgrim_id',
        'supervisor_id',
        'trip_id',
        'group_id',
        'note_type',
        'note_text',
        'created_at',
    ];

    // علاقات مهمة
    public function pilgrim()
    {
        return $this->belongsTo(Pilgrim::class, 'pilgrim_id', 'pilgrim_id');
    }

    public function supervisor()
    {
        return $this->belongsTo(User::class, 'supervisor_id', 'user_id');
    }

    public function trip()
    {
        return $this->belongsTo(Trip::class, 'trip_id', 'trip_id');
    }

    public function groupTrip()
    {
        return $this->belongsTo(GroupTrip::class, 'group_id', 'group_id');
    }
}