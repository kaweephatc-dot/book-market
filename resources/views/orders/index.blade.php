@extends('layouts.user-dashboard')

@section('title', 'คำสั่งซื้อของฉัน')

@section('content')
@if (session('success'))
    <div class="alert alert-success d-none" data-flash="success">{{ session('success') }}</div>
@endif
@if (session('error'))
    <div class="alert alert-danger d-none" data-flash="error">{{ session('error') }}</div>
@endif

{{-- แท็บสลับ ซื้อ / ขาย --}}
<ul class="nav nav-tabs mb-3" id="orderTab">
    <li class="nav-item">
        <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#buying">🛒 ที่ฉันสั่งซื้อ ({{ $buyingOrders->count() }})</button>
    </li>
    <li class="nav-item">
        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#selling">🏪 ที่ขาย/แลก ({{ $sellingOrders->count() }})</button>
    </li>
</ul>

<div class="tab-content">
    {{-- แท็บที่ฉันสั่งซื้อ --}}
    <div class="tab-pane fade show active" id="buying">
        @forelse ($buyingOrders as $order)
            @include('orders.partials.card', ['order' => $order, 'role' => 'buyer'])
        @empty
            <p class="text-muted text-center py-4">ยังไม่มีคำสั่งซื้อ</p>
        @endforelse
    </div>

    {{-- แท็บที่ขาย/แลก --}}
    <div class="tab-pane fade" id="selling">
        @forelse ($sellingOrders as $order)
            @include('orders.partials.card', ['order' => $order, 'role' => 'seller'])
        @empty
            <p class="text-muted text-center py-4">ยังไม่มีรายการขาย</p>
        @endforelse
    </div>
</div>
@endsection