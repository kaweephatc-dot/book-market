@extends('layouts.user-dashboard')

@section('title', 'โปรไฟล์')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-8">
        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if (session('info'))
            <div class="alert alert-info">{{ session('info') }}</div>
        @endif

        <div class="card shadow-sm">
            <div class="card-body p-4">
                <div class="d-flex align-items-center gap-3 mb-4">
                    @if ($user->avatar)
                        <img src="{{ asset('storage/' . $user->avatar) }}" class="rounded-circle" style="width: 80px; height: 80px; object-fit: cover;" alt="">
                    @else
                        <div class="rounded-circle bg-light d-flex align-items-center justify-content-center" style="width: 80px; height: 80px; font-size: 32px;">👤</div>
                    @endif
                    <div>
                        <h4 class="mb-0">{{ $user->name }}</h4>
                        @if ($user->is_shop)
                            <span class="badge bg-success">🏪 ร้านค้า</span>
                        @else
                            <span class="badge bg-secondary">ผู้ใช้ทั่วไป</span>
                        @endif
                    </div>
                </div>

                <table class="table">
                    <tr>
                        <th style="width: 30%;">อีเมล</th>
                        <td>{{ $user->email }}</td>
                    </tr>
                    @if ($user->is_shop)
                        <tr>
                            <th>ชื่อร้าน</th>
                            <td>{{ $user->shop_name }}</td>
                        </tr>
                    @endif
                    <tr>
                        <th>เบอร์โทร</th>
                        <td>{{ $user->phone ?? '-' }}</td>
                    </tr>
                    <tr>
                        <th>ที่อยู่</th>
                        <td>{{ $user->address ?? '-' }}</td>
                    </tr>
                </table>

                <div class="d-flex gap-2">
                    <a href="{{ route('profile.edit') }}" class="btn btn-primary">แก้ไขโปรไฟล์</a>
                    @if (!$user->is_shop)
                        <a href="{{ route('shop.register') }}" class="btn btn-success">🏪 สมัครเป็นร้านค้า</a>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection