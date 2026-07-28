<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Events\ReportMessageSent;
use App\Models\User;
use App\Models\Book;
use App\Models\Order;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Models\Report;
use App\Models\ReportChat;
use App\Models\ReportMessage;

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
            'pending_reports' => Report::where('status', 'pending')->count(),
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


    // หน้าจัดการรายงาน
    public function reports()
    {
        $pendingReports = Report::with(['reporter', 'reportable'])
            ->where('status', 'pending')
            ->latest()
            ->get();

        $resolvedReports = Report::with(['reporter', 'reportable'])
            ->where('status', '!=', 'pending')
            ->latest()
            ->paginate(15);

        // มาร์คว่าแอดมินเห็นรายงานที่ยังไม่เคยเห็นแล้ว (ทำหลังดึงข้อมูลมา เพื่อให้ badge "ใหม่" ยังขึ้นในรอบนี้)
        Report::whereNull('seen_at')->update(['seen_at' => now()]);

        return view('admin.reports', compact('pendingReports', 'resolvedReports'));
    }

    // รายการแชทกับผู้รายงาน แยกเป็นกำลังคุยอยู่ / ปิดไปแล้ว
    public function reportChats()
    {
        $unreadQuery = function ($q) {
            $q->where('is_read', false)
                ->whereColumn('report_messages.user_id', 'report_chats.user_id');
        };

        $openChats = ReportChat::with(['report', 'user'])
            ->withCount(['messages as unread_count' => $unreadQuery])
            ->where('is_closed', false)
            ->latest('updated_at')
            ->get();

        $closedChats = ReportChat::with(['report', 'user'])
            ->withCount(['messages as unread_count' => $unreadQuery])
            ->where('is_closed', true)
            ->latest('updated_at')
            ->get();

        return view('admin.report-chats', compact('openChats', 'closedChats'));
    }

    // ปิดเรื่อง (ไม่ทำอะไร - รายงานไม่มีมูล)
    public function dismissReport(Report $report)
    {
        $report->update(['status' => 'dismissed']);
        return back()->with('success', 'ปิดเรื่องรายงานแล้ว');
    }

    // จัดการแล้ว (ทำการลบ/แบนไปแล้ว)
    public function resolveReport(Report $report)
    {
        $report->update(['status' => 'resolved']);
        return back()->with('success', 'ทำเครื่องหมายว่าจัดการแล้ว');
    }

    // เปิด (หรือเข้า) ห้องแชทรายงาน กับร้านหรือผู้รายงาน
    public function openReportChat(Report $report, User $user)
    {
        // หาห้องแชทเดิม หรือสร้างใหม่
        $chat = ReportChat::firstOrCreate([
            'report_id' => $report->id,
            'user_id' => $user->id,
        ]);

        // มาร์คข้อความที่ฝ่ายผู้ใช้ (ไม่ใช่แอดมิน) ส่งมาว่าอ่านแล้ว
        $chat->messages()
            ->where('user_id', $chat->user_id)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        $chat->load(['messages.user', 'user', 'report']);

        return view('admin.report-chat', compact('chat'));
    }

    // admin ส่งข้อความในแชทรายงาน
    public function sendReportMessage(Request $request, ReportChat $chat)
    {
        if ($chat->is_closed) {
            return response()->json(['error' => 'แชทนี้ถูกปิดแล้ว'], 409);
        }

        $request->validate([
            'message' => 'required|string|max:1000',
        ]);

        $message = ReportMessage::create([
            'report_chat_id' => $chat->id,
            'user_id' => Auth::id(),
            'message' => $request->message,
        ]);

        // อัปเดตเวลาของห้องแชท (ให้ขึ้นไปอยู่บนสุดในรายการฝั่งผู้ใช้)
        $chat->touch();

        $message->load(['user', 'reportChat']);

        broadcast(new ReportMessageSent($message))->toOthers();

        return response()->json([
            'message' => [
                'id' => $message->id,
                'report_chat_id' => $message->report_chat_id,
                'user_id' => $message->user_id,
                'user_name' => $message->user->name,
                'message' => $message->message,
                'created_at' => $message->created_at->format('H:i'),
                'is_admin_sender' => (bool) $message->user->is_admin,
            ],
        ]);
    }

    // มาร์คข้อความว่าอ่านแล้ว (เรียกจาก JS ตอนแอดมินยังเปิดหน้าแชทอยู่แล้วมีข้อความใหม่เข้ามา)
    public function markReportChatRead(ReportChat $chat)
    {
        $chat->messages()
            ->where('user_id', $chat->user_id)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return response()->json(['ok' => true]);
    }

    // ปิดแชท (อีกฝ่ายส่งข้อความไม่ได้ แต่ยังดูได้)
    public function closeReportChat(ReportChat $chat)
    {
        $chat->update(['is_closed' => true]);
        return back()->with('success', 'ปิดแชทแล้ว');
    }

    // เปิดแชทที่ปิดไปให้กลับมาใช้ได้อีก
    public function reopenReportChat(ReportChat $chat)
    {
        $chat->update(['is_closed' => false]);
        return back()->with('success', 'เปิดแชทอีกครั้งแล้ว');
    }

    // ลบแชททิ้ง
    public function deleteReportChat(ReportChat $chat)
    {
        $chat->delete(); // ข้อความลบตามอัตโนมัติ (cascade)
        return back()->with('success', 'ลบแชทแล้ว');
    }

    // แบนผู้ใช้ (เลือกถาวร/ชั่วคราว)
    public function banUser(Request $request, User $user)
    {
        if ($user->is_admin) {
            return back()->with('error', 'ไม่สามารถแบนผู้ดูแลระบบได้');
        }

        $request->validate([
            'ban_type' => 'required|in:permanent,temporary',
            'days' => 'required_if:ban_type,temporary|nullable|integer|min:1',
        ]);

        $bannedUntil = null;
        if ($request->ban_type === 'temporary') {
            $bannedUntil = now()->addDays((int) $request->days);
        }

        $user->update([
            'is_banned' => true,
            'banned_until' => $bannedUntil,
        ]);

        $msg = $request->ban_type === 'permanent'
            ? 'แบนถาวรแล้ว'
            : "แบนชั่วคราว {$request->days} วันแล้ว";

        return back()->with('success', $msg);
    }

    // ปลดแบน
    public function unbanUser(User $user)
    {
        $user->update([
            'is_banned' => false,
            'banned_until' => null,
        ]);

        return back()->with('success', 'ปลดแบนแล้ว');
    }
}