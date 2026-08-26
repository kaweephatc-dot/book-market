<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * สถานะห้องแชท "รายคน" — ปักหมุด/ซ่อน/ลบ เป็นเรื่องของแต่ละฝ่าย
     * เราลบแชทของคู่สนทนาไม่ได้ จึงเก็บแยกตาม user แทนที่จะใส่คอลัมน์ใน conversations
     */
    public function up(): void
    {
        Schema::create('conversation_user_states', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            $table->timestamp('pinned_at')->nullable();
            // ลำดับของแชทที่ปักหมุด (น้อย = อยู่บนกว่า) ผู้ใช้เลื่อนเองได้
            $table->unsignedInteger('pin_order')->nullable();

            $table->timestamp('hidden_at')->nullable();
            // อยู่ในถังขยะตั้งแต่เมื่อไหร่ กู้คืนได้ภายใน 7 วัน
            $table->timestamp('trashed_at')->nullable();
            // ครบ 7 วันแล้วลบถาวร: ข้อความก่อนเวลานี้จะไม่แสดงให้ user คนนี้เห็นอีก
            $table->timestamp('cleared_at')->nullable();

            $table->timestamps();

            $table->unique(['conversation_id', 'user_id']);
            $table->index(['user_id', 'trashed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conversation_user_states');
    }
};
