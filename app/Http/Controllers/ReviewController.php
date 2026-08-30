<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Review;
use Illuminate\Support\Facades\Auth;
use App\Models\Order;

class ReviewController extends Controller
{
    // บันทึกรีวิว
    public function store(Request $request, User $shop)
    {
        $reviewerId = Auth::id();

        // 1. ร้านที่ถูกรีวิวต้องเป็นร้านค้าจริง
        if (!$shop->is_shop) {
            return back()->with('error', 'ผู้ใช้นี้ไม่ใช่ร้านค้า');
        }

        // 2. ห้ามรีวิวตัวเอง
        if ($shop->id === $reviewerId) {
            return back()->with('error', 'ไม่สามารถรีวิวร้านตัวเองได้');
        }

        // 3. ตรวจสอบว่าเคยซื้อขายเสร็จสมบูรณ์กับร้านนี้ไหม
        $hasCompletedOrder = Order::where('buyer_id', $reviewerId)
            ->where('seller_id', $shop->id)
            ->where('status', 'completed')
            ->exists();

        if (!$hasCompletedOrder) {
            return back()->with('error', 'คุณต้องซื้อขายกับร้านนี้สำเร็จก่อนถึงจะรีวิวได้');
        }

        // 4. ตรวจสอบข้อมูล
        $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
        ]);

        // 5. บันทึก (ถ้าเคยรีวิวแล้วให้อัปเดตแทน)
        Review::updateOrCreate(
            [
                'reviewer_id' => $reviewerId,
                'shop_id' => $shop->id,
            ],
            [
                'rating' => $request->rating,
                'comment' => $request->comment,
            ]
        );

        return back()->with('success', 'ขอบคุณสำหรับรีวิว!');
    }

    // ลบรีวิวของตัวเอง
    public function destroy(Review $review)
    {
        // ต้องเป็นเจ้าของรีวิวเท่านั้น
        if ($review->reviewer_id !== Auth::id()) {
            abort(403);
        }

        $review->delete();
        return back()->with('success', 'ลบรีวิวแล้ว');
    }
}
