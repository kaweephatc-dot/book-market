@extends('layouts.user-dashboard')

@section('title', 'โปรไฟล์')

@section('content')
@php
    // เปิดแท็บร้านค้าค้างไว้หลังบันทึก (?tab=shop) และตอน validate ไม่ผ่าน
    $activeTab = (request('tab') === 'shop' || $errors->any()) && $user->is_shop ? 'shop' : 'account';
@endphp

<div class="row justify-content-center">
    <div class="col-lg-8">

        @if ($user->is_shop)
            <ul class="nav profile-tabs mb-3" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="profile-tab {{ $activeTab === 'account' ? 'active' : '' }}" data-bs-toggle="tab"
                            data-bs-target="#tab-account" type="button" role="tab">👤 ข้อมูลส่วนตัว</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="profile-tab {{ $activeTab === 'shop' ? 'active' : '' }}" data-bs-toggle="tab"
                            data-bs-target="#tab-shop" type="button" role="tab">🏪 ข้อมูลร้านค้า</button>
                </li>
            </ul>
        @endif

        <div class="tab-content">

            {{-- ===== แท็บข้อมูลส่วนตัว ===== --}}
            <div class="tab-pane fade {{ $activeTab === 'account' ? 'show active' : '' }}" id="tab-account" role="tabpanel">
                <div class="card shadow-sm border-0">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center gap-3 mb-4">
                            @if ($user->avatar)
                                <img src="{{ asset('storage/' . $user->avatar) }}" class="profile-avatar" alt="">
                            @else
                                <div class="profile-avatar profile-avatar-empty">👤</div>
                            @endif
                            <div>
                                <h4 class="mb-1">{{ $user->name }}</h4>
                                @if ($user->is_shop)
                                    <span class="badge badge-soft-success">🏪 ร้านค้า</span>
                                @else
                                    <span class="badge badge-soft-secondary">ผู้ใช้ทั่วไป</span>
                                @endif
                            </div>
                        </div>

                        <dl class="info-list">
                            <dt>อีเมล</dt>
                            <dd>{{ $user->email }}</dd>

                            <dt>เบอร์โทร</dt>
                            <dd>{{ $user->phone ?: '—' }}</dd>

                            <dt>ที่อยู่</dt>
                            <dd>{{ $user->address ?: '—' }}</dd>
                        </dl>

                        <div class="d-flex gap-2 flex-wrap mt-4">
                            <a href="{{ route('profile.edit') }}" class="btn btn-primary">แก้ไขโปรไฟล์</a>
                            @if (!$user->is_shop && !$user->isAdmin())
                                <a href="{{ route('shop.register') }}" class="btn btn-success">🏪 สมัครเป็นร้านค้า</a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- ===== แท็บข้อมูลร้านค้า ===== --}}
            @if ($user->is_shop)
                <div class="tab-pane fade {{ $activeTab === 'shop' ? 'show active' : '' }}" id="tab-shop" role="tabpanel">

                    {{-- สรุปภาพรวมร้าน --}}
                    <div class="card shadow-sm border-0 mb-3">
                        <div class="card-body p-4">
                            <div class="d-flex align-items-center gap-3 flex-wrap mb-4">
                                @if ($user->shopLogoPath())
                                    <img src="{{ asset('storage/' . $user->shopLogoPath()) }}" class="profile-avatar" alt="">
                                @else
                                    <div class="profile-avatar profile-avatar-empty">🏪</div>
                                @endif
                                <div class="flex-grow-1">
                                    <h4 class="mb-1">{{ $user->shop_name ?: $user->name }}</h4>
                                    @if ($shopStats['reviews'] > 0)
                                        <div class="shop-rating">
                                            @for ($i = 1; $i <= 5; $i++)
                                                <span class="{{ $i <= round($shopStats['rating']) ? 'star-on' : 'star-off' }}">★</span>
                                            @endfor
                                            <span class="text-muted small ms-1">{{ $shopStats['rating'] }} จาก {{ $shopStats['reviews'] }} รีวิว</span>
                                        </div>
                                    @else
                                        <div class="text-muted small">ยังไม่มีรีวิว</div>
                                    @endif
                                </div>
                                <a href="{{ route('shop.show', $user) }}" class="btn btn-outline-primary btn-sm">ดูหน้าร้านสาธารณะ</a>
                            </div>

                            <div class="shop-stats">
                                <div class="shop-stat">
                                    <span class="shop-stat-value">{{ number_format($shopStats['available']) }}</span>
                                    <span class="shop-stat-label">กำลังขาย</span>
                                </div>
                                <div class="shop-stat">
                                    <span class="shop-stat-value">{{ number_format($shopStats['sold']) }}</span>
                                    <span class="shop-stat-label">ขาย/แลกแล้ว</span>
                                </div>
                                <div class="shop-stat">
                                    <span class="shop-stat-value">{{ number_format($shopStats['reviews']) }}</span>
                                    <span class="shop-stat-label">รีวิว</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- ฟอร์มแก้ไขข้อมูลร้าน --}}
                    <div class="card shadow-sm border-0">
                        <div class="card-body p-4">
                            <h5 class="mb-1">แก้ไขข้อมูลร้านค้า</h5>
                            <p class="text-muted small mb-4">ข้อมูลชุดนี้แยกจากข้อมูลส่วนตัว แก้ตรงนี้แล้วโปรไฟล์ส่วนตัวไม่เปลี่ยนตาม</p>

                            @if ($errors->any())
                                <div class="alert alert-danger">
                                    <ul class="mb-0">
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            <form method="POST" action="{{ route('profile.shop.update') }}" enctype="multipart/form-data">
                                @csrf
                                @method('PUT')

                                <div class="mb-3">
                                    <label class="form-label" for="shopLogo">โลโก้ร้าน</label>
                                    <input type="file" name="shop_logo" id="shopLogo" class="form-control" accept="image/*">
                                    <small class="text-muted">
                                        ไม่เกิน 2MB
                                        @unless ($user->shop_logo)
                                            — ตอนนี้ใช้รูปโปรไฟล์แทนอยู่
                                        @endunless
                                    </small>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label" for="shopName">ชื่อร้าน <span class="text-danger">*</span></label>
                                    <input type="text" name="shop_name" id="shopName" class="form-control"
                                           value="{{ old('shop_name', $user->shop_name) }}" required>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label" for="shopDescription">คำอธิบายร้าน</label>
                                    <textarea name="shop_description" id="shopDescription" class="form-control" rows="3"
                                              placeholder="เล่าให้ลูกค้าฟังว่าร้านคุณขายหนังสือแนวไหน">{{ old('shop_description', $user->shop_description) }}</textarea>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label" for="shopPhone">เบอร์ติดต่อร้าน</label>
                                    <input type="text" name="shop_phone" id="shopPhone" class="form-control"
                                           value="{{ old('shop_phone', $user->shop_phone) }}">
                                </div>

                                <div class="mb-3">
                                    <label class="form-label" for="shopAddress">ที่อยู่ร้าน</label>
                                    <textarea name="shop_address" id="shopAddress" class="form-control" rows="3">{{ old('shop_address', $user->shop_address) }}</textarea>
                                </div>

                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-primary">บันทึกข้อมูลร้าน</button>
                                    <a href="{{ route('shop.show', $user) }}" class="btn btn-outline-secondary">ดูหน้าร้าน</a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            @endif

        </div>
    </div>
</div>

<style>
    .profile-tabs {
        display: inline-flex;
        gap: .25rem;
        padding: .25rem;
        border-radius: .75rem;
        background: #eef1f7;
        border: 0;
    }
    .profile-tab {
        padding: .45rem 1rem;
        border: 0;
        border-radius: .55rem;
        background: transparent;
        color: #5a6478;
        font-size: .92rem;
        white-space: nowrap;
        transition: background .15s ease, color .15s ease;
    }
    .profile-tab:hover { color: var(--bs-primary); }
    .profile-tab.active {
        background: #fff;
        color: var(--bs-primary);
        font-weight: 600;
        box-shadow: 0 1px 3px rgba(31, 45, 110, .12);
    }

    .profile-avatar {
        width: 84px;
        height: 84px;
        border-radius: 50%;
        object-fit: cover;
        flex: 0 0 84px;
    }
    .profile-avatar-empty {
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 34px;
        background: #f1f3f9;
    }

    /* ตารางข้อมูลเดิมใช้ <table> ซึ่งบีบจนอ่านยากบนมือถือ */
    .info-list {
        margin: 0;
        display: grid;
        grid-template-columns: 8rem 1fr;
        gap: .1rem 1rem;
    }
    .info-list dt {
        color: #7a8394;
        font-weight: 500;
        padding: .55rem 0;
    }
    .info-list dd {
        margin: 0;
        padding: .55rem 0;
        color: #2b2f45;
        word-break: break-word;
    }
    .info-list dt + dd { border-bottom: 1px solid #f0f2f7; }
    .info-list dt { border-bottom: 1px solid #f0f2f7; }
    .info-list dt:last-of-type,
    .info-list dd:last-of-type { border-bottom: 0; }

    .shop-rating .star-on { color: #ffc107; }
    .shop-rating .star-off { color: #dfe3ec; }

    .shop-stats {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: .75rem;
    }
    .shop-stat {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: .15rem;
        padding: .85rem .5rem;
        border-radius: .75rem;
        background: #f6f8fc;
    }
    .shop-stat-value {
        font-size: 1.35rem;
        font-weight: 700;
        color: var(--bs-primary);
        line-height: 1.1;
    }
    .shop-stat-label {
        font-size: .78rem;
        color: #7a8394;
    }

    @media (max-width: 575.98px) {
        .profile-tabs { width: 100%; }
        .profile-tab { flex: 1 1 0; }
        .info-list { grid-template-columns: 1fr; gap: 0; }
        .info-list dt { padding-bottom: 0; border-bottom: 0; }
        .info-list dd { padding-top: .1rem; }
    }
</style>
@endsection
