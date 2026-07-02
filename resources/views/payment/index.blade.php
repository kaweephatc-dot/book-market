@extends('layouts.app')

@section('title', 'ช่องทางการชำระเงิน')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-8">
        <h3 class="mb-4">💳 ช่องทางการชำระเงินของฉัน</h3>

        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        {{-- รายการช่องทางที่มี --}}
        <div class="row g-3 mb-4">
            @forelse ($methods as $method)
                <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-body">
                            @if ($method->type === 'qr')
                                <h6>📱 QR Code</h6>
                                <img src="{{ asset('storage/' . $method->qr_image) }}" class="img-fluid rounded mb-2" style="max-height: 200px;" alt="QR Code">
                            @else
                                <h6>🏦 โอนเงินผ่านธนาคาร</h6>
                                <p class="mb-1"><strong>ธนาคาร:</strong> {{ $method->bank_name }}</p>
                                <p class="mb-1"><strong>เลขบัญชี:</strong> {{ $method->account_number }}</p>
                                <p class="mb-1"><strong>ชื่อบัญชี:</strong> {{ $method->account_name }}</p>
                            @endif

                            <form method="POST" action="{{ route('payment.destroy', $method) }}" class="mt-2" onsubmit="return confirm('ต้องการลบช่องทางนี้?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger">ลบ</button>
                            </form>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-12">
                    <p class="text-muted">ยังไม่มีช่องทางการชำระเงิน เพิ่มด้านล่างได้เลย</p>
                </div>
            @endforelse
        </div>

        {{-- ฟอร์มเพิ่มช่องทางใหม่ --}}
        <div class="card">
            <div class="card-body">
                <h5 class="mb-3">เพิ่มช่องทางใหม่</h5>

                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('payment.store') }}" enctype="multipart/form-data">
                    @csrf

                    <div class="mb-3">
                        <label class="form-label">ประเภทช่องทาง</label>
                        <select name="type" id="typeSelect" class="form-select" required>
                            <option value="qr">QR Code (พร้อมเพย์)</option>
                            <option value="bank">เลขบัญชีธนาคาร</option>
                        </select>
                    </div>

                    {{-- ส่วน QR --}}
                    <div id="qrFields">
                        <div class="mb-3">
                            <label class="form-label">อัปโหลดรูป QR Code</label>
                            <input type="file" name="qr_image" class="form-control" accept="image/*">
                        </div>
                    </div>

                    {{-- ส่วน Bank --}}
                    <div id="bankFields" style="display: none;">
                        <div class="mb-3">
                            <label class="form-label">ธนาคาร</label>
                            <input type="text" name="bank_name" class="form-control" placeholder="เช่น กสิกรไทย">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">เลขบัญชี</label>
                            <input type="text" name="account_number" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">ชื่อบัญชี</label>
                            <input type="text" name="account_name" class="form-control">
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary">เพิ่มช่องทาง</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    const typeSelect = document.getElementById('typeSelect');
    const qrFields = document.getElementById('qrFields');
    const bankFields = document.getElementById('bankFields');

    function toggleFields() {
        if (typeSelect.value === 'qr') {
            qrFields.style.display = 'block';
            bankFields.style.display = 'none';
        } else {
            qrFields.style.display = 'none';
            bankFields.style.display = 'block';
        }
    }

    typeSelect.addEventListener('change', toggleFields);
    toggleFields();
</script>
@endsection