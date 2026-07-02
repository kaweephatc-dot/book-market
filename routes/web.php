<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BookController;
use App\Http\Controllers\ChatController;
use Illuminate\Support\Facades\Route;

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

});

// ดูรายละเอียดหนังสือ (ทุกคนดูได้)
Route::get('/books/{book}', [BookController::class, 'show'])->name('books.show');