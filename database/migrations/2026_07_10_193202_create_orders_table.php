<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('book_id')->constrained()->onDelete('cascade');
            $table->foreignId('buyer_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('seller_id')->constrained('users')->onDelete('cascade');

            // สถานะ: pending=รอผู้ขายรับ, accepted=รอโอน/แนบสลิป,
            //        paid=ผู้ขายยืนยันรับเงิน, completed=เสร็จ, cancelled=ยกเลิก, disputed=มีปัญหา
            $table->enum('status', ['pending', 'accepted', 'paid', 'completed', 'cancelled', 'disputed'])->default('pending');

            $table->string('slip_image')->nullable();          // รูปสลิปโอนเงิน
            $table->boolean('buyer_confirmed')->default(false); // ผู้ซื้อยืนยันว่าได้ของแล้ว
            $table->boolean('seller_confirmed')->default(false);// ผู้ขายยืนยันว่าจบแล้ว
            $table->text('dispute_reason')->nullable();         // เหตุผลการแจ้งปัญหา

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};