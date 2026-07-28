@extends('admin.layout')

@section('title', 'Dashboard')

@section('content')
<h3 class="mb-4">📊 ภาพรวมระบบ</h3>

@php
    $cards = [
        ['key' => 'total_users', 'label' => 'ผู้ใช้ทั้งหมด', 'color' => 'primary', 'icon' => '👥'],
        ['key' => 'total_shops', 'label' => 'ร้านค้า', 'color' => 'success', 'icon' => '🏪'],
        ['key' => 'total_books', 'label' => 'หนังสือทั้งหมด', 'color' => 'info', 'icon' => '📚'],
        ['key' => 'books_for_sale', 'label' => 'หนังสือขาย', 'color' => 'secondary', 'icon' => '💰'],
        ['key' => 'books_for_exchange', 'label' => 'หนังสือแลกเปลี่ยน', 'color' => 'secondary', 'icon' => '🔄'],
        ['key' => 'banned_users', 'label' => 'ผู้ใช้ที่ถูกแบน', 'color' => 'danger', 'icon' => '🚫'],
        ['key' => 'total_orders', 'label' => 'คำสั่งซื้อทั้งหมด', 'color' => 'info', 'icon' => '🧾'],
        ['key' => 'disputed_orders', 'label' => 'ข้อพิพาทรอตรวจสอบ', 'color' => 'danger', 'icon' => '⚠️'],
        ['key' => 'pending_reports', 'label' => 'รายงานรอตรวจสอบ', 'color' => 'danger', 'icon' => '🚩'],
    ];
@endphp

<div class="row g-3">
    @foreach ($cards as $card)
        <div class="col-xl-3 col-md-6">
            <div class="card border-left-{{ $card['color'] }} shadow h-100 py-2">
                <div class="card-body">
                    <div class="row g-0 align-items-center">
                        <div class="col me-2">
                            <div class="text-xs fw-bold text-{{ $card['color'] }} text-uppercase mb-1">
                                {{ $card['label'] }}
                            </div>
                            <div class="h5 mb-0 fw-bold text-gray-800">{{ $stats[$card['key']] }}</div>
                        </div>
                        <div class="col-auto">
                            <span class="text-gray-300" style="font-size: 2rem;">{{ $card['icon'] }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endforeach
</div>
@endsection
