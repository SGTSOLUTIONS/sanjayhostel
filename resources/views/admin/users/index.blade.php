@extends('layouts.admin')

@section('title', 'User Management')
@section('page-title', 'User Management')

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
    <div>
        <h2 class="fw-bold mb-1">User Management</h2>
        <p class="text-muted mb-0">Manage system users and assign admins to hostels</p>
    </div>
    <button class="btn btn-primary rounded-pill" data-bs-toggle="modal" data-bs-target="#addUserModal">
        <i class="bi bi-person-plus"></i> Add New User
    </button>
</div>

<!-- Stats Cards -->
<div class="row g-4 mb-4">
    <div class="col-md-3">
        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <p class="text-muted mb-1">Total Users</p>
                    <h3 class="fw-bold mb-0">{{ $totalUsers ?? 0 }}</h3>
                </div>
                <i class="bi bi-people fs-1 text-primary opacity-50"></i>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <p class="text-muted mb-1">Super Admins</p>
                    <h3 class="fw-bold mb-0 text-danger">{{ $totalSuperAdmins ?? 0 }}</h3>
                </div>
                <i class="bi bi-shield-lock fs-1 text-danger opacity-50"></i>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <p class="text-muted mb-1">Admins</p>
                    <h3 class="fw-bold mb-0 text-info">{{ $totalAdmins ?? 0 }}</h3>
                </div>
                <i class="bi bi-person-badge fs-1 text-info opacity-50"></i>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <p class="text-muted mb-1">Active Users</p>
                    <h3 class="fw-bold mb-0 text-success">{{ $activeUsers ?? 0 }}</h3>
                </div>
                <i class="bi bi-check-circle fs-1 text-success opacity-50"></i>
            </div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="admin-card mb-4">
    <div class="card-header-custom">
        <i class="bi bi-funnel me-2"></i> Filter Users
    </div>
    <div class="p-3">
        <form method="GET" action="{{ route('admin.users.index') }}" class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Search</label>
                <input type="text" name="search" class="form-control" placeholder="Name, Email or Phone" value="{{ request('search') }}">
            </div>
            <div class="col-md-3">
                <label class="form-label">Role</label>
                <select name="role" class="form-select">
                    <option value="">All Roles</option>
                    <option value="superadmin" {{ request('role') == 'superadmin' ? 'selected' : '' }}>Super Admin</option>
                    <option value="admin" {{ request('role') == 'admin' ? 'selected' : '' }}>Admin</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="">All Status</option>
                    <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100">Apply Filter</button>
            </div>
        </form>
    </div>
</div>

<!-- Users Table -->
<div class="admin-card">
    <div class="card-header-custom">
        <i class="bi bi-list-ul me-2"></i> System Users
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>ID</th>
                    <th>User</th>
                    <th>Contact</th>
                    <th>Role</th>
                    <th>Assigned Hostels</th>
                    <th>Status</th>
                    <th>Joined Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($users as $user)
                <tr>
                    <td>{{ $user->id }}</td>
                    <td>
                        <div class="d-flex align-items-center">
                            <div class="avatar-circle me-2">
                                {{ substr($user->name, 0, 1) }}
                            </div>
                            <div>
                                <div class="fw-semibold">{{ $user->name }}</div>
                                <small class="text-muted">{{ $user->email }}</small>
                            </div>
                        </div>
                    </td>
                    <td>
                        @if($user->phone)
                            <i class="bi bi-telephone me-1"></i> {{ $user->phone }}<br>
                        @endif
                        @if($user->city)
                            <small class="text-muted"><i class="bi bi-geo-alt"></i> {{ $user->city }}</small>
                        @endif
                    </td>
                    <td>
                        @if($user->role == 'superadmin')
                            <span class="badge bg-danger"><i class="bi bi-shield-lock"></i> Super Admin</span>
                        @else
                            <span class="badge bg-info"><i class="bi bi-person-badge"></i> Admin</span>
                        @endif
                    </td>
                    <td>
                        @if($user->role == 'admin')
                            @if($user->hostels->count() > 0)
                                <span class="badge bg-secondary">{{ $user->hostels->count() }} Hostel(s)</span>
                                <button class="btn btn-link btn-sm p-0 view-hostels" data-user-id="{{ $user->id }}" data-user-name="{{ $user->name }}">
                                    <i class="bi bi-eye"></i>
                                </button>
                            @else
                                <span class="text-danger">No hostels assigned</span>
                            @endif
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td>
                        <div class="form-check form-switch">
                            <input type="checkbox" class="form-check-input toggle-status"
                                   data-id="{{ $user->id }}"
                                   {{ $user->status == 'active' ? 'checked' : '' }}
                                   {{ $user->id == auth()->id() ? 'disabled' : '' }}>
                        </div>
                    </td>
                    <td>
                        <small>{{ $user->created_at->format('d M Y') }}</small>
                    </td>
                    <td>
                        <button class="btn btn-sm btn-outline-info edit-user" data-id="{{ $user->id }}">
                            <i class="bi bi-pencil"></i>
                        </button>
                        @if($user->id != auth()->id())
                            <button class="btn btn-sm btn-outline-danger delete-user"
                                    data-id="{{ $user->id }}"
                                    data-name="{{ $user->name }}">
                                <i class="bi bi-trash"></i>
                            </button>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="text-center py-5">
                        <i class="bi bi-inbox fs-1 text-muted"></i>
                        <p class="mt-2 mb-0">No users found.</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($users->hasPages())
        <div class="p-3">
            {{ $users->links() }}
        </div>
    @endif
</div>

<!-- Add/Edit User Modal -->
<div class="modal fade" id="userModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header" id="modalHeader">
                <h5 class="modal-title"><i class="bi bi-person-plus me-2"></i>Add New User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="userForm">
                @csrf
                <input type="hidden" name="user_id" id="user_id">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Full Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="name" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Email <span class="text-danger">*</span></label>
                            <input type="email" name="email" id="email" class="form-control" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Phone</label>
                            <input type="text" name="phone" id="phone" class="form-control">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Password</label>
                            <input type="password" name="password" id="password" class="form-control">
                            <small class="text-muted" id="passwordHint">Minimum 8 characters. Leave blank to keep current password.</small>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Role <span class="text-danger">*</span></label>
                            <select name="role" id="role" class="form-select" required>
                                <option value="">Select Role</option>
                                <option value="superadmin">Super Admin</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Status <span class="text-danger">*</span></label>
                            <select name="status" id="status" class="form-select" required>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Gender</label>
                            <select name="gender" id="gender" class="form-select">
                                <option value="">Select Gender</option>
                                <option value="male">Male</option>
                                <option value="female">Female</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Date of Birth</label>
                            <input type="date" name="date_of_birth" id="date_of_birth" class="form-control">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">City</label>
                        <input type="text" name="city" id="city" class="form-control">
                    </div>

                    <!-- Hostel Assignment (Only visible for Admin role) -->
                    <div id="hostelSection" style="display: none;">
                        <div class="alert alert-info">
                            <i class="bi bi-building"></i> <strong>Assign Hostels</strong><br>
                            Select which hostels this admin can manage.
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Select Hostels</label>
                            <select name="hostel_ids[]" id="hostel_ids" class="form-select" multiple size="5">
                                @foreach($hostels as $hostel)
                                    <option value="{{ $hostel->id }}">{{ $hostel->name }}</option>
                                @endforeach
                            </select>
                            <small class="text-muted">Hold Ctrl/Cmd to select multiple hostels</small>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="submitBtn">Create User</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- View Hostels Modal -->
<div class="modal fade" id="viewHostelsModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title"><i class="bi bi-building me-2"></i>Assigned Hostels</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="viewHostelsContent">
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

<script>
let isEditMode = false;

// Show/Hide hostel section based on role
$('#role').on('change', function() {
    if ($(this).val() === 'admin') {
        $('#hostelSection').slideDown();
        $('#hostel_ids').prop('required', true);
    } else {
        $('#hostelSection').slideUp();
        $('#hostel_ids').prop('required', false);
    }
});

// Edit User
$('.edit-user').on('click', function() {
    const userId = $(this).data('id');
    isEditMode = true;

    $('#modalHeader').removeClass('bg-primary').addClass('bg-warning');
    $('#modalHeader h5').html('<i class="bi bi-pencil-square me-2"></i>Edit User');
    $('#submitBtn').html('Update User');
    $('#passwordHint').text('Leave blank to keep current password');
    $('#password').prop('required', false);

    showLoader();

    $.ajax({
       url: "{{ route('admin.users.edit', ':id') }}".replace(':id', userId),
        method: "GET",
        success: function(response) {
            hideLoader();
            if (response.success) {
                const user = response.user;
                $('#user_id').val(user.id);
                $('#name').val(user.name);
                $('#email').val(user.email);
                $('#phone').val(user.phone);
                $('#role').val(user.role);
                $('#status').val(user.status);
                $('#gender').val(user.gender);
                $('#city').val(user.city);
                $('#date_of_birth').val(user.date_of_birth);

                if (user.role === 'admin') {
                    $('#hostelSection').show();
                    $('#hostel_ids').val(user.hostel_ids);
                } else {
                    $('#hostelSection').hide();
                }

                $('#userModal').modal('show');
            }
        },
        error: function() {
            hideLoader();
            showToast('Error loading user data', 'error');
        }
    });
});

// Add User button
$('[data-bs-target="#addUserModal"]').on('click', function() {
    isEditMode = false;
    $('#modalHeader').removeClass('bg-warning').addClass('bg-primary');
    $('#modalHeader h5').html('<i class="bi bi-person-plus me-2"></i>Add New User');
    $('#submitBtn').html('Create User');
    $('#passwordHint').text('Minimum 8 characters');
    $('#password').prop('required', true);
    $('#userForm')[0].reset();
    $('#user_id').val('');
    $('#hostelSection').hide();
});

// Form Submit
$('#userForm').on('submit', function(e) {
    e.preventDefault();
    showLoader();

    const userId = $('#user_id').val();
   const url = userId
    ? "{{ route('admin.users.update', ':id') }}".replace(':id', userId)
    : "{{ route('admin.users.store') }}";const method = userId ? 'POST' : 'POST';

    let formData = $(this).serialize();
    if (userId) {
        formData += '&_method=PUT';
    }

    $.ajax({
        url: url,
        method: method,
        data: formData,
        success: function(response) {
            hideLoader();
            showToast(response.message, 'success');
            $('#userModal').modal('hide');
            setTimeout(() => location.reload(), 1000);
        },
        error: function(xhr) {
            hideLoader();
            let errorMsg = xhr.responseJSON?.message || 'Error saving user';
            showToast(errorMsg, 'error');
        }
    });
});

// Toggle Status
$('.toggle-status').on('change', function() {
    const userId = $(this).data('id');
    const isChecked = $(this).is(':checked');
    const $switch = $(this);

    showLoader();

    $.ajax({
        url: "{{ url('admin/users') }}/" + userId + "/toggle-status",
        method: "POST",
        data: {
            _token: "{{ csrf_token() }}"
        },
        success: function(response) {
            hideLoader();
            showToast(response.message, 'success');
            setTimeout(() => location.reload(), 500);
        },
        error: function(xhr) {
            hideLoader();
            $switch.prop('checked', !isChecked);
            let errorMsg = xhr.responseJSON?.message || 'Error updating status';
            showToast(errorMsg, 'error');
        }
    });
});

// Delete User
$('.delete-user').on('click', function() {
    const userId = $(this).data('id');
    const userName = $(this).data('name');

    if (confirm(`Are you sure you want to delete user "${userName}"? This action cannot be undone.`)) {
        showLoader();

        $.ajax({
            url: "{{ url('admin/users') }}/" + userId,
            method: "DELETE",
            data: {
                _token: "{{ csrf_token() }}"
            },
            success: function(response) {
                hideLoader();
                showToast(response.message, 'success');
                setTimeout(() => location.reload(), 1000);
            },
            error: function(xhr) {
                hideLoader();
                let errorMsg = xhr.responseJSON?.message || 'Error deleting user';
                showToast(errorMsg, 'error');
            }
        });
    }
});

// View Hostels
$('.view-hostels').on('click', function() {
    const userId = $(this).data('user-id');
    const userName = $(this).data('user-name');

    $('#viewHostelsContent').html(`
        <div class="text-center py-4">
            <div class="spinner-border text-primary"></div>
            <p>Loading...</p>
        </div>
    `);
    $('#viewHostelsModal').modal('show');

    $.ajax({
      url: "{{ route('admin.users.edit', ':id') }}".replace(':id', userId),
        method: "GET",
        success: function(response) {
            if (response.success && response.user.hostel_ids.length > 0) {
                let hostelsList = '<div class="list-group">';
                response.user.hostel_ids.forEach(hostelId => {
                    const hostelName = $('#hostel_ids option[value="' + hostelId + '"]').text();
                    if (hostelName) {
                        hostelsList += `<div class="list-group-item"><i class="bi bi-building me-2"></i> ${hostelName}</div>`;
                    }
                });
                hostelsList += '</div>';
                $('#viewHostelsContent').html(`<h6>Hostels assigned to ${userName}:</h6>${hostelsList}`);
            } else {
                $('#viewHostelsContent').html('<div class="alert alert-warning">No hostels assigned to this admin.</div>');
            }
        },
        error: function() {
            $('#viewHostelsContent').html('<div class="alert alert-danger">Error loading hostels data</div>');
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
                    <strong class="me-auto">${type === 'success' ? 'Success' : type === 'error' ? 'Error' : 'Info'}</strong>
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
.avatar-circle {
    width: 40px;
    height: 40px;
    background: #e9ecef;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    margin-right: 10px;
}
.table td {
    vertical-align: middle;
}
.btn-sm {
    margin: 2px;
}
.form-check-input:checked {
    background-color: #10b981;
    border-color: #10b981;
}
</style>
@endsection
