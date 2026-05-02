{{-- resources/views/admin/members/index.blade.php --}}
@extends('layouts.admin')

@section('title', 'Member Management')
@section('page-title', 'Members Management')

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
    <div>
        <h2 class="fw-bold mb-1">Member Management</h2>
        <p class="text-muted mb-0">Manage all residents across hostels</p>
    </div>
    <button class="btn btn-primary rounded-pill" data-bs-toggle="modal" data-bs-target="#addMemberModal">
        <i class="bi bi-plus-lg"></i> Add New Member
    </button>
</div>

<!-- Stats Cards -->
<div class="row g-4 mb-4">
    <div class="col-md-3">
        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <p class="text-muted mb-1">Total Members</p>
                    <h3 class="fw-bold mb-0">{{ $totalMembers ?? 0 }}</h3>
                </div>
                <i class="bi bi-people fs-1 text-primary opacity-50"></i>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <p class="text-muted mb-1">Active / Left</p>
                    <h3 class="fw-bold mb-0">{{ $activeMembers ?? 0 }} / {{ $leftMembers ?? 0 }}</h3>
                </div>
                <i class="bi bi-person-check fs-1 text-success opacity-50"></i>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <p class="text-muted mb-1">With Food / Without</p>
                    <h3 class="fw-bold mb-0">{{ $withFood ?? 0 }} / {{ $withoutFood ?? 0 }}</h3>
                </div>
                <i class="bi bi-egg-fried fs-1 text-warning opacity-50"></i>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <p class="text-muted mb-1">Monthly Revenue</p>
                    <h3 class="fw-bold mb-0">₹{{ number_format($members->sum('rent_amount') ?? 0) }}</h3>
                </div>
                <i class="bi bi-currency-rupee fs-1 text-info opacity-50"></i>
            </div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="admin-card mb-4">
    <div class="card-header-custom">
        <i class="bi bi-funnel me-2"></i> Filter Members
    </div>
    <div class="p-3">
        <form method="GET" action="{{ route('admin.members.index') }}" class="row g-3">
            <div class="col-md-3">
                <label class="form-label">Search</label>
                <input type="text" name="search" class="form-control" placeholder="Name or Phone" value="{{ request('search') }}">
            </div>
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
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="">All</option>
                    <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                    <option value="left" {{ request('status') == 'left' ? 'selected' : '' }}>Left</option>
                </select>
            </div>
            <div class="col-md-3 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100">Apply Filter</button>
            </div>
        </form>
    </div>
</div>

<!-- Members Table -->
<div class="admin-card">
    <div class="card-header-custom">
        <i class="bi bi-list-ul me-2"></i> Members List
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>ID</th>
                    <th>Photo</th>
                    <th>Name</th>
                    <th>Phone</th>
                    <th>Hostel</th>
                    <th>Room/Bed</th>
                    <th>Food</th>
                    <th>Rent (₹)</th>
                    <th>Join Date</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($members as $member)
                <tr>
                    <td>{{ $member->id }}</td>
                    <td>
                        @if($member->image && file_exists(public_path($member->image)))
                            <img src="{{ asset($member->image) }}" width="40" height="40" class="rounded-circle" style="object-fit: cover;">
                        @else
                            <div class="rounded-circle bg-secondary d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                <i class="bi bi-person text-white"></i>
                            </div>
                        @endif
                    </td>
                    <td class="fw-semibold">{{ $member->name }}</td>
                    <td>{{ $member->phone }}</td>
                    <td>{{ $member->hostel->name ?? 'N/A' }}</td>
                    <td>
                        <span class="badge bg-info">Room {{ $member->room->room_number ?? 'N/A' }}</span>
                        <span class="badge bg-secondary">Bed {{ $member->bed->bed_number ?? 'N/A' }}</span>
                    </td>
                    <td>
                        @if($member->with_food)
                            <span class="badge bg-success"><i class="bi bi-egg-fried"></i> With Food</span>
                        @else
                            <span class="badge bg-secondary"><i class="bi bi-egg"></i> Without Food</span>
                        @endif
                    </td>
                    <td class="fw-bold">₹{{ number_format($member->rent_amount) }}</td>
                    <td>{{ $member->join_date ? \Carbon\Carbon::parse($member->join_date)->format('d M Y') : '—' }}</td>
                    <td>
                        @if($member->status == 'active')
                            <span class="badge bg-success">Active</span>
                        @else
                            <span class="badge bg-danger">Left</span>
                        @endif
                    </td>
                    <td>
                        <button class="btn btn-sm btn-outline-info view-member" data-id="{{ $member->id }}">
                            <i class="bi bi-eye"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-primary edit-member"
                                data-id="{{ $member->id }}">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-danger delete-member"
                                data-id="{{ $member->id }}"
                                data-name="{{ $member->name }}">
                            <i class="bi bi-trash"></i>
                        </button>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="11" class="text-center py-5">
                        <i class="bi bi-inbox fs-1 text-muted"></i>
                        <p class="mt-2 mb-0">No members found. Click "Add New Member" to create one.</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($members->hasPages())
    <div class="p-3">
        {{ $members->links() }}
    </div>
    @endif
</div>

<!-- ==================== ADD MEMBER MODAL ==================== -->
<div class="modal fade" id="addMemberModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="bi bi-person-plus me-2"></i>Add New Member</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="addMemberForm" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Full Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Phone Number <span class="text-danger">*</span></label>
                            <input type="text" name="phone" class="form-control" required>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Select Hostel <span class="text-danger">*</span></label>
                            <select name="hostel_id" id="add_hostel_id" class="form-select" required>
                                <option value="">Select Hostel</option>
                                @foreach($hostels as $hostel)
                                    <option value="{{ $hostel->id }}">{{ $hostel->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Select Room <span class="text-danger">*</span></label>
                            <select name="room_id" id="add_room_id" class="form-select" required disabled>
                                <option value="">First select hostel</option>
                            </select>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Select Bed <span class="text-danger">*</span></label>
                            <select name="bed_id" id="add_bed_id" class="form-select" required disabled>
                                <option value="">First select room</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Food Preference <span class="text-danger">*</span></label>
                            <select name="with_food" id="add_with_food" class="form-select" required>
                                <option value="0">Without Food</option>
                                <option value="1">With Food</option>
                            </select>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Join Date <span class="text-danger">*</span></label>
                            <input type="date" name="join_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="active">Active</option>
                                <option value="left">Left</option>
                            </select>
                        </div>
                    </div>

                    <div class="alert alert-info" id="rentDisplay">
                        <i class="bi bi-info-circle"></i> Monthly Rent: <strong id="calculatedRent">₹0</strong>
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Profile Image</label>
                            <input type="file" name="image" class="form-control" accept="image/*">
                            <small class="text-muted">JPG, PNG (Max 2MB)</small>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Admission Form</label>
                            <input type="file" name="addmissionform" class="form-control" accept="image/*,pdf/*">
                            <small class="text-muted">PDF, Image (Max 2MB)</small>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Aadhar Image</label>
                            <input type="file" name="aadharimage" class="form-control" accept="image/*">
                            <small class="text-muted">JPG, PNG (Max 2MB)</small>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Member</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ==================== VIEW MEMBER MODAL ==================== -->
<div class="modal fade" id="viewMemberModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title"><i class="bi bi-person-badge me-2"></i>Member Details</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="viewMemberContent">
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

<!-- ==================== EDIT MEMBER MODAL ==================== -->
<div class="modal fade" id="editMemberModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title"><i class="bi bi-pencil-square me-2"></i>Edit Member</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="editMemberForm" enctype="multipart/form-data">
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
                            <label class="form-label">Phone Number <span class="text-danger">*</span></label>
                            <input type="text" name="phone" id="edit_phone" class="form-control" required>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Select Hostel <span class="text-danger">*</span></label>
                            <select name="hostel_id" id="edit_hostel_id" class="form-select" required>
                                <option value="">Select Hostel</option>
                                @foreach($hostels as $hostel)
                                    <option value="{{ $hostel->id }}">{{ $hostel->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Select Room <span class="text-danger">*</span></label>
                            <select name="room_id" id="edit_room_id" class="form-select" required>
                                <option value="">Select Room</option>
                            </select>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Select Bed <span class="text-danger">*</span></label>
                            <select name="bed_id" id="edit_bed_id" class="form-select" required>
                                <option value="">Select Bed</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Food Preference</label>
                            <select name="with_food" id="edit_with_food" class="form-select">
                                <option value="0">Without Food</option>
                                <option value="1">With Food</option>
                            </select>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Join Date</label>
                            <input type="date" name="join_date" id="edit_join_date" class="form-control">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Status</label>
                            <select name="status" id="edit_status" class="form-select">
                                <option value="active">Active</option>
                                <option value="left">Left</option>
                            </select>
                        </div>
                    </div>

                    <div class="alert alert-info" id="editRentDisplay">
                        <i class="bi bi-info-circle"></i> Monthly Rent: <strong id="editCalculatedRent">₹0</strong>
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Profile Image</label>
                            <input type="file" name="image" class="form-control" accept="image/*">
                            <small class="text-muted">Leave empty to keep current</small>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Admission Form</label>
                            <input type="file" name="addmissionform" class="form-control" accept="image/*,pdf/*">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Aadhar Image</label>
                            <input type="file" name="aadharimage" class="form-control" accept="image/*">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Member</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ==================== DELETE CONFIRMATION MODAL ==================== -->
<div class="modal fade" id="deleteMemberModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="bi bi-exclamation-triangle-fill me-2"></i>Confirm Delete</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete member <strong id="delete_member_name"></strong>?</p>
                <p class="text-danger mb-0"><small>This will also free the bed and delete all associated images. This action cannot be undone.</small></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteBtn">Delete Permanently</button>
            </div>
        </div>
    </div>
</div>

<script>
// ==================== ADD MEMBER - DYNAMIC LOADING ====================
// Load rooms when hostel is selected
$('#add_hostel_id').on('change', function() {
    const hostelId = $(this).val();
    const roomSelect = $('#add_room_id');
    const bedSelect = $('#add_bed_id');

    if (hostelId) {
        roomSelect.prop('disabled', false);
        roomSelect.html('<option value="">Loading...</option>');

        $.ajax({
            url: "{{ url('admin/members/rooms') }}/" + hostelId,
            method: "GET",
            success: function(response) {
                roomSelect.html('<option value="">Select Room</option>');
                if (response.data && response.data.length) {
                    response.data.forEach(room => {
                        roomSelect.append(`<option value="${room.id}" data-room-type="${room.room_type}">Room ${room.room_number} (${room.room_type} - ${room.sharing} Seater)</option>`);
                    });
                } else {
                    roomSelect.append('<option value="">No rooms available</option>');
                }
                bedSelect.prop('disabled', true);
                bedSelect.html('<option value="">First select room</option>');
            },
            error: function() {
                roomSelect.html('<option value="">Error loading rooms</option>');
            }
        });
    } else {
        roomSelect.prop('disabled', true);
        roomSelect.html('<option value="">First select hostel</option>');
        bedSelect.prop('disabled', true);
        bedSelect.html('<option value="">First select room</option>');
    }
});

// Load beds when room is selected
$('#add_room_id').on('change', function() {
    const roomId = $(this).val();
    const bedSelect = $('#add_bed_id');

    if (roomId) {
        bedSelect.prop('disabled', false);
        bedSelect.html('<option value="">Loading...</option>');

        $.ajax({
            url: "{{ url('admin/members/beds') }}/" + roomId,
            method: "GET",
            success: function(response) {
                bedSelect.html('<option value="">Select Bed</option>');
                if (response.data && response.data.length) {
                    response.data.forEach(bed => {
                        const emoji = bed.bed_type === 'bunker' ? '🪜' : '🛏️';
                        bedSelect.append(`<option value="${bed.id}">${emoji} Bed ${bed.bed_number} (${bed.bed_type})</option>`);
                    });
                } else {
                    bedSelect.append('<option value="">No vacant beds available</option>');
                }
            },
            error: function() {
                bedSelect.html('<option value="">Error loading beds</option>');
            }
        });
    } else {
        bedSelect.prop('disabled', true);
        bedSelect.html('<option value="">First select room</option>');
    }
});

// Calculate rent when room or food preference changes
function calculateRent() {
    const roomId = $('#add_room_id').val();
    const withFood = $('#add_with_food').val();

    if (roomId) {
        $.ajax({
            url: "{{ url('admin/members/rent') }}/" + roomId + "/" + withFood,
            method: "GET",
            success: function(response) {
                if (response.success) {
                    $('#calculatedRent').text('₹' + response.rent.toLocaleString('en-IN'));
                }
            }
        });
    }
}

$('#add_room_id, #add_with_food').on('change', calculateRent);

// Add Member Form Submit
$('#addMemberForm').on('submit', function(e) {
    e.preventDefault();
    showLoader();

    let formData = new FormData(this);

    $.ajax({
        url: "{{ route('admin.members.store') }}",
        method: "POST",
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            hideLoader();
            showToast(response.message, 'success');
            $('#addMemberModal').modal('hide');
            $('#addMemberForm')[0].reset();
            setTimeout(() => location.reload(), 1000);
        },
        error: function(xhr) {
            hideLoader();
            let errorMsg = xhr.responseJSON?.message || 'Error adding member';
            showToast(errorMsg, 'error');
        }
    });
});

// ==================== VIEW MEMBER ====================
// View Member - Uses JSON response from controller
$('.view-member').on('click', function() {
    const memberId = $(this).data('id');

    $('#viewMemberContent').html(`
        <div class="text-center py-4">
            <div class="spinner-border text-primary"></div>
            <p>Loading member details...</p>
        </div>
    `);
    $('#viewMemberModal').modal('show');

    $.ajax({
        url: "{{ url('admin/members') }}/" + memberId,
        method: "GET",
        success: function(response) {
            if (response.success) {
                const member = response.data;
                let html = `
                    <div class="row">
                        <div class="col-md-4 text-center">
                            ${member.image ? `<img src="${member.image}" class="rounded-circle mb-3" width="120" height="120" style="object-fit: cover;">` :
                                `<div class="rounded-circle bg-secondary mx-auto mb-3 d-flex align-items-center justify-content-center" style="width: 120px; height: 120px;">
                                    <i class="bi bi-person fs-1 text-white"></i>
                                </div>`}
                            <h5 class="fw-bold">${member.name}</h5>
                            <p class="text-muted">Member ID: ${member.id}</p>
                            ${member.status === 'active' ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-danger">Left</span>'}
                        </div>
                        <div class="col-md-8">
                            <table class="table table-bordered">
                                <tr><th width="35%">Phone Number</th><td>${member.phone}</td></tr>
                                <tr><th>Hostel</th><td>${member.hostel_name}</td></tr>
                                <tr><th>Room Number</th><td>${member.room_number}</td></tr>
                                <tr><th>Bed Number</th><td>${member.bed_number} (${member.bed_type})</td></tr>
                                <tr><th>Food Preference</th><td>${member.with_food ? '<span class="badge bg-success">With Food</span>' : '<span class="badge bg-secondary">Without Food</span>'}</td></tr>
                                <tr><th>Monthly Rent</th><td class="fw-bold text-success">₹${member.rent_amount.toLocaleString('en-IN')}</td></tr>
                                <tr><th>Join Date</th><td>${member.join_date}</td></tr>
                                ${member.exit_date !== '—' ? `<tr><th>Exit Date</th><td>${member.exit_date}</td></tr>` : ''}
                            </table>
                        </div>
                    </div>
                `;

                // Add documents section if any documents exist
                if (member.addmissionform || member.aadharimage) {
                    html += `
                        <div class="mt-3">
                            <h6 class="fw-bold mb-2">Documents</h6>
                            <div class="d-flex gap-2">
                                ${member.addmissionform ? `<a href="${member.addmissionform}" target="_blank" class="btn btn-sm btn-outline-info"><i class="bi bi-file-text"></i> Admission Form</a>` : ''}
                                ${member.aadharimage ? `<a href="${member.aadharimage}" target="_blank" class="btn btn-sm btn-outline-warning"><i class="bi bi-card-text"></i> Aadhar Card</a>` : ''}
                            </div>
                        </div>
                    `;
                }

                // Add payment history if exists
                if (member.payments && member.payments.length > 0) {
                    html += `
                        <div class="mt-3">
                            <h6 class="fw-bold mb-2">Payment History</h6>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead class="table-light">
                                        <tr><th>Month</th><th>Amount</th><th>Status</th><th>Date</th></tr>
                                    </thead>
                                    <tbody>
                                        ${member.payments.map(p => `
                                            <tr>
                                                <td>${p.month}</td>
                                                <td>₹${p.amount.toLocaleString('en-IN')}</td>
                                                <td>${p.status === 'paid' ? '<span class="badge bg-success">Paid</span>' : '<span class="badge bg-warning">Pending</span>'}</td>
                                                <td>${p.paid_date}</td>
                                            </tr>
                                        `).join('')}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    `;
                }

                $('#viewMemberContent').html(html);
            } else {
                $('#viewMemberContent').html('<div class="alert alert-danger">Error loading member details</div>');
            }
        },
        error: function() {
            $('#viewMemberContent').html('<div class="alert alert-danger">Error loading member details</div>');
        }
    });
});
// ==================== EDIT MEMBER ====================
$('.edit-member').on('click', function() {
    const memberId = $(this).data('id');

    showLoader();

    $.ajax({
        url: "{{ url('admin/members') }}/" + memberId + "/edit",
        method: "GET",
        success: function(response) {
            hideLoader();
            // Populate edit form with data
            const member = response.member;
            $('#edit_id').val(member.id);
            $('#edit_name').val(member.name);
            $('#edit_phone').val(member.phone);
            $('#edit_hostel_id').val(member.hostel_id);
            $('#edit_with_food').val(member.with_food ? '1' : '0');
            $('#edit_join_date').val(member.join_date);
            $('#edit_status').val(member.status);

            // Load rooms for this hostel
            loadEditRooms(member.hostel_id, member.room_id);

            $('#editMemberModal').modal('show');
        },
        error: function() {
            hideLoader();
            showToast('Error loading member data', 'error');
        }
    });
});

function loadEditRooms(hostelId, selectedRoomId) {
    $.ajax({
        url: "{{ url('admin/members/rooms') }}/" + hostelId,
        method: "GET",
        success: function(response) {
            const roomSelect = $('#edit_room_id');
            roomSelect.html('<option value="">Select Room</option>');

            if (response.data && response.data.length) {
                response.data.forEach(room => {
                    roomSelect.append(`<option value="${room.id}" ${selectedRoomId == room.id ? 'selected' : ''}>Room ${room.room_number} (${room.room_type})</option>`);
                });
                // Load beds for selected room
                if (selectedRoomId) {
                    loadEditBeds(selectedRoomId);
                }
            }
        }
    });
}

function loadEditBeds(roomId, selectedBedId = null) {
    $.ajax({
        url: "{{ url('admin/members/beds') }}/" + roomId,
        method: "GET",
        success: function(response) {
            const bedSelect = $('#edit_bed_id');
            bedSelect.html('<option value="">Select Bed</option>');

            if (response.data && response.data.length) {
                response.data.forEach(bed => {
                    const emoji = bed.bed_type === 'bunker' ? '🪜' : '🛏️';
                    bedSelect.append(`<option value="${bed.id}" ${selectedBedId == bed.id ? 'selected' : ''}>${emoji} Bed ${bed.bed_number} (${bed.bed_type})</option>`);
                });
            }
        }
    });
}

$('#edit_hostel_id').on('change', function() {
    const hostelId = $(this).val();
    if (hostelId) {
        loadEditRooms(hostelId);
    }
});

$('#edit_room_id').on('change', function() {
    const roomId = $(this).val();
    if (roomId) {
        loadEditBeds(roomId);

        // Calculate rent
        const withFood = $('#edit_with_food').val();
        $.ajax({
            url: "{{ url('admin/members/rent') }}/" + roomId + "/" + withFood,
            method: "GET",
            success: function(response) {
                if (response.success) {
                    $('#editCalculatedRent').text('₹' + response.rent.toLocaleString('en-IN'));
                }
            }
        });
    }
});

$('#edit_with_food').on('change', function() {
    const roomId = $('#edit_room_id').val();
    if (roomId) {
        const withFood = $(this).val();
        $.ajax({
            url: "{{ url('admin/members/rent') }}/" + roomId + "/" + withFood,
            method: "GET",
            success: function(response) {
                if (response.success) {
                    $('#editCalculatedRent').text('₹' + response.rent.toLocaleString('en-IN'));
                }
            }
        });
    }
});

// Edit Member Form Submit
$('#editMemberForm').on('submit', function(e) {
    e.preventDefault();
    showLoader();

    let id = $('#edit_id').val();
    let formData = new FormData(this);

    $.ajax({
        url: "{{ url('admin/members') }}/" + id,
        method: "POST",
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            hideLoader();
            showToast(response.message, 'success');
            $('#editMemberModal').modal('hide');
            setTimeout(() => location.reload(), 1000);
        },
        error: function(xhr) {
            hideLoader();
            let errorMsg = xhr.responseJSON?.message || 'Error updating member';
            showToast(errorMsg, 'error');
        }
    });
});

// ==================== DELETE MEMBER ====================
let deleteMemberId = null;

$('.delete-member').on('click', function() {
    deleteMemberId = $(this).data('id');
    $('#delete_member_name').text($(this).data('name'));
    $('#deleteMemberModal').modal('show');
});

$('#confirmDeleteBtn').on('click', function() {
    if (!deleteMemberId) return;
    showLoader();

    $.ajax({
        url: "{{ url('admin/members') }}/" + deleteMemberId,
        method: "DELETE",
        data: {
            _token: "{{ csrf_token() }}"
        },
        success: function(response) {
            hideLoader();
            showToast(response.message, 'success');
            $('#deleteMemberModal').modal('hide');
            setTimeout(() => location.reload(), 1000);
        },
        error: function(xhr) {
            hideLoader();
            let errorMsg = xhr.responseJSON?.message || 'Error deleting member';
            showToast(errorMsg, 'error');
        }
    });
});

// ==================== UTILITY FUNCTIONS ====================
function showToast(message, type = 'success') {
    const bgMap = { success: '#10b981', error: '#ef4444', info: '#3b82f6', warning: '#f59e0b' };
    const iconMap = { success: 'bi-check-circle-fill', error: 'bi-exclamation-triangle-fill', info: 'bi-info-circle-fill', warning: 'bi-exclamation-triangle-fill' };
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
    $('#globalLoader').fadeIn(200);
}

function hideLoader() {
    $('#globalLoader').fadeOut(200);
}

// Reset add modal when closed
$('#addMemberModal').on('hidden.bs.modal', function() {
    $('#addMemberForm')[0].reset();
    $('#add_room_id').prop('disabled', true).html('<option value="">First select hostel</option>');
    $('#add_bed_id').prop('disabled', true).html('<option value="">First select room</option>');
    $('#calculatedRent').text('₹0');
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
img.rounded-circle {
    object-fit: cover;
}
.btn-sm {
    margin: 2px;
}
</style>
@endsection
