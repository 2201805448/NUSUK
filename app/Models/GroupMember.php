<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GroupMember extends Model
{
    protected $table = 'group_members';

    public $timestamps = false;

    // لأن الجدول عنده Primary Key مركب (group_id + pilgrim_id)
    public $incrementing = false;
    protected $primaryKey = null;

    protected $fillable = [
        'group_id',
        'pilgrim_id',
        'join_date',
        'member_status',
    ];

    public function groupTrip()
    {
        return $this->belongsTo(GroupTrip::class, 'group_id', 'group_id');
    }

    public function pilgrim()
    {
        return $this->belongsTo(Pilgrim::class, 'pilgrim_id', 'pilgrim_id');
    }
}