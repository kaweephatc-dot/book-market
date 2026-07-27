@extends('layouts.app')

@section('title', 'ลงประกาศหนังสือ')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <h3 class="mb-4">ลงประกาศหนังสือ</h3>

                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('books.store') }}" enctype="multipart/form-data" data-book-condition-form data-analyze-url="{{ route('books.analyzeCondition') }}">
                    @csrf

                    <div class="mb-3">
                        <label class="form-label">ชื่อหนังสือ <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control" value="{{ old('title') }}" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">ผู้แต่ง</label>
                        <input type="text" name="author" class="form-control" value="{{ old('author') }}">
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">หมวดหมู่ <span class="text-danger">*</span></label>
                            <select name="category" class="form-select" required>
                                <option value="">-- เลือกหมวดหมู่ --</option>
                                <option value="นิยาย">นิยาย</option>
                                <option value="วิชาการ">วิชาการ</option>
                                <option value="การ์ตูน">การ์ตูน</option>
                                <option value="ตำราเรียน">ตำราเรียน</option>
                                <option value="ธุรกิจ">ธุรกิจ</option>
                                <option value="จิตวิทยา">จิตวิทยา</option>
                                <option value="อื่นๆ">อื่นๆ</option>
                            </select>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">ประเภท <span class="text-danger">*</span></label>
                            <select name="type" class="form-select" id="typeSelect" required>
                                <option value="sale">ขาย</option>
                                <option value="exchange">แลกเปลี่ยน</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3" id="priceField">
                        <label class="form-label">ราคา (บาท)</label>
                        <input type="number" name="price" class="form-control" value="{{ old('price') }}" min="0" step="0.01">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">รายละเอียด</label>
                        <textarea name="description" class="form-control" rows="4">{{ old('description') }}</textarea>
                    </div>

                    <hr>

                    <h6 class="mb-1">รูปภาพหนังสือ</h6>
                    <p class="small text-muted">ถ่ายตามมุมที่แนะนำแต่ละช่อง ยิ่งใส่ครบยิ่งช่วยให้ AI ประเมินแม่นยำขึ้น และเพิ่มความน่าเชื่อถือให้ร้านของคุณ</p>

                    @php
                        $slots = [
                            'cover' => ['label' => 'ปกหนังสือ', 'hint' => 'ถ่ายปกหน้าให้เห็นชื่อเรื่องและภาพปกชัดเจน ไม่เอียง ไม่มีแสงสะท้อน', 'required' => true],
                            'spine' => ['label' => 'สันหนังสือ', 'hint' => 'ถ่ายสันหนังสือด้านข้าง ให้เห็นรอยแตก/ยับของสันชัดเจน', 'required' => false],
                            'page' => ['label' => 'หน้าหนังสือ (สุ่ม)', 'hint' => 'เปิดหนังสือสุ่มหน้าใดก็ได้ ถ่ายให้เห็นสภาพกระดาษ รอยเปื้อนหรือรอยขีดเขียน (ถ้ามี)', 'required' => false],
                            'back' => ['label' => 'ปกหลังหนังสือ', 'hint' => 'ถ่ายปกหลังให้เห็นทั้งแผ่น รวมบาร์โค้ด/ราคาปก (ถ้ามี)', 'required' => false],
                        ];
                    @endphp

                    <div class="row g-3 mb-3">
                        @foreach ($slots as $slot => $info)
                            <div class="col-md-6">
                                <div class="border rounded p-2 h-100">
                                    <label class="form-label fw-bold mb-1">
                                        {{ $info['label'] }}
                                        @if ($info['required'])
                                            <span class="text-danger">*</span>
                                        @else
                                            <span class="text-muted small">(ไม่บังคับ)</span>
                                        @endif
                                    </label>
                                    <div class="small text-muted mb-2">{{ $info['hint'] }}</div>
                                    <input type="file" name="{{ $slot }}_image" accept="image/*"
                                           class="form-control form-control-sm"
                                           data-slot-input="{{ $slot }}"
                                           @if ($info['required']) required @endif>
                                    <img data-slot-preview="{{ $slot }}" class="img-fluid rounded mt-2 d-none" style="max-height: 150px;" alt="ตัวอย่างรูป{{ $info['label'] }}">
                                    <div data-slot-result="{{ $slot }}" class="small mt-2 d-none"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <small class="text-muted d-block mb-3">แต่ละรูปไม่เกิน 2MB</small>

                    <div class="mb-3">
                        <button type="button" class="btn btn-outline-primary" data-analyze-btn>
                            🤖 ประเมินด้วย AI
                        </button>
                        <span class="text-muted small ms-2 d-none" data-analyze-loading>
                            <span class="spinner-border spinner-border-sm" role="status"></span> กำลังประเมิน...
                        </span>
                    </div>

                    <div class="alert alert-danger d-none" data-analyze-error></div>

                    <div class="alert alert-light border d-none" data-analyze-overall>
                        <div class="fw-bold" data-overall-headline></div>
                        <div class="small" data-overall-note></div>
                        <div class="text-muted small mt-2">ประเมินโดย AI จากรูปภาพ ตรวจสอบและปรับได้ตามจริง</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">สภาพหนังสือ</label>
                        <select name="condition" class="form-select" data-condition-select>
                            <option value="">-- ยังไม่ระบุ --</option>
                            <option value="ดีมาก" {{ old('condition') === 'ดีมาก' ? 'selected' : '' }}>ดีมาก</option>
                            <option value="ดี" {{ old('condition') === 'ดี' ? 'selected' : '' }}>ดี</option>
                            <option value="พอใช้" {{ old('condition') === 'พอใช้' ? 'selected' : '' }}>พอใช้</option>
                            <option value="ต้องซ่อม" {{ old('condition') === 'ต้องซ่อม' ? 'selected' : '' }}>ต้องซ่อม</option>
                        </select>
                    </div>

                    <input type="hidden" name="ai_analysis" data-ai-analysis-field>

                    <button type="submit" class="btn btn-primary w-100">ลงประกาศ</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    // ซ่อน/แสดงช่องราคา ตามประเภทที่เลือก
    const typeSelect = document.getElementById('typeSelect');
    const priceField = document.getElementById('priceField');

    function togglePrice() {
        priceField.style.display = typeSelect.value === 'sale' ? 'block' : 'none';
    }

    typeSelect.addEventListener('change', togglePrice);
    togglePrice();
</script>
@endsection
