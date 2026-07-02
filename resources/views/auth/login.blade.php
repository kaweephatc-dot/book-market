@extends('layouts.app')

@section('title', 'เข้าสู่ระบบ')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <h3 class="text-center mb-4">เข้าสู่ระบบ</h3>

                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('login.submit') }}">
                    @csrf

                    <div class="mb-3">
                        <label class="form-label">อีเมล</label>
                        <input type="email" name="email" class="form-control" value="{{ old('email') }}" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">รหัสผ่าน</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>

                    <button type="submit" class="btn btn-primary w-100">เข้าสู่ระบบ</button>
                </form>

                <p class="text-center mt-3 mb-0">
                    ยังไม่มีบัญชี? <a href="{{ route('register') }}">สมัครสมาชิก</a>
                </p>
            </div>
        </div>
    </div>
</div>
@endsection