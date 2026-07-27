@extends('layouts.app')

@section('title', 'ข้อความของฉัน')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-8">
        <h3 class="mb-4">💬 ข้อความของฉัน</h3>

        @if ($conversations->count() > 0)
            <div class="list-group">
                @foreach ($conversations as $conv)
                    @php
                        // ระบุว่าคู่สนทนาคือใคร (ถ้าเราเป็นผู้ซื้อ คู่สนทนาคือผู้ขาย)
                        $other = $conv->buyer_id === auth()->id() ? $conv->seller : $conv->buyer;
                    @endphp
                    <a href="{{ route('chat.show', $conv) }}" class="list-group-item list-group-item-action">
                        <div class="d-flex align-items-center gap-3">
                            @if ($conv->book->images->count() > 0)
                                <img src="{{ asset('storage/' . $conv->book->coverImage()->image_path) }}" style="width: 50px; height: 50px; object-fit: cover;" class="rounded" alt="">
                            @else
                                <div class="bg-light rounded d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">📚</div>
                            @endif
                            <div class="flex-grow-1">
                                <div class="d-flex justify-content-between align-items-center">
                                    <strong>{{ $conv->book->title }}</strong>
                                    <div class="d-flex align-items-center gap-2">
                                        @if ($conv->unread_count > 0)
                                            <span class="badge rounded-pill bg-danger">{{ $conv->unread_count }}</span>
                                        @endif
                                        <small class="text-muted">{{ $conv->updated_at->diffForHumans() }}</small>
                                    </div>
                                </div>
                                <div class="small text-muted">กับ {{ $other->shop_name ?? $other->name }}</div>
                                @if ($conv->latestMessage)
                                    <div class="small text-truncate {{ $conv->unread_count > 0 ? 'fw-bold' : 'text-muted' }}">{{ $conv->latestMessage->message }}</div>
                                @endif
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>
        @else
            <div class="text-center py-5">
                <p class="text-muted">ยังไม่มีข้อความ</p>
                <a href="{{ route('home') }}" class="btn btn-primary">เลือกดูหนังสือ</a>
            </div>
        @endif
    </div>
</div>
@endsection