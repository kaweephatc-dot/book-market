<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('shipping_proof')->nullable()->after('slip_image');   // รูปหลักฐานการส่ง
            $table->string('tracking_number')->nullable()->after('shipping_proof'); // เลขพัสดุ
            $table->boolean('is_shipped')->default(false)->after('tracking_number'); // ส่งแล้วไหม
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['shipping_proof', 'tracking_number', 'is_shipped']);
        });
    }
};