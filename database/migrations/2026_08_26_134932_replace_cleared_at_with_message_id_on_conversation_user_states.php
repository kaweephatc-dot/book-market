<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * เปลี่ยนเส้นแบ่ง "ประวัติที่ถูกลบถาวร" จาก timestamp มาเป็น id ของข้อความ
     *
     * คอลัมน์ timestamp ของ MySQL ละเอียดแค่ระดับวินาที ทำให้ข้อความที่มาถึง
     * ในวินาทีเดียวกับตอนลบ ตัดสินไม่ได้ว่าอยู่ก่อนหรือหลังเส้นแบ่ง
     * id ของข้อความเพิ่มขึ้นเสมอและไม่ซ้ำ จึงแบ่งได้เป๊ะโดยไม่ต้องพึ่งความละเอียดของนาฬิกา
     */
    public function up(): void
    {
        Schema::table('conversation_user_states', function (Blueprint $table) {
            $table->unsignedBigInteger('cleared_before_message_id')->nullable()->after('trashed_at');
            $table->dropColumn('cleared_at');
        });
    }

    public function down(): void
    {
        Schema::table('conversation_user_states', function (Blueprint $table) {
            $table->timestamp('cleared_at')->nullable()->after('trashed_at');
            $table->dropColumn('cleared_before_message_id');
        });
    }
};
