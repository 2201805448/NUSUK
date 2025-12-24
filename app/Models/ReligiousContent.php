<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReligiousContent extends Model
{
    protected $table = 'religious_content';

    protected $primaryKey = 'content_id';

    public $timestamps = false;

    protected $fillable = [
        'title',
        'category',
        'body_text',
        'image_url',
    ];
}