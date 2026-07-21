<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reporter_id')->constrained('users')->onDelete('cascade');

            // ประเภทสิ่งที่ถูกรายงาน (book หรือ user) + id ของมัน
            $table->string('reportable_type');
            $table->unsignedBigInteger('reportable_id');

            $table->string('reason');                    // เหตุผลที่เลือกจาก list
            $table->text('detail')->nullable();          // รายละเอียดเพิ่มเติม
            $table->enum('status', ['pending', 'resolved', 'dismissed'])->default('pending');

            $table->timestamps();

            $table->index(['reportable_type', 'reportable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};