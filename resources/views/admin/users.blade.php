@extends('admin.layout')

@section('title', 'จัดการผู้ใช้')

@section('content')
<h3 class="mb-4">👥 จัดการผู้ใช้</h3>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead>
                <tr>
                    <th>ชื่อ</th>
                    <th>อีเมล</th>
                    <th>ประเภท</th>
                    <th>สถานะ</th>
                    <th>จัดการ</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($users as $user)
                    <tr>
                        <td>{{ $user->name }}</td>
                        <td>{{ $user->email }}</td>
                        <td>
                            @if ($user->is_shop)
                                <span class="badge bg-success">ร้านค้า</span>
                            @else
                                <span class="badge bg-secondary">ผู้ใช้ทั่วไป</span>
                            @endif
                        </td>
                        <td>
                            @if ($user->is_banned)
                                <span class="badge bg-danger">ถูกแบน</span>
                            @else
                                <span class="badge bg-primary">ปกติ</span>
                            @endif
                        </td>
                        <td>
                            <div class="d-flex gap-1">
                                <form method="POST" action="{{ route('admin.users.ban', $user) }}">
                                    @csrf
                                    <button type="submit" class="btn btn-sm {{ $user->is_banned ? 'btn-outline-success' : 'btn-outline-warning' }}">
                                        {{ $user->is_banned ? 'ปลดแบน' : 'แบน' }}
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('admin.users.delete', $user) }}" onsubmit="return confirm('ต้องการลบผู้ใช้นี้?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">ลบ</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3">
    {{ $users->links() }}
</div>
@endsection