<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    // แสดงหน้าโปรไฟล์ (แท็บข้อมูลส่วนตัว + ข้อมูลร้านค้า)
    public function index()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        // สรุปสถิติร้านไว้โชว์บนแท็บร้านค้า
        $shopStats = null;
        if ($user->is_shop) {
            $shopStats = [
                'available' => $user->books()->where('status', 'available')->count(),
                'sold' => $user->books()->whereIn('status', ['sold', 'exchanged'])->count(),
                'rating' => $user->averageRating(),
                'reviews' => $user->reviewCount(),
            ];
        }

        return view('profile.index', compact('user', 'shopStats'));
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
        // เขียนลงทั้งช่องร้านและช่องส่วนตัว: ฟอร์มสมัครถามข้อมูลติดต่อของร้าน
        // แต่ผู้ใช้เดิมอาจยังไม่เคยกรอกโปรไฟล์ เลยเติมให้ครบทีเดียว
        // หลังจากนี้แก้ข้อมูลร้านที่แท็บร้านค้าจะไม่กระทบข้อมูลส่วนตัวอีก
        $user->update([
            'shop_name' => $request->shop_name,
            'shop_phone' => $request->phone,
            'shop_address' => $request->address,
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

        // shop_name ย้ายไปแก้ที่แท็บ "ข้อมูลร้านค้า" แล้ว ไม่รับจากฟอร์มนี้อีก
        // (ถ้ายังรับอยู่ ฟอร์มที่ไม่มีช่องนี้จะส่งค่าว่างมาล้างชื่อร้านทิ้ง)
        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
            'avatar' => 'nullable|image|max:2048',
        ]);

        $data = [
            'name' => $request->name,
            'phone' => $request->phone,
            'address' => $request->address,
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

    // บันทึกการแก้ไขข้อมูลร้านค้าของตัวเอง
    public function updateShop(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $request->validate([
            'shop_name' => 'required|string|max:255',
            'shop_description' => 'nullable|string|max:1000',
            'shop_phone' => 'nullable|string|max:20',
            'shop_address' => 'nullable|string|max:500',
            'shop_logo' => 'nullable|image|max:2048',
        ]);

        $data = [
            'shop_name' => $request->shop_name,
            'shop_description' => $request->shop_description,
            'shop_phone' => $request->shop_phone,
            'shop_address' => $request->shop_address,
        ];

        // อัปโหลดโลโก้ร้านใหม่ (ถ้ามี)
        if ($request->hasFile('shop_logo')) {
            if ($user->shop_logo) {
                Storage::disk('public')->delete($user->shop_logo);
            }
            $data['shop_logo'] = $request->file('shop_logo')->store('shops', 'public');
        }

        $user->update($data);

        return redirect()->route('profile.index', ['tab' => 'shop'])
            ->with('success', 'บันทึกข้อมูลร้านค้าแล้ว');
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