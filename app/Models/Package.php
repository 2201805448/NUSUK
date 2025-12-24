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
        'services',
        'mod_policy',
        'cancel_policy',
        'is_active',
    ];

    public function trips()
    {
        return $this->hasMany(Trip::class, 'package_id', 'package_id');
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class, 'package_id', 'package_id');
    }
}