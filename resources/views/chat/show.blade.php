@extends('layouts.app')

@section('title', 'สนทนา')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-8">

        {{-- หัวข้อ: ข้อมูลหนังสือ --}}
        <div class="card mb-3">
            <div class="card-body d-flex align-items-center gap-3">
                @if ($conversation->book->images->count() > 0)
                    <img src="{{ asset('storage/' . $conversation->book->images->first()->image_path) }}" style="width: 60px; height: 60px; object-fit: cover;" class="rounded" alt="">
                @endif
                <div class="flex-grow-1">
                    <strong>{{ $conversation->book->title }}</strong>
                    @if ($conversation->book->type === 'sale')
                        <div class="text-primary">฿{{ number_format($conversation->book->price, 2) }}</div>
                    @else
                        <span class="badge bg-info">แลกเปลี่ยน</span>
                    @endif
                </div>
                <a href="{{ route('books.show', $conversation->book) }}" class="btn btn-sm btn-outline-secondary">ดูหนังสือ</a>
            </div>
        </div>

        {{-- ช่องทางการชำระเงินของผู้ขาย (เฉพาะผู้ซื้อเห็น + เฉพาะหนังสือขาย) --}}
        @if ($isBuyer && $conversation->book->type === 'sale' && $conversation->seller->paymentMethods->count() > 0)
            <div class="card mb-3 border-success">
                <div class="card-body">
                    <h6 class="card-title">💳 ช่องทางการชำระเงินของผู้ขาย</h6>
                    <div class="row g-2">
                        @foreach ($conversation->seller->paymentMethods as $method)
                            <div class="col-md-6">
                                <div class="border rounded p-2">
                                    @if ($method->type === 'qr')
                                        <div class="small fw-bold mb-1">📱 QR Code</div>
                                        <img src="{{ asset('storage/' . $method->qr_image) }}" class="img-fluid rounded" style="max-height: 180px;" alt="QR Code">
                                    @else
                                        <div class="small fw-bold mb-1">🏦 {{ $method->bank_name }}</div>
                                        <div class="small">เลขบัญชี: {{ $method->account_number }}</div>
                                        <div class="small">ชื่อบัญชี: {{ $method->account_name }}</div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif

        {{-- กล่องข้อความ --}}
        <div class="card">
            <div class="card-body" id="chatBox" style="height: 400px; overflow-y: auto;">
                @forelse ($conversation->messages as $message)
                    @php $isMe = $message->user_id === auth()->id(); @endphp
                    <div class="d-flex mb-2 {{ $isMe ? 'justify-content-end' : 'justify-content-start' }}">
                        <div class="p-2 rounded {{ $isMe ? 'bg-primary text-white' : 'bg-light' }}" style="max-width: 70%;">
                            <div>{{ $message->message }}</div>
                            <div class="small {{ $isMe ? 'text-white-50' : 'text-muted' }}" style="font-size: 10px;">
                                {{ $message->created_at->format('H:i') }}
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="text-center text-muted py-5">เริ่มการสนทนาได้เลย</div>
                @endforelse
            </div>

            {{-- ฟอร์มส่งข้อความ --}}
            <div class="card-footer">
                <form method="POST" action="{{ route('chat.send', $conversation) }}" class="d-flex gap-2">
                    @csrf
                    <input type="text" name="message" class="form-control" placeholder="พิมพ์ข้อความ..." required autofocus>
                    <button type="submit" class="btn btn-primary">ส่ง</button>
                </form>
            </div>
        </div>

        <a href="{{ route('chat.index') }}" class="btn btn-link mt-2">← กลับไปรายการแชท</a>
    </div>
</div>

<script>
    // เลื่อนไปข้อความล่าสุดอัตโนมัติ
    const chatBox = document.getElementById('chatBox');
    chatBox.scrollTop = chatBox.scrollHeight;
</script>
@endsection