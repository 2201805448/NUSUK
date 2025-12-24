<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pilgrim_gallery', function (Blueprint $table) {
            // Primary Key
            $table->id('gallery_id');

            // Foreign Key
            $table->unsignedBigInteger('pilgrim_id');

            // بيانات المعرض
            $table->string('image_url', 250)->nullable();
            $table->string('city', 100)->nullable();
            $table->text('notes')->nullable();

            // العلاقة
            $table->foreign('pilgrim_id')
                ->references('pilgrim_id')
                ->on('pilgrims')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pilgrim_gallery');
    }
};