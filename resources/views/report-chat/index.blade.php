@extends('layouts.user-dashboard')

@section('title', 'ข้อความจากผู้ดูแลระบบ')

@section('content')
<h3 class="mb-4">📨 ข้อความจากผู้ดูแลระบบ</h3>

@if ($chats->count() > 0)
    <div class="list-group" data-chat-list data-current-user-id="{{ auth()->id() }}">
        @foreach ($chats as $chat)
            <a href="{{ route('report-chat.show', $chat) }}" class="list-group-item list-group-item-action" data-report-chat-id="{{ $chat->id }}">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <strong>เรื่อง: {{ $chat->report->reason }}</strong>
                        <div class="small text-muted">
                            <span data-message-count>{{ $chat->messages->count() }}</span> ข้อความ
                        </div>
                    </div>
                    <div>
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
@else
    <div class="text-center py-5">
        <p class="text-muted">ยังไม่มีข้อความจากผู้ดูแลระบบ</p>
    </div>
@endif
@endsection