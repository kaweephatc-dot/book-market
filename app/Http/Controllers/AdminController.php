<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Book;
use App\Models\Order;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class AdminController extends Controller
{
    // หน้า Dashboard - สถิติภาพรวม
    public function dashboard()
    {
        $stats = [
            'total_users' => User::where('is_admin', false)->count(),
            'total_shops' => User::where('is_shop', true)->count(),
            'total_books' => Book::count(),
            'books_for_sale' => Book::where('type', 'sale')->count(),
            'books_for_exchange' => Book::where('type', 'exchange')->count(),
            'banned_users' => User::where('is_banned', true)->count(),
            'total_orders' => Order::count(),
            'disputed_orders' => Order::where('status', 'disputed')->count(),
        ];

        return view('admin.dashboard', compact('stats'));
    }

    // หน้าจัดการผู้ใช้
    public function users()
    {
        $users = User::where('is_admin', false)
            ->latest()
            ->paginate(15);

        return view('admin.users', compact('users'));
    }

    // แบน / ปลดแบน ผู้ใช้
    public function toggleBan(User $user)
    {
        // ห้ามแบน admin
        if ($user->is_admin) {
            return back()->with('error', 'ไม่สามารถแบนผู้ดูแลระบบได้');
        }

        $user->update(['is_banned' => !$user->is_banned]);

        $message = $user->is_banned ? 'แบนผู้ใช้แล้ว' : 'ปลดแบนผู้ใช้แล้ว';
        return back()->with('success', $message);
    }

    // ลบผู้ใช้
    public function deleteUser(User $user)
    {
        // ห้ามลบ admin
        if ($user->is_admin) {
            return back()->with('error', 'ไม่สามารถลบผู้ดูแลระบบได้');
        }

        $user->delete();
        return back()->with('success', 'ลบผู้ใช้แล้ว');
    }

    // หน้าจัดการหนังสือ
    public function books()
    {
        $books = Book::with(['user', 'images'])
            ->latest()
            ->paginate(15);

        return view('admin.books', compact('books'));
    }

    // ลบหนังสือ
    public function deleteBook(Book $book)
    {
        // ลบรูปออกจาก storage ด้วย
        foreach ($book->images as $image) {
            Storage::disk('public')->delete($image->image_path);
        }

        $book->delete();
        return back()->with('success', 'ลบหนังสือแล้ว');
    }

    // หน้าจัดการคำสั่งซื้อ (เน้นที่มีปัญหา)
    public function orders()
    {
        // ออเดอร์ที่มีปัญหาขึ้นก่อน
        $disputedOrders = Order::with(['book', 'buyer', 'seller'])
            ->where('status', 'disputed')
            ->latest()
            ->get();

        // ออเดอร์ทั้งหมด
        $allOrders = Order::with(['book', 'buyer', 'seller'])
            ->where('status', '!=', 'disputed')
            ->latest()
            ->paginate(15);

        return view('admin.orders', compact('disputedOrders', 'allOrders'));
    }

    // แก้ไขข้อพิพาท - ตัดสินให้จบ
    public function resolveDispute(Request $request, Order $order)
    {
        $request->validate([
            'resolution' => 'required|in:completed,cancelled',
        ]);

        $order->update(['status' => $request->resolution]);

        // ถ้าตัดสินให้เสร็จสิ้น มาร์คหนังสือเป็นขายแล้ว
        if ($request->resolution === 'completed') {
            $order->book->update([
                'status' => $order->book->type === 'sale' ? 'sold' : 'exchanged',
            ]);
        }

        return back()->with('success', 'จัดการข้อพิพาทเรียบร้อยแล้ว');
    }
}