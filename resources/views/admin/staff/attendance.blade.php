@extends('layouts.admin')

@section('title', 'Mark Attendance')
@section('page-title', 'Mark Staff Attendance')

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
    <div>
        <h2 class="fw-bold mb-1">Mark Attendance</h2>
        <p class="text-muted mb-0">Mark staff attendance for the day</p>
    </div>
    <a href="{{ route('admin.staff.index') }}" class="btn btn-secondary rounded-pill">
        <i class="bi bi-arrow-left"></i> Back to Staff
    </a>
</div>

<!-- Date Selector -->
<div class="admin-card mb-4">
    <div class="card-header-custom">
        <i class="bi bi-calendar-date me-2"></i> Select Date
    </div>
    <div class="p-3">
        <form method="GET" action="{{ route('admin.staff.attendance') }}" class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Attendance Date</label>
                <input type="date" name="date" class="form-control" value="{{ $selectedDate }}" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">Filter by Hostel</label>
                <select name="hostel_id" id="hostel_filter" class="form-select">
                    <option value="">All Hostels</option>
                    @foreach($hostels as $hostel)
                        <option value="{{ $hostel->id }}">{{ $hostel->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100">Load Staff</button>
            </div>
        </form>
    </div>
</div>

<!-- Staff Attendance Table -->
<div class="admin-card">
    <div class="card-header-custom">
        <i class="bi bi-person-check me-2"></i> Staff Attendance - {{ date('d F Y', strtotime($selectedDate)) }}
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>ID</th>
                    <th>Photo</th>
                    <th>Name</th>
                    <th>Position</th>
                    <th>Hostel</th>
                    <th>Status</th>
                    <th>Work Details</th>
                    <th>Leave Reason</th>
                    <th>Proof</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="attendanceTableBody">
                @forelse($staff as $member)
                @php
                    $attendance = $member->attendances->first();
                @endphp
                <tr data-staff-id="{{ $member->id }}">
                    <td>{{ $member->id }}</td>
                    <td>
                        @if($member->profile_image)
                            <img src="{{ asset($member->profile_image) }}" class="rounded-circle" width="40" height="40" style="object-fit: cover;">
                        @else
                            <div class="avatar-circle bg-secondary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                {{ strtoupper(substr($member->name, 0, 1)) }}
                            </div>
                        @endif
                    </td>
                    <td class="fw-semibold">{{ $member->name }}</td>
                    <td><span class="badge bg-info">{{ ucfirst($member->position) }}</span></td>
                    <td>{{ $member->hostel->name ?? 'N/A' }}</td>
                    <td>
                        <select class="form-select status-select" style="width: 130px;">
                            <option value="present" {{ $attendance && $attendance->status == 'present' ? 'selected' : '' }}>✓ Present</option>
                            <option value="leave" {{ $attendance && $attendance->status == 'leave' ? 'selected' : '' }}>❌ Leave</option>
                            <option value="half_day" {{ $attendance && $attendance->status == 'half_day' ? 'selected' : '' }}>⏳ Half Day</option>
                            <option value="holiday" {{ $attendance && $attendance->status == 'holiday' ? 'selected' : '' }}>🎉 Holiday</option>
                        </select>
                    </td>
                    <td>
                        <textarea class="form-control work-details" rows="1" placeholder="Work done today..." style="width: 200px;">{{ $attendance->work_details ?? '' }}</textarea>
                    </td>
                    <td>
                        <textarea class="form-control leave-reason" rows="1" placeholder="Reason for leave..." style="width: 200px;">{{ $attendance->leave_reason ?? '' }}</textarea>
                    </td>
                    <td>
                        <input type="file" class="form-control proof-image" accept="image/*" style="width: 100px;">
                        @if($attendance && $attendance->proof_image)
                            <a href="{{ asset($attendance->proof_image) }}" target="_blank" class="btn btn-sm btn-link">View</a>
                        @endif
                    </td>
                    <td>
                        <button class="btn btn-sm btn-primary save-attendance" data-staff-id="{{ $member->id }}" data-staff-name="{{ $member->name }}">
                            <i class="bi bi-save"></i> Save
                        </button>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="10" class="text-center py-5">
                        <i class="bi bi-inbox fs-1 text-muted"></i>
                        <p class="mt-2 mb-0">No staff members found for the selected filters.</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<script>
// Save attendance for a staff member
$('.save-attendance').on('click', function() {
    const staffId = $(this).data('staff-id');
    const staffName = $(this).data('staff-name');
    const row = $(this).closest('tr');
    const status = row.find('.status-select').val();
    const workDetails = row.find('.work-details').val();
    const leaveReason = row.find('.leave-reason').val();
    const proofFile = row.find('.proof-image')[0].files[0];
    const attendanceDate = "{{ $selectedDate }}";

    showLoader();

    const formData = new FormData();
    formData.append('staff_id', staffId);
    formData.append('attendance_date', attendanceDate);
    formData.append('status', status);
    formData.append('work_details', workDetails);
    formData.append('leave_reason', leaveReason);
    if (proofFile) {
        formData.append('proof_image', proofFile);
    }

    $.ajax({
        url: "{{ route('admin.staff.attendance.mark') }}",
        method: "POST",
        data: formData,
        processData: false,
        contentType: false,
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        success: function(response) {
            hideLoader();
            showToast(`${staffName}: ${response.message}`, 'success');
            // Reload to show updated proof link
            setTimeout(() => location.reload(), 1000);
        },
        error: function(xhr) {
            hideLoader();
            let errorMsg = xhr.responseJSON?.message || 'Error marking attendance';
            showToast(errorMsg, 'error');
        }
    });
});

// Filter by hostel
$('#hostel_filter').on('change', function() {
    const hostelId = $(this).val();
    const date = "{{ $selectedDate }}";
    let url = "{{ route('admin.staff.attendance') }}?date=" + date;
    if (hostelId) {
        url += "&hostel_id=" + hostelId;
    }
    window.location.href = url;
});

function showToast(message, type = 'success') {
    const bgMap = { success: '#10b981', error: '#ef4444', info: '#3b82f6' };
    const toast = $(`
        <div class="position-fixed top-0 end-0 p-3" style="z-index: 9999;">
            <div class="toast show" role="alert">
                <div class="toast-header" style="background: ${bgMap[type]}; color: white;">
                    <strong class="me-auto">${type === 'success' ? 'Success' : 'Error'}</strong>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast"></button>
                </div>
                <div class="toast-body">${message}</div>
            </div>
        </div>
    `);
    $('body').append(toast);
    setTimeout(() => toast.remove(), 3000);
}

function showLoader() {
    if ($('#globalLoader').length === 0) {
        $('body').append('<div id="globalLoader" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);z-index:9999;"><div style="position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);"><div class="spinner-border text-light" style="width:3rem;height:3rem;"></div></div></div>');
    }
    $('#globalLoader').fadeIn(200);
}

function hideLoader() {
    $('#globalLoader').fadeOut(200);
}
</script>

<style>
.stat-card {
    background: white;
    border-radius: 16px;
    padding: 1.25rem;
    transition: all 0.3s;
    border: 1px solid #e9ecef;
}
.admin-card {
    background: white;
    border-radius: 16px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.04);
    overflow: hidden;
}
.card-header-custom {
    padding: 1rem 1.25rem;
    background: white;
    border-bottom: 2px solid #f0f2f5;
    font-weight: 600;
}
</style>
@endsection
