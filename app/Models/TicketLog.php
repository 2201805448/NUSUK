<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TicketLog extends Model
{
    protected $table = 'ticket_logs';
    protected $primaryKey = 'log_id';

    public $timestamps = false; // عندك created_at فقط

    protected $fillable = [
        'ticket_id',
        'action_by',
        'action_note',
        'created_at',
    ];

    // العلاقات (المهم)
    public function ticket()
    {
        return $this->belongsTo(Ticket::class, 'ticket_id', 'ticket_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'action_by', 'user_id');
    }
}