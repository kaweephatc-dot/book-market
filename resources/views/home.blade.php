@extends('layouts.app')

@section('title', 'หน้าหลัก')

@section('content')

@if (session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

{{-- แท็บ ซื้อ / แลกเปลี่ยน --}}
<ul class="nav nav-pills mb-4 justify-content-center">
    <li class="nav-item">
        <a class="nav-link {{ !request('type') ? 'active' : '' }}" href="{{ route('home') }}">ทั้งหมด</a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request('type') === 'sale' ? 'active' : '' }}" href="{{ route('home', ['type' => 'sale']) }}">📗 สำหรับซื้อ</a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request('type') === 'exchange' ? 'active' : '' }}" href="{{ route('home', ['type' => 'exchange']) }}">🔄 สำหรับแลกเปลี่ยน</a>
    </li>
</ul>

{{-- แถบค้นหาและกรอง --}}
<form method="GET" action="{{ route('home') }}" class="row g-2 mb-4">
    <input type="hidden" name="type" value="{{ request('type') }}">

    <div class="col-md-4">
        <input type="text" name="search" class="form-control" placeholder="ค้นหาชื่อหนังสือ หรือ ชื่อร้าน" value="{{ request('search') }}">
    </div>

    <div class="col-md-3">
        <select name="category" class="form-select">
            <option value="">-- ทุกหมวดหมู่ --</option>
            @foreach (['นิยาย', 'วิชาการ', 'การ์ตูน', 'ตำราเรียน', 'ธุรกิจ', 'จิตวิทยา', 'อื่นๆ'] as $cat)
                <option value="{{ $cat }}" {{ request('category') === $cat ? 'selected' : '' }}>{{ $cat }}</option>
            @endforeach
        </select>
    </div>

    <div class="col-md-3">
        <select name="sort" class="form-select">
            <option value="">เรียงล่าสุด</option>
            <option value="price_asc" {{ request('sort') === 'price_asc' ? 'selected' : '' }}>ราคาน้อย → มาก</option>
            <option value="price_desc" {{ request('sort') === 'price_desc' ? 'selected' : '' }}>ราคามาก → น้อย</option>
        </select>
    </div>

    <div class="col-md-2">
        <button type="submit" class="btn btn-primary w-100">ค้นหา</button>
    </div>
</form>

{{-- ปุ่มสลับ Grid / List --}}
<div class="d-flex justify-content-between align-items-center mb-3">
    <span class="text-muted">พบ {{ $books->total() }} รายการ</span>
    <div class="btn-group" role="group">
        <button type="button" class="btn btn-outline-secondary btn-sm active" id="gridBtn" onclick="setView('grid')">
            ⊞ Grid
        </button>
        <button type="button" class="btn btn-outline-secondary btn-sm" id="listBtn" onclick="setView('list')">
            ☰ List
        </button>
    </div>
</div>

{{-- รายการหนังสือ --}}
@if ($books->count() > 0)
    <div id="bookContainer" class="row g-3">
        @foreach ($books as $book)
            <div class="col-6 col-md-4 col-lg-3 book-item">
                <div class="card h-100 shadow-sm">
                    @if ($book->images->count() > 0)
                        <img src="{{ asset('storage/' . $book->images->first()->image_path) }}" class="card-img-top" style="height: 200px; object-fit: cover;" alt="{{ $book->title }}">
                    @else
                        <div class="bg-light d-flex align-items-center justify-content-center" style="height: 200px;">
                            <span class="text-muted">ไม่มีรูป</span>
                        </div>
                    @endif
                    <div class="card-body">
                        <h6 class="card-title">{{ $book->title }}</h6>
                        <p class="card-text small text-muted mb-1">{{ $book->category }}</p>
                        @if ($book->images->first() && $book->images->first()->ai_condition)
                            @php
                                $cond = $book->images->first()->ai_condition;
                                $color = match($cond) {
                                    'ดีมาก' => 'success',
                                    'ดี' => 'primary',
                                    'พอใช้' => 'warning',
                                    'ต้องซ่อม' => 'danger',
                                    default => 'secondary',
                                };
                            @endphp
                            <span class="badge bg-{{ $color }} mb-1">🤖 {{ $cond }}</span>
                        @endif
                        @if ($book->type === 'sale')
                            <p class="fw-bold text-primary mb-1">฿{{ number_format($book->price, 2) }}</p>
                        @else
                            <span class="badge bg-info">แลกเปลี่ยน</span>
                        @endif
                        <p class="small text-muted mb-2">🏪 {{ $book->user->shop_name ?? $book->user->name }}</p>
                        <a href="{{ route('books.show', $book) }}" class="btn btn-sm btn-outline-primary w-100">ดูรายละเอียด</a>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="mt-4">
        {{ $books->links() }}
    </div>
@else
    <div class="text-center py-5">
        <p class="text-muted">ไม่พบหนังสือที่ค้นหา</p>
    </div>
@endif

<script>
    function setView(view) {
        const container = document.getElementById('bookContainer');
        const items = document.querySelectorAll('.book-item');
        const gridBtn = document.getElementById('gridBtn');
        const listBtn = document.getElementById('listBtn');

        if (view === 'list') {
            items.forEach(item => {
                item.classList.remove('col-6', 'col-md-4', 'col-lg-3');
                item.classList.add('col-12');
            });
            listBtn.classList.add('active');
            gridBtn.classList.remove('active');
        } else {
            items.forEach(item => {
                item.classList.remove('col-12');
                item.classList.add('col-6', 'col-md-4', 'col-lg-3');
            });
            gridBtn.classList.add('active');
            listBtn.classList.remove('active');
        }
    }
</script>
@endsection