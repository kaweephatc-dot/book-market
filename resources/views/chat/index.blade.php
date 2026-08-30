@extends('layouts.user-dashboard')

@section('title', 'ข้อความของฉัน')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-9">
        <h3 class="mb-3">💬 ข้อความของฉัน</h3>

        {{-- แท็บมุมมอง: กล่องหลัก / ซ่อนไว้ / ถังขยะ --}}
        <div class="chat-views mb-3">
            @php
                $tabs = [
                    'inbox' => ['label' => '💬 กล่องหลัก', 'count' => $counts['inbox']],
                    'hidden' => ['label' => '🙈 ซ่อนไว้', 'count' => $counts['hidden']],
                    'trash' => ['label' => '🗑️ ถังขยะ', 'count' => $counts['trash']],
                ];
            @endphp
            @foreach ($tabs as $key => $tab)
                <a class="chat-view-tab {{ $view === $key ? 'active' : '' }}"
                   href="{{ $key === 'inbox' ? route('chat.index') : route('chat.index', ['view' => $key]) }}">
                    {{ $tab['label'] }}
                    @if ($tab['count'] > 0)
                        <span class="chat-view-count">{{ $tab['count'] }}</span>
                    @endif
                </a>
            @endforeach
        </div>

        @if ($view === 'trash')
            <p class="text-muted small mb-3">
                แชทในถังขยะจะถูกลบถาวรอัตโนมัติหลังครบ {{ $trashDays }} วัน กู้คืนได้ก่อนหมดเวลา
            </p>
        @elseif ($view === 'hidden')
            <p class="text-muted small mb-3">
                แชทที่ซ่อนไว้ยังได้รับข้อความตามปกติ แค่ไม่แสดงในกล่องหลัก
            </p>
        @endif

        @php
            // นับจำนวนที่ปักหมุด ไว้ตัดสินใจว่าจะโชว์ปุ่มเลื่อนขึ้น/ลงไหม
            $pinnedTotal = $conversations->whereNotNull('pinned_at')->count();
            $pinnedIndex = 0;
        @endphp

        <div class="chat-list" data-chat-list data-current-user-id="{{ auth()->id() }}"
             data-chat-view="{{ $view }}"
             data-excluded-ids="{{ implode(',', $excludedIds) }}"
             data-chat-show-url-template="{{ route('chat.show', ['conversation' => '__ID__']) }}"
             style="{{ $conversations->count() > 0 ? '' : 'display: none;' }}">

            @foreach ($conversations as $conv)
                @php
                    // ระบุว่าคู่สนทนาคือใคร (ถ้าเราเป็นผู้ซื้อ คู่สนทนาคือผู้ขาย)
                    $other = $conv->buyer_id === auth()->id() ? $conv->seller : $conv->buyer;
                    $isPinned = (bool) $conv->pinned_at;
                    $stamp = $conv->latest_message_at ?? $conv->updated_at;
                    if ($isPinned) { $pinnedIndex++; }

                    // ใช้ตัวคำนวณตัวเดียวกับ ConversationUserState::daysLeftInTrash()
                    // จะได้ไม่ต้องมี logic วันหมดอายุถังขยะซ้ำสองที่
                    // ($conv มาจาก join จึงถือแค่คอลัมน์ดิบ ไม่ใช่ตัว state จริง เลยห่อเป็น instance ชั่วคราว)
                    $daysLeft = $conv->trashed_at
                        ? (new \App\Models\ConversationUserState(['trashed_at' => $conv->trashed_at]))->daysLeftInTrash()
                        : null;
                @endphp

                <div class="chat-item {{ $isPinned ? 'is-pinned' : '' }}"
                     data-conversation-id="{{ $conv->id }}"
                     @if ($isPinned) data-pinned="1" @endif>

                    <a href="{{ route('chat.show', $conv) }}" class="chat-item-link">
                        @if ($conv->book->images->count() > 0)
                            <img src="{{ asset('storage/' . $conv->book->coverImage()->image_path) }}" class="chat-thumb" alt="">
                        @else
                            <div class="chat-thumb chat-thumb-empty">📚</div>
                        @endif

                        <div class="chat-item-body">
                            <div class="chat-item-head">
                                <strong class="chat-item-title">
                                    @if ($isPinned)<span class="chat-pin-mark" title="ปักหมุดไว้">📌</span>@endif
                                    {{ $conv->book->title }}
                                </strong>
                                <span class="badge rounded-pill bg-danger {{ $conv->unread_count > 0 ? '' : 'd-none' }}" data-unread-badge>{{ $conv->unread_count }}</span>
                            </div>

                            <div class="chat-item-sub">กับ {{ $other->shop_name ?? $other->name }}</div>

                            <div class="chat-item-preview {{ $conv->unread_count > 0 ? 'is-unread' : '' }} {{ $conv->latest_message_text ? '' : 'd-none' }}" data-latest-message>{{ $conv->latest_message_text ?? '' }}</div>

                            <div class="chat-item-foot">
                                <span data-updated-at>{{ \Carbon\Carbon::parse($stamp)->diffForHumans() }}</span>
                                @if ($daysLeft !== null)
                                    <span class="chat-trash-left">เหลืออีก {{ $daysLeft }} วันก่อนลบถาวร</span>
                                @endif
                            </div>
                        </div>
                    </a>

                    {{-- เมนูจัดการของแต่ละห้อง --}}
                    <div class="chat-item-actions">
                        @if ($view === 'inbox' && $isPinned)
                            <div class="chat-pin-move">
                                <form method="POST" action="{{ route('chat.pin.move', $conv) }}">
                                    @csrf
                                    <input type="hidden" name="direction" value="up">
                                    <button type="submit" class="pin-move-btn" title="เลื่อนขึ้น" {{ $pinnedIndex === 1 ? 'disabled' : '' }}>▲</button>
                                </form>
                                <form method="POST" action="{{ route('chat.pin.move', $conv) }}">
                                    @csrf
                                    <input type="hidden" name="direction" value="down">
                                    <button type="submit" class="pin-move-btn" title="เลื่อนลง" {{ $pinnedIndex === $pinnedTotal ? 'disabled' : '' }}>▼</button>
                                </form>
                            </div>
                        @endif

                        <div class="dropdown">
                            <button class="chat-menu-btn" type="button" data-bs-toggle="dropdown" data-bs-boundary="viewport" aria-expanded="false" aria-label="ตัวเลือกแชท">⋯</button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                @if ($view === 'trash')
                                    <li>
                                        <form method="POST" action="{{ route('chat.restore', $conv) }}">
                                            @csrf
                                            <button type="submit" class="dropdown-item">↩️ กู้คืนแชท</button>
                                        </form>
                                    </li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <form method="POST" action="{{ route('chat.purge', $conv) }}"
                                              data-confirm="ลบแชทนี้ถาวรเลยไหม? ประวัติข้อความฝั่งคุณจะหายทั้งหมดและกู้คืนไม่ได้">
                                            @csrf
                                            <button type="submit" class="dropdown-item text-danger">🔥 ลบถาวรทันที</button>
                                        </form>
                                    </li>
                                @else
                                    <li>
                                        <form method="POST" action="{{ route('chat.pin', $conv) }}">
                                            @csrf
                                            <button type="submit" class="dropdown-item">
                                                {{ $isPinned ? '📌 เลิกปักหมุด' : '📌 ปักหมุดไว้บนสุด' }}
                                            </button>
                                        </form>
                                    </li>
                                    <li>
                                        <form method="POST" action="{{ route('chat.hide', $conv) }}">
                                            @csrf
                                            <button type="submit" class="dropdown-item">
                                                {{ $conv->hidden_at ? '👁️ เอากลับเข้ากล่องหลัก' : '🙈 ซ่อนแชทนี้' }}
                                            </button>
                                        </form>
                                    </li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <form method="POST" action="{{ route('chat.trash', $conv) }}"
                                              data-confirm="ย้ายแชทนี้ลงถังขยะ? กู้คืนได้ภายใน {{ $trashDays }} วัน">
                                            @csrf
                                            <button type="submit" class="dropdown-item text-danger">🗑️ ลบแชท</button>
                                        </form>
                                    </li>
                                @endif
                            </ul>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- แม่แบบเมนูสำหรับแถวที่ JS สร้างสดตอนมีคนทักเข้ามาใหม่
             ให้ Blade เป็นคนออก URL + CSRF token เพราะ layout ฝั่ง user ไม่มี meta csrf-token --}}
        @if ($view === 'inbox')
            <template id="chatMenuTemplate">
                <div class="chat-item-actions">
                    <div class="dropdown">
                        <button class="chat-menu-btn" type="button" data-bs-toggle="dropdown" data-bs-boundary="viewport" aria-expanded="false" aria-label="ตัวเลือกแชท">⋯</button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li>
                                <form method="POST" data-action-template="{{ route('chat.pin', ['conversation' => '__ID__']) }}">
                                    @csrf
                                    <button type="submit" class="dropdown-item">📌 ปักหมุดไว้บนสุด</button>
                                </form>
                            </li>
                            <li>
                                <form method="POST" data-action-template="{{ route('chat.hide', ['conversation' => '__ID__']) }}">
                                    @csrf
                                    <button type="submit" class="dropdown-item">🙈 ซ่อนแชทนี้</button>
                                </form>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <form method="POST" data-action-template="{{ route('chat.trash', ['conversation' => '__ID__']) }}"
                                      data-confirm="ย้ายแชทนี้ลงถังขยะ? กู้คืนได้ภายใน {{ $trashDays }} วัน">
                                    @csrf
                                    <button type="submit" class="dropdown-item text-danger">🗑️ ลบแชท</button>
                                </form>
                            </li>
                        </ul>
                    </div>
                </div>
            </template>
        @endif

        <div class="chat-empty" data-chat-empty-state style="{{ $conversations->count() > 0 ? 'display: none;' : '' }}">
            @if ($view === 'hidden')
                <p class="text-muted mb-0">ยังไม่มีแชทที่ซ่อนไว้</p>
            @elseif ($view === 'trash')
                <p class="text-muted mb-0">ถังขยะว่างเปล่า</p>
            @else
                <p class="text-muted">ยังไม่มีข้อความ</p>
                <a href="{{ route('home') }}" class="btn btn-primary">เลือกดูหนังสือ</a>
            @endif
        </div>
    </div>
</div>

<style>
    .chat-views {
        display: inline-flex;
        gap: .25rem;
        padding: .25rem;
        border-radius: .75rem;
        background: #eef1f7;
        max-width: 100%;
        overflow-x: auto;
        scrollbar-width: none;
    }
    .chat-views::-webkit-scrollbar { display: none; }
    .chat-view-tab {
        display: inline-flex;
        align-items: center;
        gap: .4rem;
        padding: .45rem .9rem;
        border-radius: .55rem;
        color: #5a6478;
        font-size: .9rem;
        text-decoration: none;
        white-space: nowrap;
        transition: background .15s ease, color .15s ease;
    }
    .chat-view-tab:hover { color: var(--bs-primary); }
    .chat-view-tab.active {
        background: #fff;
        color: var(--bs-primary);
        font-weight: 600;
        box-shadow: 0 1px 3px rgba(31, 45, 110, .12);
    }
    .chat-view-count {
        min-width: 1.3rem;
        padding: 0 .35rem;
        border-radius: 1rem;
        background: #dfe4f0;
        color: #5a6478;
        font-size: .72rem;
        line-height: 1.3rem;
        text-align: center;
    }
    .chat-view-tab.active .chat-view-count {
        background: color-mix(in srgb, var(--bs-primary) 15%, #fff);
        color: var(--bs-primary);
    }

    .chat-list {
        display: flex;
        flex-direction: column;
        gap: .5rem;
    }
    .chat-item {
        display: flex;
        align-items: stretch;
        border: 1px solid #e9edf5;
        border-radius: .85rem;
        background: #fff;
        /* ห้ามใส่ overflow: hidden ตรงนี้ เมนู ⋯ จะโดนตัดจนกดเลือกไม่ได้
           มุมมนใช้ border-radius ของลูกแต่ละตัวแทน */
        position: relative;
        transition: border-color .15s ease, box-shadow .15s ease;
    }
    .chat-item:hover {
        border-color: color-mix(in srgb, var(--bs-primary) 35%, #fff);
        box-shadow: 0 .3rem .9rem rgba(31, 45, 110, .08);
    }
    /* แชทที่ปักหมุดมีแถบสีด้านซ้าย ให้กวาดตาเจอได้ทันที */
    .chat-item.is-pinned {
        border-left: 3px solid var(--bs-primary);
        background: #fbfcff;
    }

    .chat-item-link {
        display: flex;
        align-items: center;
        gap: .85rem;
        flex: 1 1 auto;
        min-width: 0;
        padding: .8rem .9rem;
        color: inherit;
        text-decoration: none;
    }
    .chat-item-link:hover { color: inherit; }

    .chat-thumb {
        flex: 0 0 52px;
        width: 52px;
        height: 52px;
        border-radius: .55rem;
        object-fit: cover;
        background: #f1f3f9;
    }
    .chat-thumb-empty {
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 22px;
    }

    .chat-item-body {
        flex: 1 1 auto;
        min-width: 0;
    }
    .chat-item-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: .5rem;
    }
    .chat-item-title {
        min-width: 0;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        color: #2b2f45;
    }
    .chat-pin-mark { font-size: .8rem; }
    .chat-item-sub {
        font-size: .8rem;
        color: #8a92a3;
    }
    .chat-item-preview {
        font-size: .85rem;
        color: #8a92a3;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .chat-item-preview.is-unread {
        color: #2b2f45;
        font-weight: 600;
    }
    .chat-item-foot {
        display: flex;
        flex-wrap: wrap;
        gap: .5rem;
        margin-top: .15rem;
        font-size: .74rem;
        color: #9aa2b1;
    }
    .chat-trash-left { color: #c1666f; }

    .chat-item-actions {
        display: flex;
        align-items: center;
        gap: .25rem;
        padding-right: .5rem;
    }
    .chat-pin-move {
        display: flex;
        flex-direction: column;
        gap: 2px;
    }
    .pin-move-btn {
        width: 1.5rem;
        height: 1.1rem;
        padding: 0;
        border: 0;
        border-radius: .25rem;
        background: #eef1f7;
        color: #5a6478;
        font-size: .6rem;
        line-height: 1;
    }
    .pin-move-btn:hover:not(:disabled) { background: #dfe4f0; color: var(--bs-primary); }
    .pin-move-btn:disabled { opacity: .35; }

    .chat-menu-btn {
        width: 2rem;
        height: 2rem;
        border: 0;
        border-radius: .5rem;
        background: transparent;
        color: #8a92a3;
        font-size: 1.1rem;
        line-height: 1;
    }
    .chat-menu-btn:hover { background: #eef1f7; color: var(--bs-primary); }

    /* ปุ่มในเมนูเป็น <button> ในฟอร์ม ต้องบังคับให้กว้างเต็มเหมือน dropdown-item ปกติ */
    .dropdown-menu form { margin: 0; }
    .dropdown-menu form .dropdown-item { width: 100%; text-align: start; }

    .chat-empty {
        text-align: center;
        padding: 3rem 1rem;
        border: 1px dashed #d8dee9;
        border-radius: 1rem;
        background: #fbfcfe;
    }

    @media (max-width: 575.98px) {
        .chat-views { width: 100%; }
        .chat-item-link { padding: .7rem; gap: .65rem; }
        .chat-thumb { flex-basis: 44px; width: 44px; height: 44px; }
    }
</style>
@endsection
