<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('book_images', function (Blueprint $table) {
            // มุมถ่ายภาพ: cover/spine/page/back — null สำหรับรูปเก่าก่อนมีฟีเจอร์นี้
            $table->string('slot')->nullable()->after('image_path');
            // คำอธิบายสภาพ/ตำหนิต่อรูปจาก AI
            $table->text('ai_note')->nullable()->after('ai_score');
            // AI เห็นว่ารูปตรงกับมุมของช่องไหม (null = ไม่เคยประเมิน)
            $table->boolean('ai_angle_match')->nullable()->after('ai_note');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('book_images', function (Blueprint $table) {
            $table->dropColumn(['slot', 'ai_note', 'ai_angle_match']);
        });
    }
};
