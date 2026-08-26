@extends('admin.layout')

@section('title', 'แชทกับผู้รายงาน')

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <h3 class="mb-0">💬 แชทกับผู้รายงาน</h3>

    {{-- สลับระหว่างรายการหลักกับที่ซ่อนไว้ --}}
    <div class="btn-group">
        <a href="{{ route('admin.report-chats.index') }}"
           class="btn btn-sm {{ $showHidden ? 'btn-outline-primary' : 'btn-primary' }}">รายการหลัก</a>
        <a href="{{ route('admin.report-chats.index', ['view' => 'hidden']) }}"
           class="btn btn-sm {{ $showHidden ? 'btn-primary' : 'btn-outline-primary' }}">
            🙈 ที่ซ่อนไว้
            @if ($hiddenCount > 0)
                <span class="badge bg-light text-dark ms-1">{{ $hiddenCount }}</span>
            @endif
        </a>
    </div>
</div>

@if ($showHidden)
    <p class="text-muted small">แชทที่ซ่อนไว้ยังทำงานตามปกติทุกอย่าง แค่ไม่แสดงในรายการหลักของคุณ (แอดมินคนอื่นยังเห็น)</p>
@endif

<div class="card mb-4">
    <div class="card-header bg-success text-white">กำลังคุยอยู่ ({{ $openChats->count() }})</div>
    <div class="list-group list-group-flush">
        @forelse ($openChats as $chat)
            <div class="list-group-item d-flex justify-content-between align-items-center gap-2">
                <a href="{{ route('admin.report.chat', [$chat->report, $chat->user]) }}" class="text-decoration-none text-dark flex-grow-1">
                    <strong>{{ $chat->user->shop_name ?? $chat->user->name }}</strong>
                    <div class="small text-muted">เรื่อง: {{ $chat->report->reason }}</div>
                </a>
                <div class="d-flex align-items-center gap-2">
                    @if ($chat->unread_count > 0)
                        <span class="badge rounded-pill bg-danger">{{ $chat->unread_count }}</span>
                    @endif
                    <small class="text-muted">{{ $chat->updated_at->diffForHumans() }}</small>
                    <form method="POST" action="{{ route('admin.report.chat.hide', $chat) }}">
                        @csrf
                        <button class="btn btn-sm btn-outline-secondary" title="{{ $showHidden ? 'เอากลับเข้ารายการหลัก' : 'ซ่อนแชทนี้' }}">
                            {{ $showHidden ? '👁️' : '🙈' }}
                        </button>
                    </form>
                </div>
            </div>
        @empty
            <div class="list-group-item text-muted text-center py-4">
                {{ $showHidden ? 'ไม่มีแชทที่ซ่อนไว้ในหมวดนี้' : 'ไม่มีแชทที่กำลังคุยอยู่' }}
            </div>
        @endforelse
    </div>
</div>

<div class="card">
    <div class="card-header bg-secondary text-white">ปิดไปแล้ว ({{ $closedChats->count() }})</div>
    <div class="list-group list-group-flush">
        @forelse ($closedChats as $chat)
            <div class="list-group-item d-flex justify-content-between align-items-center gap-2">
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
                    <form method="POST" action="{{ route('admin.report.chat.hide', $chat) }}">
                        @csrf
                        <button class="btn btn-sm btn-outline-secondary" title="{{ $showHidden ? 'เอากลับเข้ารายการหลัก' : 'ซ่อนแชทนี้' }}">
                            {{ $showHidden ? '👁️' : '🙈' }}
                        </button>
                    </form>
                </div>
            </div>
        @empty
            <div class="list-group-item text-muted text-center py-4">
                {{ $showHidden ? 'ไม่มีแชทที่ซ่อนไว้ในหมวดนี้' : 'ไม่มีแชทที่ปิดไปแล้ว' }}
            </div>
        @endforelse
    </div>
</div>
@endsection
