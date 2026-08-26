@extends('layouts.user-dashboard')

@section('title', 'หนังสือของฉัน')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h3>📚 หนังสือของฉัน</h3>
    <a href="{{ route('books.create') }}" class="btn btn-primary">➕ ลงประกาศใหม่</a>
</div>


@if ($books->count() > 0)
    <div class="table-responsive">
        <table class="table align-middle">
            <thead>
                <tr>
                    <th>รูป</th>
                    <th>ชื่อหนังสือ</th>
                    <th>ประเภท</th>
                    <th>ราคา</th>
                    <th>สถานะ</th>
                    <th>จัดการ</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($books as $book)
                    <tr>
                        <td>
                            @if ($book->images->count() > 0)
                                <img src="{{ asset('storage/' . $book->coverImage()->image_path) }}" style="width: 50px; height: 50px; object-fit: cover;" class="rounded" alt="">
                            @else
                                <div class="bg-light rounded d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">📚</div>
                            @endif
                        </td>
                        <td>{{ $book->title }}</td>
                        <td>
                            @if ($book->type === 'sale')
                                <span class="badge bg-success">ขาย</span>
                            @else
                                <span class="badge bg-info">แลกเปลี่ยน</span>
                            @endif
                        </td>
                        <td>
                            @if ($book->type === 'sale')
                                ฿{{ number_format($book->price, 2) }}
                            @else
                                -
                            @endif
                        </td>
                        <td>
                            @if ($book->status === 'available')
                                <span class="badge bg-primary">กำลังขาย</span>
                            @elseif ($book->status === 'sold')
                                <span class="badge bg-secondary">ขายแล้ว</span>
                            @else
                                <span class="badge bg-secondary">แลกแล้ว</span>
                            @endif
                        </td>
                        <td>
                            <div class="d-flex gap-1">
                                <a href="{{ route('books.show', $book) }}" class="btn btn-sm btn-outline-secondary">ดู</a>
                                <a href="{{ route('books.edit', $book) }}" class="btn btn-sm btn-outline-primary">แก้ไข</a>

                                @if ($book->status === 'available')
                                    <form method="POST" action="{{ route('books.markSold', $book) }}" data-confirm="ยืนยันว่าขาย/แลกเรียบร้อยแล้ว?">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline-success">ขายแล้ว</button>
                                    </form>
                                @endif

                                <form method="POST" action="{{ route('books.destroy', $book) }}" data-confirm="ต้องการลบหนังสือเล่มนี้?">
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
@else
    <div class="text-center py-5">
        <p class="text-muted">คุณยังไม่มีหนังสือที่ลงประกาศ</p>
        <a href="{{ route('books.create') }}" class="btn btn-primary">ลงประกาศเล่มแรก</a>
    </div>
@endif
@endsection