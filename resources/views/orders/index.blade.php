@extends('layouts.user-dashboard')

@section('title', 'คำสั่งซื้อของฉัน')

@section('content')

{{-- แท็บสลับ ซื้อ / ขาย --}}
<div id="orderNotifications" data-order-read-url="{{ route('orders.notifications.read') }}" data-active-order-role="{{ $role }}">
<ul class="nav nav-tabs mb-3" id="orderTab">
    <li class="nav-item">
        <button class="nav-link {{ $role === 'buyer' ? 'active' : '' }}" data-order-tab="buyer" data-order-tab-url="{{ route('orders.index', ['tab' => 'buyer']) }}" data-bs-toggle="tab" data-bs-target="#buying">🛒 ที่ฉันสั่งซื้อ ({{ $buyingOrders->count() }})</button>
    </li>
    <li class="nav-item">
        <button class="nav-link {{ $role === 'seller' ? 'active' : '' }}" data-order-tab="seller" data-order-tab-url="{{ route('orders.index', ['tab' => 'seller']) }}" data-bs-toggle="tab" data-bs-target="#selling">🏪 ที่ขาย/แลก ({{ $sellingOrders->count() }})</button>
    </li>
</ul>

<div class="tab-content">
    {{-- แท็บที่ฉันสั่งซื้อ --}}
    <div class="tab-pane fade {{ $role === 'buyer' ? 'show active' : '' }}" id="buying">
        @forelse ($buyingOrders as $order)
            @include('orders.partials.card', ['order' => $order, 'role' => 'buyer'])
        @empty
            <p class="text-muted text-center py-4">ยังไม่มีคำสั่งซื้อ</p>
        @endforelse
    </div>

    {{-- แท็บที่ขาย/แลก --}}
    <div class="tab-pane fade {{ $role === 'seller' ? 'show active' : '' }}" id="selling">
        @forelse ($sellingOrders as $order)
            @include('orders.partials.card', ['order' => $order, 'role' => 'seller'])
        @empty
            <p class="text-muted text-center py-4">ยังไม่มีรายการขาย</p>
        @endforelse
    </div>
</div>
</div>
@endsection