@extends('admin.layout')

@section('title', 'จัดการรายงาน')

@section('content')
<h3 class="mb-4">🚩 จัดการรายงาน</h3>

{{-- รายงานที่รอตรวจสอบ --}}
@if ($pendingReports->count() > 0)
    <div class="card border-danger mb-4">
        <div class="card-header bg-danger text-white">
            รายงานที่รอตรวจสอบ ({{ $pendingReports->count() }})
        </div>
        <div class="card-body">
            @foreach ($pendingReports as $report)
                <div class="border rounded p-3 mb-2">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="flex-grow-1">
                            <span class="badge bg-secondary">{{ $report->typeLabel() }}</span>
                            <span class="badge bg-warning text-dark">{{ $report->reason }}</span>

                            {{-- แสดงสิ่งที่ถูกรายงาน --}}
                            <div class="mt-2">
                                @if ($report->reportable_type === 'App\Models\Book' && $report->reportable)
                                    <strong>หนังสือ:</strong> {{ $report->reportable->title }}
                                    <a href="{{ route('books.show', $report->reportable) }}" target="_blank" class="small">(ดู)</a>
                                @elseif ($report->reportable_type === 'App\Models\User' && $report->reportable)
                                    <strong>ร้าน:</strong> {{ $report->reportable->shop_name ?? $report->reportable->name }}
                                    <a href="{{ route('shop.show', $report->reportable) }}" target="_blank" class="small">(ดู)</a>
                                @else
                                    <span class="text-muted">(สิ่งที่ถูกรายงานถูกลบไปแล้ว)</span>
                                @endif
                            </div>

                            @if ($report->detail)
                                <div class="alert alert-light mt-2 mb-1 small">{{ $report->detail }}</div>
                            @endif

                            <div class="small text-muted">
                                รายงานโดย: {{ $report->reporter->name }} · {{ $report->created_at->diffForHumans() }}
                            </div>
                        </div>
                    </div>

                    <div class="d-flex gap-1 mt-2 flex-wrap">
                        {{-- แชทกับผู้รายงาน --}}
                        <a href="{{ route('admin.report.chat', [$report, $report->reporter]) }}" class="btn btn-sm btn-outline-primary">💬 คุยกับผู้รายงาน</a>

                        {{-- แชทกับเจ้าของ (ร้าน/หนังสือ) --}}
                        @php
                            $owner = null;
                            if ($report->reportable_type === 'App\Models\User') {
                                $owner = $report->reportable;
                            } elseif ($report->reportable_type === 'App\Models\Book' && $report->reportable) {
                                $owner = $report->reportable->user;
                            }
                        @endphp
                        @if ($owner)
                            <a href="{{ route('admin.report.chat', [$report, $owner]) }}" class="btn btn-sm btn-outline-info">💬 คุยกับผู้ถูกรายงาน</a>

                            {{-- แบน --}}
                            <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#banModal{{ $report->id }}">🚫 แบน</button>
                        @endif

                        <form method="POST" action="{{ route('admin.reports.resolve', $report) }}">
                            @csrf
                            <button class="btn btn-sm btn-success">✓ จัดการแล้ว</button>
                        </form>
                        <form method="POST" action="{{ route('admin.reports.dismiss', $report) }}">
                            @csrf
                            <button class="btn btn-sm btn-outline-secondary">ปิดเรื่อง</button>
                        </form>
                    </div>

                    {{-- Modal แบน --}}
                    @if ($owner)
                    <div class="modal fade" id="banModal{{ $report->id }}" tabindex="-1">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <form method="POST" action="{{ route('admin.users.banAdvanced', $owner) }}">
                                    @csrf
                                    <div class="modal-header">
                                        <h5 class="modal-title">แบนผู้ใช้: {{ $owner->shop_name ?? $owner->name }}</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="mb-3">
                                            <label class="form-label">ประเภทการแบน</label>
                                            <select name="ban_type" class="form-select" id="banType{{ $report->id }}" onchange="toggleDays{{ $report->id }}()" required>
                                                <option value="permanent">แบนถาวร</option>
                                                <option value="temporary">แบนชั่วคราว</option>
                                            </select>
                                        </div>
                                        <div class="mb-3" id="daysField{{ $report->id }}" style="display: none;">
                                            <label class="form-label">จำนวนวัน</label>
                                            <input type="number" name="days" class="form-control" min="1" placeholder="เช่น 7">
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                                        <button type="submit" class="btn btn-danger">ยืนยันแบน</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    <script>
                        function toggleDays{{ $report->id }}() {
                            const type = document.getElementById('banType{{ $report->id }}').value;
                            document.getElementById('daysField{{ $report->id }}').style.display = type === 'temporary' ? 'block' : 'none';
                        }
                    </script>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
@else
    <div class="alert alert-success">ไม่มีรายงานที่รอตรวจสอบ 🎉</div>
@endif

{{-- ประวัติรายงานที่จัดการแล้ว --}}
<div class="card shadow-sm">
    <div class="card-header">ประวัติรายงาน</div>
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead>
                <tr>
                    <th>ประเภท</th>
                    <th>เหตุผล</th>
                    <th>ผู้รายงาน</th>
                    <th>สถานะ</th>
                    <th>วันที่</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($resolvedReports as $report)
                    <tr>
                        <td>{{ $report->typeLabel() }}</td>
                        <td>{{ $report->reason }}</td>
                        <td>{{ $report->reporter->name }}</td>
                        <td>
                            @if ($report->status === 'resolved')
                                <span class="badge bg-success">จัดการแล้ว</span>
                            @else
                                <span class="badge bg-secondary">ปิดเรื่อง</span>
                            @endif
                        </td>
                        <td class="small text-muted">{{ $report->created_at->format('d/m/Y') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3">
    {{ $resolvedReports->links() }}
</div>
@endsection