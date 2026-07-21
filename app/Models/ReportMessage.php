<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReportMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'report_chat_id',
        'user_id',
        'message',
    ];

    // แชทที่ข้อความนี้อยู่
    public function reportChat()
    {
        return $this->belongsTo(ReportChat::class);
    }

    // ผู้ส่ง
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}