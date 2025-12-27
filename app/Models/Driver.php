<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Driver extends Model
{
    protected $primaryKey = 'driver_id';

    protected $fillable = [
        'name',
        'license_number',
        'phone_number',
        'status',
    ];

    public function transports()
    {
        return $this->hasMany(Transport::class, 'driver_id', 'driver_id');
    }
}
