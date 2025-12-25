<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    // اسم الـ Primary Key
    protected $primaryKey = 'user_id';

    // السماح بالـ mass assignment
    protected $fillable = [
        'full_name',
        'email',
        'phone_number',
        'password',
        'role',
        'account_status',
    ];

    // لأن جدولك فيه created_at و updated_at
    public $timestamps = true;

    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'password' => 'hashed',
    ];

    /**
     * العلاقة مع جدول pilgrims
     * User -> Pilgrim (one to one)
     */
    public function pilgrim()
    {
        return $this->hasOne(Pilgrim::class, 'user_id', 'user_id');
    }
}