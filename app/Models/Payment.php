<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $table = 'payments';
    protected $primaryKey = 'payment_id';

    // عندك payment_date و ماعندكش created_at/updated_at في الجدول
    public $timestamps = false;

    protected $fillable = [
        'booking_id',
        'amount',
        'pay_method',
        'payment_date',
        'payment_status',
    ];

    public function booking()
    {
        return $this->belongsTo(Booking::class, 'booking_id', 'booking_id');
    }
}