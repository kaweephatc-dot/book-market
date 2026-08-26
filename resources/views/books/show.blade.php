@extends('layouts.user-dashboard')

@section('title', $book->title)

@section('content')
<a href="{{ route('home') }}" class="btn-back mb-3"><span class="btn-back-arrow">←</span> กลับหน้าหลัก</a>

<div class="card border-0 shadow-sm">
<div class="card-body p-4">
<div class="row g-4">
    {{-- รูปภาพ --}}
    <div class="col-md-6">
        @if ($book->images->count() > 0)
            {{-- รูปหลัก: แสดงเต็มภาพไม่ครอป (คลิกเพื่อขยาย) --}}
            <div class="book-stage mb-2" data-bs-toggle="modal" data-bs-target="#imageModal">
                <img id="mainImage"
                     src="{{ asset('storage/' . $book->coverImage()->image_path) }}"
                     alt="{{ $book->title }}">
                <span class="book-stage-zoom">🔍 คลิกเพื่อดูเต็มจอ</span>
            </div>

            {{-- แถวรูปย่อทั้งหมด --}}
            <div class="d-flex gap-2 flex-wrap">
                @foreach ($book->images as $index => $image)
                    <div class="position-relative">
                        <img src="{{ asset('storage/' . $image->image_path) }}"
                             class="thumbnail-img {{ $index === 0 ? 'is-active' : '' }}"
                             onclick="changeMainImage(this, '{{ asset('storage/' . $image->image_path) }}')"
                             alt="รูปที่ {{ $index + 1 }}">
                        @if ($image->ai_score)
                            <span class="badge bg-dark position-absolute bottom-0 start-0" style="font-size: 9px;">{{ $image->ai_score }}</span>
                        @endif
                    </div>
                @endforeach
            </div>
        @else
            <div class="book-stage book-stage-empty">
                <span class="text-muted">ไม่มีรูป</span>
            </div>
        @endif
    </div>

    {{-- รายละเอียด --}}
    <div class="col-md-6">
        <h2 class="fw-bold">{{ $book->title }}</h2>
        @if ($book->author)
            <p class="text-muted">โดย {{ $book->author }}</p>
        @endif

        <div class="mb-3 d-flex gap-2">
            <span class="badge badge-soft-secondary">{{ $book->category }}</span>
            @if ($book->type === 'sale')
                <span class="badge badge-soft-success">ขาย</span>
            @else
                <span class="badge badge-soft-info">แลกเปลี่ยน</span>
            @endif
        </div>

        @if ($book->type === 'sale')
            <h3 class="text-primary fw-bold mb-3">฿{{ number_format($book->price, 2) }}</h3>
        @endif

        {{-- ผลวิเคราะห์ AI (คะแนนเฉลี่ย) --}}
        @if ($avgScore > 0)
            @php
                $color = match($avgCondition) {
                    'ดีมาก' => 'success',
                    'ดี' => 'primary',
                    'พอใช้' => 'warning',
                    'ต้องซ่อม' => 'danger',
                    default => 'secondary',
                };
            @endphp
            <div class="card mb-3 border-{{ $color }}">
                <div class="card-body">
                    <h6 class="card-title">🤖 ผลการตรวจสอบสภาพด้วย AI</h6>
                    <p class="small text-muted mb-2">คะแนนเฉลี่ยจากทั้งหมด {{ $book->images->count() }} รูป</p>
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-{{ $color }} fs-6">{{ $avgCondition }}</span>
                        <div class="progress flex-grow-1" style="height: 20px;">
                            <div class="progress-bar bg-{{ $color }}" style="width: {{ $avgScore }}%;">{{ $avgScore }}/100</div>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        @if ($book->condition)
            <p><strong>สภาพ (จากผู้ขาย):</strong> {{ $book->condition }}</p>
        @endif

        @if ($book->description)
            <div class="mb-3">
                <strong>รายละเอียด:</strong>
                <p>{{ $book->description }}</p>
            </div>
        @endif

        <hr>

        @php
            $seller = $book->user;
            $shopAvg = $seller->averageRating();
            $shopCount = $seller->reviewCount();
            $shopBooks = $seller->books()->where('status', 'available')->count();
        @endphp

        {{-- การ์ดร้านผู้ขาย: ทั้งใบเป็นลิงก์ไปหน้าร้าน --}}
        <a href="{{ route('shop.show', $seller) }}" class="seller-card mb-3">
            @if ($seller->shopLogoPath())
                <img src="{{ asset('storage/' . $seller->shopLogoPath()) }}" class="seller-logo" alt="{{ $seller->shop_name ?? $seller->name }}">
            @else
                <div class="seller-logo seller-logo-empty">🏪</div>
            @endif

            <div class="seller-info">
                <span class="seller-label">ผู้ขาย</span>
                <span class="seller-name">{{ $seller->shop_name ?? $seller->name }}</span>

                @if ($shopCount > 0)
                    <span class="seller-rating">
                        @for ($i = 1; $i <= 5; $i++)
                            <span class="{{ $i <= round($shopAvg) ? 'star-on' : 'star-off' }}">★</span>
                        @endfor
                        <span class="seller-meta">{{ $shopAvg }} ({{ $shopCount }} รีวิว)</span>
                    </span>
                @else
                    <span class="seller-meta">ยังไม่มีรีวิว</span>
                @endif

                <span class="seller-meta">📚 มีหนังสือ {{ number_format($shopBooks) }} เล่มในร้าน</span>
            </div>

            <span class="seller-go" aria-hidden="true">›</span>
        </a>

        @php
            $isOwner = auth()->check() && auth()->id() === $book->user_id;
            $isAdminViewer = auth()->check() && auth()->user()->isAdmin();
        @endphp

        @auth
            @if ($isAdminViewer)
                <div class="alert alert-info text-center mb-0">🛡️ โหมดแอดมิน: ดูข้อมูลได้อย่างเดียว</div>
            @elseif ($isOwner)
                <div class="alert alert-secondary text-center mb-0">นี่คือหนังสือของคุณ</div>
            @else
                @if ($book->status === 'available')
                    <form method="POST" action="{{ route('orders.store', $book) }}" data-confirm="ยืนยันการสั่งซื้อ/แลกหนังสือเล่มนี้?">
                        @csrf
                        <button type="submit" class="btn btn-success w-100 mb-2">
                            {{ $book->type === 'sale' ? '🛒 สั่งซื้อ' : '🔄 ขอแลกเปลี่ยน' }}
                        </button>
                    </form>
                @else
                    <div class="alert alert-secondary text-center">หนังสือเล่มนี้ไม่พร้อมแล้ว</div>
                @endif

                <a href="{{ route('chat.start', $book) }}" class="btn btn-primary w-100">💬 ติดต่อผู้ขาย</a>
            @endif
        @else

            <a href="{{ route('login') }}" class="btn btn-outline-primary w-100">เข้าสู่ระบบเพื่อติดต่อผู้ขาย</a>
        @endauth

        @auth
            @if (!$isOwner && !$isAdminViewer)
                <button type="button" class="btn-report w-100 mt-2" data-bs-toggle="modal" data-bs-target="#reportBookModal">🚩 รายงานหนังสือนี้</button>
            @endif
        @endauth

    </div>
</div>
</div>
</div>

{{-- Modal ขยายรูปเต็ม --}}
<div class="modal fade" id="imageModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body p-0 position-relative d-flex justify-content-center">
                <button type="button" class="btn-close position-absolute top-0 end-0 m-2 bg-white" data-bs-dismiss="modal" style="z-index: 10;"></button>
                {{-- ไม่ใส่ .w-100: รูปแนวตั้งจะถูกยืดจนล้นความสูงจอ --}}
                <img id="modalImage" src="" class="rounded" alt="ภาพเต็ม">
            </div>
        </div>
    </div>
</div>

<style>
    /* กรอบรูปหลัก: ความสูงคงที่เพื่อไม่ให้หน้ากระตุกตอนสลับรูป
       แต่ตัวรูปใช้ contain จึงเห็นเต็มภาพ ไม่ถูกครอปเหมือน cover */
    .book-stage {
        position: relative;
        display: flex;
        align-items: center;
        justify-content: center;
        aspect-ratio: 4 / 3;
        padding: .5rem;
        border-radius: .75rem;
        background: #f4f6fb;
        overflow: hidden;
        cursor: zoom-in;
    }
    .book-stage img {
        max-width: 100%;
        max-height: 100%;
        width: auto;
        height: auto;
        object-fit: contain;
        border-radius: .4rem;
    }
    .book-stage-empty {
        cursor: default;
    }
    .book-stage-zoom {
        position: absolute;
        right: .6rem;
        bottom: .6rem;
        padding: .2rem .6rem;
        border-radius: 1rem;
        background: rgba(20, 24, 45, .65);
        color: #fff;
        font-size: .72rem;
        opacity: 0;
        transition: opacity .15s ease;
        pointer-events: none;
    }
    .book-stage:hover .book-stage-zoom { opacity: 1; }

    .thumbnail-img {
        width: 70px;
        height: 70px;
        padding: 2px;
        object-fit: contain;
        border-radius: .5rem;
        background: #f4f6fb;
        border: 2px solid transparent;
        cursor: pointer;
        transition: border-color .15s ease;
    }
    .thumbnail-img:hover { border-color: #c3cbe8; }
    .thumbnail-img.is-active { border-color: var(--bs-primary); }

    /* --- การ์ดร้านผู้ขาย --- */
    .seller-card {
        display: flex;
        align-items: center;
        gap: .85rem;
        padding: .85rem 1rem;
        border: 1px solid #e6eaf3;
        border-radius: .9rem;
        background: #fbfcfe;
        color: inherit;
        text-decoration: none;
        transition: border-color .15s ease, background-color .15s ease, box-shadow .15s ease;
    }
    .seller-card:hover {
        color: inherit;
        border-color: var(--bs-primary);
        background: #fff;
        box-shadow: 0 .4rem 1rem rgba(31, 45, 110, .1);
    }

    .seller-logo {
        flex: 0 0 54px;
        width: 54px;
        height: 54px;
        border-radius: 50%;
        object-fit: cover;
        background: #eef1f7;
        border: 2px solid #fff;
        box-shadow: 0 0 0 1px #e6eaf3;
    }
    .seller-logo-empty {
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
    }

    .seller-info {
        display: flex;
        flex-direction: column;
        gap: .1rem;
        min-width: 0;
        flex: 1 1 auto;
    }
    .seller-label {
        font-size: .72rem;
        color: #9aa2b1;
        line-height: 1.2;
    }
    .seller-name {
        font-weight: 600;
        color: #2b2f45;
        line-height: 1.3;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .seller-card:hover .seller-name { color: var(--bs-primary); }

    .seller-rating { line-height: 1.3; }
    .seller-rating .star-on { color: #ffc107; }
    .seller-rating .star-off { color: #dfe3ec; }
    .seller-meta {
        font-size: .78rem;
        color: #8a92a3;
        line-height: 1.35;
    }

    .seller-go {
        flex: 0 0 auto;
        font-size: 1.5rem;
        line-height: 1;
        color: #c3cbe0;
        transition: transform .15s ease, color .15s ease;
    }
    .seller-card:hover .seller-go {
        color: var(--bs-primary);
        transform: translateX(3px);
    }

    /* รูปในโมดัลต้องไม่สูงเกินจอ ไม่งั้นรูปแนวตั้งจะล้นออกนอกหน้าต่าง */
    #modalImage {
        max-height: 85vh;
        width: auto;
        max-width: 100%;
        object-fit: contain;
    }
</style>

<script>
    // เปลี่ยนรูปหลักเมื่อคลิกรูปย่อ
    function changeMainImage(thumb, src) {
        document.getElementById('mainImage').src = src;
        document.getElementById('modalImage').src = src;

        // ไฮไลต์รูปที่เลือก
        document.querySelectorAll('.thumbnail-img').forEach(img => {
            img.classList.remove('is-active');
        });
        thumb.classList.add('is-active');
    }

    // ตั้งค่ารูปเริ่มต้นใน modal ตั้งแต่โหลดหน้า
    // (เดิมผูกกับ click ของ #mainImage ซึ่งพลาดกรณีคลิกโดนขอบกรอบ)
    (function () {
        const main = document.getElementById('mainImage');
        const modalImg = document.getElementById('modalImage');
        if (main && modalImg) modalImg.src = main.src;
    })();
</script>

    {{-- Modal รายงานหนังสือ --}}
@auth
@if (!$isOwner && !$isAdminViewer)
<div class="modal fade" id="reportBookModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('report.book', $book) }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">🚩 รายงานหนังสือ</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">เหตุผล <span class="text-danger">*</span></label>
                        <select name="reason" class="form-select" required>
                            <option value="">-- เลือกเหตุผล --</option>
                            <option value="เนื้อหาไม่เหมาะสม">เนื้อหาไม่เหมาะสม</option>
                            <option value="สินค้าผิดกฎหมาย">สินค้าผิดกฎหมาย</option>
                            <option value="หลอกลวง/ฉ้อโกง">หลอกลวง/ฉ้อโกง</option>
                            <option value="ข้อมูลเท็จ">ข้อมูลเท็จ</option>
                            <option value="ราคาไม่เหมาะสม">ราคาไม่เหมาะสม</option>
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