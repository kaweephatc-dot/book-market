@extends('layouts.user-dashboard')

@section('title', 'ข้อความจากผู้ดูแลระบบ')

@section('content')
<h3 class="mb-4">📨 ข้อความจากผู้ดูแลระบบ</h3>

<div class="list-group" data-chat-list data-current-user-id="{{ auth()->id() }}"
     data-report-chat-show-url-template="{{ route('report-chat.show', ['chat' => '__ID__']) }}"
     style="{{ $chats->count() > 0 ? '' : 'display: none;' }}">
    @foreach ($chats as $chat)
        <a href="{{ route('report-chat.show', $chat) }}" class="list-group-item list-group-item-action" data-report-chat-id="{{ $chat->id }}">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <strong>เรื่อง: {{ $chat->report->reason }}</strong>
                    <div class="small text-muted">
                        <span data-message-count>{{ $chat->messages->count() }}</span> ข้อความ
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span class="badge rounded-pill bg-danger {{ $chat->unread_count > 0 ? '' : 'd-none' }}" data-unread-badge>{{ $chat->unread_count }}</span>
                    @if ($chat->is_closed)
                        <span class="badge bg-secondary">ปิดแล้ว</span>
                    @else
                        <span class="badge bg-success">กำลังสนทนา</span>
                    @endif
                </div>
            </div>
        </a>
    @endforeach
</div>

<div class="text-center py-5" data-chat-empty-state style="{{ $chats->count() > 0 ? 'display: none;' : '' }}">
    <p class="text-muted">ยังไม่มีข้อความจากผู้ดูแลระบบ</p>
</div>
@endsection