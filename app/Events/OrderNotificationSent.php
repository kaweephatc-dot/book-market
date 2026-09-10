<?php

namespace App\Events;

use App\Models\OrderNotification;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderNotificationSent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public OrderNotification $notification,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('order-user.' . $this->notification->recipient_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'order.notification.sent';
    }

    public function broadcastWith(): array
    {
        $order = $this->notification->order()->with('book')->first();
        $recipientRole = $order && (int) $order->buyer_id === (int) $this->notification->recipient_id
            ? 'buyer'
            : 'seller';

        $unreadCount = OrderNotification::where('recipient_id', $this->notification->recipient_id)
            ->where('is_read', false)
            ->count();

        return [
            'id' => $this->notification->id,
            'order_id' => $this->notification->order_id,
            'type' => $this->notification->type,
            'type_label' => $this->notification->typeLabel(),
            'recipient_role' => $recipientRole,
            'book_title' => $order?->book?->title,
            'order_url' => $order ? route('orders.index', ['tab' => $recipientRole]) . '#order-' . $order->id : route('orders.index'),
            'created_at' => $this->notification->created_at?->toISOString(),
            'unread_count' => $unreadCount,
        ];
    }
}
