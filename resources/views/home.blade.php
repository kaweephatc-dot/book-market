@extends('layouts.app')

@section('title', 'หน้าหลัก')

@section('content')

@if (session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

{{-- Hero: แบนเนอร์ต้อนรับ + ค้นหา --}}
<div class="hero-banner rounded-4 p-4 p-md-5 mb-4 text-white">
    <h1 class="h3 h2-md fw-bold mb-2">📚 ตลาดซื้อขายแลกเปลี่ยนหนังสือมือสอง</h1>
    <p class="mb-4 opacity-75">ค้นหาหนังสือที่ใช่ ซื้อ ขาย หรือแลกเปลี่ยนกับร้านค้าทั่วประเทศ</p>

    {{-- แถบค้นหาและกรอง --}}
    <form method="GET" action="{{ route('home') }}" class="row g-2">
        <input type="hidden" name="type" value="{{ request('type') }}">

        <div class="col-md-4">
            <input type="text" name="search" class="form-control form-control-lg" placeholder="ค้นหาชื่อหนังสือ หรือ ชื่อร้าน" value="{{ request('search') }}">
        </div>

        <div class="col-md-3">
            <select name="category" class="form-select form-select-lg">
                <option value="">-- ทุกหมวดหมู่ --</option>
                @foreach (['นิยาย', 'วิชาการ', 'การ์ตูน', 'ตำราเรียน', 'ธุรกิจ', 'จิตวิทยา', 'อื่นๆ'] as $cat)
                    <option value="{{ $cat }}" {{ request('category') === $cat ? 'selected' : '' }}>{{ $cat }}</option>
                @endforeach
            </select>
        </div>

        <div class="col-md-3">
            <select name="sort" class="form-select form-select-lg">
                <option value="">เรียงล่าสุด</option>
                <option value="price_asc" {{ request('sort') === 'price_asc' ? 'selected' : '' }}>ราคาน้อย → มาก</option>
                <option value="price_desc" {{ request('sort') === 'price_desc' ? 'selected' : '' }}>ราคามาก → น้อย</option>
            </select>
        </div>

        <div class="col-md-2">
            <button type="submit" class="btn btn-light btn-lg w-100">🔍 ค้นหา</button>
        </div>
    </form>
</div>

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
    <div id="bookContainer" class="row g-4">
        @foreach ($books as $book)
            <div class="col-6 col-md-4 col-lg-3 book-item">
                <div class="card book-card h-100 border-0 shadow-sm">
                    <div class="position-relative">
                        @if ($book->images->count() > 0)
                            <img src="{{ asset('storage/' . $book->coverImage()->image_path) }}" class="card-img-top" style="height: 210px; object-fit: cover;" alt="{{ $book->title }}">
                        @else
                            <div class="bg-light d-flex align-items-center justify-content-center" style="height: 210px;">
                                <span class="text-muted">ไม่มีรูป</span>
                            </div>
                        @endif

                        @if ($book->type === 'sale')
                            <span class="badge bg-primary position-absolute top-0 end-0 m-2">฿{{ number_format($book->price, 2) }}</span>
                        @else
                            <span class="badge bg-info position-absolute top-0 end-0 m-2">แลกเปลี่ยน</span>
                        @endif
                    </div>
                    <div class="card-body d-flex flex-column">
                        <h6 class="card-title mb-1 text-truncate">{{ $book->title }}</h6>
                        <p class="card-text small text-muted mb-2">{{ $book->category }}</p>

                        @if ($book->coverImage() && $book->coverImage()->ai_condition)
                            @php
                                $cond = $book->coverImage()->ai_condition;
                                $color = match($cond) {
                                    'ดีมาก' => 'success',
                                    'ดี' => 'primary',
                                    'พอใช้' => 'warning',
                                    'ต้องซ่อม' => 'danger',
                                    default => 'secondary',
                                };
                            @endphp
                            <span class="badge bg-{{ $color }}-subtle text-{{ $color }}-emphasis mb-2 align-self-start">🤖 {{ $cond }}</span>
                        @endif

                        <p class="small text-muted mb-3">🏪 {{ $book->user->shop_name ?? $book->user->name }}</p>

                        <a href="{{ route('books.show', $book) }}" class="btn btn-sm btn-outline-primary w-100 mt-auto">ดูรายละเอียด</a>
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

<style>
    .hero-banner {
        background: linear-gradient(135deg, var(--bs-primary), color-mix(in srgb, var(--bs-primary) 70%, #6f42c1));
    }
    .book-card {
        transition: transform .15s ease, box-shadow .15s ease;
    }
    .book-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 .5rem 1.5rem rgba(58, 59, 69, .2) !important;
    }
</style>

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
