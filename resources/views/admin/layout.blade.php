<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Admin - @yield('title')</title>
    <link href="{{ asset('vendor/sb-admin-2/css/sb-admin-2.min.css') }}" rel="stylesheet">
    <style>
        /* ธีมเดิมวาดไอคอนปุ่ม #sidebarToggle ด้วย FontAwesome ผ่าน ::after ซึ่งเราไม่ได้โหลดฟอนต์นี้ (ใช้ emoji แทน)
           เลยปิด pseudo-element นี้ไว้ แล้วใช้ตัวอักษร ‹ / › จริงใน sidebar.js แทน */
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

        /* โหมดยุบ sidebar: ให้เหลือแค่ emoji ไม่โชว์ label ข้อความ (ธีมเดิมแค่ย่อ font ตัวหนังสือ ไม่ได้ซ่อน) */
        .sidebar.toggled { width: 5.5rem !important; }
        .sidebar.toggled .nav-item .nav-link { width: 5.5rem; }
        .sidebar.toggled .nav-item .nav-link span { display: none; }
        .sidebar.toggled .sidebar-heading { display: none; }
        .sidebar.toggled .nav-item .nav-link i { margin-right: 0; font-size: 1.3rem; }
    </style>
    <link href="{{ asset('css/modal-form-theme.css') }}" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    @vite(['resources/js/app.js'])
</head>
<body id="page-top">

    <div id="wrapper">

        {{-- Sidebar --}}
        <ul class="navbar-nav sidebar sidebar-dark accordion" id="accordionSidebar">

            <a class="sidebar-brand d-flex align-items-center justify-content-center" href="{{ route('admin.dashboard') }}">
                <div class="sidebar-brand-icon">📚</div>
                <div class="sidebar-brand-text mx-3">Admin Panel</div>
            </a>

            <hr class="sidebar-divider my-0">

            <li class="nav-item {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                <a class="nav-link" href="{{ route('admin.dashboard') }}">
                    <i>📊</i>
                    <span>Dashboard</span>
                </a>
            </li>

            <hr class="sidebar-divider">

            <div class="sidebar-heading">จัดการระบบ</div>

            <li class="nav-item {{ request()->routeIs('admin.users') ? 'active' : '' }}">
                <a class="nav-link" href="{{ route('admin.users') }}">
                    <i>👥</i>
                    <span>ผู้ใช้</span>
                </a>
            </li>

            <li class="nav-item {{ request()->routeIs('admin.books') ? 'active' : '' }}">
                <a class="nav-link" href="{{ route('admin.books') }}">
                    <i>📖</i>
                    <span>หนังสือ</span>
                </a>
            </li>

            <li class="nav-item {{ request()->routeIs('admin.orders') ? 'active' : '' }}">
                <a class="nav-link" href="{{ route('admin.orders') }}">
                    <i>🧾</i>
                    <span>คำสั่งซื้อ</span>
                </a>
            </li>

            <li class="nav-item {{ request()->routeIs('admin.reports') ? 'active' : '' }}">
                <a class="nav-link position-relative" href="{{ route('admin.reports') }}">
                    <i>🚩</i>
                    <span>รายงาน</span>
                    <span id="newReportsBadge" class="badge badge-counter bg-danger {{ (($unseenReportCount ?? 0) > 0) ? '' : 'd-none' }}">
                        {{ (($unseenReportCount ?? 0) > 99) ? '99+' : ($unseenReportCount ?? 0) }}
                    </span>
                </a>
            </li>

            <li class="nav-item {{ request()->routeIs('admin.report-chats.index') || request()->routeIs('admin.report.chat') ? 'active' : '' }}">
                <a class="nav-link position-relative" href="{{ route('admin.report-chats.index') }}">
                    <i>💬</i>
                    <span>แชทผู้รายงาน</span>
                    <span id="reportUnreadBadge" class="badge badge-counter bg-danger {{ (($unreadReportCount ?? 0) > 0) ? '' : 'd-none' }}">
                        {{ (($unreadReportCount ?? 0) > 99) ? '99+' : ($unreadReportCount ?? 0) }}
                    </span>
                </a>
            </li>

            <hr class="sidebar-divider">

            <li class="nav-item">
                <a class="nav-link" href="{{ route('home') }}">
                    <i>🏠</i>
                    <span>กลับหน้าเว็บ</span>
                </a>
            </li>

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
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('admin.dashboard') }}">🛡️ Admin Panel</a>
                        </li>
                    </ul>
                </nav>
                {{-- End of Topbar --}}

                <div class="container-fluid">
                    @if (session('success'))
                        <div class="alert alert-success d-none" data-flash="success">{{ session('success') }}</div>
                    @endif
                    @if (session('error'))
                        <div class="alert alert-danger d-none" data-flash="error">{{ session('error') }}</div>
                    @endif

                    @yield('content')
                </div>

            </div>

            <footer class="sticky-footer bg-white">
                <div class="container my-auto">
                    <div class="copyright text-center my-auto">
                        <span>ตลาดซื้อขายแลกเปลี่ยนหนังสือมือสอง — Admin Panel</span>
                    </div>
                </div>
            </footer>
        </div>

    </div>

    <a class="scroll-to-top rounded" href="#page-top">↑</a>

    <script src="{{ asset('vendor/sb-admin-2/vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('vendor/sb-admin-2/js/sidebar.js') }}"></script>
</body>
</html>
