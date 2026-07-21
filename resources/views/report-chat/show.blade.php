@extends('layouts.app')

@section('title', 'แชทกับผู้ดูแลระบบ')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4>💬 แชทกับผู้ดูแลระบบ</h4>
    <a href="{{ route('report-chat.index') }}" class="btn btn-sm btn-secondary">← กลับ</a>
</div>

{{-- เรื่องที่เกี่ยวข้อง --}}
<div class="alert alert-light border">
    <strong>เรื่อง:</strong> {{ $chat->report->reason }}
</div>

<div class="card">
    <div class="card-body" style="height: 400px; overflow-y: auto;" id="chatBox">
        @forelse ($chat->messages as $msg)
            <div class="mb-2 d-flex {{ $msg->user_id === auth()->id() ? 'justify-content-end' : 'justify-content-start' }}">
                <div class="p-2 rounded {{ $msg->user_id === auth()->id() ? 'bg-primary text-white' : 'bg-light' }}" style="max-width: 70%;">
                    <div class="small fw-bold">{{ $msg->user_id === auth()->id() ? 'คุณ' : '🛡️ ผู้ดูแลระบบ' }}</div>
                    <div>{{ $msg->message }}</div>
                    <div class="small opacity-75">{{ $msg->created_at->diffForHumans() }}</div>
                </div>
            </div>
        @empty
            <p class="text-muted text-center">ยังไม่มีข้อความ</p>
        @endforelse
    </div>

    <div class="card-footer">
        @if ($chat->is_closed)
            <div class="text-muted text-center small">แชทนี้ถูกปิดแล้ว ไม่สามารถส่งข้อความได้</div>
        @else
            <form method="POST" action="{{ route('report-chat.send', $chat) }}" class="d-flex gap-2">
                @csrf
                <input type="text" name="message" class="form-control" placeholder="พิมพ์ข้อความ..." required autofocus>
                <button type="submit" class="btn btn-primary">ส่ง</button>
            </form>
        @endif
    </div>
</div>

<script>
    const chatBox = document.getElementById('chatBox');
    chatBox.scrollTop = chatBox.scrollHeight;
</script>
@endsection