<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'ตลาดหนังสือมือสอง')</title>
    <link href="{{ asset('vendor/sb-admin-2/css/sb-admin-2.min.css') }}" rel="stylesheet">
    <style>
        /* เหตุผลเดียวกับ admin/layout.blade.php: ปิด ::after ไอคอนเดิมของธีม (FontAwesome ที่เราไม่โหลด)
           แล้วใช้ตัวอักษร ‹ / › จริงใน sidebar.js แทน */
        .sidebar #sidebarToggle::after,
        .sidebar.toggled #sidebarToggle::after { content: none !important; }
        #sidebarToggle {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
            font-weight: 700;
            line-height: 1;
            color: rgba(255, 255, 255, .85);
        }

        .sidebar.toggled { width: 5.5rem !important; }
        .sidebar.toggled .nav-item .nav-link { width: 5.5rem; }
        .sidebar.toggled .nav-item .nav-link span { display: none; }
        .sidebar.toggled .sidebar-heading { display: none; }
        .sidebar.toggled .nav-item .nav-link i { margin-right: 0; font-size: 1.3rem; }
    </style>
    @vite(['resources/js/app.js'])
</head>
<body id="page-top">

    <div id="wrapper">

        {{-- Sidebar --}}
        <ul class="navbar-nav sidebar sidebar-dark accordion" id="accordionSidebar">

            <a class="sidebar-brand d-flex align-items-center justify-content-center" href="{{ route('home') }}">
                <div class="sidebar-brand-icon">📚</div>
                <div class="sidebar-brand-text mx-3">ตลาดหนังสือมือสอง</div>
            </a>

            <hr class="sidebar-divider my-0">

            <div class="sidebar-heading">หน้าร้าน</div>

            <li class="nav-item {{ request()->routeIs('home') ? 'active' : '' }}">
                <a class="nav-link" href="{{ route('home') }}">
                    <i>🏠</i>
                    <span>หน้าแรก</span>
                </a>
            </li>

            <hr class="sidebar-divider">

            @guest
                <li class="nav-item {{ request()->routeIs('login') ? 'active' : '' }}">
                    <a class="nav-link" href="{{ route('login') }}">
                        <i>🔑</i>
                        <span>เข้าสู่ระบบ</span>
                    </a>
                </li>

                <li class="nav-item {{ request()->routeIs('register') ? 'active' : '' }}">
                    <a class="nav-link" href="{{ route('register') }}">
                        <i>📝</i>
                        <span>สมัครสมาชิก</span>
                    </a>
                </li>
            @else
                @unless (auth()->user()->isAdmin())

                    <div class="sidebar-heading">บัญชีของฉัน</div>

                    <li class="nav-item {{ request()->routeIs('report-chat.*') ? 'active' : '' }}">
                        <a class="nav-link position-relative" href="{{ route('report-chat.index') }}">
                            <i>📨</i>
                            <span>ข้อความจากแอดมิน</span>
                            <span id="reportMessageUnreadBadge" class="badge badge-counter bg-danger {{ (($unreadReportMessageCount ?? 0) > 0) ? '' : 'd-none' }}">
                                {{ (($unreadReportMessageCount ?? 0) > 99) ? '99+' : ($unreadReportMessageCount ?? 0) }}
                            </span>
                        </a>
                    </li>

                    <li class="nav-item {{ request()->routeIs('books.create') ? 'active' : '' }}">
                        <a class="nav-link" href="{{ route('books.create') }}">
                            <i>➕</i>
                            <span>ลงประกาศหนังสือ</span>
                        </a>
                    </li>

                    <li class="nav-item {{ request()->routeIs('books.my') || request()->routeIs('books.edit') ? 'active' : '' }}">
                        <a class="nav-link" href="{{ route('books.my') }}">
                            <i>📚</i>
                            <span>หนังสือของฉัน</span>
                        </a>
                    </li>

                    <li class="nav-item {{ request()->routeIs('chat.*') ? 'active' : '' }}" data-current-user-id="{{ auth()->id() }}">
                        <a class="nav-link position-relative" href="{{ route('chat.index') }}">
                            <i>💬</i>
                            <span>ข้อความ</span>
                            <span id="chatUnreadBadge" class="badge badge-counter bg-danger {{ (isset($unreadMessageCount) && $unreadMessageCount > 0) ? '' : 'd-none' }}">
                                {{ isset($unreadMessageCount) && $unreadMessageCount > 99 ? '99+' : ($unreadMessageCount ?? 0) }}
                            </span>
                        </a>
                    </li>

                    <li class="nav-item {{ request()->routeIs('payment.index') ? 'active' : '' }}">
                        <a class="nav-link" href="{{ route('payment.index') }}">
                            <i>💳</i>
                            <span>การชำระเงิน</span>
                        </a>
                    </li>

                    <li class="nav-item {{ request()->routeIs('orders.index') ? 'active' : '' }}">
                        <a class="nav-link" href="{{ route('orders.index') }}">
                            <i>🧾</i>
                            <span>คำสั่งซื้อ</span>
                        </a>
                    </li>

                    <hr class="sidebar-divider">
                @endunless

                <li class="nav-item {{ request()->routeIs('profile.*') ? 'active' : '' }}">
                    <a class="nav-link" href="{{ route('profile.index') }}">
                        <i>👤</i>
                        <span>โปรไฟล์</span>
                    </a>
                </li>

                <hr class="sidebar-divider">

                <li class="nav-item">
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="nav-link btn btn-link text-start w-100 border-0">
                            <i>🚪</i>
                            <span>ออกจากระบบ</span>
                        </button>
                    </form>
                </li>
            @endguest

            <hr class="sidebar-divider d-none d-md-block">

            <div class="text-center d-none d-md-inline">
                <button class="rounded-circle border-0" id="sidebarToggle">◀</button>
            </div>

        </ul>
        {{-- End of Sidebar --}}

        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">

                {{-- Topbar --}}
                <nav class="navbar navbar-expand navbar-light bg-white topbar mb-4 static-top shadow">
                    <button id="sidebarToggleTop" class="btn btn-link d-md-none rounded-circle me-3">
                        ☰
                    </button>

                    <ul class="navbar-nav ms-auto">
                        @auth
                            <li class="nav-item">
                                <span class="nav-link">👤 {{ Auth::user()->name }}</span>
                            </li>
                        @else
                            <li class="nav-item">
                                <a class="nav-link" href="{{ route('login') }}">เข้าสู่ระบบ</a>
                            </li>
                        @endauth
                    </ul>
                </nav>
                {{-- End of Topbar --}}

                <div class="container-fluid">
                    @yield('content')
                </div>

            </div>

            <footer class="sticky-footer bg-white">
                <div class="container my-auto">
                    <div class="copyright text-center my-auto">
                        <span>ตลาดซื้อขายแลกเปลี่ยนหนังสือมือสอง</span>
                    </div>
                </div>
            </footer>
        </div>

    </div>

    <a class="scroll-to-top rounded" href="#page-top">↑</a>

    <script src="{{ asset('vendor/sb-admin-2/vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('vendor/sb-admin-2/js/sidebar.js') }}"></script>

    <script>
        // ทำให้ข้อความแจ้งเตือนหายไปเองหลังผ่านไป 4 วินาที (พฤติกรรมเดิมจาก layouts.app)
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
