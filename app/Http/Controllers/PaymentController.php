<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PaymentMethod;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class PaymentController extends Controller
{
    // แสดงหน้าจัดการช่องทางการชำระเงิน
    public function index()
    {
        $methods = Auth::user()->paymentMethods;
        return view('payment.index', compact('methods'));
    }

    // บันทึกช่องทางใหม่
    public function store(Request $request)
    {
        $request->validate([
            'type' => 'required|in:qr,bank',
        ]);

        $data = [
            'user_id' => Auth::id(),
            'type' => $request->type,
        ];

        if ($request->type === 'qr') {
            $request->validate([
                'qr_image' => 'required|image|max:2048',
            ]);
            $data['qr_image'] = $request->file('qr_image')->store('qr_codes', 'public');
        } else {
            $request->validate([
                'bank_name' => 'required|string',
                'account_number' => 'required|string',
                'account_name' => 'required|string',
            ]);
            $data['bank_name'] = $request->bank_name;
            $data['account_number'] = $request->account_number;
            $data['account_name'] = $request->account_name;
        }

        PaymentMethod::create($data);

        return redirect()->route('payment.index')->with('success', 'เพิ่มช่องทางการชำระเงินสำเร็จ!');
    }

    // ลบช่องทาง
    public function destroy(PaymentMethod $paymentMethod)
    {
        // ตรวจสอบสิทธิ์ - ต้องเป็นเจ้าของเท่านั้น
        if ($paymentMethod->user_id !== Auth::id()) {
            abort(403);
        }

        // ลบรูป QR ออกจาก storage ด้วย (ถ้ามี)
        if ($paymentMethod->qr_image) {
            Storage::disk('public')->delete($paymentMethod->qr_image);
        }

        $paymentMethod->delete();

        return redirect()->route('payment.index')->with('success', 'ลบช่องทางการชำระเงินแล้ว');
    }
}