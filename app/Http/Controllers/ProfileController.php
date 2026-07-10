<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    // แสดงหน้าโปรไฟล์
    public function index()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        return view('profile.index', compact('user'));
    }

    // แสดงฟอร์มสมัครเป็นร้าน
    public function showRegisterShop()
    {
        // ถ้าเป็นร้านอยู่แล้ว พาไปหน้าโปรไฟล์
        if (Auth::user()->is_shop) {
            return redirect()->route('profile.index')->with('info', 'คุณเป็นร้านค้าอยู่แล้ว');
        }

        return view('profile.register-shop');
    }

    // บันทึกการสมัครร้าน
    public function registerShop(Request $request)
    {
        $request->validate([
            'shop_name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'address' => 'required|string|max:500',
        ]);

        /** @var \App\Models\User $user */
        $user = Auth::user();
        $user->update([
            'shop_name' => $request->shop_name,
            'phone' => $request->phone,
            'address' => $request->address,
            'is_shop' => true,
        ]);

        return redirect()->route('books.create')->with('success', 'สมัครเป็นร้านค้าสำเร็จ! ลงขายหนังสือได้เลย');
    }

    // แสดงฟอร์มแก้ไขโปรไฟล์
    public function edit()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        return view('profile.edit', compact('user'));
    }

    // บันทึกการแก้ไขโปรไฟล์
    public function update(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
            'shop_name' => 'nullable|string|max:255',
            'avatar' => 'nullable|image|max:2048',
        ]);

        $data = [
            'name' => $request->name,
            'phone' => $request->phone,
            'address' => $request->address,
            'shop_name' => $request->shop_name,
        ];

        // อัปโหลดรูปโปรไฟล์ใหม่ (ถ้ามี)
        if ($request->hasFile('avatar')) {
            // ลบรูปเก่าออกก่อน (ถ้ามี)
            if ($user->avatar) {
                Storage::disk('public')->delete($user->avatar);
            }
            $data['avatar'] = $request->file('avatar')->store('avatars', 'public');
        }

        $user->update($data);

        return redirect()->route('profile.index')->with('success', 'แก้ไขโปรไฟล์สำเร็จ!');
    }

    // แสดงหน้าโปรไฟล์ร้าน (สาธารณะ)
    public function showShop(\App\Models\User $shop)
    {
        // ต้องเป็นร้านค้าเท่านั้น
        if (!$shop->is_shop) {
            abort(404);
        }

        // โหลดข้อมูล
        $shop->load(['reviews.reviewer', 'books' => function ($q) {
            $q->where('status', 'available')->with('images');
        }]);

        // เช็คว่าคนที่ดูอยู่รีวิวได้ไหม
        $canReview = false;
        $myReview = null;

        if (auth()->check() && auth()->id() !== $shop->id) {
            // เคยซื้อขายเสร็จกับร้านนี้ไหม
            $canReview = \App\Models\Order::where('buyer_id', auth()->id())
                ->where('seller_id', $shop->id)
                ->where('status', 'completed')
                ->exists();

            // เคยรีวิวไปแล้วหรือยัง
            $myReview = \App\Models\Review::where('reviewer_id', auth()->id())
                ->where('shop_id', $shop->id)
                ->first();
        }

        return view('profile.shop', compact('shop', 'canReview', 'myReview'));
    }
}