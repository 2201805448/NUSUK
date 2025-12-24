<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Announcement extends Model
{
    protected $table = 'announcements';

    protected $primaryKey = 'announcement_id';

    public $timestamps = false;

    protected $fillable = [
        'title',
        'content',
        'image_url',
        'is_active',
        'expiry_date',
    ];
}