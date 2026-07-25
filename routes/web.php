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
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ReportChatController;

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

        // จัดการรายงาน
        Route::get('/reports', [AdminController::class, 'reports'])->name('admin.reports');
        Route::post('/reports/{report}/resolve', [AdminController::class, 'resolveReport'])->name('admin.reports.resolve');
        Route::post('/reports/{report}/dismiss', [AdminController::class, 'dismissReport'])->name('admin.reports.dismiss');

        // แชทรายงาน + แบนขั้นสูง (admin)
        Route::get('/report-chats', [AdminController::class, 'reportChats'])->name('admin.report-chats.index');
        Route::get('/report/{report}/chat/{user}', [AdminController::class, 'openReportChat'])->name('admin.report.chat');
        Route::post('/report-chat/{chat}/send', [AdminController::class, 'sendReportMessage'])->name('admin.report.chat.send');
        Route::post('/report-chat/{chat}/read', [AdminController::class, 'markReportChatRead'])->name('admin.report.chat.read');
        Route::post('/report-chat/{chat}/close', [AdminController::class, 'closeReportChat'])->name('admin.report.chat.close');
        Route::post('/report-chat/{chat}/reopen', [AdminController::class, 'reopenReportChat'])->name('admin.report.chat.reopen');
        Route::delete('/report-chat/{chat}', [AdminController::class, 'deleteReportChat'])->name('admin.report.chat.delete');
        Route::post('/users/{user}/ban-advanced', [AdminController::class, 'banUser'])->name('admin.users.banAdvanced');
        Route::post('/users/{user}/unban', [AdminController::class, 'unbanUser'])->name('admin.users.unban');
    });

// ==== ต้อง login ก่อน ====
Route::middleware('auth')->group(function () {

    // ลงประกาศหนังสือ (ต้องเป็นร้านก่อน และห้ามแอดมิน)
    Route::middleware(['shop', 'not.admin'])->group(function () {
        Route::get('/books/create', [BookController::class, 'create'])->name('books.create');
        Route::post('/books', [BookController::class, 'store'])->name('books.store');
    });

    // รายงานโพสต์
    Route::post('/report/book/{book}', [ReportController::class, 'reportBook'])->name('report.book');
    Route::post('/report/shop/{shop}', [ReportController::class, 'reportShop'])->name('report.shop');

    // โปรไฟล์
    Route::get('/profile', [ProfileController::class, 'index'])->name('profile.index');
    Route::get('/profile/edit', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');

    // สมัครร้านค้า (ห้ามแอดมิน)
    Route::middleware('not.admin')->group(function () {
        Route::get('/shop/register', [ProfileController::class, 'showRegisterShop'])->name('shop.register');
        Route::post('/shop/register', [ProfileController::class, 'registerShop'])->name('shop.register.submit');
    });

    // จัดการหนังสือของฉัน
    Route::get('/my-books', [BookController::class, 'myBooks'])->name('books.my');
    Route::get('/books/{book}/edit', [BookController::class, 'edit'])->name('books.edit')->middleware('not.admin');
    Route::put('/books/{book}', [BookController::class, 'update'])->name('books.update')->middleware('not.admin');
    Route::delete('/books/{book}', [BookController::class, 'destroy'])->name('books.destroy')->middleware('not.admin');
    Route::post('/books/{book}/mark-sold', [BookController::class, 'markAsSold'])->name('books.markSold')->middleware('not.admin');

    // ระบบแชท (ห้ามแอดมิน)
    Route::middleware('not.admin')->group(function () {
        Route::get('/chat', [ChatController::class, 'index'])->name('chat.index');
        Route::get('/chat/start/{book}', [ChatController::class, 'start'])->name('chat.start');
        Route::get('/chat/{conversation}', [ChatController::class, 'show'])->name('chat.show');
        Route::post('/chat/{conversation}/send', [ChatController::class, 'sendMessage'])->name('chat.send');
        Route::post('/chat/{conversation}/read', [ChatController::class, 'markRead'])->name('chat.read');
    });

    // ช่องทางการชำระเงิน (ห้ามแอดมิน)
    Route::middleware('not.admin')->group(function () {
        Route::get('/payment', [PaymentController::class, 'index'])->name('payment.index');
        Route::post('/payment', [PaymentController::class, 'store'])->name('payment.store');
        Route::delete('/payment/{paymentMethod}', [PaymentController::class, 'destroy'])->name('payment.destroy');
    });

    // รีวิวร้าน (ห้ามแอดมิน)
    Route::middleware('not.admin')->group(function () {
        Route::post('/reviews/{shop}', [ReviewController::class, 'store'])->name('reviews.store');
        Route::delete('/reviews/{review}', [ReviewController::class, 'destroy'])->name('reviews.destroy');
    });

    // ระบบซื้อขาย (ห้ามแอดมิน)
    Route::middleware('not.admin')->group(function () {
        Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
        Route::post('/orders/{book}', [OrderController::class, 'store'])->name('orders.store');
        Route::post('/orders/{order}/accept', [OrderController::class, 'accept'])->name('orders.accept');
        Route::post('/orders/{order}/slip', [OrderController::class, 'uploadSlip'])->name('orders.slip');
        Route::post('/orders/{order}/confirm-payment', [OrderController::class, 'confirmPayment'])->name('orders.confirmPayment');
        Route::post('/orders/{order}/shipping', [OrderController::class, 'confirmShipping'])->name('orders.shipping');
        Route::post('/orders/{order}/received', [OrderController::class, 'confirmReceived'])->name('orders.received');
        Route::post('/orders/{order}/cancel', [OrderController::class, 'cancel'])->name('orders.cancel');
        Route::post('/orders/{order}/dispute', [OrderController::class, 'dispute'])->name('orders.dispute');
    });

    // แชทรายงาน (ฝั่งผู้ใช้)
    Route::get('/report-chats', [ReportChatController::class, 'index'])->name('report-chat.index');
    Route::get('/report-chats/{chat}', [ReportChatController::class, 'show'])->name('report-chat.show');
    Route::post('/report-chats/{chat}/send', [ReportChatController::class, 'send'])->name('report-chat.send');
    Route::post('/report-chats/{chat}/read', [ReportChatController::class, 'markRead'])->name('report-chat.read');
});

// ดูรายละเอียดหนังสือ (ทุกคนดูได้)
Route::get('/books/{book}', [BookController::class, 'show'])->name('books.show');

// หน้าโปรไฟล์ร้าน (ทุกคนดูได้)
Route::get('/shop/{shop}', [ProfileController::class, 'showShop'])->name('shop.show');