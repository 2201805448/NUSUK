<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('packages', function (Blueprint $table) {
            // Primary Key
            $table->id('package_id');

            // بيانات الباقة
            $table->string('package_name', 150);
            $table->decimal('price', 10, 2);
            $table->text('description')->nullable();
            $table->integer('duration_days');
            $table->text('services')->nullable();
            $table->text('mod_policy')->nullable();
            $table->text('cancel_policy')->nullable();

            // حالة التفعيل
            $table->boolean('is_active')->default(true);

            // created_at فقط (بدون updated_at)
            $table->dateTime('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('packages');
    }
};