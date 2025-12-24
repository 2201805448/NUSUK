<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evaluations', function (Blueprint $table) {
            $table->id('evaluation_id');

            $table->unsignedBigInteger('pilgrim_id');

            $table->enum('type', [
                'TRIP',
                'HOTEL',
                'SERVICE',
                'SUPPORT',
            ]);

            $table->integer('score');
            $table->unsignedBigInteger('target_id');

            $table->text('concern_text')->nullable();

            // created_at فقط (مافيش updated_at في SQL)
            $table->dateTime('created_at')->useCurrent();

            $table->boolean('internal_only')->default(1);

            // FK: pilgrim_id → pilgrims(pilgrim_id)
            $table->foreign('pilgrim_id')
                ->references('pilgrim_id')
                ->on('pilgrims')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evaluations');
    }
};