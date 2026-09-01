<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Book;
use Illuminate\Support\Facades\Auth;
use App\Services\BookConditionService;
use App\Services\BookSearchAiService;
use Illuminate\Support\Facades\Storage;

class BookController extends Controller
{
    // หน้าหลัก - แสดงหนังสือทั้งหมด พร้อมค้นหา/กรอง/เรียง
    public function index(Request $request)
    {
        // เริ่มต้น query โดยดึงเฉพาะหนังสือที่ยังว่างอยู่
        $query = Book::with(['user', 'images'])->where('status', 'available');

        // กรองตามประเภท (ซื้อ หรือ แลกเปลี่ยน)
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        // ค้นหาชื่อหนังสือ หรือ ชื่อร้าน
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($u) use ($search) {
                      $u->where('shop_name', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%");
                  });
            });
        }

        // กรองตามหมวดหมู่
        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        // เรียงตามราคา
        if ($request->sort === 'price_asc') {
            $query->orderBy('price', 'asc');
        } elseif ($request->sort === 'price_desc') {
            $query->orderBy('price', 'desc');
        } else {
            $query->latest();
        }

        $books = $query->paginate(12)->withQueryString();

        return view('home', compact('books'));
    }

    /**
     * ค้นหาด้วยประโยคภาษาธรรมชาติ (ช่อง "ค้นหาด้วย AI") — แยกจากช่องค้นหาปกติคนละ route
     *
     * ส่งประโยคให้ Gemini แปลงเป็น filter แล้วยิงเข้า query เดียวกับหน้าหลัก
     * ถ้า AI ใช้ไม่ได้ด้วยเหตุผลใดก็ตาม จะถอยไปค้นแบบ keyword ธรรมดาเสมอ ไม่มีทางขึ้นหน้า error
     */
    public function aiSearch(Request $request, BookSearchAiService $ai)
    {
        $sentence = trim((string) $request->query('q', ''));

        // ยังไม่ได้พิมพ์อะไรมา ก็ไม่ต้องรบกวน AI ให้เปลือง quota
        if ($sentence === '') {
            return redirect()->route('home');
        }

        $result = $ai->interpret($sentence);

        if ($result['success']) {
            $filters = $result['filters'];
            $aiFallbackReason = null;
        } else {
            // ถอยไปค้นแบบปกติ: เอาทั้งประโยคไปค้นเป็น keyword ผ่าน applyAiFilters()
            // จึงกว้างกว่าช่องค้นหาปกติอยู่บ้าง (ครอบ author/description ด้วย) ซึ่งตั้งใจให้เป็นแบบนั้น
            $filters = ['keyword' => $sentence, 'category' => null,
                        'price_min' => null, 'price_max' => null, 'type' => null];
            $aiFallbackReason = $result['error'] ?? 'ใช้ AI ไม่ได้ในขณะนี้';
        }

        $books = $this->applyAiFilters($filters)->paginate(12)->withQueryString();

        return view('home', [
            'books' => $books,
            'aiQuery' => $sentence,
            'aiFilters' => $filters,
            'aiFallbackReason' => $aiFallbackReason,
        ]);
    }

    /**
     * ประกอบ query จาก filter ที่ AI ตีความมา
     *
     * เงื่อนไข category / type เขียนซ้ำให้ตรงกับ index() ด้านบน (บรรทัด ~17-39)
     * เพื่อไม่ต้องไปแก้ index() ที่ใช้งานอยู่จริง ถ้าวันหลังจะยุบรวมให้ย้ายทั้งสองที่มาใช้ตัวนี้
     * ส่วน keyword ที่นี่ค้นกว้างกว่า index() — เพิ่ม author/description นอกเหนือจาก title/ชื่อร้าน
     * และ price_min/price_max เป็นของที่ index() ไม่มี (ช่องค้นหาปกติกรองราคาไม่ได้)
     *
     * @param array{keyword: ?string, category: ?string, price_min: ?float, price_max: ?float, type: ?string} $filters
     */
    private function applyAiFilters(array $filters)
    {
        $query = Book::with(['user', 'images'])->where('status', 'available');

        if (! empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        // ทุกเงื่อนไข keyword อยู่ในวงเล็บเดียวกัน ไม่ให้ OR หลุดไปกระทบ price/category/type ข้างล่าง
        // author/description เป็น nullable ได้ แถวที่เป็น NULL แค่ไม่แมตช์เงื่อนไขนั้น ไม่ตกหล่นจากทางอื่น
        if (! empty($filters['keyword'])) {
            $keyword = $filters['keyword'];
            $query->where(function ($q) use ($keyword) {
                $q->where('title', 'like', "%{$keyword}%")
                  ->orWhere('author', 'like', "%{$keyword}%")
                  ->orWhere('description', 'like', "%{$keyword}%")
                  ->orWhereHas('user', function ($u) use ($keyword) {
                      $u->where('shop_name', 'like', "%{$keyword}%")
                        ->orWhere('name', 'like', "%{$keyword}%");
                  });
            });
        }

        if (! empty($filters['category'])) {
            $query->where('category', $filters['category']);
        }

        // หนังสือแลกเปลี่ยนมี price เป็น NULL จึงหลุดออกจากผลเมื่อผู้ใช้ระบุช่วงราคา ซึ่งตรงกับที่ควรเป็น
        if ($filters['price_min'] !== null) {
            $query->where('price', '>=', $filters['price_min']);
        }

        if ($filters['price_max'] !== null) {
            $query->where('price', '<=', $filters['price_max']);
        }

        return $query->latest();
    }

    // แสดงฟอร์มเพิ่มหนังสือ
    public function create()
    {
        return view('books.create');
    }

    // บันทึกหนังสือใหม่
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'category' => 'required|string',
            'type' => 'required|in:sale,exchange',
            'price' => 'nullable|numeric|min:0',
            'cover_image' => 'required|image|max:2048',
            'spine_image' => 'nullable|image|max:2048',
            'page_image' => 'nullable|image|max:2048',
            'back_image' => 'nullable|image|max:2048',
            'ai_analysis' => 'nullable|string',
        ]);

        $book = Book::create([
            'user_id' => Auth::id(),
            'title' => $request->title,
            'author' => $request->author,
            'category' => $request->category,
            'type' => $request->type,
            'price' => $request->type === 'sale' ? $request->price : null,
            'description' => $request->description,
            'condition' => $request->condition,
        ]);

        // อ่านผลประเมิน AI ที่ทำไว้ก่อนหน้านี้ผ่านปุ่ม "ประเมินด้วย AI" (ถ้ามี)
        $aiPerImage = [];
        if ($request->filled('ai_analysis')) {
            $decoded = json_decode($request->input('ai_analysis'), true);

            if (is_array($decoded) && isset($decoded['per_image']) && is_array($decoded['per_image'])) {
                $aiPerImage = $decoded['per_image'];
            }
        }

        // บันทึกรูปแต่ละมุมที่มี พร้อมผลประเมิน AI ต่อรูป (ถ้ามี)
        foreach (['cover', 'spine', 'page', 'back'] as $slot) {
            $file = $request->file("{$slot}_image");

            if (! $file) {
                continue;
            }

            $path = $file->store('books', 'public');
            $result = $aiPerImage[$slot] ?? null;

            $book->images()->create([
                'image_path' => $path,
                'slot' => $slot,
                'ai_condition' => $result['condition'] ?? null,
                'ai_score' => $result['score'] ?? null,
                'ai_note' => $result['note'] ?? null,
                'ai_angle_match' => $result['angle_match'] ?? null,
            ]);
        }

        return redirect('/')->with('success', 'ลงประกาศหนังสือสำเร็จ!');
    }

    // ประเมินสภาพหนังสือด้วย AI ก่อน submit จริง (เรียกจากปุ่ม "ประเมินด้วย AI")
    public function analyzeCondition(Request $request)
    {
        $request->validate([
            'cover_image' => 'required|image|max:2048',
            'spine_image' => 'nullable|image|max:2048',
            'page_image' => 'nullable|image|max:2048',
            'back_image' => 'nullable|image|max:2048',
        ]);

        $slotFiles = array_filter([
            'cover' => $request->file('cover_image'),
            'spine' => $request->file('spine_image'),
            'page' => $request->file('page_image'),
            'back' => $request->file('back_image'),
        ]);

        $result = (new BookConditionService())->analyze($slotFiles);

        return response()->json($result);
    }

    // แสดงรายละเอียดหนังสือ 1 เล่ม
    // แสดงรายละเอียดหนังสือ 1 เล่ม
    public function show(Book $book)
    {
        $book->load(['user', 'images']);

        // คำนวณคะแนน AI เฉลี่ยจากทุกรูป
        $avgScore = round($book->images->avg('ai_score'));

        // แปลงคะแนนเฉลี่ยเป็นระดับสภาพ
        $avgCondition = match(true) {
            $avgScore >= 85 => 'ดีมาก',
            $avgScore >= 70 => 'ดี',
            $avgScore >= 50 => 'พอใช้',
            $avgScore >= 30 => 'ต้องซ่อม',
            default => 'ไม่ระบุ',
        };

        return view('books.show', compact('book', 'avgScore', 'avgCondition'));
    }
    // หน้ารายการหนังสือของฉัน
    public function myBooks()
    {
        $books = Book::with('images')
            ->where('user_id', Auth::id())
            ->latest()
            ->get();

        return view('books.my-books', compact('books'));
    }

    // แสดงฟอร์มแก้ไขหนังสือ
    public function edit(Book $book)
    {
        // ตรวจสอบสิทธิ์ - ต้องเป็นเจ้าของเท่านั้น
        if ($book->user_id !== Auth::id()) {
            abort(403);
        }

        return view('books.edit', compact('book'));
    }

    // บันทึกการแก้ไข
    public function update(Request $request, Book $book)
    {
        if ($book->user_id !== Auth::id()) {
            abort(403);
        }

        $request->validate([
            'title' => 'required|string|max:255',
            'category' => 'required|string',
            'type' => 'required|in:sale,exchange',
            'price' => 'nullable|numeric|min:0',
        ]);

        $book->update([
            'title' => $request->title,
            'author' => $request->author,
            'category' => $request->category,
            'type' => $request->type,
            'price' => $request->type === 'sale' ? $request->price : null,
            'description' => $request->description,
            'condition' => $request->condition,
        ]);

        return redirect()->route('books.my')->with('success', 'แก้ไขหนังสือสำเร็จ!');
    }

    // ลบหนังสือ
    public function destroy(Book $book)
    {
        if ($book->user_id !== Auth::id()) {
            abort(403);
        }

        // ลบรูปภาพออกจาก storage ด้วย
        foreach ($book->images as $image) {
            Storage::disk('public')->delete($image->image_path);
        }

        $book->delete();

        return redirect()->route('books.my')->with('success', 'ลบหนังสือแล้ว');
    }

    // เปลี่ยนสถานะเป็นขายแล้ว/แลกแล้ว
    public function markAsSold(Book $book)
    {
        if ($book->user_id !== Auth::id()) {
            abort(403);
        }

        $book->update([
            'status' => $book->type === 'sale' ? 'sold' : 'exchanged',
        ]);

        return redirect()->route('books.my')->with('success', 'อัปเดตสถานะแล้ว');
    }
}