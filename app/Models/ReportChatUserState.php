<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * สถานะแชทรายงานของผู้ใช้แต่ละคน — ตอนนี้ใช้แค่ "ซ่อน" สำหรับฝั่งแอดมิน
 */
class ReportChatUserState extends Model
{
    protected $fillable = [
        'report_chat_id',
        'user_id',
        'hidden_at',
    ];

    protected function casts(): array
    {
        return [
            'hidden_at' => 'datetime',
        ];
    }

    public function reportChat()
    {
        return $this->belongsTo(ReportChat::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
