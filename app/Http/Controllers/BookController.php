<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Book;
use Illuminate\Support\Facades\Auth;
use App\Services\BookConditionService;
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
            'images.*' => 'image|max:2048',
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

        // บันทึกรูปภาพ + วิเคราะห์สภาพด้วย AI (ถ้ามี)
        if ($request->hasFile('images')) {
            $aiService = new BookConditionService();

            foreach ($request->file('images') as $image) {
                $path = $image->store('books', 'public');

                // ให้ AI วิเคราะห์สภาพหนังสือจากรูป
                $result = $aiService->analyze($path);

                $book->images()->create([
                    'image_path' => $path,
                    'ai_condition' => $result['condition'],
                    'ai_score' => $result['score'],
                ]);
            }
        }

        return redirect('/')->with('success', 'ลงประกาศหนังสือสำเร็จ!');
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