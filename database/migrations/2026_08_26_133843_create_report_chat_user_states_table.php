<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * สถานะแชทรายงานรายคน — ใช้เฉพาะ "ซ่อน" เพื่อให้แอดมินเก็บกวาดรายการที่จัดการเสร็จแล้ว
     * เก็บตาม user เพราะระบบมีแอดมินได้หลายคน ซ่อนของใครของมัน
     */
    public function up(): void
    {
        Schema::create('report_chat_user_states', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_chat_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            $table->timestamp('hidden_at')->nullable();

            $table->timestamps();

            $table->unique(['report_chat_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_chat_user_states');
    }
};
