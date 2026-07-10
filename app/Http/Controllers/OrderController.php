<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Book;
use App\Models\Order;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class OrderController extends Controller
{
    // ผู้ซื้อกดสั่งซื้อ
    public function store(Book $book)
    {
        $buyerId = Auth::id();

        // ห้ามสั่งซื้อหนังสือตัวเอง
        if ($book->user_id === $buyerId) {
            return back()->with('error', 'ไม่สามารถสั่งซื้อหนังสือของตัวเองได้');
        }

        // หนังสือต้องยังว่างอยู่
        if ($book->status !== 'available') {
            return back()->with('error', 'หนังสือเล่มนี้ไม่พร้อมขายแล้ว');
        }

        // เช็คว่ามีออเดอร์ที่ยังไม่จบอยู่แล้วไหม
        $existing = Order::where('book_id', $book->id)
            ->where('buyer_id', $buyerId)
            ->whereNotIn('status', ['cancelled', 'completed'])
            ->exists();

        if ($existing) {
            return back()->with('error', 'คุณมีคำสั่งซื้อหนังสือเล่มนี้อยู่แล้ว');
        }

        Order::create([
            'book_id' => $book->id,
            'buyer_id' => $buyerId,
            'seller_id' => $book->user_id,
            'status' => 'pending',
        ]);

        return redirect()->route('orders.index')->with('success', 'ส่งคำสั่งซื้อแล้ว รอผู้ขายตอบรับ');
    }

    // หน้าประวัติคำสั่งซื้อ (ทั้งที่ซื้อและขาย)
    public function index()
    {
        $userId = Auth::id();

        $buyingOrders = Order::with(['book.images', 'seller'])
            ->where('buyer_id', $userId)
            ->latest()
            ->get();

        $sellingOrders = Order::with(['book.images', 'buyer'])
            ->where('seller_id', $userId)
            ->latest()
            ->get();

        return view('orders.index', compact('buyingOrders', 'sellingOrders'));
    }

    // ผู้ขายกดรับออเดอร์
    public function accept(Order $order)
    {
        if ($order->seller_id !== Auth::id()) {
            abort(403);
        }

        if ($order->status !== 'pending') {
            return back()->with('error', 'ออเดอร์นี้ดำเนินการไปแล้ว');
        }

        $order->update(['status' => 'accepted']);
        return back()->with('success', 'รับออเดอร์แล้ว รอผู้ซื้อโอนเงิน');
    }

    // ผู้ซื้อแนบสลิปโอนเงิน
    public function uploadSlip(Request $request, Order $order)
    {
        if ($order->buyer_id !== Auth::id()) {
            abort(403);
        }

        $request->validate([
            'slip' => 'required|image|max:2048',
        ]);

        // ลบสลิปเก่า (ถ้ามี)
        if ($order->slip_image) {
            Storage::disk('public')->delete($order->slip_image);
        }

        $path = $request->file('slip')->store('slips', 'public');
        $order->update(['slip_image' => $path]);

        return back()->with('success', 'แนบสลิปแล้ว รอผู้ขายยืนยัน');
    }

    // ผู้ขายยืนยันรับเงิน
    public function confirmPayment(Order $order)
    {
        if ($order->seller_id !== Auth::id()) {
            abort(403);
        }

        $order->update(['status' => 'paid']);
        return back()->with('success', 'ยืนยันรับเงินแล้ว');
    }

    // ยืนยันว่าซื้อขายเสร็จสมบูรณ์ (ทั้งสองฝ่ายต้องกด)
    public function confirmComplete(Order $order)
    {
        $userId = Auth::id();

        if ($order->buyer_id === $userId) {
            $order->buyer_confirmed = true;
        } elseif ($order->seller_id === $userId) {
            $order->seller_confirmed = true;
        } else {
            abort(403);
        }

        // ถ้าทั้งสองฝ่ายยืนยันแล้ว → เสร็จสิ้น
        if ($order->buyer_confirmed && $order->seller_confirmed) {
            $order->status = 'completed';
            // เปลี่ยนสถานะหนังสือเป็นขายแล้ว
            $order->book->update([
                'status' => $order->book->type === 'sale' ? 'sold' : 'exchanged',
            ]);
        }

        $order->save();
        return back()->with('success', 'ยืนยันแล้ว');
    }

    // ยกเลิกออเดอร์
    public function cancel(Order $order)
    {
        $userId = Auth::id();

        // ผู้ซื้อหรือผู้ขายยกเลิกได้ (ก่อนเสร็จสิ้น)
        if ($order->buyer_id !== $userId && $order->seller_id !== $userId) {
            abort(403);
        }

        if (in_array($order->status, ['completed', 'cancelled'])) {
            return back()->with('error', 'ออเดอร์นี้จบแล้ว ยกเลิกไม่ได้');
        }

        $order->update(['status' => 'cancelled']);
        return back()->with('success', 'ยกเลิกออเดอร์แล้ว');
    }

    // แจ้งปัญหา (ข้อพิพาท)
    public function dispute(Request $request, Order $order)
    {
        $userId = Auth::id();

        if ($order->buyer_id !== $userId && $order->seller_id !== $userId) {
            abort(403);
        }

        $request->validate([
            'reason' => 'required|string|max:1000',
        ]);

        $order->update([
            'status' => 'disputed',
            'dispute_reason' => $request->reason,
        ]);

        return back()->with('success', 'แจ้งปัญหาแล้ว ผู้ดูแลระบบจะตรวจสอบ');
    }
}