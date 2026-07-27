@extends('admin.layout')

@section('title', 'จัดการหนังสือ')

@section('content')
<h3 class="mb-4">📚 จัดการหนังสือ</h3>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead>
                <tr>
                    <th>รูป</th>
                    <th>ชื่อหนังสือ</th>
                    <th>ผู้ขาย</th>
                    <th>ประเภท</th>
                    <th>สถานะ</th>
                    <th>จัดการ</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($books as $book)
                    <tr>
                        <td>
                            @if ($book->images->count() > 0)
                                <img src="{{ asset('storage/' . $book->coverImage()->image_path) }}" style="width: 45px; height: 45px; object-fit: cover;" class="rounded" alt="">
                            @else
                                <div class="bg-light rounded d-flex align-items-center justify-content-center" style="width: 45px; height: 45px;">📚</div>
                            @endif
                        </td>
                        <td>{{ $book->title }}</td>
                        <td>{{ $book->user->shop_name ?? $book->user->name }}</td>
                        <td>
                            @if ($book->type === 'sale')
                                <span class="badge bg-success">ขาย</span>
                            @else
                                <span class="badge bg-info">แลกเปลี่ยน</span>
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
                                <a href="{{ route('books.show', $book) }}" target="_blank" class="btn btn-sm btn-outline-secondary">ดู</a>
                                <form method="POST" action="{{ route('admin.books.delete', $book) }}" onsubmit="return confirm('ต้องการลบหนังสือนี้?')">
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
    {{ $books->links() }}
</div>
@endsection