<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsShop
{
    public function handle(Request $request, Closure $next): Response
    {
        // ถ้ายังไม่เป็นร้าน พาไปหน้าสมัครร้าน
        if (!Auth::user()->is_shop) {
            return redirect()->route('shop.register')
                ->with('info', 'กรุณาสมัครเป็นร้านค้าก่อนลงขายหนังสือ');
        }

        return $next($request);
    }
}