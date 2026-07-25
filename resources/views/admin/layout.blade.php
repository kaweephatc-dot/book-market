<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - @yield('title')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    @vite(['resources/js/app.js'])
</head>
<body class="bg-light">

    <nav class="navbar navbar-dark bg-danger">
        <div class="container">
            <span class="navbar-brand">🛡️ Admin Panel</span>
            <div class="d-flex gap-2">
                <a href="{{ route('admin.dashboard') }}" class="btn btn-sm btn-outline-light">Dashboard</a>
                <a href="{{ route('admin.users') }}" class="btn btn-sm btn-outline-light">ผู้ใช้</a>
                <a href="{{ route('admin.books') }}" class="btn btn-sm btn-outline-light">หนังสือ</a>
                <a href="{{ route('home') }}" class="btn btn-sm btn-outline-light">กลับหน้าเว็บ</a>
                <a href="{{ route('admin.reports') }}" class="btn btn-sm btn-outline-light position-relative">
                    รายงาน
                    <span id="newReportsBadge" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger {{ (($unseenReportCount ?? 0) > 0) ? '' : 'd-none' }}" style="font-size: 10px;">
                        {{ (($unseenReportCount ?? 0) > 99) ? '99+' : ($unseenReportCount ?? 0) }}
                    </span>
                </a>
                <a href="{{ route('admin.report-chats.index') }}" class="btn btn-sm btn-outline-light position-relative">
                    💬 แชทผู้รายงาน
                    <span id="reportUnreadBadge" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger {{ (($unreadReportCount ?? 0) > 0) ? '' : 'd-none' }}" style="font-size: 10px;">
                        {{ (($unreadReportCount ?? 0) > 99) ? '99+' : ($unreadReportCount ?? 0) }}
                    </span>
                </a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        @yield('content')
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>