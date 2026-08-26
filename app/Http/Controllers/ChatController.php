<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Events\MessageSent;
use App\Models\Book;
use App\Models\Conversation;
use App\Models\ConversationUserState;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ChatController extends Controller
{
    // แสดงรายการแชท แยกเป็น 3 มุมมอง: กล่องหลัก / ซ่อนไว้ / ถังขยะ
    public function index(Request $request)
    {
        $userId = Auth::id();

        // ล้างถังขยะที่ครบกำหนดก่อนเสมอ ทำตรงนี้เผื่อเครื่องไม่ได้เปิด scheduler ไว้
        // (มี command chat:purge-trash ให้ตั้ง cron ได้ด้วย)
        self::purgeExpiredTrash($userId);

        $view = in_array($request->query('view'), ['hidden', 'trash'], true)
            ? $request->query('view')
            : 'inbox';

        $counts = [
            'inbox' => $this->conversationQuery($userId, 'inbox')->count(),
            'hidden' => $this->conversationQuery($userId, 'hidden')->count(),
            'trash' => $this->conversationQuery($userId, 'trash')->count(),
        ];

        $conversations = $this->conversationQuery($userId, $view)
            ->addSelect([
                's.pinned_at',
                's.pin_order',
                's.hidden_at',
                's.trashed_at',
                's.cleared_before_message_id',
            ])
            // ข้อความล่าสุด/จำนวนที่ยังไม่อ่าน ต้องนับเฉพาะที่ user คนนี้ยังมีสิทธิ์เห็น
            // (ห้องที่เคยถูกลบถาวรจะเห็นเฉพาะข้อความที่ id ใหม่กว่าเส้นแบ่ง)
            ->selectRaw('(select m.message from messages m
                          where m.conversation_id = conversations.id
                            and (s.cleared_before_message_id is null or m.id > s.cleared_before_message_id)
                          order by m.id desc limit 1) as latest_message_text')
            ->selectRaw('(select m.created_at from messages m
                          where m.conversation_id = conversations.id
                            and (s.cleared_before_message_id is null or m.id > s.cleared_before_message_id)
                          order by m.id desc limit 1) as latest_message_at')
            ->selectRaw('(select count(*) from messages m
                          where m.conversation_id = conversations.id
                            and m.user_id <> ?
                            and m.is_read = 0
                            and (s.cleared_before_message_id is null or m.id > s.cleared_before_message_id)) as unread_count', [$userId])
            ->with(['book.images', 'buyer', 'seller'])
            ->get();

        // ห้องที่ซ่อน/อยู่ถังขยะ ไม่ควรให้ JS แอบสร้างแถวใหม่ตอนมีข้อความเข้ามา
        $excludedIds = ConversationUserState::where('user_id', $userId)
            ->where(function ($q) {
                $q->whereNotNull('hidden_at')->orWhereNotNull('trashed_at');
            })
            ->pluck('conversation_id')
            ->all();

        $trashDays = ConversationUserState::TRASH_DAYS;

        return view('chat.index', compact('conversations', 'view', 'counts', 'excludedIds', 'trashDays'));
    }

    /**
     * ฐาน query ของรายการแชท: join สถานะรายคน + กรองตามมุมมอง + เรียงลำดับ
     * แยกออกมาเพราะทั้งการนับจำนวนและการดึงรายการใช้เงื่อนไขชุดเดียวกัน
     */
    private function conversationQuery(int $userId, string $view)
    {
        $query = Conversation::query()
            ->select('conversations.*')
            ->leftJoin('conversation_user_states as s', function ($join) use ($userId) {
                $join->on('s.conversation_id', '=', 'conversations.id')
                    ->where('s.user_id', '=', $userId);
            })
            ->where(function ($q) use ($userId) {
                $q->where('conversations.buyer_id', $userId)
                    ->orWhere('conversations.seller_id', $userId);
            })
            // ลบถาวรไปแล้วและยังไม่มีข้อความใหม่เข้ามา = ถือว่าห้องนี้ไม่มีอยู่สำหรับเรา
            ->whereRaw('(s.cleared_before_message_id is null or exists (
                            select 1 from messages m
                            where m.conversation_id = conversations.id
                              and m.id > s.cleared_before_message_id))');

        if ($view === 'hidden') {
            return $query->whereNotNull('s.hidden_at')
                ->whereNull('s.trashed_at')
                ->orderByDesc('conversations.updated_at');
        }

        if ($view === 'trash') {
            return $query->whereNotNull('s.trashed_at')
                ->orderByDesc('s.trashed_at');
        }

        // กล่องหลัก: ปักหมุดขึ้นก่อนเสมอ แล้วเรียงตามลำดับที่ผู้ใช้จัดเอง
        // (s.pinned_at is null) ให้ 0 กับห้องที่ปักหมุด จึงลอยขึ้นบนสุดเมื่อเรียงจากน้อยไปมาก
        return $query->whereNull('s.hidden_at')
            ->whereNull('s.trashed_at')
            ->orderByRaw('(s.pinned_at is null) asc')
            ->orderByRaw('s.pin_order asc')
            ->orderByDesc('conversations.updated_at');
    }

    /**
     * ลบถาวรรายการที่อยู่ในถังขยะครบกำหนดแล้ว
     * ไม่ลบแถว conversation ทิ้ง เพราะคู่สนทนาอีกฝั่งยังต้องเห็นประวัติของเขาอยู่
     * แต่จดไว้ว่าเห็นได้ตั้งแต่ข้อความ id ไหนเป็นต้นไป ทำให้ฝั่งเราไม่เห็นของเก่าอีกเลย
     */
    public static function purgeExpiredTrash(?int $userId = null): int
    {
        $query = ConversationUserState::whereNotNull('trashed_at')
            ->where('trashed_at', '<=', now()->subDays(ConversationUserState::TRASH_DAYS));

        if ($userId !== null) {
            $query->where('user_id', $userId);
        }

        return $query->update([
            // ปักเส้นแบ่งที่ข้อความล่าสุดของห้องนั้น ณ ตอนลบ
            'cleared_before_message_id' => DB::raw('(select coalesce(max(m.id), 0) from messages m
                                                    where m.conversation_id = conversation_user_states.conversation_id)'),
            'trashed_at' => null,
            'pinned_at' => null,
            'pin_order' => null,
            'hidden_at' => null,
        ]);
    }

    // เริ่มแชท (จากปุ่มติดต่อผู้ขาย)
    public function start(Book $book)
    {
        $buyerId = Auth::id();

        // ห้ามแชทกับตัวเอง (ถ้าเป็นเจ้าของหนังสือ)
        if ($book->user_id === $buyerId) {
            return redirect()->route('books.show', $book)
                ->with('error', 'คุณไม่สามารถแชทกับหนังสือของตัวเองได้');
        }

        // หาห้องแชทเดิม หรือสร้างใหม่ถ้ายังไม่มี
        $conversation = Conversation::firstOrCreate(
            [
                'book_id' => $book->id,
                'buyer_id' => $buyerId,
            ],
            [
                'seller_id' => $book->user_id,
            ]
        );

        // เคยซ่อนหรือทิ้งไว้ในถังขยะ แต่กลับมาเปิดแชทเอง = ตั้งใจใช้ห้องนี้ต่อ
        $conversation->stateFor($buyerId)->update([
            'hidden_at' => null,
            'trashed_at' => null,
        ]);

        return redirect()->route('chat.show', $conversation);
    }

    // เปิดห้องสนทนา
    public function show(Conversation $conversation)
    {
        $userId = Auth::id();

        // ตรวจสอบสิทธิ์ - ต้องเป็นผู้ซื้อหรือผู้ขายในห้องนี้เท่านั้น
        if (!$conversation->hasParticipant($userId)) {
            abort(403, 'คุณไม่มีสิทธิ์เข้าถึงการสนทนานี้');
        }

        $state = $conversation->stateFor($userId);

        // มาร์คข้อความที่คนอื่นส่งมาในห้องนี้ว่าอ่านแล้ว
        $conversation->messages()
            ->where('user_id', '!=', $userId)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        $conversation->load(['book', 'buyer', 'seller.paymentMethods']);

        // ข้อความที่ id เก่ากว่าเส้นแบ่ง คือประวัติที่ถูกลบถาวรไปแล้ว ต้องไม่โผล่มาอีก
        $messages = $conversation->messages()
            ->with('user')
            ->when($state->cleared_before_message_id, fn ($q) => $q->where('id', '>', $state->cleared_before_message_id))
            ->orderBy('id')
            ->get();

        $conversation->setRelation('messages', $messages);

        // เช็คว่าคนที่กำลังดูเป็นผู้ซื้อไหม (ผู้ซื้อเท่านั้นที่เห็นช่องทางจ่ายเงิน)
        $isBuyer = $conversation->buyer_id === $userId;

        return view('chat.show', compact('conversation', 'isBuyer', 'state'));
    }

    // ส่งข้อความ
    public function sendMessage(Request $request, Conversation $conversation)
    {
        $userId = Auth::id();

        // ตรวจสอบสิทธิ์
        if (!$conversation->hasParticipant($userId)) {
            abort(403);
        }

        $request->validate([
            'message' => 'required|string|max:1000',
        ]);

        $message = $conversation->messages()->create([
            'user_id' => $userId,
            'message' => $request->message,
        ]);

        // อัปเดตเวลาของห้องแชท (ให้ขึ้นไปอยู่บนสุดในรายการ)
        $conversation->touch();

        // ตอบกลับเองแปลว่ายังใช้ห้องนี้อยู่ ดึงออกจากที่ซ่อน/ถังขยะให้
        $conversation->stateFor($userId)->update([
            'hidden_at' => null,
            'trashed_at' => null,
        ]);

        $message->load('user');

        $recipientId = $conversation->buyer_id === $userId
            ? $conversation->seller_id
            : $conversation->buyer_id;

        broadcast(new MessageSent($message, $recipientId))->toOthers();

        return response()->json([
            'message' => [
                'id' => $message->id,
                'conversation_id' => $message->conversation_id,
                'user_id' => $message->user_id,
                'user_name' => $message->user->name,
                'message' => $message->message,
                'created_at' => $message->created_at->format('H:i'),
            ],
        ]);
    }

    // มาร์คข้อความว่าอ่านแล้ว (เรียกจาก JS ตอนยังเปิดหน้าแชทอยู่แล้วมีข้อความใหม่เข้ามา)
    public function markRead(Conversation $conversation)
    {
        $userId = Auth::id();

        if (!$conversation->hasParticipant($userId)) {
            abort(403);
        }

        $conversation->messages()
            ->where('user_id', '!=', $userId)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return response()->json(['ok' => true]);
    }

    // ===== ปักหมุด / ซ่อน / ลบ =====

    // ปักหมุด หรือเลิกปักหมุด
    public function togglePin(Conversation $conversation)
    {
        $state = $this->stateOrFail($conversation);

        if ($state->pinned_at) {
            $state->update(['pinned_at' => null, 'pin_order' => null]);

            return back()->with('success', 'เลิกปักหมุดแล้ว');
        }

        // ปักหมุดใหม่ให้ไปต่อท้ายกลุ่มที่ปักหมุดอยู่ แล้วผู้ใช้ค่อยเลื่อนเอง
        $lastOrder = ConversationUserState::where('user_id', Auth::id())
            ->whereNotNull('pinned_at')
            ->max('pin_order');

        $state->update([
            'pinned_at' => now(),
            'pin_order' => (int) $lastOrder + 1,
            'hidden_at' => null,
            'trashed_at' => null,
        ]);

        return back()->with('success', 'ปักหมุดแชทแล้ว');
    }

    // เลื่อนลำดับแชทที่ปักหมุด ขึ้น/ลง ทีละขั้น
    public function movePin(Request $request, Conversation $conversation)
    {
        $request->validate(['direction' => 'required|in:up,down']);

        $state = $this->stateOrFail($conversation);

        if (!$state->pinned_at) {
            return back()->with('error', 'แชทนี้ยังไม่ได้ปักหมุด');
        }

        $up = $request->direction === 'up';

        // หาเพื่อนบ้านที่อยู่ติดกันในทิศที่จะเลื่อนไป แล้วสลับลำดับกัน
        $neighbour = ConversationUserState::where('user_id', Auth::id())
            ->whereNotNull('pinned_at')
            ->where('id', '!=', $state->id)
            ->when(
                $up,
                fn ($q) => $q->where('pin_order', '<', $state->pin_order)->orderByDesc('pin_order'),
                fn ($q) => $q->where('pin_order', '>', $state->pin_order)->orderBy('pin_order')
            )
            ->first();

        if (!$neighbour) {
            return back();
        }

        $mine = $state->pin_order;
        $state->update(['pin_order' => $neighbour->pin_order]);
        $neighbour->update(['pin_order' => $mine]);

        return back();
    }

    // ซ่อน หรือเอากลับจากที่ซ่อน
    public function toggleHide(Conversation $conversation)
    {
        $state = $this->stateOrFail($conversation);

        if ($state->hidden_at) {
            $state->update(['hidden_at' => null]);

            return back()->with('success', 'เอาแชทกลับเข้ากล่องหลักแล้ว');
        }

        // ซ่อนแล้วไม่ควรค้างปักหมุดไว้ ไม่งั้นกลับมาจะงงว่าทำไมอยู่บนสุด
        $state->update([
            'hidden_at' => now(),
            'pinned_at' => null,
            'pin_order' => null,
        ]);

        return back()->with('success', 'ซ่อนแชทแล้ว ดูได้ที่แท็บ "ซ่อนไว้"');
    }

    // ย้ายลงถังขยะ
    public function trash(Conversation $conversation)
    {
        $state = $this->stateOrFail($conversation);

        $state->update([
            'trashed_at' => now(),
            'hidden_at' => null,
            'pinned_at' => null,
            'pin_order' => null,
        ]);

        return back()->with('success', 'ย้ายแชทลงถังขยะแล้ว กู้คืนได้ภายใน ' . ConversationUserState::TRASH_DAYS . ' วัน');
    }

    // กู้คืนจากถังขยะ
    public function restore(Conversation $conversation)
    {
        $state = $this->stateOrFail($conversation);

        $state->update(['trashed_at' => null]);

        return back()->with('success', 'กู้คืนแชทแล้ว');
    }

    // ลบถาวรทันที ไม่ต้องรอครบกำหนด
    public function purge(Conversation $conversation)
    {
        $state = $this->stateOrFail($conversation);

        $state->update([
            'cleared_before_message_id' => $conversation->messages()->max('id') ?? 0,
            'trashed_at' => null,
            'hidden_at' => null,
            'pinned_at' => null,
            'pin_order' => null,
        ]);

        return redirect()->route('chat.index', ['view' => 'trash'])
            ->with('success', 'ลบแชทถาวรแล้ว');
    }

    // หาสถานะของห้องนี้สำหรับผู้ใช้ปัจจุบัน พร้อมกันคนนอกออกไป
    private function stateOrFail(Conversation $conversation): ConversationUserState
    {
        $userId = Auth::id();

        if (!$conversation->hasParticipant($userId)) {
            abort(403);
        }

        return $conversation->stateFor($userId);
    }
}
