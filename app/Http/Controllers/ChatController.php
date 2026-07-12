<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Book;
use App\Models\Conversation;
use Illuminate\Support\Facades\Auth;

class ChatController extends Controller
{
    // แสดงรายการแชททั้งหมดของเรา
    public function index()
    {
        $userId = Auth::id();

        // ดึงห้องแชทที่เราเป็นผู้ซื้อหรือผู้ขาย
        $conversations = Conversation::with(['book', 'buyer', 'seller', 'latestMessage'])
            ->withCount(['messages as unread_count' => function ($q) use ($userId) {
                $q->where('user_id', '!=', $userId)->where('is_read', false);
            }])
            ->where(function ($q) use ($userId) {
                $q->where('buyer_id', $userId)->orWhere('seller_id', $userId);
            })
            ->latest('updated_at')
            ->get();

        return view('chat.index', compact('conversations'));
    }

    // เริ่มแชท (จากปุ่มติดต่อผู้ขาย)
    public function start(Book $book)
    {
        $buyerId = Auth::id();

        // ห้ามแชทกับตัวเอง (ถ้าเป็นเจ้าของหนังสือ)
        if ($book->user_id === $buyerId) {
            return redirect()->route('books.show', $book)
                ->with('error', 'คุณไม่สามารถแชทกับหนังสือของตัวเองได้');
        }

        // หาห้องแชทเดิม หรือสร้างใหม่ถ้ายังไม่มี
        $conversation = Conversation::firstOrCreate(
            [
                'book_id' => $book->id,
                'buyer_id' => $buyerId,
            ],
            [
                'seller_id' => $book->user_id,
            ]
        );

        return redirect()->route('chat.show', $conversation);
    }

    // เปิดห้องสนทนา
    public function show(Conversation $conversation)
    {
        $userId = Auth::id();

        // ตรวจสอบสิทธิ์ - ต้องเป็นผู้ซื้อหรือผู้ขายในห้องนี้เท่านั้น
        if ($conversation->buyer_id !== $userId && $conversation->seller_id !== $userId) {
            abort(403, 'คุณไม่มีสิทธิ์เข้าถึงการสนทนานี้');
        }

        // มาร์คข้อความที่คนอื่นส่งมาในห้องนี้ว่าอ่านแล้ว
        $conversation->messages()
            ->where('user_id', '!=', $userId)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        $conversation->load(['book', 'buyer', 'seller.paymentMethods', 'messages.user']);

        // เช็คว่าคนที่กำลังดูเป็นผู้ซื้อไหม (ผู้ซื้อเท่านั้นที่เห็นช่องทางจ่ายเงิน)
        $isBuyer = $conversation->buyer_id === $userId;

        return view('chat.show', compact('conversation', 'isBuyer'));
    }

    // ส่งข้อความ
    public function sendMessage(Request $request, Conversation $conversation)
    {
        $userId = Auth::id();

        // ตรวจสอบสิทธิ์
        if ($conversation->buyer_id !== $userId && $conversation->seller_id !== $userId) {
            abort(403);
        }

        $request->validate([
            'message' => 'required|string|max:1000',
        ]);

        $conversation->messages()->create([
            'user_id' => $userId,
            'message' => $request->message,
        ]);

        // อัปเดตเวลาของห้องแชท (ให้ขึ้นไปอยู่บนสุดในรายการ)
        $conversation->touch();

        return redirect()->route('chat.show', $conversation);
    }
}