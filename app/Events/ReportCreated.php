<?php

namespace App\Events;

use App\Models\Book;
use App\Models\Report;
use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ReportCreated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Report $report,
    ) {}

    /**
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('admin-notifications'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'report.created';
    }

    public function broadcastWith(): array
    {
        $report = $this->report;

        // เจ้าของสิ่งที่ถูกรายงาน (ร้าน หรือ เจ้าของหนังสือ) ไว้ทำปุ่มแชท/แบนในการ์ดใหม่ฝั่ง client
        $owner = match ($report->reportable_type) {
            User::class => $report->reportable,
            Book::class => $report->reportable?->user,
            default => null,
        };

        $reportableLabel = match (true) {
            $report->reportable_type === Book::class && $report->reportable => $report->reportable->title,
            $report->reportable_type === User::class && $report->reportable => $report->reportable->shop_name ?? $report->reportable->name,
            default => null,
        };

        $reportableUrl = match (true) {
            $report->reportable_type === Book::class && $report->reportable => route('books.show', $report->reportable),
            $report->reportable_type === User::class && $report->reportable => route('shop.show', $report->reportable),
            default => null,
        };

        return [
            'id' => $report->id,
            'reason' => $report->reason,
            'detail' => $report->detail,
            'type_label' => $report->typeLabel(),
            'reporter_name' => $report->reporter->name,
            'reportable_label' => $reportableLabel,
            'reportable_url' => $reportableUrl,
            'reportable_type_key' => $report->reportable_type === Book::class ? 'book' : 'user',
            'chat_with_reporter_url' => route('admin.report.chat', [$report, $report->reporter]),
            'chat_with_owner_url' => $owner ? route('admin.report.chat', [$report, $owner]) : null,
            'owner_display_name' => $owner ? ($owner->shop_name ?? $owner->name) : null,
            'ban_url' => $owner ? route('admin.users.banAdvanced', $owner) : null,
            'resolve_url' => route('admin.reports.resolve', $report),
            'dismiss_url' => route('admin.reports.dismiss', $report),
        ];
    }
}
