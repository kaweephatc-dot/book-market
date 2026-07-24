<?php

use App\Models\Conversation;
use App\Models\ReportChat;
use Illuminate\Support\Facades\Broadcast;

// ห้องแชทผู้ซื้อ/ผู้ขาย - เข้าได้เฉพาะผู้ซื้อหรือผู้ขายของห้องนั้น
Broadcast::channel('chat.{conversationId}', function ($user, $conversationId) {
    $conversation = Conversation::find($conversationId);

    return $conversation
        && ($conversation->buyer_id === $user->id || $conversation->seller_id === $user->id);
});

// ช่องส่วนตัวต่อผู้ใช้ สำหรับอัปเดตตัวเลขแดง navbar แบบสด
Broadcast::channel('chat-user.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});

// ห้องแชทรายงาน - เข้าได้ทั้งเจ้าของแชทและแอดมิน
Broadcast::channel('report-chat.{chatId}', function ($user, $chatId) {
    $chat = ReportChat::find($chatId);

    return $chat && ((int) $chat->user_id === (int) $user->id || $user->is_admin);
});

// ช่องแจ้งเตือนรวมของแอดมินทุกคน สำหรับตัวเลขแดงข้อความแชทรายงาน
Broadcast::channel('admin-notifications', function ($user) {
    return (bool) $user->is_admin;
});
