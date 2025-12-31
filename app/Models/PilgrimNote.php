<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PilgrimNote extends Model
{
    protected $table = 'pilgrim_notes';
    protected $primaryKey = 'note_id';

    public $timestamps = false;

    protected $fillable = [
        'pilgrim_id',
        'trip_id',
        'group_id',
        'note_type',
        'note_text',
        'category',
        'priority',
        'status',
        'reviewed_by',
        'response',
        'reviewed_at',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'reviewed_at' => 'datetime',
    ];

    /**
     * PilgrimNote -> Pilgrim
     */
    public function pilgrim()
    {
        return $this->belongsTo(Pilgrim::class, 'pilgrim_id', 'pilgrim_id');
    }

    /**
     * PilgrimNote -> Trip
     */
    public function trip()
    {
        return $this->belongsTo(Trip::class, 'trip_id', 'trip_id');
    }

    /**
     * PilgrimNote -> GroupTrip
     */
    public function group()
    {
        return $this->belongsTo(GroupTrip::class, 'group_id', 'group_id');
    }

    /**
     * PilgrimNote -> User (reviewer)
     */
    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by', 'user_id');
    }
}
