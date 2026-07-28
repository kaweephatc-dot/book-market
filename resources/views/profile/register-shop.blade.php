@extends('layouts.user-dashboard')

@section('title', 'สมัครเป็นร้านค้า')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <h3 class="text-center mb-2">🏪 สมัครเป็นร้านค้า</h3>
                <p class="text-center text-muted mb-4">กรอกข้อมูลร้านเพื่อเริ่มลงขายหนังสือ</p>

                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('shop.register.submit') }}">
                    @csrf

                    <div class="mb-3">
                        <label class="form-label">ชื่อร้าน <span class="text-danger">*</span></label>
                        <input type="text" name="shop_name" class="form-control" value="{{ old('shop_name') }}" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">เบอร์โทรติดต่อ <span class="text-danger">*</span></label>
                        <input type="text" name="phone" class="form-control" value="{{ old('phone') }}" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">ที่อยู่ <span class="text-danger">*</span></label>
                        <textarea name="address" class="form-control" rows="3" required>{{ old('address') }}</textarea>
                    </div>

                    <button type="submit" class="btn btn-success w-100">สมัครเป็นร้านค้า</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection