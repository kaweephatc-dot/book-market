<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Conversation extends Model
{
    use HasFactory;

    protected $fillable = [
        'book_id',
        'buyer_id',
        'seller_id',
    ];

    // การสนทนานี้เกี่ยวกับหนังสือเล่มไหน
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

    // ข้อความทั้งหมดในการสนทนานี้
    public function messages()
    {
        return $this->hasMany(Message::class);
    }

    // ข้อความล่าสุด (ไว้แสดงในรายการแชท)
    public function latestMessage()
    {
        return $this->hasOne(Message::class)->latestOfMany();
    }

    // สถานะรายคน: ปักหมุด / ซ่อน / ถังขยะ
    public function userStates()
    {
        return $this->hasMany(ConversationUserState::class);
    }

    // สถานะของผู้ใช้คนหนึ่ง สร้างแถวให้ถ้ายังไม่มี
    public function stateFor(int $userId): ConversationUserState
    {
        return ConversationUserState::firstOrCreate([
            'conversation_id' => $this->id,
            'user_id' => $userId,
        ]);
    }

    // ผู้ใช้คนนี้อยู่ในห้องนี้ไหม
    public function hasParticipant(int $userId): bool
    {
        return $this->buyer_id === $userId || $this->seller_id === $userId;
    }
}