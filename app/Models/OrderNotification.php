<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderNotification extends Model
{
    use HasFactory;

    public const ORDER_CREATED = 'order_created';
    public const ORDER_ACCEPTED = 'order_accepted';
    public const SLIP_UPLOADED = 'slip_uploaded';
    public const PAYMENT_CONFIRMED = 'payment_confirmed';
    public const SHIPPING_CONFIRMED = 'shipping_confirmed';
    public const RECEIVED_CONFIRMED = 'received_confirmed';
    public const ORDER_CANCELLED = 'order_cancelled';
    public const ORDER_DISPUTED = 'order_disputed';

    protected $fillable = [
        'order_id',
        'recipient_id',
        'type',
        'is_read',
    ];

    protected function casts(): array
    {
        return [
            'is_read' => 'boolean',
        ];
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function recipient()
    {
        return $this->belongsTo(User::class, 'recipient_id');
    }

    public function typeLabel(): string
    {
        return match ($this->type) {
            self::ORDER_CREATED => 'มีคำสั่งซื้อใหม่',
            self::ORDER_ACCEPTED => 'ผู้ขายตอบรับออเดอร์',
            self::SLIP_UPLOADED => 'ผู้ซื้อแนบสลิปแล้ว',
            self::PAYMENT_CONFIRMED => 'ผู้ขายยืนยันรับเงินแล้ว',
            self::SHIPPING_CONFIRMED => 'ผู้ขายยืนยันจัดส่งแล้ว',
            self::RECEIVED_CONFIRMED => 'ผู้ซื้อยืนยันรับของแล้ว',
            self::ORDER_CANCELLED => 'ออเดอร์ถูกยกเลิก',
            self::ORDER_DISPUTED => 'มีการแจ้งข้อพิพาท',
            default => 'มีความเคลื่อนไหวในออเดอร์',
        };
    }
}
