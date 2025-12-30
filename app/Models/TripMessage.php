<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TripMessage extends Model
{
    protected $table = 'trip_messages';

    public $timestamps = false; // created_at exists, updated_at does not

    protected $fillable = [
        'trip_id',
        'user_id',
        'content',
        'created_at',
    ];

    public function trip()
    {
        return $this->belongsTo(Trip::class, 'trip_id', 'trip_id');
    }

    public function sender()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }
}
