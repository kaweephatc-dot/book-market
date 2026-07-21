<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('report_chats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // คู่สนทนาของ admin (ร้านหรือผู้รายงาน)
            $table->boolean('is_closed')->default(false);                     // ปิดแชทแล้วไหม
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_chats');
    }
};