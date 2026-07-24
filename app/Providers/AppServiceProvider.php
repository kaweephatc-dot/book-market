<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;

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

        // แชร์จำนวนข้อความที่ยังไม่อ่านไปทุกหน้า (สำหรับ navbar)
        \Illuminate\Support\Facades\View::composer('layouts.app', function ($view) {
            $unreadCount = 0;
            $unreadReportMessageCount = 0;

            if (\Illuminate\Support\Facades\Auth::check()) {
                $userId = \Illuminate\Support\Facades\Auth::id();

                // นับข้อความที่ยังไม่อ่าน ในห้องแชทที่เราเกี่ยวข้อง และไม่ใช่ข้อความที่เราส่งเอง
                $unreadCount = \App\Models\Message::whereHas('conversation', function ($q) use ($userId) {
                        $q->where('buyer_id', $userId)->orWhere('seller_id', $userId);
                    })
                    ->where('user_id', '!=', $userId)
                    ->where('is_read', false)
                    ->count();

                // นับข้อความในแชทรายงานของเรา ที่ไม่ใช่ข้อความที่เราส่งเอง (คือมาจากแอดมิน) และยังไม่อ่าน
                $unreadReportMessageCount = \App\Models\ReportMessage::query()
                    ->join('report_chats', 'report_chats.id', '=', 'report_messages.report_chat_id')
                    ->where('report_chats.user_id', $userId)
                    ->where('report_messages.user_id', '!=', $userId)
                    ->where('report_messages.is_read', false)
                    ->count();
            }

            $view->with('unreadMessageCount', $unreadCount);
            $view->with('unreadReportMessageCount', $unreadReportMessageCount);
        });

        // แชร์จำนวนข้อความแชทรายงานที่ยังไม่อ่านไปหน้าแอดมิน (สำหรับ navbar)
        \Illuminate\Support\Facades\View::composer('admin.layout', function ($view) {
            $unreadReportCount = 0;

            if (\Illuminate\Support\Facades\Auth::check() && \Illuminate\Support\Facades\Auth::user()->is_admin) {
                // นับข้อความที่ผู้ใช้ (ไม่ใช่แอดมิน) ส่งมาแล้วยังไม่มีแอดมินคนไหนอ่าน
                $unreadReportCount = \App\Models\ReportMessage::query()
                    ->join('report_chats', 'report_chats.id', '=', 'report_messages.report_chat_id')
                    ->whereColumn('report_messages.user_id', 'report_chats.user_id')
                    ->where('report_messages.is_read', false)
                    ->count();
            }

            $view->with('unreadReportCount', $unreadReportCount);
        });
    }
}