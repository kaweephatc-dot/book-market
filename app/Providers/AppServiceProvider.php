<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // บังคับ HTTPS เมื่ออยู่บน production (server จริง)
        if (env('APP_ENV') === 'production') {
            \Illuminate\Support\Facades\URL::forceScheme('https');
        }

        // แชร์จำนวนข้อความที่ยังไม่อ่านไปทุกหน้า (สำหรับ navbar / sidebar badge)
        \Illuminate\Support\Facades\View::composer(['layouts.app', 'layouts.user-dashboard'], function ($view) {
            $unreadCount = 0;
            $unreadReportMessageCount = 0;
            $orderUnreadCount = 0;
            $orderUnreadNotifications = collect();

            if (\Illuminate\Support\Facades\Auth::check()) {
                $userId = \Illuminate\Support\Facades\Auth::id();

                // นับข้อความที่ยังไม่อ่าน ในห้องแชทที่เราเกี่ยวข้อง และไม่ใช่ข้อความที่เราส่งเอง
                // ห้องที่อยู่ในถังขยะ หรือข้อความเก่าที่ถูกลบถาวรไปแล้ว ต้องไม่นับเข้า badge
                // (ห้องที่ "ซ่อน" ยังนับอยู่ เพราะซ่อนไว้แค่ไม่ให้รก ไม่ได้แปลว่าเลิกสนใจ)
                $unreadCount = \App\Models\Message::whereHas('conversation', function ($q) use ($userId) {
                        $q->where(function ($q) use ($userId) {
                            $q->where('buyer_id', $userId)->orWhere('seller_id', $userId);
                        });
                    })
                    ->where('user_id', '!=', $userId)
                    ->where('is_read', false)
                    ->whereNotExists(function ($q) use ($userId) {
                        $q->selectRaw('1')
                            ->from('conversation_user_states as s')
                            ->whereColumn('s.conversation_id', 'messages.conversation_id')
                            ->where('s.user_id', $userId)
                            ->where(function ($q) {
                                $q->whereNotNull('s.trashed_at')
                                    ->orWhereColumn('s.cleared_before_message_id', '>=', 'messages.id');
                            });
                    })
                    ->count();

                // นับข้อความในแชทรายงานของเรา ที่ไม่ใช่ข้อความที่เราส่งเอง (คือมาจากแอดมิน) และยังไม่อ่าน
                $unreadReportMessageCount = \App\Models\ReportMessage::query()
                    ->join('report_chats', 'report_chats.id', '=', 'report_messages.report_chat_id')
                    ->where('report_chats.user_id', $userId)
                    ->where('report_messages.user_id', '!=', $userId)
                    ->where('report_messages.is_read', false)
                    ->count();

                    $orderUnreadCount = \App\Models\OrderNotification::where('recipient_id', $userId)
                        ->where('is_read', false)
                        ->whereHas('order', function ($query) use ($userId) {
                            $query->where(function ($query) use ($userId) {
                                $query->where('buyer_id', $userId)
                                    ->orWhere('seller_id', $userId);
                            });
                        })
                        ->count();

                    $orderUnreadNotifications = \App\Models\OrderNotification::with('order.book')
                        ->where('recipient_id', $userId)
                        ->where('is_read', false)
                        ->whereHas('order', function ($query) use ($userId) {
                            $query->where(function ($query) use ($userId) {
                                $query->where('buyer_id', $userId)
                                    ->orWhere('seller_id', $userId);
                            });
                        })
                        ->latest()
                        ->limit(10)
                        ->get();
            }

            $view->with('unreadMessageCount', $unreadCount);
            $view->with('unreadReportMessageCount', $unreadReportMessageCount);
                    $view->with('orderUnreadCount', $orderUnreadCount);
                    $view->with('orderUnreadNotifications', $orderUnreadNotifications);
        });

        // แชร์จำนวนข้อความแชทรายงานที่ยังไม่อ่านไปหน้าแอดมิน (สำหรับ navbar)
        \Illuminate\Support\Facades\View::composer('admin.layout', function ($view) {
            $unreadReportCount = 0;
            $unseenReportCount = 0;

            if (\Illuminate\Support\Facades\Auth::check() && \Illuminate\Support\Facades\Auth::user()->is_admin) {
                // นับข้อความที่ผู้ใช้ (ไม่ใช่แอดมิน) ส่งมาแล้วยังไม่มีแอดมินคนไหนอ่าน
                $unreadReportCount = \App\Models\ReportMessage::query()
                    ->join('report_chats', 'report_chats.id', '=', 'report_messages.report_chat_id')
                    ->whereColumn('report_messages.user_id', 'report_chats.user_id')
                    ->where('report_messages.is_read', false)
                    ->count();

                // นับรายงานที่ยังไม่มีแอดมินคนไหนเคยเห็นเลย
                $unseenReportCount = \App\Models\Report::whereNull('seen_at')->count();
            }

            $view->with('unreadReportCount', $unreadReportCount);
            $view->with('unseenReportCount', $unseenReportCount);
        });
    }
}
