@extends('layouts.user-dashboard')

@section('title', 'แก้ไขหนังสือ')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <h3 class="mb-4">แก้ไขหนังสือ</h3>

                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('books.update', $book) }}">
                    @csrf
                    @method('PUT')

                    <div class="mb-3">
                        <label class="form-label">ชื่อหนังสือ <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control" value="{{ old('title', $book->title) }}" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">ผู้แต่ง</label>
                        <input type="text" name="author" class="form-control" value="{{ old('author', $book->author) }}">
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">หมวดหมู่ <span class="text-danger">*</span></label>
                            <select name="category" class="form-select" required>
                                @foreach (['นิยาย', 'วิชาการ', 'การ์ตูน', 'ตำราเรียน', 'ธุรกิจ', 'จิตวิทยา', 'อื่นๆ'] as $cat)
                                    <option value="{{ $cat }}" {{ $book->category === $cat ? 'selected' : '' }}>{{ $cat }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">ประเภท <span class="text-danger">*</span></label>
                            <select name="type" class="form-select" id="typeSelect" required>
                                <option value="sale" {{ $book->type === 'sale' ? 'selected' : '' }}>ขาย</option>
                                <option value="exchange" {{ $book->type === 'exchange' ? 'selected' : '' }}>แลกเปลี่ยน</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3" id="priceField">
                        <label class="form-label">ราคา (บาท)</label>
                        <input type="number" name="price" class="form-control" value="{{ old('price', $book->price) }}" min="0" step="0.01">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">สภาพหนังสือ</label>
                        <input type="text" name="condition" class="form-control" value="{{ old('condition', $book->condition) }}">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">รายละเอียด</label>
                        <textarea name="description" class="form-control" rows="4">{{ old('description', $book->description) }}</textarea>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">บันทึกการแก้ไข</button>
                        <a href="{{ route('books.my') }}" class="btn btn-secondary">ยกเลิก</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    const typeSelect = document.getElementById('typeSelect');
    const priceField = document.getElementById('priceField');

    function togglePrice() {
        priceField.style.display = typeSelect.value === 'sale' ? 'block' : 'none';
    }

    typeSelect.addEventListener('change', togglePrice);
    togglePrice();
</script>
@endsection