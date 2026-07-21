<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - @yield('title')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
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
                <a href="{{ route('admin.reports') }}" class="btn btn-sm btn-outline-light">รายงาน</a>
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