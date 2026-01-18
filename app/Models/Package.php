<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Package extends Model
{
    protected $table = 'packages';
    protected $primaryKey = 'package_id';
    public $timestamps = false;

    protected $fillable = [
        'package_name',
        'price',
        'description',
        'duration_days',
        'accommodation_id',
        'room_type',
        'services',
        'mod_policy',
        'cancel_policy',
        'is_active',
    ];
    protected $casts = [
        'services' => 'array',      // يحول النص المخزن في القاعدة لمصفوفة يقرأها الـ Vue
        'is_active' => 'boolean',    // يضمن وصول حالة النشاط كقيمة صح/خطأ وليس 0 أو 1
        'price' => 'decimal:2',      // يحافظ على دقة السعر عند الإرسال
        'duration_days' => 'integer' // يضمن وصول المدة كرقم صحيح
    ];

    public function trips()
    {
        return $this->hasMany(Trip::class, 'package_id', 'package_id');
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class, 'package_id', 'package_id');
    }

    public function accommodation()
    {
        return $this->belongsTo(Accommodation::class, 'accommodation_id', 'accommodation_id');
    }
}