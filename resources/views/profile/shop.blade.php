@extends('layouts.app')

@section('title', $shop->shop_name ?? $shop->name)

@section('content')
@if (session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if (session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif

<div class="row">
    {{-- ข้อมูลร้าน --}}
    <div class="col-md-4">
        <div class="card shadow-sm mb-3">
            <div class="card-body text-center">
                @if ($shop->avatar)
                    <img src="{{ asset('storage/' . $shop->avatar) }}" class="rounded-circle mb-2" style="width: 90px; height: 90px; object-fit: cover;" alt="">
                @else
                    <div class="rounded-circle bg-light d-flex align-items-center justify-content-center mx-auto mb-2" style="width: 90px; height: 90px; font-size: 36px;">🏪</div>
                @endif
                <h5>{{ $shop->shop_name ?? $shop->name }}</h5>

                {{-- ดาวเฉลี่ย --}}
                @php $avg = $shop->averageRating(); $count = $shop->reviewCount(); @endphp
                @if ($count > 0)
                    <div class="mb-1">
                        @for ($i = 1; $i <= 5; $i++)
                            <span style="color: {{ $i <= round($avg) ? '#ffc107' : '#ddd' }};">★</span>
                        @endfor
                    </div>
                    <div class="text-muted small">{{ $avg }} จาก {{ $count }} รีวิว</div>
                @else
                    <div class="text-muted small">ยังไม่มีรีวิว</div>
                @endif
            </div>
        </div>

        {{-- ฟอร์มรีวิว (เฉพาะคนที่เคยแชท) --}}
        @auth
            @if ($canReview)
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
            @elseif (auth()->id() !== $shop->id)
                <div class="alert alert-secondary small">ติดต่อร้านผ่านแชทก่อนถึงจะรีวิวได้</div>
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
        <div class="row g-2">
            @forelse ($shop->books as $book)
                <div class="col-6 col-md-4">
                    <div class="card h-100">
                        @if ($book->images->count() > 0)
                            <img src="{{ asset('storage/' . $book->images->first()->image_path) }}" class="card-img-top" style="height: 120px; object-fit: cover;" alt="">
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
@endsection