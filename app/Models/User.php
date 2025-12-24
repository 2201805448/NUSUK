<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    // اسم الـ Primary Key
    protected $primaryKey = 'user_id';

    // السماح بالـ mass assignment
    protected $fillable = [
        'full_name',
        'email',
        'phone_number',
        'password_hash',
        'role',
        'account_status',
    ];

    // لأن جدولك فيه created_at و updated_at
    public $timestamps = true;

    /**
     * العلاقة مع جدول pilgrims
     * User -> Pilgrim (one to one)
     */
    public function pilgrim()
    {
        return $this->hasOne(Pilgrim::class, 'user_id', 'user_id');
    }
}