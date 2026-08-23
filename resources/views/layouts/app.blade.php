<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'ตลาดหนังสือมือสอง')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="{{ asset('css/user-theme.css') }}" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    @vite(['resources/js/app.js'])
</head>
<body>

    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="{{ url('/') }}">📚 ตลาดซื้อขายแลกเปลี่ยนหนังสือมือสอง</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    @guest
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('login') }}">เข้าสู่ระบบ</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('register') }}">สมัครสมาชิก</a>
                        </li>
                    @else

                        @unless (auth()->user()->isAdmin())

                            <li class="nav-item">
                                <a class="nav-link position-relative" href="{{ route('report-chat.index') }}">
                                    📨 ข้อความจากแอดมิน
                                    <span id="reportMessageUnreadBadge" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger {{ (($unreadReportMessageCount ?? 0) > 0) ? '' : 'd-none' }}" style="font-size: 10px;">
                                        {{ (($unreadReportMessageCount ?? 0) > 99) ? '99+' : ($unreadReportMessageCount ?? 0) }}
                                    </span>
                                </a>
                            </li>

                            <li class="nav-item">
                                <a class="nav-link" href="{{ route('books.create') }}">➕ ลงประกาศหนังสือ</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="{{ route('books.my') }}">📚 หนังสือของฉัน</a>
                            </li>
                            <li class="nav-item" data-current-user-id="{{ auth()->id() }}">
                                <a class="nav-link position-relative" href="{{ route('chat.index') }}">
                                    💬 ข้อความ
                                    <span id="chatUnreadBadge" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger {{ (isset($unreadMessageCount) && $unreadMessageCount > 0) ? '' : 'd-none' }}" style="font-size: 10px;">
                                        {{ isset($unreadMessageCount) && $unreadMessageCount > 99 ? '99+' : ($unreadMessageCount ?? 0) }}
                                    </span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="{{ route('payment.index') }}">💳 การชำระเงิน</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="{{ route('orders.index') }}">🧾 คำสั่งซื้อ</a>
                            </li>
                        @endunless

                            @if (Auth::user()->is_admin)
                                <li class="nav-item">
                                    <a class="nav-link text-warning" href="{{ route('admin.dashboard') }}">🛡️ Admin</a>
                                </li>
                            @endif

                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('profile.index') }}">👤 {{ Auth::user()->name }}</a>
                        </li>
                        <li class="nav-item">
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="btn btn-link nav-link">ออกจากระบบ</button>
                            </form>
                        </li>
                    @endguest
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        @yield('content')
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // ทำให้ข้อความแจ้งเตือนหายไปเองหลังผ่านไป 4 วินาที
        setTimeout(function () {
            document.querySelectorAll('.alert-success, .alert-info, .alert-danger').forEach(function (alert) {
                alert.style.transition = 'opacity 0.5s';
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 500);
            });
        }, 2000);
    </script>
</body>
</html>