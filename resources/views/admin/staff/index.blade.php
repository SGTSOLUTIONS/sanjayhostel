@extends('layouts.admin')

@section('title', 'Staff Management')
@section('page-title', 'Staff Management')

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
    <div>
        <h2 class="fw-bold mb-1">Staff Management</h2>
        <p class="text-muted mb-0">Manage staff members and track attendance</p>
    </div>
    <button class="btn btn-primary rounded-pill" data-bs-toggle="modal" data-bs-target="#addStaffModal">
        <i class="bi bi-plus-lg"></i> Add Staff Member
    </button>
</div>

<!-- Stats Cards -->
<div class="row g-4 mb-4">
    <div class="col-md-3">
        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <p class="text-muted mb-1">Total Staff</p>
                    <h3 class="fw-bold mb-0 text-primary">{{ $totalStaff ?? 0 }}</h3>
                    <small class="text-muted">All staff members</small>
                </div>
                <div class="stat-icon bg-primary bg-opacity-10 rounded-circle p-2">
                    <i class="bi bi-people fs-4 text-primary"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <p class="text-muted mb-1">Active Staff</p>
                    <h3 class="fw-bold mb-0 text-success">{{ $activeStaff ?? 0 }}</h3>
                    <small class="text-muted">Currently working</small>
                </div>
                <div class="stat-icon bg-success bg-opacity-10 rounded-circle p-2">
                    <i class="bi bi-person-check fs-4 text-success"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <p class="text-muted mb-1">Monthly Salary</p>
                    <h3 class="fw-bold mb-0 text-danger">₹{{ number_format($totalSalary ?? 0) }}</h3>
                    <small class="text-muted">Total payroll</small>
                </div>
                <div class="stat-icon bg-danger bg-opacity-10 rounded-circle p-2">
                    <i class="bi bi-cash-stack fs-4 text-danger"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <p class="text-muted mb-1">This Month</p>
                    <h3 class="fw-bold mb-0 text-info">{{ date('F Y') }}</h3>
                    <small class="text-muted">{{ $currentMonth ?? date('Y-m') }}</small>
                </div>
                <div class="stat-icon bg-info bg-opacity-10 rounded-circle p-2">
                    <i class="bi bi-calendar-check fs-4 text-info"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="admin-card mb-4">
    <div class="card-header-custom">
        <i class="bi bi-funnel me-2"></i> Filter Staff
    </div>
    <div class="p-3">
        <form method="GET" action="{{ route('admin.staff.index') }}" class="row g-3">
            <div class="col-md-3">
                <label class="form-label">Hostel</label>
                <select name="hostel_id" class="form-select">
                    <option value="">All Hostels</option>
                    @foreach($hostels as $hostel)
                        <option value="{{ $hostel->id }}" {{ request('hostel_id') == $hostel->id ? 'selected' : '' }}>
                            {{ $hostel->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Position</label>
                <select name="position" class="form-select">
                    <option value="">All Positions</option>
                    @foreach($positions ?? [] as $position)
                        <option value="{{ $position }}" {{ request('position') == $position ? 'selected' : '' }}>
                            {{ ucfirst($position) }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="">All Status</option>
                    <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                    <option value="left" {{ request('status') == 'left' ? 'selected' : '' }}>Left</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Search</label>
                <input type="text" name="search" class="form-control" placeholder="Name or phone..." value="{{ request('search') }}">
            </div>
            <div class="col-md-12">
                <button type="submit" class="btn btn-primary">Apply Filter</button>
                <a href="{{ route('admin.staff.index') }}" class="btn btn-secondary">Reset</a>
                <a href="{{ route('admin.staff.attendance') }}" class="btn btn-info float-end">
                    <i class="bi bi-calendar-check"></i> Mark Attendance
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Staff Table -->
<div class="admin-card">
    <div class="card-header-custom">
        <i class="bi bi-list-ul me-2"></i> Staff Members
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>ID</th>
                    <th>Photo</th>
                    <th>Name</th>
                    <th>Position</th>
                    <th>Phone</th>
                    <th>Hostel</th>
                    <th>Salary</th>
                    <th>Joining Date</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($staff as $member)
                <tr>
                    <td>{{ $member->id }}</td>
                    <td>
                        @if($member->profile_image)
                            <img src="{{ asset($member->profile_image) }}" alt="{{ $member->name }}" class="rounded-circle" width="40" height="40" style="object-fit: cover;">
                        @else
                            <div class="avatar-circle bg-secondary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                {{ strtoupper(substr($member->name, 0, 1)) }}
                            </div>
                        @endif
                    </td>
                    <td class="fw-semibold">{{ $member->name }}</td>
                    <td><span class="badge bg-info">{{ ucfirst($member->position) }}</span></td>
                    <td>{{ $member->phone ?? '—' }}</td>
                    <td>{{ $member->hostel->name ?? 'N/A' }}</td>
                    <td class="fw-bold">₹{{ number_format($member->salary) }}</td>
                    <td>{{ $member->joining_date->format('d M Y') }}</td>
                    <td>
                        @if($member->status == 'active')
                            <span class="badge bg-success">Active</span>
                        @elseif($member->status == 'inactive')
                            <span class="badge bg-warning">Inactive</span>
                        @else
                            <span class="badge bg-danger">Left</span>
                        @endif
                    </td>
                    <td>
                        <button class="btn btn-sm btn-outline-info view-staff" data-id="{{ $member->id }}">
                            <i class="bi bi-eye"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-primary edit-staff" data-id="{{ $member->id }}">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-success attendance-history" data-id="{{ $member->id }}" data-name="{{ $member->name }}">
                            <i class="bi bi-calendar-check"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-danger delete-staff"
                                data-id="{{ $member->id }}"
                                data-name="{{ $member->name }}">
                            <i class="bi bi-trash"></i>
                        </button>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="10" class="text-center py-5">
                        <i class="bi bi-inbox fs-1 text-muted"></i>
                        <p class="mt-2 mb-0">No staff members found. Click "Add Staff Member" to add one.</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($staff->hasPages())
    <div class="p-3">
        {{ $staff->links() }}
    </div>
    @endif
</div>

<!-- ==================== ADD STAFF MODAL ==================== -->
<div class="modal fade" id="addStaffModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="bi bi-person-plus me-2"></i>Add Staff Member</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="addStaffForm" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Full Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Phone</label>
                            <input type="text" name="phone" class="form-control">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Position <span class="text-danger">*</span></label>
                            <select name="position" class="form-select" required>
                                <option value="">Select Position</option>
                                <option value="manager">Manager</option>
                                <option value="cook">Cook</option>
                                <option value="cleaner">Cleaner</option>
                                <option value="security">Security</option>
                                <option value="maintenance">Maintenance</option>
                                <option value="receptionist">Receptionist</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Salary (₹) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" name="salary" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Joining Date <span class="text-danger">*</span></label>
                            <input type="date" name="joining_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Hostel <span class="text-danger">*</span></label>
                            <select name="hostel_id" class="form-select" required>
                                <option value="">Select Hostel</option>
                                @foreach($hostels as $hostel)
                                    <option value="{{ $hostel->id }}">{{ $hostel->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                                <option value="left">Left</option>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Aadhar Number</label>
                            <input type="text" name="aadhar_number" class="form-control">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Profile Image</label>
                            <input type="file" name="profile_image" class="form-control" accept="image/*">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Address</label>
                        <textarea name="address" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Staff</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ==================== VIEW STAFF MODAL ==================== -->
<div class="modal fade" id="viewStaffModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title"><i class="bi bi-person-badge me-2"></i>Staff Details</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="viewStaffContent">
                <div class="text-center py-4">
                    <div class="spinner-border text-primary"></div>
                    <p>Loading...</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- ==================== EDIT STAFF MODAL ==================== -->
<div class="modal fade" id="editStaffModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title"><i class="bi bi-pencil-square me-2"></i>Edit Staff Member</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="editStaffForm" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <input type="hidden" name="id" id="edit_id">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Full Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="edit_name" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Phone</label>
                            <input type="text" name="phone" id="edit_phone" class="form-control">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" id="edit_email" class="form-control">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Position <span class="text-danger">*</span></label>
                            <select name="position" id="edit_position" class="form-select" required>
                                <option value="manager">Manager</option>
                                <option value="cook">Cook</option>
                                <option value="cleaner">Cleaner</option>
                                <option value="security">Security</option>
                                <option value="maintenance">Maintenance</option>
                                <option value="receptionist">Receptionist</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Salary (₹) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" name="salary" id="edit_salary" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Joining Date <span class="text-danger">*</span></label>
                            <input type="date" name="joining_date" id="edit_joining_date" class="form-control" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Hostel <span class="text-danger">*</span></label>
                            <select name="hostel_id" id="edit_hostel_id" class="form-select" required>
                                <option value="">Select Hostel</option>
                                @foreach($hostels as $hostel)
                                    <option value="{{ $hostel->id }}">{{ $hostel->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Status</label>
                            <select name="status" id="edit_status" class="form-select">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                                <option value="left">Left</option>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Aadhar Number</label>
                            <input type="text" name="aadhar_number" id="edit_aadhar_number" class="form-control">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">New Profile Image</label>
                            <input type="file" name="profile_image" class="form-control" accept="image/*">
                            <small class="text-muted">Leave empty to keep current image</small>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Address</label>
                        <textarea name="address" id="edit_address" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Staff</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ==================== ATTENDANCE HISTORY MODAL ==================== -->
<div class="modal fade" id="attendanceHistoryModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title"><i class="bi bi-calendar-check me-2"></i>Attendance History</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="attendanceHistoryContent">
                <div class="text-center py-4">
                    <div class="spinner-border text-primary"></div>
                    <p>Loading...</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- ==================== DELETE CONFIRMATION MODAL ==================== -->
<div class="modal fade" id="deleteStaffModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="bi bi-exclamation-triangle-fill me-2"></i>Confirm Delete</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete staff member: <strong id="delete_staff_name"></strong>?</p>
                <p class="text-danger mb-0"><small>This will also delete all attendance records for this staff member.</small></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteBtn">Delete Permanently</button>
            </div>
        </div>
    </div>
</div>

<script>
// ==================== ADD STAFF ====================
$('#addStaffForm').on('submit', function(e) {
    e.preventDefault();
    showLoader();

    const formData = new FormData(this);

    $.ajax({
        url: "{{ route('admin.staff.store') }}",
        method: "POST",
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            hideLoader();
            showToast(response.message, 'success');
            $('#addStaffModal').modal('hide');
            $('#addStaffForm')[0].reset();
            setTimeout(() => location.reload(), 1000);
        },
        error: function(xhr) {
            hideLoader();
            let errorMsg = xhr.responseJSON?.message || 'Error adding staff';
            showToast(errorMsg, 'error');
        }
    });
});

// ==================== VIEW STAFF ====================
$('.view-staff').on('click', function() {
    const staffId = $(this).data('id');

    $('#viewStaffContent').html(`
        <div class="text-center py-4">
            <div class="spinner-border text-primary"></div>
            <p>Loading...</p>
        </div>
    `);
    $('#viewStaffModal').modal('show');

    $.ajax({
        url: "{{ url('admin/staff') }}/" + staffId,
        method: "GET",
        success: function(response) {
            if (response.success) {
                const s = response.data;
                let html = `
                    <div class="row">
                        <div class="col-md-4 text-center">
                            ${s.profile_image ? `<img src="${s.profile_image}" class="rounded-circle mb-3" width="150" height="150" style="object-fit: cover;">` : `<div class="avatar-circle bg-secondary text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 150px; height: 150px; font-size: 48px;">${s.name.charAt(0)}</div>`}
                            <h4>${s.name}</h4>
                            <span class="badge bg-info">${s.position.toUpperCase()}</span>
                            <span class="badge ${s.status === 'active' ? 'bg-success' : (s.status === 'inactive' ? 'bg-warning' : 'bg-danger')}">${s.status.toUpperCase()}</span>
                        </div>
                        <div class="col-md-8">
                            <table class="table table-bordered">
                                <tr><th width="35%">Phone</th><td>${s.phone || '—'}</td></tr>
                                <tr><th>Email</th><td>${s.email || '—'}</td></tr>
                                <tr><th>Position</th><td>${s.position}</td></tr>
                                <tr><th>Salary</th><td class="text-danger fw-bold">${s.formatted_salary}</td></tr>
                                <tr><th>Joining Date</th><td>${s.joining_date}</td></tr>
                                <tr><th>Hostel</th><td>${s.hostel_name}</td></tr>
                                <tr><th>Aadhar Number</th><td>${s.aadhar_number || '—'}</td></tr>
                                <tr><th>Address</th><td>${s.address || '—'}</td></tr>
                                <tr><th>Added By</th><td>${s.created_by}</td></tr>
                            </table>

                            <div class="card mt-3">
                                <div class="card-header bg-info text-white">Current Month Summary - ${new Date().toLocaleString('default', { month: 'long', year: 'numeric' })}</div>
                                <div class="card-body">
                                    <div class="row text-center">
                                        <div class="col-md-4">
                                            <h5>Present Days</h5>
                                            <h3 class="text-success">${s.current_month_summary.present_days}</h3>
                                        </div>
                                        <div class="col-md-4">
                                            <h5>Leave Days</h5>
                                            <h3 class="text-danger">${s.current_month_summary.leave_days}</h3>
                                        </div>
                                        <div class="col-md-4">
                                            <h5>Salary This Month</h5>
                                            <h3 class="text-primary">${s.current_month_summary.salary_for_month}</h3>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="card mt-3">
                                <div class="card-header bg-secondary text-white">Recent Attendance</div>
                                <div class="table-responsive">
                                    <table class="table table-sm mb-0">
                                        <thead>
                                            <tr><th>Date</th><th>Status</th><th>Work Details</th><th>Leave Reason</th></tr>
                                        </thead>
                                        <tbody>
                                            ${s.recent_attendance.map(a => `
                                                <tr>
                                                    <td>${a.date}</td>
                                                    <td><span class="badge ${a.status_badge}">${a.status}</span></td>
                                                    <td>${a.work_details || '—'}</td>
                                                    <td>${a.leave_reason || '—'}</td>
                                                </tr>
                                            `).join('')}
                                            ${s.recent_attendance.length === 0 ? '<tr><td colspan="4" class="text-center">No attendance records found</td></tr>' : ''}
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
                $('#viewStaffContent').html(html);
            }
        },
        error: function() {
            $('#viewStaffContent').html('<div class="alert alert-danger">Error loading staff details</div>');
        }
    });
});

// ==================== EDIT STAFF ====================
$('.edit-staff').on('click', function() {
    const staffId = $(this).data('id');
    showLoader();

    $.ajax({
        url: "{{ url('admin/staff') }}/" + staffId + "/edit",
        method: "GET",
        success: function(response) {
            hideLoader();
            if (response.success) {
                const s = response.staff;
                $('#edit_id').val(s.id);
                $('#edit_name').val(s.name);
                $('#edit_phone').val(s.phone);
                $('#edit_email').val(s.email);
                $('#edit_position').val(s.position);
                $('#edit_salary').val(s.salary);
                $('#edit_joining_date').val(s.joining_date);
                $('#edit_hostel_id').val(s.hostel_id);
                $('#edit_status').val(s.status);
                $('#edit_aadhar_number').val(s.aadhar_number);
                $('#edit_address').val(s.address);
                $('#editStaffModal').modal('show');
            }
        },
        error: function() {
            hideLoader();
            showToast('Error loading staff data', 'error');
        }
    });
});

// ==================== EDIT STAFF SUBMIT ====================
$('#editStaffForm').on('submit', function(e) {
    e.preventDefault();
    showLoader();

    const id = $('#edit_id').val();
    const formData = new FormData(this);
    formData.append('_method', 'PUT');

    $.ajax({
        url: "{{ url('admin/staff') }}/" + id,
        method: "POST",
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            hideLoader();
            showToast(response.message, 'success');
            $('#editStaffModal').modal('hide');
            setTimeout(() => location.reload(), 1000);
        },
        error: function(xhr) {
            hideLoader();
            let errorMsg = xhr.responseJSON?.message || 'Error updating staff';
            showToast(errorMsg, 'error');
        }
    });
});

// ==================== ATTENDANCE HISTORY ====================
$('.attendance-history').on('click', function() {
    const staffId = $(this).data('id');
    const staffName = $(this).data('name');

    $('#attendanceHistoryContent').html(`
        <div class="text-center py-4">
            <div class="spinner-border text-primary"></div>
            <p>Loading attendance history for ${staffName}...</p>
        </div>
    `);
    $('#attendanceHistoryModal').modal('show');

    const month = new Date().getMonth() + 1;
    const year = new Date().getFullYear();

    $.ajax({
        url: "{{ url('admin/staff') }}/" + staffId + "/attendance-history",
        method: "GET",
        data: { month: month, year: year },
        success: function(response) {
            if (response.success) {
                const data = response.data;
                let html = `
                    <h5 class="mb-3">Staff: ${data.staff_name} (${data.staff_position})</h5>
                    <h6>Month: ${data.month}</h6>

                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="card text-center bg-success text-white">
                                <div class="card-body">
                                    <h5>Present</h5>
                                    <h3>${data.summary.present}</h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card text-center bg-danger text-white">
                                <div class="card-body">
                                    <h5>Leave</h5>
                                    <h3>${data.summary.leave}</h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card text-center bg-warning text-white">
                                <div class="card-body">
                                    <h5>Half Day</h5>
                                    <h3>${data.summary.half_day}</h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card text-center bg-info text-white">
                                <div class="card-body">
                                    <h5>Holiday</h5>
                                    <h3>${data.summary.holiday}</h3>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Status</th>
                                    <th>Work Details</th>
                                    <th>Leave Reason</th>
                                    <th>Proof</th>
                                    <th>Marked By</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${data.attendances.map(a => `
                                    <tr>
                                        <td>${a.date}</td>
                                        <td><span class="badge ${a.status_badge}">${a.status}</span></td>
                                        <td>${a.work_details || '—'}</td>
                                        <td>${a.leave_reason || '—'}</td>
                                        <td>${a.proof_image ? `<a href="${a.proof_image}" target="_blank" class="btn btn-sm btn-outline-primary">View</a>` : '—'}</td>
                                        <td>${a.marked_by}</td>
                                    </tr>
                                `).join('')}
                                ${data.attendances.length === 0 ? '<tr><td colspan="6" class="text-center">No attendance records found</td></tr>' : ''}
                            </tbody>
                        </table>
                    </div>
                `;
                $('#attendanceHistoryContent').html(html);
            }
        },
        error: function() {
            $('#attendanceHistoryContent').html('<div class="alert alert-danger">Error loading attendance history</div>');
        }
    });
});

// ==================== DELETE STAFF ====================
let deleteStaffId = null;

$('.delete-staff').on('click', function() {
    deleteStaffId = $(this).data('id');
    $('#delete_staff_name').text($(this).data('name'));
    $('#deleteStaffModal').modal('show');
});

$('#confirmDeleteBtn').on('click', function() {
    if (!deleteStaffId) return;
    showLoader();

    $.ajax({
        url: "{{ url('admin/staff') }}/" + deleteStaffId,
        method: "DELETE",
        data: { _token: "{{ csrf_token() }}" },
        success: function(response) {
            hideLoader();
            showToast(response.message, 'success');
            $('#deleteStaffModal').modal('hide');
            setTimeout(() => location.reload(), 1000);
        },
        error: function(xhr) {
            hideLoader();
            let errorMsg = xhr.responseJSON?.message || 'Error deleting staff';
            showToast(errorMsg, 'error');
        }
    });
});

// Utility Functions
function showToast(message, type = 'success') {
    const bgMap = { success: '#10b981', error: '#ef4444', info: '#3b82f6' };
    const iconMap = { success: 'bi-check-circle-fill', error: 'bi-exclamation-triangle-fill', info: 'bi-info-circle-fill' };
    const toast = $(`
        <div class="position-fixed top-0 end-0 p-3" style="z-index: 9999;">
            <div class="toast show" role="alert">
                <div class="toast-header" style="background: ${bgMap[type]}; color: white;">
                    <i class="bi ${iconMap[type]} me-2"></i>
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

// Reset modals
$('#addStaffModal, #editStaffModal').on('hidden.bs.modal', function() {
    $(this).find('form')[0]?.reset();
});
</script>

<style>
.stat-card {
    background: white;
    border-radius: 16px;
    padding: 1.25rem;
    transition: all 0.3s;
    border: 1px solid #e9ecef;
}
.stat-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 20px rgba(0,0,0,0.08);
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
.badge {
    font-size: 0.75rem;
    padding: 5px 10px;
}
.table td {
    vertical-align: middle;
}
.btn-sm {
    margin: 2px;
}
.stat-icon {
    width: 48px;
    height: 48px;
    display: flex;
    align-items: center;
    justify-content: center;
}
</style>
@endsection
