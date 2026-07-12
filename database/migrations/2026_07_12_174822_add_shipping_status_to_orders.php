<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // เพิ่มค่า shipping เข้าไปใน enum status
        DB::statement("ALTER TABLE orders MODIFY COLUMN status ENUM('pending', 'accepted', 'paid', 'shipping', 'completed', 'cancelled', 'disputed') DEFAULT 'pending'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE orders MODIFY COLUMN status ENUM('pending', 'accepted', 'paid', 'completed', 'cancelled', 'disputed') DEFAULT 'pending'");
    }
};