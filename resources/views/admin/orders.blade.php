@extends('admin.layout')

@section('title', 'จัดการคำสั่งซื้อ')

@section('content')
<h3 class="mb-4">🧾 จัดการคำสั่งซื้อ</h3>

{{-- ออเดอร์ที่มีปัญหา (เด่นสุด) --}}
@if ($disputedOrders->count() > 0)
    <div class="card border-danger mb-4">
        <div class="card-header bg-danger text-white">
            ⚠️ ข้อพิพาทที่รอตรวจสอบ ({{ $disputedOrders->count() }})
        </div>
        <div class="card-body">
            @foreach ($disputedOrders as $order)
                <div class="border rounded p-3 mb-2">
                    <div class="row">
                        <div class="col-md-8">
                            <strong>{{ $order->book->title }}</strong>
                            <div class="small text-muted">
                                ผู้ซื้อ: {{ $order->buyer->name }} |
                                ผู้ขาย: {{ $order->seller->shop_name ?? $order->seller->name }}
                            </div>
                            <div class="alert alert-warning mt-2 mb-2 small">
                                <strong>ปัญหา:</strong> {{ $order->dispute_reason }}
                            </div>
                            @if ($order->slip_image)
                                <a href="{{ asset('storage/' . $order->slip_image) }}" target="_blank">
                                    <img src="{{ asset('storage/' . $order->slip_image) }}" style="max-height: 100px;" class="rounded border" alt="สลิป">
                                </a>
                                <div class="small text-muted">สลิปโอนเงิน (คลิกดูเต็ม)</div>
                            @else
                                <div class="small text-muted">ไม่มีสลิปแนบ</div>
                            @endif
                        </div>
                        <div class="col-md-4">
                            <form method="POST" action="{{ route('admin.orders.resolve', $order) }}">
                                @csrf
                                <p class="small mb-2">ตัดสิน:</p>
                                <button type="submit" name="resolution" value="completed" class="btn btn-sm btn-success w-100 mb-2" data-confirm="ตัดสินว่าซื้อขายสำเร็จ?">✓ ให้ถือว่าสำเร็จ</button>
                                <button type="submit" name="resolution" value="cancelled" class="btn btn-sm btn-danger w-100" data-confirm="ตัดสินให้ยกเลิก?">✗ ยกเลิกออเดอร์</button>
                            </form>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endif

{{-- ออเดอร์ทั้งหมด --}}
<div class="card shadow-sm">
    <div class="card-header">คำสั่งซื้อทั้งหมด</div>
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead>
                <tr>
                    <th>หนังสือ</th>
                    <th>ผู้ซื้อ</th>
                    <th>ผู้ขาย</th>
                    <th>สถานะ</th>
                    <th>วันที่</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($allOrders as $order)
                    @php $info = $order->statusInfo(); @endphp
                    <tr>
                        <td>{{ $order->book->title }}</td>
                        <td>{{ $order->buyer->name }}</td>
                        <td>{{ $order->seller->shop_name ?? $order->seller->name }}</td>
                        <td><span class="badge bg-{{ $info['color'] }}">{{ $info['label'] }}</span></td>
                        <td class="small text-muted">{{ $order->created_at->format('d/m/Y') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3">
    {{ $allOrders->links() }}
</div>
@endsection