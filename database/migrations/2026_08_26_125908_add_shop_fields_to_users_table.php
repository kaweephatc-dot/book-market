<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * ข้อมูลร้านค้าแยกออกจากข้อมูลส่วนตัว เดิมร้านใช้ phone/address/avatar ร่วมกับโปรไฟล์
     * ทำให้แก้เบอร์ร้านแล้วเบอร์ส่วนตัวเปลี่ยนตามไปด้วย
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->text('shop_description')->nullable()->after('shop_name');
            $table->string('shop_phone', 20)->nullable()->after('shop_description');
            $table->string('shop_address', 500)->nullable()->after('shop_phone');
            $table->string('shop_logo')->nullable()->after('shop_address');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['shop_description', 'shop_phone', 'shop_address', 'shop_logo']);
        });
    }
};
