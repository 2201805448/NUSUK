<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    protected $table = 'tickets';
    protected $primaryKey = 'ticket_id';

    public $timestamps = false; // لأن عندك created_at فقط

    protected $fillable = [
        'user_id',
        'trip_id',
        'priority',
        'status',
        'created_at',
    ];


    // Ticket تتبع User
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    // Ticket ممكن تتبع Trip (nullable)
    public function trip()
    {
        return $this->belongsTo(Trip::class, 'trip_id', 'trip_id');
    }

    // Ticket عندها Logs
    public function logs()
    {
        return $this->hasMany(TicketLog::class, 'ticket_id', 'ticket_id');
    }
}