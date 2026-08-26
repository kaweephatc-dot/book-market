<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * สถานะห้องแชทของผู้ใช้แต่ละคน (ปักหมุด / ซ่อน / ถังขยะ / ลบถาวรแล้ว)
 */
class ConversationUserState extends Model
{
    // จำนวนวันที่แชทค้างอยู่ในถังขยะก่อนถูกลบถาวร
    public const TRASH_DAYS = 7;

    protected $fillable = [
        'conversation_id',
        'user_id',
        'pinned_at',
        'pin_order',
        'hidden_at',
        'trashed_at',
        'cleared_before_message_id',
    ];

    protected function casts(): array
    {
        return [
            'pinned_at' => 'datetime',
            'hidden_at' => 'datetime',
            'trashed_at' => 'datetime',
        ];
    }

    public function conversation()
    {
        return $this->belongsTo(Conversation::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // เหลืออีกกี่วันก่อนถูกลบถาวร (อย่างน้อย 1 เพื่อไม่ให้ขึ้น "เหลือ 0 วัน")
    public function daysLeftInTrash(): int
    {
        if (!$this->trashed_at) {
            return 0;
        }

        $deadline = $this->trashed_at->copy()->addDays(self::TRASH_DAYS);

        return max(1, (int) ceil(now()->diffInDays($deadline, false)));
    }
}
