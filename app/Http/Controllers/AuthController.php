<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    // แสดงหน้าฟอร์มสมัครสมาชิก
    public function showRegister()
    {
        return view('auth.register');
    }

    // บันทึกข้อมูลสมาชิกใหม่
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6|confirmed',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        Auth::login($user);

        return redirect('/')->with('success', 'สมัครสมาชิกสำเร็จ!');
    }

    // แสดงหน้าฟอร์ม login
    public function showLogin()
    {
        return view('auth.login');
    }

    // ตรวจสอบและเข้าสู่ระบบ
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::attempt($credentials)) {
            // เช็คว่าถูกแบนไหม
            if (Auth::user()->is_banned) {
                Auth::logout();
                return back()->withErrors([
                    'email' => 'บัญชีของคุณถูกระงับการใช้งาน',
                ])->onlyInput('email');
            }

            $request->session()->regenerate();
            return redirect('/')->with('success', 'เข้าสู่ระบบสำเร็จ!');
        }

        return back()->withErrors([
            'email' => 'อีเมลหรือรหัสผ่านไม่ถูกต้อง',
        ])->onlyInput('email');
    }

    // ออกจากระบบ
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}