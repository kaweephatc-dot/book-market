<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsNotAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        // แอดมินใช้งานระบบซื้อขายไม่ได้
        if (Auth::user()->isAdmin()) {
            return redirect()->route('admin.dashboard')
                ->with('error', 'บัญชีแอดมินไม่สามารถใช้งานระบบซื้อขายได้');
        }

        return $next($request);
    }
}
