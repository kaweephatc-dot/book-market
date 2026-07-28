<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Events\ReportMessageSent;
use App\Models\ReportChat;
use App\Models\ReportMessage;
use Illuminate\Support\Facades\Auth;

class ReportChatController extends Controller
{
    // หน้ารวมแชทรายงานของผู้ใช้ (กล่องข้อความจาก admin)
    public function index()
    {
        $chats = ReportChat::with(['report', 'messages'])
            ->where('user_id', Auth::id())
            ->latest('updated_at')
            ->get();

        return view('report-chat.index', compact('chats'));
    }

    // เปิดดูห้องแชท
    public function show(ReportChat $chat)
    {
        // ต้องเป็นเจ้าของแชทเท่านั้น
        if ($chat->user_id !== Auth::id()) {
            abort(403);
        }

        // มาร์คข้อความที่แอดมินส่งมาว่าอ่านแล้ว
        $chat->messages()
            ->where('user_id', '!=', Auth::id())
            ->where('is_read', false)
            ->update(['is_read' => true]);

        $chat->load(['messages.user', 'report']);

        return view('report-chat.show', compact('chat'));
    }

    // ผู้ใช้ตอบข้อความ
    public function send(Request $request, ReportChat $chat)
    {
        if ($chat->user_id !== Auth::id()) {
            abort(403);
        }

        if ($chat->is_closed) {
            return response()->json(['error' => 'แชทนี้ถูกปิดแล้ว ไม่สามารถส่งข้อความได้'], 409);
        }

        $request->validate([
            'message' => 'required|string|max:1000',
        ]);

        $message = ReportMessage::create([
            'report_chat_id' => $chat->id,
            'user_id' => Auth::id(),
            'message' => $request->message,
        ]);

        // อัปเดตเวลาของห้องแชท (ให้ขึ้นไปอยู่บนสุดในรายการ เหมือนแชทซื้อขาย)
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

    // มาร์คข้อความว่าอ่านแล้ว (เรียกจาก JS ตอนยังเปิดหน้าแชทอยู่แล้วมีข้อความใหม่เข้ามา)
    public function markRead(ReportChat $chat)
    {
        if ($chat->user_id !== Auth::id()) {
            abort(403);
        }

        $chat->messages()
            ->where('user_id', '!=', Auth::id())
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return response()->json(['ok' => true]);
    }
}