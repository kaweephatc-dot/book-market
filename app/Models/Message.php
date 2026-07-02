<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    use HasFactory;

    protected $fillable = [
        'conversation_id',
        'user_id',
        'message',
        'is_read',
    ];

    // ข้อความนี้อยู่ในการสนทนาไหน
    public function conversation()
    {
        return $this->belongsTo(Conversation::class);
    }

    // ใครเป็นคนส่งข้อความนี้
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}