<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
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
            return back()->with('error', 'แชทนี้ถูกปิดแล้ว ไม่สามารถส่งข้อความได้');
        }

        $request->validate([
            'message' => 'required|string|max:1000',
        ]);

        ReportMessage::create([
            'report_chat_id' => $chat->id,
            'user_id' => Auth::id(),
            'message' => $request->message,
        ]);

        return back();
    }
}