@extends('admin.layout')

@section('title', 'แชทรายงาน')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4>💬 แชทกับ {{ $chat->user->shop_name ?? $chat->user->name }}</h4>
    <a href="{{ route('admin.reports') }}" class="btn btn-sm btn-secondary">← กลับ</a>
</div>

{{-- ข้อมูลรายงานที่เกี่ยวข้อง --}}
<div class="alert alert-light border">
    <strong>รายงาน:</strong> {{ $chat->report->reason }}
    @if ($chat->report->detail)
        <div class="small text-muted">{{ $chat->report->detail }}</div>
    @endif
</div>

{{-- สถานะแชท + ปุ่มจัดการ --}}
<div class="d-flex gap-2 mb-3">
    @if ($chat->is_closed)
        <span class="badge bg-secondary align-self-center">แชทถูกปิด</span>
        <form method="POST" action="{{ route('admin.report.chat.reopen', $chat) }}">
            @csrf
            <button class="btn btn-sm btn-outline-success">เปิดแชทอีกครั้ง</button>
        </form>
    @else
        <span class="badge bg-success align-self-center">แชทเปิดอยู่</span>
        <form method="POST" action="{{ route('admin.report.chat.close', $chat) }}">
            @csrf
            <button class="btn btn-sm btn-outline-warning">ปิดแชท</button>
        </form>
    @endif

    <form method="POST" action="{{ route('admin.report.chat.delete', $chat) }}" onsubmit="return confirm('ลบแชทนี้ถาวร?')">
        @csrf
        @method('DELETE')
        <button class="btn btn-sm btn-outline-danger">ลบแชท</button>
    </form>
</div>

{{-- กล่องข้อความ --}}
<div class="card">
    <div class="card-body" style="height: 400px; overflow-y: auto;" id="chatBox">
        @forelse ($chat->messages as $msg)
            <div class="mb-2 d-flex {{ $msg->user_id === auth()->id() ? 'justify-content-end' : 'justify-content-start' }}">
                <div class="p-2 rounded {{ $msg->user_id === auth()->id() ? 'bg-primary text-white' : 'bg-light' }}" style="max-width: 70%;">
                    <div class="small fw-bold">{{ $msg->user_id === auth()->id() ? 'คุณ (Admin)' : $msg->user->name }}</div>
                    <div>{{ $msg->message }}</div>
                    <div class="small opacity-75">{{ $msg->created_at->diffForHumans() }}</div>
                </div>
            </div>
        @empty
            <p class="text-muted text-center">ยังไม่มีข้อความ เริ่มบทสนทนาได้เลย</p>
        @endforelse
    </div>

    {{-- ช่องพิมพ์ --}}
    <div class="card-footer">
        @if ($chat->is_closed)
            <div class="text-muted text-center small">แชทนี้ถูกปิดแล้ว ส่งข้อความไม่ได้</div>
        @else
            <form method="POST" action="{{ route('admin.report.chat.send', $chat) }}" class="d-flex gap-2">
                @csrf
                <input type="text" name="message" class="form-control" placeholder="พิมพ์ข้อความ..." required autofocus>
                <button type="submit" class="btn btn-primary">ส่ง</button>
            </form>
        @endif
    </div>
</div>

<script>
    // เลื่อนแชทลงล่างสุดอัตโนมัติ
    const chatBox = document.getElementById('chatBox');
    chatBox.scrollTop = chatBox.scrollHeight;
</script>
@endsection