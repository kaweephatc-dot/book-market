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

                <form method="POST" action="{{ route('books.store') }}" enctype="multipart/form-data">
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
                        <label class="form-label">สภาพหนังสือ</label>
                        <input type="text" name="condition" class="form-control" value="{{ old('condition') }}" placeholder="เช่น สภาพดี มีรอยพับเล็กน้อย">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">รายละเอียด</label>
                        <textarea name="description" class="form-control" rows="4">{{ old('description') }}</textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">รูปภาพหนังสือ</label>
                        <input type="file" name="images[]" class="form-control" accept="image/*" multiple>
                        <small class="text-muted">เลือกได้หลายรูป (ไม่เกิน 2MB ต่อรูป)</small>
                    </div>

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