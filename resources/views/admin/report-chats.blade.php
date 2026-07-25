@extends('admin.layout')

@section('title', 'แชทกับผู้รายงาน')

@section('content')
<h3 class="mb-4">💬 แชทกับผู้รายงาน</h3>

<div class="card mb-4">
    <div class="card-header bg-success text-white">กำลังคุยอยู่ ({{ $openChats->count() }})</div>
    <div class="list-group list-group-flush">
        @forelse ($openChats as $chat)
            <a href="{{ route('admin.report.chat', [$chat->report, $chat->user]) }}" class="list-group-item list-group-item-action">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <strong>{{ $chat->user->shop_name ?? $chat->user->name }}</strong>
                        <div class="small text-muted">เรื่อง: {{ $chat->report->reason }}</div>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        @if ($chat->unread_count > 0)
                            <span class="badge rounded-pill bg-danger">{{ $chat->unread_count }}</span>
                        @endif
                        <small class="text-muted">{{ $chat->updated_at->diffForHumans() }}</small>
                    </div>
                </div>
            </a>
        @empty
            <div class="list-group-item text-muted text-center py-4">ไม่มีแชทที่กำลังคุยอยู่</div>
        @endforelse
    </div>
</div>

<div class="card">
    <div class="card-header bg-secondary text-white">ปิดไปแล้ว ({{ $closedChats->count() }})</div>
    <div class="list-group list-group-flush">
        @forelse ($closedChats as $chat)
            <div class="list-group-item d-flex justify-content-between align-items-center">
                <a href="{{ route('admin.report.chat', [$chat->report, $chat->user]) }}" class="text-decoration-none text-dark flex-grow-1">
                    <strong>{{ $chat->user->shop_name ?? $chat->user->name }}</strong>
                    <div class="small text-muted">เรื่อง: {{ $chat->report->reason }}</div>
                </a>
                <div class="d-flex align-items-center gap-2">
                    <small class="text-muted">{{ $chat->updated_at->diffForHumans() }}</small>
                    <form method="POST" action="{{ route('admin.report.chat.reopen', $chat) }}">
                        @csrf
                        <button class="btn btn-sm btn-outline-success">เปิดแชทอีกครั้ง</button>
                    </form>
                </div>
            </div>
        @empty
            <div class="list-group-item text-muted text-center py-4">ไม่มีแชทที่ปิดไปแล้ว</div>
        @endforelse
    </div>
</div>
@endsection
