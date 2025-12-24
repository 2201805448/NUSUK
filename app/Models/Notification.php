<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    protected $table = 'notifications';
    protected $primaryKey = 'notification_id';

    public $timestamps = false; // عندك created_at فقط

    protected $fillable = [
        'user_id',
        'title',
        'message',
        'is_read',
        'created_at',
    ];

    // 🟢 Notification تتبع User
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }
}