<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BookController;
use App\Http\Controllers\ChatController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\OrderController;

// หน้าสมัครสมาชิก
Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
Route::post('/register', [AuthController::class, 'register'])->name('register.submit');

// หน้าเข้าสู่ระบบ
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.submit');

// ออกจากระบบ
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// หน้าหลัก
Route::get('/', [BookController::class, 'index'])->name('home');

// ==== Admin เท่านั้น ====
    Route::middleware('admin')->prefix('admin')->group(function () {
        Route::get('/', [AdminController::class, 'dashboard'])->name('admin.dashboard');

        // จัดการผู้ใช้
        Route::get('/users', [AdminController::class, 'users'])->name('admin.users');
        Route::post('/users/{user}/ban', [AdminController::class, 'toggleBan'])->name('admin.users.ban');
        Route::delete('/users/{user}', [AdminController::class, 'deleteUser'])->name('admin.users.delete');

        // จัดการหนังสือ
        Route::get('/books', [AdminController::class, 'books'])->name('admin.books');
        Route::delete('/books/{book}', [AdminController::class, 'deleteBook'])->name('admin.books.delete');

        // จัดการคำสั่งซื้อ
        Route::get('/orders', [AdminController::class, 'orders'])->name('admin.orders');
        Route::post('/orders/{order}/resolve', [AdminController::class, 'resolveDispute'])->name('admin.orders.resolve');
    });

// ==== ต้อง login ก่อน ====
Route::middleware('auth')->group(function () {

    // ลงประกาศหนังสือ (ต้องเป็นร้านก่อน)
    Route::middleware('shop')->group(function () {
        Route::get('/books/create', [BookController::class, 'create'])->name('books.create');
        Route::post('/books', [BookController::class, 'store'])->name('books.store');
    });

    // โปรไฟล์
    Route::get('/profile', [ProfileController::class, 'index'])->name('profile.index');
    Route::get('/profile/edit', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');

    // สมัครร้านค้า
    Route::get('/shop/register', [ProfileController::class, 'showRegisterShop'])->name('shop.register');
    Route::post('/shop/register', [ProfileController::class, 'registerShop'])->name('shop.register.submit');

    // จัดการหนังสือของฉัน
    Route::get('/my-books', [BookController::class, 'myBooks'])->name('books.my');
    Route::get('/books/{book}/edit', [BookController::class, 'edit'])->name('books.edit');
    Route::put('/books/{book}', [BookController::class, 'update'])->name('books.update');
    Route::delete('/books/{book}', [BookController::class, 'destroy'])->name('books.destroy');
    Route::post('/books/{book}/mark-sold', [BookController::class, 'markAsSold'])->name('books.markSold');

    // ระบบแชท
    Route::get('/chat', [ChatController::class, 'index'])->name('chat.index');
    Route::get('/chat/start/{book}', [ChatController::class, 'start'])->name('chat.start');
    Route::get('/chat/{conversation}', [ChatController::class, 'show'])->name('chat.show');
    Route::post('/chat/{conversation}/send', [ChatController::class, 'sendMessage'])->name('chat.send');

    // ช่องทางการชำระเงิน
    Route::get('/payment', [PaymentController::class, 'index'])->name('payment.index');
    Route::post('/payment', [PaymentController::class, 'store'])->name('payment.store');
    Route::delete('/payment/{paymentMethod}', [PaymentController::class, 'destroy'])->name('payment.destroy');

    // รีวิวร้าน
    Route::post('/reviews/{shop}', [ReviewController::class, 'store'])->name('reviews.store');
    Route::delete('/reviews/{review}', [ReviewController::class, 'destroy'])->name('reviews.destroy');

    // ระบบซื้อขาย
    Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
    Route::post('/orders/{book}', [OrderController::class, 'store'])->name('orders.store');
    Route::post('/orders/{order}/accept', [OrderController::class, 'accept'])->name('orders.accept');
    Route::post('/orders/{order}/slip', [OrderController::class, 'uploadSlip'])->name('orders.slip');
    Route::post('/orders/{order}/confirm-payment', [OrderController::class, 'confirmPayment'])->name('orders.confirmPayment');
    Route::post('/orders/{order}/complete', [OrderController::class, 'confirmComplete'])->name('orders.complete');
    Route::post('/orders/{order}/cancel', [OrderController::class, 'cancel'])->name('orders.cancel');
    Route::post('/orders/{order}/dispute', [OrderController::class, 'dispute'])->name('orders.dispute');
});

// ดูรายละเอียดหนังสือ (ทุกคนดูได้)
Route::get('/books/{book}', [BookController::class, 'show'])->name('books.show');

// หน้าโปรไฟล์ร้าน (ทุกคนดูได้)
Route::get('/shop/{shop}', [ProfileController::class, 'showShop'])->name('shop.show');