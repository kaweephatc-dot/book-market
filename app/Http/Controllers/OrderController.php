<?php

namespace App\Http\Controllers;

use App\Events\OrderNotificationSent;
use Illuminate\Http\Request;
use App\Models\Book;
use App\Models\Order;
use App\Models\OrderNotification;
use Illuminate\Broadcasting\BroadcastException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
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

        $order = Order::create([
            'book_id' => $book->id,
            'buyer_id' => $buyerId,
            'seller_id' => $book->user_id,
            'status' => 'pending',
        ]);

        $this->createNotification($order, $order->seller_id, OrderNotification::ORDER_CREATED);

        return redirect()->route('orders.index')->with('success', 'ส่งคำสั่งซื้อแล้ว รอผู้ขายตอบรับ');
    }

    // หน้าประวัติคำสั่งซื้อ (ทั้งที่ซื้อและขาย)
    public function index(Request $request)
    {
        $userId = Auth::id();
        $role = $request->query('tab', 'buyer');

        if (!in_array($role, ['buyer', 'seller'], true)) {
            $role = 'buyer';
        }

        $this->markNotificationsReadForRole($userId, $role);

        $buyingOrders = Order::with(['book.images', 'seller'])
            ->where('buyer_id', $userId)
            ->latest()
            ->get();

        $sellingOrders = Order::with(['book.images', 'buyer'])
            ->where('seller_id', $userId)
            ->latest()
            ->get();

        return view('orders.index', compact('buyingOrders', 'sellingOrders', 'role'));
    }

    public function markNotificationsRead(Request $request)
    {
        $validated = $request->validate([
            'role' => 'required|in:buyer,seller',
        ]);

        $userId = Auth::id();
        $this->markNotificationsReadForRole($userId, $validated['role']);

        $unreadCount = OrderNotification::where('recipient_id', $userId)
            ->where('is_read', false)
            ->count();

        return response()->json(['unread_count' => $unreadCount]);
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
        $this->createNotification($order, $order->buyer_id, OrderNotification::ORDER_ACCEPTED);
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
        $order->update([
            'slip_image' => $path,
            'status' => 'paid',
        ]);

        $this->createNotification($order, $order->seller_id, OrderNotification::SLIP_UPLOADED);

        return back()->with('success', 'แนบสลิปแล้ว รอผู้ขายยืนยัน');
    }

    // ผู้ขายยืนยันสลิป (ตรวจแล้วว่าโอนจริง)
    public function confirmPayment(Order $order)
    {
        if ($order->seller_id !== Auth::id()) {
            abort(403);
        }

        if ($order->status !== 'paid' || !$order->slip_image) {
            return back()->with('error', 'ยังไม่มีสลิปให้ยืนยัน');
        }

        // ยืนยันสลิปแล้ว แต่ยังไม่เปลี่ยนสถานะ (รอส่งของต่อ)
        $order->update(['seller_confirmed' => true]);
        $this->createNotification($order, $order->buyer_id, OrderNotification::PAYMENT_CONFIRMED);

        return back()->with('success', 'ยืนยันสลิปแล้ว กรุณาส่งของและแนบหลักฐาน');
    }

    // ผู้ขายยืนยันการส่งของ + แนบหลักฐาน
    public function confirmShipping(Request $request, Order $order)
    {
        if ($order->seller_id !== Auth::id()) {
            abort(403);
        }

        // ต้องยืนยันสลิปก่อน (seller_confirmed) ถึงจะส่งได้
        if ($order->status !== 'paid' || !$order->seller_confirmed) {
            return back()->with('error', 'กรุณายืนยันสลิปก่อนส่งของ');
        }

        $request->validate([
            'shipping_proof' => 'required|image|max:2048',
            'tracking_number' => 'nullable|string|max:100',
        ]);

        $path = $request->file('shipping_proof')->store('shipping', 'public');

        $order->update([
            'shipping_proof' => $path,
            'tracking_number' => $request->tracking_number,
            'is_shipped' => true,
            'status' => 'shipping',
        ]);

        $this->createNotification($order, $order->buyer_id, OrderNotification::SHIPPING_CONFIRMED);

        return back()->with('success', 'ยืนยันการส่งของแล้ว รอผู้ซื้อยืนยันรับของ');
    }

    // ผู้ซื้อยืนยันได้รับของแล้ว
    public function confirmReceived(Order $order)
    {
        if ($order->buyer_id !== Auth::id()) {
            abort(403);
        }

        // ต้องอยู่สถานะกำลังจัดส่งก่อน
        if ($order->status !== 'shipping') {
            return back()->with('error', 'ผู้ขายยังไม่ได้ส่งของ');
        }

        $order->update([
            'buyer_confirmed' => true,
            'status' => 'completed',
        ]);

        $this->createNotification($order, $order->seller_id, OrderNotification::RECEIVED_CONFIRMED);

        // มาร์คหนังสือเป็นขายแล้ว
        $order->book->update([
            'status' => $order->book->type === 'sale' ? 'sold' : 'exchanged',
        ]);

        return back()->with('success', 'ยืนยันรับของแล้ว ขอบคุณที่ใช้บริการ!');
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
        $recipientId = $userId === $order->buyer_id ? $order->seller_id : $order->buyer_id;
        $this->createNotification($order, $recipientId, OrderNotification::ORDER_CANCELLED);
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

        $recipientId = $userId === $order->buyer_id ? $order->seller_id : $order->buyer_id;
        $this->createNotification($order, $recipientId, OrderNotification::ORDER_DISPUTED);

        return back()->with('success', 'แจ้งปัญหาแล้ว ผู้ดูแลระบบจะตรวจสอบ');
    }

    private function createNotification(Order $order, int $recipientId, string $type): void
    {
        if ($recipientId === Auth::id()) {
            return;
        }

        // Keep only the latest unread event for this order and recipient.
        $order->notifications()
            ->where('recipient_id', $recipientId)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        $notification = $order->notifications()->create([
            'recipient_id' => $recipientId,
            'type' => $type,
        ]);

        try {
            broadcast(new OrderNotificationSent($notification));
        } catch (BroadcastException $exception) {
            Log::warning('สร้าง order notification แล้ว แต่ส่ง realtime ไม่สำเร็จ', [
                'notification_id' => $notification->id,
                'order_id' => $order->id,
                'exception' => $exception,
            ]);
        }
    }

    private function markNotificationsReadForRole(int $userId, string $role): void
    {
        OrderNotification::where('recipient_id', $userId)
            ->where('is_read', false)
            ->whereHas('order', function ($query) use ($userId, $role) {
                $query->where($role . '_id', $userId);
            })
            ->update(['is_read' => true]);
    }
}