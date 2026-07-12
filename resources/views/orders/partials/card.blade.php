@php $info = $order->statusInfo(); @endphp
<div class="card mb-3 shadow-sm">
    <div class="card-body">
        <div class="d-flex gap-3">
            {{-- รูปหนังสือ --}}
            @if ($order->book->images->count() > 0)
                <img src="{{ asset('storage/' . $order->book->images->first()->image_path) }}" style="width: 70px; height: 70px; object-fit: cover;" class="rounded" alt="">
            @else
                <div class="bg-light rounded d-flex align-items-center justify-content-center" style="width: 70px; height: 70px;">📚</div>
            @endif

            <div class="flex-grow-1">
                <div class="d-flex justify-content-between">
                    <strong>{{ $order->book->title }}</strong>
                    <span class="badge bg-{{ $info['color'] }}">{{ $info['label'] }}</span>
                </div>

                {{-- คู่ค้า --}}
                @if ($role === 'buyer')
                    <div class="small text-muted">ผู้ขาย: {{ $order->seller->shop_name ?? $order->seller->name }}</div>
                @else
                    <div class="small text-muted">ผู้ซื้อ: {{ $order->buyer->name }}</div>
                @endif

                @if ($order->book->type === 'sale')
                    <div class="text-primary fw-bold">฿{{ number_format($order->book->price, 2) }}</div>
                @else
                    <span class="badge bg-info">แลกเปลี่ยน</span>
                @endif

                {{-- แสดงสลิป (ถ้ามี) --}}
                @if ($order->slip_image)
                    <div class="mt-2">
                        <a href="{{ asset('storage/' . $order->slip_image) }}" target="_blank">
                            <img src="{{ asset('storage/' . $order->slip_image) }}" style="max-height: 80px;" class="rounded border" alt="สลิป">
                        </a>
                        <div class="small text-muted">สลิปโอนเงิน (คลิกเพื่อดูเต็ม)</div>
                    </div>
                @endif
                {{-- แสดงหลักฐานการส่งของ (ถ้ามี) --}}
                @if ($order->shipping_proof)
                    <div class="mt-2">
                        <a href="{{ asset('storage/' . $order->shipping_proof) }}" target="_blank">
                            <img src="{{ asset('storage/' . $order->shipping_proof) }}" style="max-height: 80px;" class="rounded border" alt="หลักฐานการส่ง">
                        </a>
                        <div class="small text-muted">หลักฐานการส่งของ (คลิกดูเต็ม)</div>
                        @if ($order->tracking_number)
                            <div class="small"><strong>เลขพัสดุ:</strong> {{ $order->tracking_number }}</div>
                        @endif
                    </div>
                @endif

                {{-- ปุ่มจัดการ ตามสถานะและบทบาท --}}
                <div class="mt-2 d-flex gap-1 flex-wrap">

                    {{-- ผู้ขาย: รับออเดอร์ --}}
                    @if ($role === 'seller' && $order->status === 'pending')
                        <form method="POST" action="{{ route('orders.accept', $order) }}">
                            @csrf
                            <button class="btn btn-sm btn-success">รับออเดอร์</button>
                        </form>
                    @endif

                    {{-- ผู้ซื้อ: แนบสลิป --}}
                    @if ($role === 'buyer' && $order->status === 'accepted')
                        <form method="POST" action="{{ route('orders.slip', $order) }}" enctype="multipart/form-data" class="d-flex gap-1">
                            @csrf
                            <input type="file" name="slip" class="form-control form-control-sm" accept="image/*" required style="max-width: 200px;">
                            <button class="btn btn-sm btn-primary">แนบสลิป</button>
                        </form>
                    @endif

                    {{-- ผู้ขาย: ยืนยันสลิป --}}
                    @if ($role === 'seller' && $order->status === 'paid' && $order->slip_image && !$order->seller_confirmed)
                        <form method="POST" action="{{ route('orders.confirmPayment', $order) }}">
                            @csrf
                            <button class="btn btn-sm btn-primary">🧾 ยืนยันสลิป</button>
                        </form>
                    @endif

                    {{-- ผู้ขาย: ยืนยันสลิปแล้ว → ส่งของ --}}
                    @if ($role === 'seller' && $order->status === 'paid' && $order->seller_confirmed && !$order->is_shipped)
                        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#shipModal{{ $order->id }}">📦 ยืนยันการส่งของ</button>
                    @endif

                    {{-- ผู้ซื้อ: รอผู้ขายดำเนินการ (ตอน paid) --}}
                    @if ($role === 'buyer' && $order->status === 'paid')
                        @if (!$order->seller_confirmed)
                            <span class="badge bg-warning">รอผู้ขายยืนยันสลิป</span>
                        @else
                            <span class="badge bg-info">รอผู้ขายส่งของ</span>
                        @endif
                    @endif

                    {{-- สถานะกำลังจัดส่ง --}}
                    @if ($order->status === 'shipping')
                        @if ($role === 'seller')
                            <span class="badge bg-info">ส่งของแล้ว รอผู้ซื้อยืนยันรับ</span>
                        @else
                            <form method="POST" action="{{ route('orders.received', $order) }}" onsubmit="return confirm('ยืนยันว่าได้รับหนังสือเรียบร้อยแล้ว?')">
                                @csrf
                                <button class="btn btn-sm btn-success">📬 ยืนยันได้รับของแล้ว</button>
                            </form>
                        @endif
                    @endif

                    {{-- ยกเลิก (ก่อนเสร็จ) --}}
                    @if (in_array($order->status, ['pending', 'accepted', 'paid', 'shipping']))
                        <form method="POST" action="{{ route('orders.cancel', $order) }}" onsubmit="return confirm('ยกเลิกออเดอร์นี้?')">
                            @csrf
                            <button class="btn btn-sm btn-outline-danger">ยกเลิก</button>
                        </form>
                    @endif

                    {{-- แจ้งปัญหา (ตอน paid หรือ shipping) --}}
                    @if (in_array($order->status, ['paid', 'shipping']))
                        <button class="btn btn-sm btn-outline-warning" data-bs-toggle="modal" data-bs-target="#disputeModal{{ $order->id }}">แจ้งปัญหา</button>
                    @endif

                    {{-- เสร็จแล้ว: ปุ่มรีวิว (เฉพาะผู้ซื้อ) --}}
                    @if ($order->status === 'completed' && $role === 'buyer')
                        <a href="{{ route('shop.show', $order->seller) }}" class="btn btn-sm btn-warning">⭐ รีวิวร้าน</a>
                    @endif
                </div>

                @if ($order->status === 'disputed')
                    <div class="alert alert-danger mt-2 mb-0 small">
                        <strong>ปัญหา:</strong> {{ $order->dispute_reason }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- Modal แจ้งปัญหา --}}
@if (in_array($order->status, ['paid', 'shipping']))
<div class="modal fade" id="disputeModal{{ $order->id }}" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('orders.dispute', $order) }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">แจ้งปัญหา</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="small text-muted">อธิบายปัญหาที่พบ ผู้ดูแลระบบจะตรวจสอบ</p>
                    <textarea name="reason" class="form-control" rows="3" placeholder="เช่น โอนเงินแล้วแต่ไม่ได้รับหนังสือ" required></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ปิด</button>
                    <button type="submit" class="btn btn-danger">ส่งแจ้งปัญหา</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

{{-- Modal ยืนยันการส่งของ (ผู้ขาย) --}}
@if ($order->status === 'paid' && $role === 'seller' && $order->seller_confirmed && !$order->is_shipped)
<div class="modal fade" id="shipModal{{ $order->id }}" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('orders.shipping', $order) }}" enctype="multipart/form-data">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">ยืนยันการส่งของ</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">หลักฐานการส่ง <span class="text-danger">*</span></label>
                        <input type="file" name="shipping_proof" class="form-control" accept="image/*" required>
                        <small class="text-muted">รูปพัสดุ หรือ ใบเสร็จส่งของ</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">เลขพัสดุ (ถ้ามี)</label>
                        <input type="text" name="tracking_number" class="form-control" placeholder="เช่น TH1234567890">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ปิด</button>
                    <button type="submit" class="btn btn-primary">ยืนยันการส่ง</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif