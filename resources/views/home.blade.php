@extends('layouts.user-dashboard')

@section('title', 'หน้าหลัก')

@section('content')

@if (session('success'))
    <div class="alert alert-success d-none" data-flash="success">{{ session('success') }}</div>
@endif

@php
    // สรุปตัวกรองที่กำลังใช้อยู่ เพื่อแสดงเป็นชิป และนับจำนวนบนปุ่ม "ตัวกรอง" ของจอมือถือ
    $sortLabels = ['price_asc' => 'ราคาน้อย → มาก', 'price_desc' => 'ราคามาก → น้อย'];
    $activeFilters = [];

    if (request('search')) {
        $activeFilters[] = ['key' => 'search', 'label' => '“' . request('search') . '”'];
    }
    if (request('category')) {
        $activeFilters[] = ['key' => 'category', 'label' => request('category')];
    }
    if (request('sort') && isset($sortLabels[request('sort')])) {
        $activeFilters[] = ['key' => 'sort', 'label' => $sortLabels[request('sort')]];
    }

    // ล้างทั้งหมดแล้วยังคงแท็บ ซื้อ/แลกเปลี่ยน เดิมไว้
    $resetUrl = route('home', request('type') ? ['type' => request('type')] : []);
    $queryWithoutType = collect(request()->query())->except(['type', 'page'])->all();
@endphp

{{-- Hero: แบนเนอร์ต้อนรับ --}}
<div class="hero-banner rounded-4 px-4 pt-4 pb-5 px-md-5 pt-md-5 text-white">
    <h1 class="h3 fw-bold mb-2">📚 ตลาดซื้อขายแลกเปลี่ยนหนังสือมือสอง</h1>
    <p class="mb-0 opacity-75">ค้นหาหนังสือที่ใช่ ซื้อ ขาย หรือแลกเปลี่ยนกับร้านค้าทั่วประเทศ</p>
</div>

{{-- การ์ดค้นหา: ลอยทับขอบล่างของ hero --}}
<form method="GET" action="{{ route('home') }}" class="search-panel">
    <input type="hidden" name="type" value="{{ request('type') }}">

    <div class="search-bar">
        <div class="search-field">
            <span class="search-icon">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                    <circle cx="11" cy="11" r="7"></circle><path d="m20 20-3.5-3.5"></path>
                </svg>
            </span>
            <input type="text" id="searchInput" name="search" class="form-control search-input"
                   placeholder="ค้นหาชื่อหนังสือ หรือ ชื่อร้าน" value="{{ request('search') }}" autocomplete="off">
            <button type="button" class="search-clear" id="clearSearch" aria-label="ล้างคำค้นหา" hidden>&times;</button>
        </div>

        <button type="submit" class="btn btn-primary btn-search">ค้นหา</button>
    </div>

    {{-- จอมือถือ: ซ่อนตัวกรองไว้ก่อน กดเปิดเมื่อต้องการ --}}
    <button class="btn btn-filter-toggle d-md-none w-100 mt-2" type="button"
            data-bs-toggle="collapse" data-bs-target="#searchFilters">
        ตัวกรอง
        @if (count($activeFilters))
            <span class="filter-count">{{ count($activeFilters) }}</span>
        @endif
    </button>

    <div class="collapse d-md-block {{ count($activeFilters) ? 'show' : '' }}" id="searchFilters">
        <div class="row g-3 search-filters">
            <div class="col-12 col-md-6">
                <label class="filter-label" for="filterCategory">หมวดหมู่</label>
                <select id="filterCategory" name="category" class="form-select filter-select" onchange="this.form.submit()">
                    <option value="">ทุกหมวดหมู่</option>
                    @foreach (['นิยาย', 'วิชาการ', 'การ์ตูน', 'ตำราเรียน', 'ธุรกิจ', 'จิตวิทยา', 'อื่นๆ'] as $cat)
                        <option value="{{ $cat }}" {{ request('category') === $cat ? 'selected' : '' }}>{{ $cat }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-12 col-md-6">
                <label class="filter-label" for="filterSort">เรียงลำดับ</label>
                <select id="filterSort" name="sort" class="form-select filter-select" onchange="this.form.submit()">
                    <option value="">ล่าสุดก่อน</option>
                    @foreach ($sortLabels as $value => $label)
                        <option value="{{ $value }}" {{ request('sort') === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    @if (count($activeFilters))
        <div class="active-filters">
            <span class="active-filters-title">กำลังกรอง</span>
            @foreach ($activeFilters as $filter)
                <a class="filter-chip"
                   href="{{ route('home', collect(request()->query())->except([$filter['key'], 'page'])->all()) }}">
                    {{ $filter['label'] }}
                    <span class="filter-chip-x">&times;</span>
                </a>
            @endforeach
            <a class="filter-reset" href="{{ $resetUrl }}">ล้างทั้งหมด</a>
        </div>
    @endif
</form>

{{-- แถบเครื่องมือ: แท็บประเภท + จำนวนผลลัพธ์ + สลับมุมมอง --}}
<div class="results-toolbar mb-3">
    <div class="type-tabs">
        <a class="type-tab {{ !request('type') ? 'active' : '' }}"
           href="{{ route('home', $queryWithoutType) }}">ทั้งหมด</a>
        <a class="type-tab {{ request('type') === 'sale' ? 'active' : '' }}"
           href="{{ route('home', array_merge($queryWithoutType, ['type' => 'sale'])) }}">📗 สำหรับซื้อ</a>
        <a class="type-tab {{ request('type') === 'exchange' ? 'active' : '' }}"
           href="{{ route('home', array_merge($queryWithoutType, ['type' => 'exchange'])) }}">🔄 แลกเปลี่ยน</a>
    </div>

    <div class="toolbar-right">
        <span class="results-count">พบ <strong>{{ number_format($books->total()) }}</strong> รายการ</span>
        <div class="view-switch" role="group" aria-label="สลับมุมมอง">
            <button type="button" class="view-btn active" id="gridBtn" onclick="setView('grid')" aria-label="มุมมองตาราง" title="ตาราง">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round">
                    <rect x="3" y="3" width="7" height="7" rx="1"></rect><rect x="14" y="3" width="7" height="7" rx="1"></rect>
                    <rect x="3" y="14" width="7" height="7" rx="1"></rect><rect x="14" y="14" width="7" height="7" rx="1"></rect>
                </svg>
            </button>
            <button type="button" class="view-btn" id="listBtn" onclick="setView('list')" aria-label="มุมมองรายการ" title="รายการ">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                    <path d="M4 6h16M4 12h16M4 18h16"></path>
                </svg>
            </button>
        </div>
    </div>
</div>

{{-- รายการหนังสือ --}}
@if ($books->count() > 0)
    <div id="bookContainer" class="book-grid">
        @foreach ($books as $book)
            @php
                $cover = $book->coverImage();
                $condition = $cover->ai_condition ?? null;
                $conditionColor = match ($condition) {
                    'ดีมาก' => 'success',
                    'ดี' => 'primary',
                    'พอใช้' => 'warning',
                    'ต้องซ่อม' => 'danger',
                    default => 'secondary',
                };
            @endphp

            {{-- ทั้งการ์ดเป็นลิงก์เดียว ไม่ต้องมีปุ่ม "ดูรายละเอียด" แยก ประหยัดพื้นที่บนมือถือ --}}
            <a href="{{ route('books.show', $book) }}" class="book-card">
                <div class="book-thumb">
                    @if ($book->images->count() > 0)
                        <img src="{{ asset('storage/' . $cover->image_path) }}" alt="{{ $book->title }}" loading="lazy">
                    @else
                        <div class="book-thumb-empty">📕</div>
                    @endif

                    @if ($book->type === 'exchange')
                        <span class="book-tag book-tag-exchange">🔄 แลกเปลี่ยน</span>
                    @endif

                    @if ($condition)
                        <span class="book-tag book-tag-condition is-{{ $conditionColor }}">🤖 {{ $condition }}</span>
                    @endif
                </div>

                <div class="book-body">
                    <h3 class="book-title">{{ $book->title }}</h3>

                    <div class="book-price">
                        @if ($book->type === 'sale')
                            ฿{{ number_format($book->price, 2) }}
                        @else
                            <span class="book-price-exchange">รับแลกเปลี่ยน</span>
                        @endif
                    </div>

                    <div class="book-foot">
                        <span class="book-cat">{{ $book->category }}</span>
                        <span class="book-shop">🏪 {{ $book->user->shop_name ?? $book->user->name }}</span>
                    </div>
                </div>
            </a>
        @endforeach
    </div>

    <div class="mt-4">
        {{ $books->links() }}
    </div>
@else
    <div class="empty-results">
        <div class="empty-results-icon">🔍</div>
        <h6 class="fw-bold mb-1">ไม่พบหนังสือที่ค้นหา</h6>
        <p class="text-muted small mb-3">ลองเปลี่ยนคำค้นหา หรือปรับตัวกรองให้กว้างขึ้น</p>
        @if (count($activeFilters))
            <a href="{{ $resetUrl }}" class="btn btn-outline-primary btn-sm">ล้างตัวกรองทั้งหมด</a>
        @endif
    </div>
@endif

<style>
    .hero-banner {
        background: linear-gradient(135deg, var(--bs-primary), color-mix(in srgb, var(--bs-primary) 60%, #6f42c1));
        position: relative;
        overflow: hidden;
    }
    /* วงกลมจาง ๆ ให้แบนเนอร์ไม่แบนเกินไป */
    .hero-banner::after {
        content: '';
        position: absolute;
        top: -80px;
        right: -60px;
        width: 260px;
        height: 260px;
        border-radius: 50%;
        background: rgba(255, 255, 255, .08);
    }
    .hero-banner > * { position: relative; }

    /* --- การ์ดค้นหา --- */
    .search-panel {
        position: relative;
        z-index: 2;
        margin: -2.25rem 1rem 1.5rem;
        background: #fff;
        border-radius: 1rem;
        padding: 1.25rem;
        box-shadow: 0 .75rem 2rem rgba(31, 45, 110, .12);
    }
    .search-bar {
        display: flex;
        gap: .5rem;
    }
    .search-field {
        position: relative;
        flex: 1 1 auto;
        min-width: 0;
    }
    .search-icon {
        position: absolute;
        left: 1rem;
        top: 50%;
        transform: translateY(-50%);
        color: #9aa2b1;
        pointer-events: none;
        display: flex;
    }
    .search-input {
        height: 3.25rem;
        padding-left: 2.9rem;
        padding-right: 2.6rem;
        border-radius: .75rem;
        background: #f4f6fb;
        border: 1px solid transparent;
        font-size: 1rem;
    }
    .search-input:focus {
        background: #fff;
        border-color: var(--bs-primary);
        box-shadow: 0 0 0 .2rem color-mix(in srgb, var(--bs-primary) 18%, transparent);
    }
    .search-clear {
        position: absolute;
        right: .65rem;
        top: 50%;
        transform: translateY(-50%);
        width: 1.6rem;
        height: 1.6rem;
        border: 0;
        border-radius: 50%;
        background: #e3e7f2;
        color: #5a6478;
        font-size: 1.1rem;
        line-height: 1;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .search-clear:hover { background: #d3d9ea; }
    .btn-search {
        height: 3.25rem;
        border-radius: .75rem;
        padding-inline: 1.75rem;
        font-weight: 600;
        white-space: nowrap;
    }

    .btn-filter-toggle {
        border: 1px dashed #ccd3e4;
        border-radius: .75rem;
        color: #5a6478;
        font-weight: 500;
    }
    .filter-count {
        display: inline-block;
        min-width: 1.25rem;
        padding: 0 .35rem;
        margin-left: .35rem;
        border-radius: 1rem;
        background: var(--bs-primary);
        color: #fff;
        font-size: .75rem;
        line-height: 1.25rem;
    }

    .search-filters { margin-top: 1rem; }
    .filter-label {
        display: block;
        margin-bottom: .3rem;
        font-size: .8rem;
        font-weight: 600;
        color: #7a8394;
    }
    .filter-select {
        height: 2.75rem;
        border-radius: .75rem;
        background-color: #f4f6fb;
        border: 1px solid transparent;
    }
    .filter-select:focus {
        background-color: #fff;
        border-color: var(--bs-primary);
        box-shadow: 0 0 0 .2rem color-mix(in srgb, var(--bs-primary) 18%, transparent);
    }

    /* --- ชิปตัวกรองที่ใช้อยู่ --- */
    .active-filters {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: .5rem;
        margin-top: 1rem;
        padding-top: 1rem;
        border-top: 1px solid #eef1f7;
    }
    .active-filters-title {
        font-size: .8rem;
        color: #7a8394;
    }
    .filter-chip {
        display: inline-flex;
        align-items: center;
        gap: .4rem;
        padding: .3rem .7rem;
        border-radius: 2rem;
        background: color-mix(in srgb, var(--bs-primary) 10%, #fff);
        color: var(--bs-primary);
        font-size: .85rem;
        text-decoration: none;
        transition: background .15s ease;
    }
    .filter-chip:hover {
        background: color-mix(in srgb, var(--bs-primary) 18%, #fff);
        color: var(--bs-primary);
    }
    .filter-chip-x { font-size: 1rem; line-height: 1; opacity: .7; }
    .filter-reset {
        font-size: .82rem;
        color: #8a92a3;
        text-decoration: none;
    }
    .filter-reset:hover { color: var(--bs-danger); text-decoration: underline; }

    /* --- แถบเครื่องมือผลลัพธ์ --- */
    .results-toolbar {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: .75rem;
    }
    .toolbar-right {
        display: flex;
        align-items: center;
        gap: .75rem;
    }
    .type-tabs {
        display: inline-flex;
        gap: .25rem;
        padding: .25rem;
        border-radius: .75rem;
        background: #eef1f7;
    }
    .type-tab {
        padding: .4rem .9rem;
        border-radius: .55rem;
        font-size: .9rem;
        color: #5a6478;
        text-decoration: none;
        white-space: nowrap;
        transition: background .15s ease, color .15s ease;
    }
    .type-tab:hover { color: var(--bs-primary); }
    .type-tab.active {
        background: #fff;
        color: var(--bs-primary);
        font-weight: 600;
        box-shadow: 0 1px 3px rgba(31, 45, 110, .12);
    }
    .results-count { font-size: .9rem; color: #7a8394; }

    .view-switch {
        display: inline-flex;
        padding: .2rem;
        border-radius: .6rem;
        background: #eef1f7;
    }
    .view-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 2rem;
        height: 2rem;
        border: 0;
        border-radius: .45rem;
        background: transparent;
        color: #7a8394;
        transition: background .15s ease, color .15s ease;
    }
    .view-btn.active {
        background: #fff;
        color: var(--bs-primary);
        box-shadow: 0 1px 3px rgba(31, 45, 110, .12);
    }

    /* --- ตารางหนังสือ ---
       ใช้ CSS grid แทน row/col ของ Bootstrap: auto-fill ทำให้จำนวนคอลัมน์
       ปรับตามความกว้างที่มีจริง และสลับเป็นมุมมอง list ได้ด้วยคลาสเดียว */
    .book-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
        gap: .85rem;
    }

    .book-card {
        display: flex;
        flex-direction: column;
        overflow: hidden;
        border: 1px solid #edf0f7;
        border-radius: .9rem;
        background: #fff;
        color: inherit;
        text-decoration: none;
        box-shadow: 0 1px 3px rgba(31, 45, 110, .06);
        transition: transform .15s ease, box-shadow .15s ease, border-color .15s ease;
    }
    .book-card:hover {
        transform: translateY(-3px);
        border-color: color-mix(in srgb, var(--bs-primary) 35%, #fff);
        box-shadow: 0 .6rem 1.4rem rgba(31, 45, 110, .14);
        color: inherit;
    }

    .book-thumb {
        position: relative;
        aspect-ratio: 3 / 4;
        background: #f4f6fb;
        overflow: hidden;
    }
    .book-thumb img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
    }
    .book-thumb-empty {
        width: 100%;
        height: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2rem;
        opacity: .35;
    }

    .book-tag {
        position: absolute;
        padding: .18rem .5rem;
        border-radius: 1rem;
        font-size: .7rem;
        font-weight: 600;
        line-height: 1.4;
        white-space: nowrap;
        backdrop-filter: blur(4px);
    }
    .book-tag-exchange {
        top: .5rem;
        left: .5rem;
        background: rgba(13, 202, 240, .92);
        color: #06333d;
    }
    /* สีป้ายสภาพหนังสือ นิยามเองเพราะธีมใช้ Bootstrap 5.1.3 ที่ยังไม่มี bg-*-subtle / text-*-emphasis */
    .book-tag-condition {
        bottom: .5rem;
        left: .5rem;
        background: rgba(255, 255, 255, .92);
        color: #4a5164;
    }
    .book-tag-condition.is-success { background: rgba(209, 245, 226, .95); color: #0a6b3d; }
    .book-tag-condition.is-primary { background: rgba(219, 224, 254, .95); color: #22319e; }
    .book-tag-condition.is-warning { background: rgba(255, 240, 205, .95); color: #7a5600; }
    .book-tag-condition.is-danger  { background: rgba(255, 220, 220, .95); color: #96232c; }

    .book-body {
        display: flex;
        flex-direction: column;
        gap: .3rem;
        padding: .7rem .75rem .8rem;
        flex: 1 1 auto;
        min-width: 0;
    }
    .book-title {
        margin: 0;
        font-size: .92rem;
        font-weight: 600;
        line-height: 1.35;
        color: #2b2f45;
        /* ตัดที่ 2 บรรทัด เพื่อให้การ์ดทุกใบสูงเท่ากันโดยไม่ตัดคำทิ้งตั้งแต่คำแรก */
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
    .book-price {
        font-size: 1.05rem;
        font-weight: 700;
        color: var(--bs-primary);
        line-height: 1.2;
    }
    .book-price-exchange {
        font-size: .85rem;
        font-weight: 600;
        color: #0aa2c0;
    }
    .book-foot {
        margin-top: auto;
        display: flex;
        flex-direction: column;
        gap: .25rem;
        font-size: .75rem;
        color: #8a92a3;
        min-width: 0;
    }
    .book-cat {
        align-self: flex-start;
        padding: .1rem .45rem;
        border-radius: .35rem;
        background: #f1f3f9;
        color: #6b7488;
    }
    .book-shop {
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    /* --- มุมมอง list: รูปซ้าย ข้อมูลขวา อ่านง่ายกว่าบนจอแคบ --- */
    .book-grid.is-list {
        grid-template-columns: 1fr;
        gap: .6rem;
    }
    .book-grid.is-list .book-card {
        flex-direction: row;
    }
    .book-grid.is-list .book-thumb {
        flex: 0 0 92px;
        width: 92px;
        aspect-ratio: 3 / 4;
    }
    .book-grid.is-list .book-tag-exchange {
        top: .3rem;
        left: .3rem;
        font-size: .62rem;
        padding: .1rem .35rem;
    }
    .book-grid.is-list .book-tag-condition { display: none; }
    .book-grid.is-list .book-body {
        padding: .7rem .9rem;
    }
    .book-grid.is-list .book-title {
        font-size: 1rem;
        -webkit-line-clamp: 2;
    }
    .book-grid.is-list .book-foot {
        flex-direction: row;
        flex-wrap: wrap;
        align-items: center;
        gap: .5rem;
    }
    .book-grid.is-list .book-card:hover {
        transform: none;
    }

    .empty-results {
        text-align: center;
        padding: 3.5rem 1rem;
        border: 1px dashed #d8dee9;
        border-radius: 1rem;
        background: #fbfcfe;
    }
    .empty-results-icon { font-size: 2.5rem; margin-bottom: .5rem; }

    @media (min-width: 768px) {
        .book-grid {
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 1.25rem;
        }
        .book-grid.is-list .book-thumb {
            flex: 0 0 130px;
            width: 130px;
        }
        .book-grid.is-list .book-tag-condition { display: block; }
    }

    @media (max-width: 767.98px) {
        .search-panel { margin-inline: .25rem; padding: 1rem; }
        .btn-search { padding-inline: 1rem; }
        /* แท็บเลื่อนแนวนอนได้ ไม่ดันแถวจนล้น */
        .type-tabs {
            width: 100%;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            scrollbar-width: none;
        }
        .type-tabs::-webkit-scrollbar { display: none; }
        .toolbar-right {
            width: 100%;
            justify-content: space-between;
        }
    }
</style>

<script>
    // ปุ่มล้างคำค้นหาในช่อง input จะโผล่เฉพาะตอนมีข้อความ
    (function () {
        const input = document.getElementById('searchInput');
        const clearBtn = document.getElementById('clearSearch');
        if (!input || !clearBtn) return;

        const sync = () => { clearBtn.hidden = input.value.trim() === ''; };
        input.addEventListener('input', sync);
        clearBtn.addEventListener('click', function () {
            input.value = '';
            sync();
            input.focus();
        });
        sync();
    })();

    // สลับ grid/list ด้วยคลาสเดียวบน container แล้วจำค่าไว้ให้ผู้ใช้
    function setView(view) {
        const container = document.getElementById('bookContainer');
        const gridBtn = document.getElementById('gridBtn');
        const listBtn = document.getElementById('listBtn');
        if (!container || !gridBtn || !listBtn) return;

        const isList = view === 'list';
        container.classList.toggle('is-list', isList);
        listBtn.classList.toggle('active', isList);
        gridBtn.classList.toggle('active', !isList);

        try { localStorage.setItem('bookView', view); } catch (e) { /* โหมดส่วนตัวอาจเขียนไม่ได้ */ }
    }

    (function () {
        let saved = null;
        try { saved = localStorage.getItem('bookView'); } catch (e) { /* ไม่มีก็ใช้ค่าเริ่มต้น */ }
        if (saved === 'list') setView('list');
    })();
</script>
@endsection
