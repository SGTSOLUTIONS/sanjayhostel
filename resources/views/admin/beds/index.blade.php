{{-- resources/views/admin/beds/index.blade.php --}}
@extends('layouts.admin')

@section('title', 'Bed Management')
@section('page-title', 'Beds Management')

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
    <div>
        <h2 class="fw-bold mb-1">Bed Management</h2>
        <p class="text-muted mb-0">Manage normal and bunker beds across all rooms</p>
    </div>
    <button class="btn btn-primary rounded-pill" data-bs-toggle="modal" data-bs-target="#addBedsModal">
        <i class="bi bi-plus-lg"></i> Add Beds to Room
    </button>
</div>

<!-- Stats Cards -->
<div class="row g-4 mb-4">
    <div class="col-md-3">
        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <p class="text-muted mb-1">Total Beds</p>
                    <h3 class="fw-bold mb-0">{{ $totalBeds ?? 0 }}</h3>
                </div>
                <i class="bi bi-bed fs-1 text-primary opacity-50"></i>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <p class="text-muted mb-1">Occupied / Vacant</p>
                    <h3 class="fw-bold mb-0">{{ $occupiedBeds ?? 0 }} / {{ $vacantBeds ?? 0 }}</h3>
                </div>
                <i class="bi bi-people fs-1 text-success opacity-50"></i>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <p class="text-muted mb-1">Normal / Bunker</p>
                    <h3 class="fw-bold mb-0">{{ $normalBeds ?? 0 }} / {{ $bunkerBeds ?? 0 }}</h3>
                </div>
                <i class="bi bi-grid-3x3-gap-fill fs-1 text-info opacity-50"></i>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <p class="text-muted mb-1">Occupancy Rate</p>
                    <h3 class="fw-bold mb-0">{{ $totalBeds > 0 ? round(($occupiedBeds / $totalBeds) * 100) : 0 }}%</h3>
                </div>
                <i class="bi bi-graph-up fs-1 text-warning opacity-50"></i>
            </div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="admin-card mb-4">
    <div class="card-header-custom">
        <i class="bi bi-funnel me-2"></i> Filter Beds
    </div>
    <div class="p-3">
        <form method="GET" action="{{ route('beds.index') }}" class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Select Room</label>
                <select name="room_id" class="form-select">
                    <option value="">All Rooms</option>
                    @foreach($rooms as $room)
                        <option value="{{ $room->id }}" {{ request('room_id') == $room->id ? 'selected' : '' }}>
                            {{ $room->hostel->name ?? 'N/A' }} - Room {{ $room->room_number }}
                            ({{ $room->roomType->name ?? 'No Type' }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Bed Type</label>
                <select name="bed_type" class="form-select">
                    <option value="">All Types</option>
                    <option value="normal" {{ request('bed_type') == 'normal' ? 'selected' : '' }}>🛏️ Normal Cots</option>
                    <option value="bunker" {{ request('bed_type') == 'bunker' ? 'selected' : '' }}>🪜 Bunker Cots</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="">All</option>
                    <option value="0" {{ request('status') === '0' ? 'selected' : '' }}>Vacant</option>
                    <option value="1" {{ request('status') === '1' ? 'selected' : '' }}>Occupied</option>
                </select>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100">Apply Filter</button>
            </div>
        </form>
    </div>
</div>

<!-- Beds Table -->
<div class="admin-card">
    <div class="card-header-custom">
        <i class="bi bi-list-ul me-2"></i> Beds List
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>ID</th>
                    <th>Hostel</th>
                    <th>Room</th>
                    <th>Bed Number</th>
                    <th>Bed Type</th>
                    <th>Status</th>
                    <th>Current Resident</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($beds as $bed)
                <tr>
                    <td>{{ $bed->id }}</td>
                    <td>{{ $bed->room->hostel->name ?? 'N/A' }}</td>
                    <td><span class="badge bg-secondary">{{ $bed->room->room_number ?? 'N/A' }}</span></td>
                    <td class="fw-semibold">{{ $bed->bed_number }}</td>
                    <td>
                        @if($bed->bed_type == 'bunker')
                            <span class="badge bg-warning"><i class="bi bi-stairs"></i> Bunker</span>
                        @else
                            <span class="badge bg-success"><i class="bi bi-bed"></i> Normal</span>
                        @endif
                    </td>
                    <td>
                        @if($bed->is_occupied)
                            <span class="badge bg-danger"><i class="bi bi-person-fill"></i> Occupied</span>
                        @else
                            <span class="badge bg-success"><i class="bi bi-check-circle"></i> Vacant</span>
                        @endif
                    </td>
                    <td>
                        @if($bed->currentMember)
                            <div class="d-flex flex-column">
                                <strong>{{ $bed->currentMember->name }}</strong>
                                <small class="text-muted">ID: {{ $bed->currentMember->id }}</small>
                            </div>
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td>
                        <button class="btn btn-sm btn-outline-primary edit-bed"
                                data-id="{{ $bed->id }}"
                                data-room_id="{{ $bed->room_id }}"
                                data-bed_number="{{ $bed->bed_number }}"
                                data-bed_type="{{ $bed->bed_type }}"
                                data-is_occupied="{{ $bed->is_occupied }}"
                                data-member_name="{{ $bed->currentMember->name ?? '' }}"
                                data-member_id="{{ $bed->current_member_id ?? '' }}">
                            <i class="bi bi-pencil"></i>
                        </button>
                        @if(!$bed->is_occupied)
                        <button class="btn btn-sm btn-outline-danger delete-bed"
                                data-id="{{ $bed->id }}"
                                data-bed_number="{{ $bed->bed_number }}">
                            <i class="bi bi-trash"></i>
                        </button>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="text-center py-5">
                        <i class="bi bi-inbox fs-1 text-muted"></i>
                        <p class="mt-2 mb-0">No beds found. Click "Add Beds to Room" to create beds.</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($beds->hasPages())
    <div class="p-3">
        {{ $beds->links() }}
    </div>
    @endif
</div>

<!-- ==================== MODALS ==================== -->

<!-- Add Beds Modal -->
<div class="modal fade" id="addBedsModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="bi bi-plus-circle me-2"></i>Add Beds to Room</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="addBedsForm">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Select Room <span class="text-danger">*</span></label>
                        <select name="room_id" id="add_room_id" class="form-select" required>
                            <option value="">Select Room</option>
                            @foreach($rooms as $room)
                                <option value="{{ $room->id }}"
                                        data-normal="{{ $room->roomType->normal_cot_count ?? 0 }}"
                                        data-bunker="{{ $room->roomType->bunker_cot_count ?? 0 }}"
                                        data-typename="{{ $room->roomType->name ?? 'N/A' }}"
                                        data-sharing="{{ $room->roomType->sharing ?? 0 }}">
                                    {{ $room->hostel->name ?? 'N/A' }} - Room {{ $room->room_number }}
                                    ({{ $room->roomType->name ?? 'No Type' }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Room Type Info Card -->
                    <div id="roomTypeInfo" class="alert alert-secondary" style="display: none;">
                        <i class="bi bi-info-circle"></i>
                        <strong>Room Type: <span id="roomTypeName"></span></strong><br>
                        <small>
                            📊 Sharing: <span id="roomSharing">0</span>-Seater<br>
                            🛏️ Recommended configuration:
                            <span id="recNormal">0</span> Normal Cots +
                            <span id="recBunker">0</span> Bunker Cots
                            (Total: <span id="recTotal">0</span> beds)
                        </small>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Normal Cots Count</label>
                            <input type="number" name="normal_cot_count" id="normal_cot_count" class="form-control" value="0" min="0">
                            <small class="text-muted">🛏️ Single beds</small>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Bunker Cots Count</label>
                            <input type="number" name="bunker_cot_count" id="bunker_cot_count" class="form-control" value="0" min="0">
                            <small class="text-muted">🪜 Double-decker beds</small>
                        </div>
                    </div>

                    <div class="alert alert-info" id="bedCountInfo">
                        <i class="bi bi-info-circle"></i> Total beds: <strong id="totalBedsCount">0</strong>
                    </div>

                    <div id="mismatchWarning" class="alert alert-warning" style="display: none;">
                        <i class="bi bi-exclamation-triangle"></i>
                        <strong>Warning:</strong> Your bed count doesn't match the room type recommendation!
                    </div>

                    <div id="existingBedsWarning" class="alert alert-warning" style="display: none;">
                        <i class="bi bi-exclamation-triangle"></i>
                        <strong>Warning:</strong> This room already has beds! Adding more will create additional beds.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Beds</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit/Assign Bed Modal -->
<div class="modal fade" id="editBedModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title"><i class="bi bi-pencil-square me-2"></i>Edit / Assign Bed</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="editBedForm">
                @csrf
                @method('PUT')
                <input type="hidden" name="id" id="edit_bed_id">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Bed Number</label>
                            <input type="text" id="edit_bed_number" class="form-control" readonly disabled>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Bed Type</label>
                            <input type="text" id="edit_bed_type_display" class="form-control" readonly disabled>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Status <span class="text-danger">*</span></label>
                        <select name="is_occupied" id="edit_is_occupied" class="form-select" required>
                            <option value="0">Vacant</option>
                            <option value="1">Occupied</option>
                        </select>
                    </div>

                    <div class="mb-3" id="editMemberSearchDiv" style="display: none;">
                        <label class="form-label">Select Member <span class="text-danger">*</span></label>
                        <select name="current_member_id" id="edit_member_id" class="form-select">
                            <option value="">Select Member</option>
                        </select>
                    </div>

                    <div class="alert alert-info" id="editInfoMsg">
                        <i class="bi bi-info-circle"></i> Select "Occupied" to assign a member to this bed.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteBedModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="bi bi-exclamation-triangle-fill me-2"></i>Confirm Delete</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete bed <strong id="delete_bed_number"></strong>?</p>
                <p class="text-danger mb-0"><small>This action cannot be undone.</small></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteBtn">Delete Permanently</button>
            </div>
        </div>
    </div>
</div>

<script>
// ==================== ADD BEDS ====================
// Update total beds count
function updateTotalBeds() {
    let normal = parseInt($('#normal_cot_count').val()) || 0;
    let bunker = parseInt($('#bunker_cot_count').val()) || 0;
    $('#totalBedsCount').text(normal + bunker);

    // Update color based on count
    if ((normal + bunker) > 0) {
        $('#bedCountInfo').removeClass('alert-info').addClass('alert-success');
    } else {
        $('#bedCountInfo').removeClass('alert-success').addClass('alert-info');
    }
}

// Check mismatch between entered and recommended
function checkMismatch() {
    let normal = parseInt($('#normal_cot_count').val()) || 0;
    let bunker = parseInt($('#bunker_cot_count').val()) || 0;
    let recNormal = parseInt($('#recNormal').text()) || 0;
    let recBunker = parseInt($('#recBunker').text()) || 0;

    if ((normal !== recNormal || bunker !== recBunker) && (recNormal > 0 || recBunker > 0)) {
        $('#mismatchWarning').show();
    } else {
        $('#mismatchWarning').hide();
    }
}

// When room is selected, show room type info
$('#add_room_id').on('change', function() {
    const selected = $(this).find('option:selected');
    const normal = parseInt(selected.data('normal')) || 0;
    const bunker = parseInt(selected.data('bunker')) || 0;
    const sharing = parseInt(selected.data('sharing')) || 0;
    const typeName = selected.data('typename');
    const roomId = $(this).val();

    if (roomId && (normal > 0 || bunker > 0)) {
        $('#roomTypeName').text(typeName);
        $('#roomSharing').text(sharing);
        $('#recNormal').text(normal);
        $('#recBunker').text(bunker);
        $('#recTotal').text(normal + bunker);
        $('#roomTypeInfo').show();

        // Auto-fill recommended values
        $('#normal_cot_count').val(normal);
        $('#bunker_cot_count').val(bunker);
        updateTotalBeds();
        checkMismatch();
    } else if (roomId) {
        $('#roomTypeInfo').hide();
        $('#normal_cot_count').val(0);
        $('#bunker_cot_count').val(0);
        updateTotalBeds();
    } else {
        $('#roomTypeInfo').hide();
    }

    // Check if room already has beds
    if (roomId) {
        $.ajax({
            url: "{{ url('admin/beds/check-room') }}/" + roomId,
            method: "GET",
            success: function(response) {
                if (response.has_beds) {
                    $('#existingBedsWarning').show();
                } else {
                    $('#existingBedsWarning').hide();
                }
            },
            error: function() {
                $('#existingBedsWarning').hide();
            }
        });
    } else {
        $('#existingBedsWarning').hide();
    }
});

// Input handlers
$('#normal_cot_count, #bunker_cot_count').on('input', function() {
    updateTotalBeds();
    checkMismatch();
});

// Add Beds Form Submit
$('#addBedsForm').on('submit', function(e) {
    e.preventDefault();

    let normal = parseInt($('#normal_cot_count').val()) || 0;
    let bunker = parseInt($('#bunker_cot_count').val()) || 0;

    if (normal === 0 && bunker === 0) {
        showToast('Please add at least one bed', 'error');
        return;
    }

    showLoader();

    $.ajax({
        url: "{{ route('beds.store') }}",
        method: "POST",
        data: $(this).serialize(),
        success: function(response) {
            hideLoader();
            showToast(response.message, 'success');
            $('#addBedsModal').modal('hide');
            $('#addBedsForm')[0].reset();
            $('#normal_cot_count').val(0);
            $('#bunker_cot_count').val(0);
            $('#roomTypeInfo').hide();
            $('#mismatchWarning').hide();
            $('#existingBedsWarning').hide();
            updateTotalBeds();
            setTimeout(() => location.reload(), 1000);
        },
        error: function(xhr) {
            hideLoader();
            let errorMsg = xhr.responseJSON?.message || 'Error creating beds';
            showToast(errorMsg, 'error');
        }
    });
});

// Reset form when modal closes
$('#addBedsModal').on('hidden.bs.modal', function() {
    $('#addBedsForm')[0].reset();
    $('#normal_cot_count').val(0);
    $('#bunker_cot_count').val(0);
    $('#roomTypeInfo').hide();
    $('#mismatchWarning').hide();
    $('#existingBedsWarning').hide();
    updateTotalBeds();
});

// ==================== EDIT/ASSIGN BED ====================
// Load members for a room
function loadMembersForRoom(roomId, selectedId = null) {
    if (!roomId) return;

    $.ajax({
        url: "{{ url('admin/members/by-room') }}/" + roomId,
        method: "GET",
        success: function(response) {
            const select = $('#edit_member_id');
            select.empty();
            select.append('<option value="">Select Member</option>');

            if (response.data && response.data.length) {
                response.data.forEach(member => {
                    select.append(`<option value="${member.id}" ${selectedId == member.id ? 'selected' : ''}>${member.name} (ID: ${member.id})</option>`);
                });
            } else {
                select.append('<option value="">No members available in this room</option>');
            }
        },
        error: function() {
            const select = $('#edit_member_id');
            select.empty();
            select.append('<option value="">Error loading members</option>');
        }
    });
}

// Toggle member search based on status
function toggleEditMemberSearch() {
    if ($('#edit_is_occupied').val() === '1') {
        $('#editMemberSearchDiv').slideDown();
        $('#edit_member_id').prop('required', true);
        $('#editInfoMsg').html('<i class="bi bi-info-circle"></i> Select a member to assign to this bed.');
    } else {
        $('#editMemberSearchDiv').slideUp();
        $('#edit_member_id').prop('required', false);
        $('#edit_member_id').val('');
        $('#editInfoMsg').html('<i class="bi bi-info-circle"></i> Bed will be marked as vacant.');
    }
}

// Edit Bed Button Click
$('.edit-bed').on('click', function() {
    const bedId = $(this).data('id');
    const roomId = $(this).data('room_id');
    const bedNumber = $(this).data('bed_number');
    const bedType = $(this).data('bed_type');
    const isOccupied = $(this).data('is_occupied');
    const memberId = $(this).data('member_id');

    $('#edit_bed_id').val(bedId);
    $('#edit_bed_number').val(bedNumber);
    $('#edit_bed_type_display').val(bedType === 'bunker' ? '🪜 Bunker Cot' : '🛏️ Normal Cot');
    $('#edit_is_occupied').val(isOccupied ? '1' : '0');

    if (isOccupied && memberId) {
        loadMembersForRoom(roomId, memberId);
        $('#editMemberSearchDiv').show();
        $('#edit_member_id').val(memberId);
    } else if (isOccupied) {
        loadMembersForRoom(roomId);
        $('#editMemberSearchDiv').show();
    } else {
        $('#editMemberSearchDiv').hide();
        $('#edit_member_id').val('');
    }

    toggleEditMemberSearch();
    $('#editBedModal').modal('show');
});

// Status change handler for edit modal
$('#edit_is_occupied').on('change', function() {
    toggleEditMemberSearch();
});

// Edit Bed Form Submit
$('#editBedForm').on('submit', function(e) {
    e.preventDefault();
    showLoader();

    let id = $('#edit_bed_id').val();
    let formData = {
        _token: "{{ csrf_token() }}",
        _method: "PUT",
        is_occupied: $('#edit_is_occupied').val(),
        current_member_id: $('#edit_is_occupied').val() === '1' ? $('#edit_member_id').val() : null
    };

    $.ajax({
        url: "{{ url('admin/beds') }}/" + id,
        method: "POST",
        data: formData,
        success: function(response) {
            hideLoader();
            showToast(response.message, 'success');
            $('#editBedModal').modal('hide');
            setTimeout(() => location.reload(), 1000);
        },
        error: function(xhr) {
            hideLoader();
            let errorMsg = xhr.responseJSON?.message || 'Error updating bed';
            showToast(errorMsg, 'error');
        }
    });
});

// ==================== DELETE BED ====================
let deleteBedId = null;
let deleteBedNumber = null;

$('.delete-bed').on('click', function() {
    deleteBedId = $(this).data('id');
    deleteBedNumber = $(this).data('bed_number');
    $('#delete_bed_number').text(deleteBedNumber);
    $('#deleteBedModal').modal('show');
});

$('#confirmDeleteBtn').on('click', function() {
    if (!deleteBedId) return;
    showLoader();

    $.ajax({
        url: "{{ url('admin/beds') }}/" + deleteBedId,
        method: "DELETE",
        data: {
            _token: "{{ csrf_token() }}"
        },
        success: function(response) {
            hideLoader();
            showToast(response.message, 'success');
            $('#deleteBedModal').modal('hide');
            setTimeout(() => location.reload(), 1000);
        },
        error: function(xhr) {
            hideLoader();
            let errorMsg = xhr.responseJSON?.message || 'Error deleting bed';
            showToast(errorMsg, 'error');
        }
    });
});

// ==================== UTILITY FUNCTIONS ====================
function showToast(message, type = 'success') {
    const bgMap = { success: '#10b981', error: '#ef4444', info: '#3b82f6', warning: '#f59e0b' };
    const iconMap = { success: 'bi-check-circle-fill', error: 'bi-exclamation-triangle-fill', info: 'bi-info-circle-fill', warning: 'bi-exclamation-triangle-fill' };
    const titleMap = { success: 'Success', error: 'Error', info: 'Info', warning: 'Warning' };

    const toast = $(`
        <div class="position-fixed top-0 end-0 p-3" style="z-index: 9999;">
            <div class="toast show" role="alert">
                <div class="toast-header" style="background: ${bgMap[type]}; color: white;">
                    <i class="bi ${iconMap[type]} me-2"></i>
                    <strong class="me-auto">${titleMap[type]}</strong>
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
    margin: 0 2px;
}
.modal-header .btn-close {
    filter: brightness(0) invert(1);
}
select.form-select, input.form-control {
    border-radius: 10px;
}
</style>
@endsection
