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

            if (\Illuminate\Support\Facades\Auth::check()) {
                $userId = \Illuminate\Support\Facades\Auth::id();

                // นับข้อความที่ยังไม่อ่าน ในห้องแชทที่เราเกี่ยวข้อง และไม่ใช่ข้อความที่เราส่งเอง
                $unreadCount = \App\Models\Message::whereHas('conversation', function ($q) use ($userId) {
                        $q->where('buyer_id', $userId)->orWhere('seller_id', $userId);
                    })
                    ->where('user_id', '!=', $userId)
                    ->where('is_read', false)
                    ->count();
            }

            $view->with('unreadMessageCount', $unreadCount);
        });
    }
}