<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'book_id',
        'buyer_id',
        'seller_id',
        'status',
        'slip_image',
        'buyer_confirmed',
        'seller_confirmed',
        'dispute_reason',
        'shipping_proof',
        'tracking_number',
        'is_shipped',
    ];

    // หนังสือที่สั่งซื้อ
    public function book()
    {
        return $this->belongsTo(Book::class);
    }

    // ผู้ซื้อ
    public function buyer()
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    // ผู้ขาย
    public function seller()
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    // แปลงสถานะเป็นข้อความไทย + สี (ไว้แสดงบนหน้าเว็บ)
    // แปลงสถานะเป็นข้อความไทย + สี (ไว้แสดงบนหน้าเว็บ)
    public function statusInfo()
    {
        return match($this->status) {
            'pending'   => ['label' => 'รอผู้ขายรับออเดอร์', 'color' => 'warning'],
            'accepted'  => ['label' => 'รอผู้ซื้อโอนเงิน/แนบสลิป', 'color' => 'info'],
            'paid'      => ['label' => $this->slip_image && !$this->seller_confirmed
                                ? 'รอผู้ขายยืนยันสลิป'
                                : 'รอผู้ขายส่งของ', 'color' => 'primary'],
            'shipping'  => ['label' => 'กำลังจัดส่งหนังสือ', 'color' => 'info'],
            'completed' => ['label' => 'เสร็จสิ้น', 'color' => 'success'],
            'cancelled' => ['label' => 'ยกเลิก', 'color' => 'secondary'],
            'disputed'  => ['label' => 'มีปัญหา (รอตรวจสอบ)', 'color' => 'danger'],
            default     => ['label' => 'ไม่ทราบสถานะ', 'color' => 'secondary'],
        };
    }
}