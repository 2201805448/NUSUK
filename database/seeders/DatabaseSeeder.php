<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // إضافة مستخدم الأدمن (دعاء) مع مطابقة حقول قاعدة البيانات الخاصة بكِ
        User::updateOrCreate(
            ['email' => 'doaa@gmail.com'], // للبحث إذا كان المستخدم موجود مسبقاً
            [
                'full_name' => 'Doaa',
                'phone_number' => '0924576189',
                'password' => Hash::make('0924576189'),
                'role' => 'ADMIN',
                'account_status' => 'ACTIVE', // لضمان عدم ظهور خطأ الحقل الفارغ
            ]
        );
    }
}