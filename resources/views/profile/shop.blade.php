@extends('layouts.user-dashboard')

@section('title', $shop->shop_name ?? $shop->name)

@section('content')
@if (session('success'))
    <div class="alert alert-success d-none" data-flash="success">{{ session('success') }}</div>
@endif
@if (session('error'))
    <div class="alert alert-danger d-none" data-flash="error">{{ session('error') }}</div>
@endif

{{-- แบนเนอร์ข้อมูลร้าน --}}
<div class="shop-banner rounded-4 p-4 p-md-5 mb-4 text-white d-flex align-items-center gap-4 flex-wrap">
    @if ($shop->avatar)
        <img src="{{ asset('storage/' . $shop->avatar) }}" class="rounded-circle border border-3 border-white" style="width: 100px; height: 100px; object-fit: cover;" alt="">
    @else
        <div class="rounded-circle bg-white bg-opacity-25 d-flex align-items-center justify-content-center" style="width: 100px; height: 100px; font-size: 40px;">🏪</div>
    @endif
    <div>
        <h3 class="fw-bold mb-1">{{ $shop->shop_name ?? $shop->name }}</h3>
        @php $avg = $shop->averageRating(); $count = $shop->reviewCount(); @endphp
        @if ($count > 0)
            <div>
                @for ($i = 1; $i <= 5; $i++)
                    <span style="color: {{ $i <= round($avg) ? '#ffc107' : 'rgba(255,255,255,.4)' }};">★</span>
                @endfor
                <span class="small opacity-75 ms-1">{{ $avg }} จาก {{ $count }} รีวิว</span>
            </div>
        @else
            <div class="small opacity-75">ยังไม่มีรีวิว</div>
        @endif
    </div>
</div>

<div class="row">
    {{-- ข้อมูลร้าน --}}
    <div class="col-md-4">
        @php
            $isOwnShop = auth()->check() && auth()->id() === $shop->id;
            $isAdminViewer = auth()->check() && auth()->user()->isAdmin();
        @endphp

        @auth
            @if (!$isOwnShop && !$isAdminViewer)
                <button class="btn btn-link btn-sm text-danger mt-2" data-bs-toggle="modal" data-bs-target="#reportShopModal">🚩 รายงานร้านนี้</button>
            @endif
        @endauth

        {{-- ฟอร์มรีวิว (เฉพาะคนที่เคยแชท) --}}
        @auth
            @if ($isAdminViewer)
                <div class="alert alert-info small mt-2">🛡️ โหมดแอดมิน: ดูข้อมูลได้อย่างเดียว</div>
            @elseif ($canReview)
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h6>{{ $myReview ? 'แก้ไขรีวิวของคุณ' : 'เขียนรีวิว' }}</h6>
                        <form method="POST" action="{{ route('reviews.store', $shop) }}">
                            @csrf
                            <div class="mb-2">
                                <label class="form-label small">ให้คะแนน</label>
                                <select name="rating" class="form-select form-select-sm" required>
                                    @for ($i = 5; $i >= 1; $i--)
                                        <option value="{{ $i }}" {{ ($myReview && $myReview->rating == $i) ? 'selected' : '' }}>
                                            {{ str_repeat('★', $i) }} ({{ $i }})
                                        </option>
                                    @endfor
                                </select>
                            </div>
                            <div class="mb-2">
                                <textarea name="comment" class="form-control form-control-sm" rows="2" placeholder="ความคิดเห็น (ไม่บังคับ)">{{ $myReview->comment ?? '' }}</textarea>
                            </div>
                            <button type="submit" class="btn btn-sm btn-primary w-100">{{ $myReview ? 'อัปเดตรีวิว' : 'ส่งรีวิว' }}</button>
                        </form>
                    </div>
                </div>
            @elseif (!$isOwnShop)
                <div class="alert alert-secondary small">ซื้อขายกับร้านนี้สำเร็จก่อนถึงจะรีวิวได้</div>
            @endif
        @endauth
    </div>

    {{-- รายการรีวิว + หนังสือ --}}
    <div class="col-md-8">
        {{-- รีวิว --}}
        <h5 class="mb-3">⭐ รีวิวจากลูกค้า</h5>
        @forelse ($shop->reviews->sortByDesc('created_at') as $review)
            <div class="card mb-2">
                <div class="card-body py-2">
                    <div class="d-flex justify-content-between">
                        <strong>{{ $review->reviewer->name }}</strong>
                        <span>
                            @for ($i = 1; $i <= 5; $i++)
                                <span style="color: {{ $i <= $review->rating ? '#ffc107' : '#ddd' }};">★</span>
                            @endfor
                        </span>
                    </div>
                    @if ($review->comment)
                        <p class="mb-1 small">{{ $review->comment }}</p>
                    @endif
                    <div class="text-muted" style="font-size: 11px;">{{ $review->created_at->diffForHumans() }}</div>
                </div>
            </div>
        @empty
            <p class="text-muted">ยังไม่มีรีวิว</p>
        @endforelse

        {{-- หนังสือของร้าน --}}
        <h5 class="mb-3 mt-4">📚 หนังสือของร้าน</h5>
        <div class="row g-3">
            @forelse ($shop->books as $book)
                <div class="col-6 col-md-4">
                    <div class="card h-100 border-0 shadow-sm">
                        @if ($book->images->count() > 0)
                            <img src="{{ asset('storage/' . $book->coverImage()->image_path) }}" class="card-img-top" style="height: 130px; object-fit: cover;" alt="">
                        @endif
                        <div class="card-body p-2">
                            <div class="small fw-bold text-truncate">{{ $book->title }}</div>
                            <a href="{{ route('books.show', $book) }}" class="btn btn-sm btn-outline-primary w-100 mt-1">ดู</a>
                        </div>
                    </div>
                </div>
            @empty
                <p class="text-muted">ยังไม่มีหนังสือ</p>
            @endforelse
        </div>
    </div>
</div>

<style>
    .shop-banner {
        background: linear-gradient(135deg, var(--bs-primary), color-mix(in srgb, var(--bs-primary) 70%, #6f42c1));
    }
</style>

{{-- Modal รายงานร้าน --}}
@auth
@if (!$isOwnShop && !$isAdminViewer)
<div class="modal fade" id="reportShopModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('report.shop', $shop) }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">🚩 รายงานร้าน</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">เหตุผล <span class="text-danger">*</span></label>
                        <select name="reason" class="form-select" required>
                            <option value="">-- เลือกเหตุผล --</option>
                            <option value="หลอกลวง/ฉ้อโกง">หลอกลวง/ฉ้อโกง</option>
                            <option value="พฤติกรรมไม่เหมาะสม">พฤติกรรมไม่เหมาะสม</option>
                            <option value="ขายสินค้าผิดกฎหมาย">ขายสินค้าผิดกฎหมาย</option>
                            <option value="ข้อมูลร้านเป็นเท็จ">ข้อมูลร้านเป็นเท็จ</option>
                            <option value="อื่นๆ">อื่นๆ</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">รายละเอียดเพิ่มเติม</label>
                        <textarea name="detail" class="form-control" rows="3" placeholder="อธิบายเพิ่มเติม (ไม่บังคับ)"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ปิด</button>
                    <button type="submit" class="btn btn-danger">ส่งรายงาน</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif
@endauth
@endsection