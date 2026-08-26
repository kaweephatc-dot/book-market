@extends('layouts.user-dashboard')

@section('title', 'แก้ไขโปรไฟล์')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <h3 class="mb-4">แก้ไขโปรไฟล์</h3>

                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')

                    <div class="mb-3 text-center">
                        @if ($user->avatar)
                            <img src="{{ asset('storage/' . $user->avatar) }}" class="rounded-circle mb-2" style="width: 100px; height: 100px; object-fit: cover;" alt="">
                        @else
                            <div class="rounded-circle bg-light d-flex align-items-center justify-content-center mx-auto mb-2" style="width: 100px; height: 100px; font-size: 40px;">👤</div>
                        @endif
                        <input type="file" name="avatar" class="form-control" accept="image/*">
                        <small class="text-muted">รูปโปรไฟล์ (ไม่เกิน 2MB)</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">ชื่อ <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" value="{{ old('name', $user->name) }}" required>
                    </div>

                    @if ($user->is_shop)
                        {{-- ชื่อร้านย้ายไปแก้ที่แท็บ "ข้อมูลร้านค้า" เพื่อไม่ให้มี 2 ที่แก้คอลัมน์เดียวกัน --}}
                        <div class="alert alert-info small">
                            🏪 ข้อมูลร้านค้า (ชื่อร้าน โลโก้ เบอร์ติดต่อ ที่อยู่ร้าน)
                            แก้ได้ที่ <a href="{{ route('profile.index', ['tab' => 'shop']) }}">แท็บข้อมูลร้านค้า</a>
                        </div>
                    @endif

                    <div class="mb-3">
                        <label class="form-label">เบอร์โทร</label>
                        <input type="text" name="phone" class="form-control" value="{{ old('phone', $user->phone) }}">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">ที่อยู่</label>
                        <textarea name="address" class="form-control" rows="3">{{ old('address', $user->address) }}</textarea>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">บันทึก</button>
                        <a href="{{ route('profile.index') }}" class="btn btn-secondary">ยกเลิก</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection