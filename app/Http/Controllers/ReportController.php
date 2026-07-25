<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Events\ReportCreated;
use App\Models\Book;
use App\Models\User;
use App\Models\Report;
use Illuminate\Support\Facades\Auth;

class ReportController extends Controller
{
    // รายงานหนังสือ
    public function reportBook(Request $request, Book $book)
    {
        return $this->store($request, Book::class, $book->id, $book->user_id);
    }

    // รายงานร้าน
    public function reportShop(Request $request, User $shop)
    {
        if (!$shop->is_shop) {
            return back()->with('error', 'ผู้ใช้นี้ไม่ใช่ร้านค้า');
        }
        return $this->store($request, User::class, $shop->id, $shop->id);
    }

    // บันทึกรายงาน (ใช้ร่วมกัน)
    private function store(Request $request, string $type, int $id, int $ownerId)
    {
        $reporterId = Auth::id();

        // ห้ามรายงานของตัวเอง
        if ($ownerId === $reporterId) {
            return back()->with('error', 'ไม่สามารถรายงานของตัวเองได้');
        }

        $request->validate([
            'reason' => 'required|string',
            'detail' => 'nullable|string|max:1000',
        ]);

        // เช็คว่าเคยรายงานไปแล้วหรือยัง
        $existing = Report::where('reporter_id', $reporterId)
            ->where('reportable_type', $type)
            ->where('reportable_id', $id)
            ->where('status', 'pending')
            ->exists();

        if ($existing) {
            return back()->with('error', 'คุณเคยรายงานสิ่งนี้ไปแล้ว กำลังรอตรวจสอบ');
        }

        $report = Report::create([
            'reporter_id' => $reporterId,
            'reportable_type' => $type,
            'reportable_id' => $id,
            'reason' => $request->reason,
            'detail' => $request->detail,
        ]);

        $report->load('reporter');

        broadcast(new ReportCreated($report));

        return back()->with('success', 'ส่งรายงานแล้ว ขอบคุณที่ช่วยดูแลชุมชน');
    }
}