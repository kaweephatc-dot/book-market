<?php

namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageSent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Message $message,
        public int $recipientId,
    ) {}

    /**
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('chat.' . $this->message->conversation_id),
            new PrivateChannel('chat-user.' . $this->recipientId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'message.sent';
    }

    public function broadcastWith(): array
    {
        $book = $this->message->conversation->book;
        $cover = $book->images->count() > 0 ? $book->coverImage() : null;

        return [
            'id' => $this->message->id,
            'conversation_id' => $this->message->conversation_id,
            'user_id' => $this->message->user_id,
            'user_name' => $this->message->user->name,
            'message' => $this->message->message,
            'created_at' => $this->message->created_at->format('H:i'),
            // ข้อมูลเพิ่มสำหรับสร้างแถวใหม่ในหน้ารายการแชท ถ้าห้องนี้ยังไม่เคยแสดงในรายการ
            // (เช่น มีคนเริ่มแชทกับเราครั้งแรกขณะเปิดหน้ารายการค้างไว้)
            'sender_display_name' => $this->message->user->shop_name ?? $this->message->user->name,
            'book_title' => $book->title,
            'book_cover_url' => $cover ? asset('storage/' . $cover->image_path) : null,
        ];
    }
}
