@extends('admin.layout')

@section('title', 'Dashboard')

@section('content')
<h3 class="mb-4">📊 ภาพรวมระบบ</h3>

@php
    $cards = [
        'total_users' => ['label' => 'ผู้ใช้ทั้งหมด', 'color' => 'primary', 'icon' => '👥'],
        'total_shops' => ['label' => 'ร้านค้า', 'color' => 'success', 'icon' => '🏪'],
        'banned_users' => ['label' => 'ผู้ใช้ที่ถูกแบน', 'color' => 'danger', 'icon' => '🚫'],
        'total_books' => ['label' => 'หนังสือทั้งหมด', 'color' => 'info', 'icon' => '📚'],
        'books_for_sale' => ['label' => 'หนังสือขาย', 'color' => 'secondary', 'icon' => '💰'],
        'books_for_exchange' => ['label' => 'หนังสือแลกเปลี่ยน', 'color' => 'secondary', 'icon' => '🔄'],
        'total_orders' => ['label' => 'คำสั่งซื้อทั้งหมด', 'color' => 'info', 'icon' => '🧾'],
        'disputed_orders' => ['label' => 'ข้อพิพาทรอตรวจสอบ', 'color' => 'danger', 'icon' => '⚠️'],
        'pending_reports' => ['label' => 'รายงานรอตรวจสอบ', 'color' => 'danger', 'icon' => '🚩'],
    ];

    $groups = [
        ['heading' => '👥 ผู้ใช้', 'keys' => ['total_users', 'total_shops', 'banned_users']],
        ['heading' => '📚 หนังสือ', 'keys' => ['total_books', 'books_for_sale', 'books_for_exchange']],
        ['heading' => '🧾 ออเดอร์', 'keys' => ['total_orders', 'disputed_orders']],
        ['heading' => '🚩 รายงาน', 'keys' => ['pending_reports']],
    ];

    // การ์ดที่ต้อง action จริง (ไม่ใช่แค่สถิติเฉยๆ) — เน้นให้สะดุดตากว่ากลุ่มอื่น
    $needsAttention = ['banned_users', 'disputed_orders', 'pending_reports'];
@endphp

<style>
    .stat-card {
        transition: transform .15s ease, box-shadow .15s ease;
    }
    .stat-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 .5rem 1.5rem rgba(58, 59, 69, .2) !important;
    }
    .stat-card.needs-attention {
        background: linear-gradient(180deg, rgba(231, 74, 59, .06), transparent 60%);
    }
    .stat-group-heading {
        font-size: .8rem;
        font-weight: 700;
        letter-spacing: .04em;
        text-transform: uppercase;
        color: #6b6f8a;
        margin-bottom: .6rem;
    }
</style>

@foreach ($groups as $group)
    <div class="stat-group-heading">{{ $group['heading'] }}</div>
    <div class="row g-3 mb-4">
        @foreach ($group['keys'] as $key)
            @php $card = $cards[$key]; @endphp
            <div class="col-xl-3 col-md-6">
                <div class="card stat-card border-left-{{ $card['color'] }} shadow-sm h-100 py-2 {{ in_array($key, $needsAttention) ? 'needs-attention' : '' }}">
                    <div class="card-body">
                        <div class="row g-0 align-items-center">
                            <div class="col me-2">
                                <div class="text-xs fw-bold text-{{ $card['color'] }} text-uppercase mb-1">
                                    {{ $card['label'] }}
                                </div>
                                <div class="h5 mb-0 fw-bold text-gray-800">{{ $stats[$key] }}</div>
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
@endforeach

{{-- กราฟออเดอร์รายเดือน --}}
<div class="card shadow-sm mb-4">
    <div class="card-header bg-white">
        <strong>📈 ออเดอร์รายเดือน (ย้อนหลัง 12 เดือน)</strong>
    </div>
    <div class="card-body">
        <div style="position: relative; height: 320px;">
            <canvas id="monthlyOrdersChart"></canvas>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
<script>
    const monthlyOrdersLabels = @json($monthlyOrders->pluck('label'));
    const monthlyOrdersCounts = @json($monthlyOrders->pluck('count'));

    new Chart(document.getElementById('monthlyOrdersChart'), {
        type: 'line',
        data: {
            labels: monthlyOrdersLabels,
            datasets: [{
                label: 'จำนวนออเดอร์',
                data: monthlyOrdersCounts,
                borderColor: '#3248f2',
                backgroundColor: 'rgba(50, 72, 242, .1)',
                fill: true,
                tension: .3,
                pointBackgroundColor: '#3248f2',
                pointRadius: 4,
            }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { precision: 0 },
                },
            },
        },
    });
</script>
@endsection
