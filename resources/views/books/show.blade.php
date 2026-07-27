@extends('layouts.app')

@section('title', $book->title)

@section('content')
<div class="row">
    {{-- รูปภาพ --}}
    <div class="col-md-6">
        @if ($book->images->count() > 0)
            {{-- รูปหลัก (คลิกเพื่อขยาย) --}}
            <img id="mainImage"
                 src="{{ asset('storage/' . $book->coverImage()->image_path) }}"
                 class="d-block w-100 rounded shadow-sm mb-2"
                 style="height: 400px; object-fit: cover; cursor: pointer;"
                 alt="{{ $book->title }}"
                 data-bs-toggle="modal"
                 data-bs-target="#imageModal">

            {{-- แถวรูปย่อทั้งหมด --}}
            <div class="d-flex gap-2 flex-wrap">
                @foreach ($book->images as $index => $image)
                    <div class="position-relative">
                        <img src="{{ asset('storage/' . $image->image_path) }}"
                             class="rounded border thumbnail-img {{ $index === 0 ? 'border-primary border-3' : '' }}"
                             style="width: 70px; height: 70px; object-fit: cover; cursor: pointer;"
                             onclick="changeMainImage(this, '{{ asset('storage/' . $image->image_path) }}')"
                             alt="รูปที่ {{ $index + 1 }}">
                        @if ($image->ai_score)
                            <span class="badge bg-dark position-absolute bottom-0 start-0" style="font-size: 9px;">{{ $image->ai_score }}</span>
                        @endif
                    </div>
                @endforeach
            </div>
        @else
            <div class="bg-light d-flex align-items-center justify-content-center rounded" style="height: 400px;">
                <span class="text-muted">ไม่มีรูป</span>
            </div>
        @endif
    </div>

    {{-- รายละเอียด --}}
    <div class="col-md-6">
        <h2>{{ $book->title }}</h2>
        @if ($book->author)
            <p class="text-muted">โดย {{ $book->author }}</p>
        @endif

        <div class="mb-3">
            <span class="badge bg-secondary">{{ $book->category }}</span>
            @if ($book->type === 'sale')
                <span class="badge bg-success">ขาย</span>
            @else
                <span class="badge bg-info">แลกเปลี่ยน</span>
            @endif
        </div>

        @if ($book->type === 'sale')
            <h3 class="text-primary mb-3">฿{{ number_format($book->price, 2) }}</h3>
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

        <div class="mb-3">
            <strong>ผู้ขาย:</strong>
            <a href="{{ route('shop.show', $book->user) }}" class="text-decoration-none">
                🏪 {{ $book->user->shop_name ?? $book->user->name }}
            </a>
            @php
                $shopAvg = $book->user->averageRating();
                $shopCount = $book->user->reviewCount();
            @endphp
            @if ($shopCount > 0)
                <div class="mt-1">
                    @for ($i = 1; $i <= 5; $i++)
                        <span style="color: {{ $i <= round($shopAvg) ? '#ffc107' : '#ddd' }};">★</span>
                    @endfor
                    <span class="text-muted small">{{ $shopAvg }} ({{ $shopCount }} รีวิว)</span>
                </div>
            @else
                <div class="text-muted small mt-1">ยังไม่มีรีวิว</div>
            @endif
        </div>

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
                    <form method="POST" action="{{ route('orders.store', $book) }}" onsubmit="return confirm('ยืนยันการสั่งซื้อ/แลกหนังสือเล่มนี้?')">
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
                <button class="btn btn-link btn-sm text-danger w-100 mt-2" data-bs-toggle="modal" data-bs-target="#reportBookModal">🚩 รายงานหนังสือนี้</button>
            @endif
        @endauth

        <a href="{{ route('home') }}" class="btn btn-link w-100 mt-2">← กลับหน้าหลัก</a>
    </div>
</div>

{{-- Modal ขยายรูปเต็ม --}}
<div class="modal fade" id="imageModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body p-0 position-relative">
                <button type="button" class="btn-close position-absolute top-0 end-0 m-2 bg-white" data-bs-dismiss="modal" style="z-index: 10;"></button>
                <img id="modalImage" src="" class="w-100 rounded" alt="ภาพเต็ม">
            </div>
        </div>
    </div>
</div>

<script>
    // เปลี่ยนรูปหลักเมื่อคลิกรูปย่อ
    function changeMainImage(thumb, src) {
        document.getElementById('mainImage').src = src;
        document.getElementById('modalImage').src = src;

        // ไฮไลต์รูปที่เลือก
        document.querySelectorAll('.thumbnail-img').forEach(img => {
            img.classList.remove('border-primary', 'border-3');
        });
        thumb.classList.add('border-primary', 'border-3');
    }

    // ตั้งค่ารูปเริ่มต้นใน modal
    document.getElementById('mainImage')?.addEventListener('click', function() {
        document.getElementById('modalImage').src = this.src;
    });
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