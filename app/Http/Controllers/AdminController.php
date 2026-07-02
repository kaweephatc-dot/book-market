<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Book;
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
}