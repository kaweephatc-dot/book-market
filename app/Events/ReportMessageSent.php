<?php

namespace App\Events;

use App\Models\ReportMessage;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ReportMessageSent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public ReportMessage $message,
    ) {}

    /**
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        $channels = [
            new PrivateChannel('report-chat.' . $this->message->report_chat_id),
        ];

        if ($this->message->user->is_admin) {
            // แอดมินตอบกลับ -> แจ้งเตือนผู้ใช้เจ้าของแชทแบบสด
            $channels[] = new PrivateChannel('chat-user.' . $this->message->reportChat->user_id);
        } else {
            // ผู้ใช้ส่งมา -> แจ้งเตือนแอดมินทุกคนแบบสด
            $channels[] = new PrivateChannel('admin-notifications');
        }

        return $channels;
    }

    public function broadcastAs(): string
    {
        return 'report-message.sent';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->message->id,
            'report_chat_id' => $this->message->report_chat_id,
            'user_id' => $this->message->user_id,
            'user_name' => $this->message->user->name,
            'message' => $this->message->message,
            'created_at' => $this->message->created_at->format('H:i'),
            'is_admin_sender' => (bool) $this->message->user->is_admin,
            // ข้อมูลเพิ่มสำหรับสร้างแถวใหม่ในหน้ารายการ ถ้าห้องนี้ยังไม่เคยแสดง (แอดมินเพิ่งเปิดแชทครั้งแรก)
            'report_reason' => $this->message->reportChat->report->reason,
        ];
    }
}
