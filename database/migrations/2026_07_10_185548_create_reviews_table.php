<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reviewer_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('shop_id')->constrained('users')->onDelete('cascade');
            $table->integer('rating');
            $table->text('comment')->nullable();
            $table->timestamps();

            // ผู้รีวิว 1 คน รีวิวร้าน 1 ร้านได้ครั้งเดียว
            $table->unique(['reviewer_id', 'shop_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};