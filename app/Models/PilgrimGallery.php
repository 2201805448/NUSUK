<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PilgrimGallery extends Model
{
    protected $table = 'pilgrim_gallery';
    protected $primaryKey = 'gallery_id';
    public $timestamps = false;

    protected $fillable = [
        'pilgrim_id',
        'image_url',
        'city',
        'notes',
    ];

    public function pilgrim()
    {
        return $this->belongsTo(Pilgrim::class, 'pilgrim_id', 'pilgrim_id');
    }
}