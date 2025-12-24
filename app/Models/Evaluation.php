<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Evaluation extends Model
{
    protected $table = 'evaluations';
    protected $primaryKey = 'evaluation_id';

    public $timestamps = false; // عندك created_at فقط

    protected $fillable = [
        'pilgrim_id',
        'type',
        'score',
        'target_id',
        'concern_text',
        'created_at',
        'internal_only',
    ];

    // العلاقات المهمة فقط
    public function pilgrim()
    {
        return $this->belongsTo(Pilgrim::class, 'pilgrim_id', 'pilgrim_id');
    }
}