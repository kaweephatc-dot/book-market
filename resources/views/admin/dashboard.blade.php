@extends('admin.layout')

@section('title', 'Dashboard')

@section('content')
<h3 class="mb-4">📊 ภาพรวมระบบ</h3>

<div class="row g-3">
    <div class="col-md-4">
        <div class="card text-center shadow-sm">
            <div class="card-body">
                <h2 class="text-primary">{{ $stats['total_users'] }}</h2>
                <p class="text-muted mb-0">ผู้ใช้ทั้งหมด</p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-center shadow-sm">
            <div class="card-body">
                <h2 class="text-success">{{ $stats['total_shops'] }}</h2>
                <p class="text-muted mb-0">ร้านค้า</p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-center shadow-sm">
            <div class="card-body">
                <h2 class="text-info">{{ $stats['total_books'] }}</h2>
                <p class="text-muted mb-0">หนังสือทั้งหมด</p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-center shadow-sm">
            <div class="card-body">
                <h2 class="text-secondary">{{ $stats['books_for_sale'] }}</h2>
                <p class="text-muted mb-0">หนังสือขาย</p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-center shadow-sm">
            <div class="card-body">
                <h2 class="text-secondary">{{ $stats['books_for_exchange'] }}</h2>
                <p class="text-muted mb-0">หนังสือแลกเปลี่ยน</p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-center shadow-sm">
            <div class="card-body">
                <h2 class="text-danger">{{ $stats['banned_users'] }}</h2>
                <p class="text-muted mb-0">ผู้ใช้ที่ถูกแบน</p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-center shadow-sm">
            <div class="card-body">
                <h2 class="text-info">{{ $stats['total_orders'] }}</h2>
                <p class="text-muted mb-0">คำสั่งซื้อทั้งหมด</p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-center shadow-sm">
            <div class="card-body">
                <h2 class="text-danger">{{ $stats['disputed_orders'] }}</h2>
                <p class="text-muted mb-0">ข้อพิพาทรอตรวจสอบ</p>
            </div>
        </div>
    </div>
</div>
@endsection