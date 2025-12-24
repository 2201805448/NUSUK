<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    protected $table = 'messages';
    protected $primaryKey = 'message_id';

    public $timestamps = false; // عندك created_at فقط

    protected $fillable = [
        'sender_id',
        'receiver_id',
        'content',
        'created_at',
    ];

    // 🟢 المرسل
    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id', 'user_id');
    }

    // 🟢 المستقبل
    public function receiver()
    {
        return $this->belongsTo(User::class, 'receiver_id', 'user_id');
    }
}