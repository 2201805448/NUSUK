<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TripUpdate extends Model
{
    protected $table = 'trip_updates';
    protected $primaryKey = 'update_id';
    public $timestamps = false;

    protected $fillable = [
        'trip_id',
        'title',
        'message',
        'created_by',
        'created_at',
    ];

    public function trip()
    {
        return $this->belongsTo(Trip::class, 'trip_id', 'trip_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by', 'user_id');
    }
}
