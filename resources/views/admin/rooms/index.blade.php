{{-- resources/views/admin/rooms/index.blade.php --}}
@extends('layouts.admin')

@section('title', 'Room Management')
@section('page-title', 'Rooms Management')

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
    <div>
        <h2 class="fw-bold mb-1">Room Management</h2>
        <p class="text-muted mb-0">Manage rooms across all hostels</p>
    </div>
    <button class="btn btn-primary rounded-pill" data-bs-toggle="modal" data-bs-target="#addRoomModal">
        <i class="bi bi-plus-lg"></i> Add New Room
    </button>
</div>

<!-- Stats Cards -->
<div class="row g-4 mb-4">
    <div class="col-md-4">
        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <p class="text-muted mb-1">Total Rooms</p>
                    <h3 class="fw-bold mb-0">{{ $totalRooms ?? 0 }}</h3>
                </div>
                <i class="bi bi-door-closed fs-1 text-primary opacity-50"></i>
            </div>
        </div>
    </div>
    <div class="col-md-8">
        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-center">
                <div class="w-100">
                    <p class="text-muted mb-1">Rooms by Hostel</p>
                    <div class="d-flex flex-wrap gap-3">
                        @foreach($totalRoomsByHostel as $hostel)
                            <div>
                                <span class="badge bg-info">{{ $hostel->name }}</span>
                                <strong>{{ $hostel->rooms_count }} rooms</strong>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Rooms Table -->
<div class="admin-card">
    <div class="card-header-custom">
        <i class="bi bi-list-ul me-2"></i> All Rooms
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0" id="roomsTable">
            <thead class="table-light">
                <tr>
                    <th>ID</th>
                    <th>Hostel</th>
                    <th>Room Number</th>
                    <th>Room Type</th>
                    <th>AC Status</th>
                    <th>Sharing</th>
                    <th>Cot Configuration</th>
                    <th>Rent (with Food)</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rooms as $room)
                <tr>
                    <td>{{ $room->id }}</td>
                    <td class="fw-semibold">{{ $room->hostel->name ?? 'N/A' }}</td>
                    <td><span class="badge bg-secondary">{{ $room->room_number }}</span></td>
                    <td>{{ $room->roomType->name ?? 'N/A' }}</td>
                    <td>
                        @if($room->roomType && $room->roomType->is_ac)
                            <span class="badge bg-info"><i class="bi bi-snow"></i> AC</span>
                        @else
                            <span class="badge bg-secondary"><i class="bi bi-sun"></i> Non-AC</span>
                        @endif
                    </td>
                    <td>{{ $room->roomType->sharing ?? 'N/A' }}-Seater</td>
                    <td>
                        @if($room->roomType)
                            <div class="d-flex gap-1">
                                @if($room->roomType->normal_cot_count > 0)
                                    <span class="badge bg-success">🛏️ {{ $room->roomType->normal_cot_count }}</span>
                                @endif
                                @if($room->roomType->bunker_cot_count > 0)
                                    <span class="badge bg-warning">🪜 {{ $room->roomType->bunker_cot_count }}</span>
                                @endif
                            </div>
                        @else
                            N/A
                        @endif
                    </td>
                    <td class="text-success fw-bold">
                        @if($room->roomType)
                            ₹{{ number_format($room->roomType->rent_with_food) }}
                        @else
                            N/A
                        @endif
                    </td>
                    <td>
                        <button class="btn btn-sm btn-outline-primary edit-room"
                                data-id="{{ $room->id }}"
                                data-hostel_id="{{ $room->hostel_id }}"
                                data-room_type_id="{{ $room->room_type_id }}"
                                data-room_number="{{ $room->room_number }}">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-danger delete-room"
                                data-id="{{ $room->id }}"
                                data-room_number="{{ $room->room_number }}"
                                data-hostel="{{ $room->hostel->name ?? 'N/A' }}">
                            <i class="bi bi-trash"></i>
                        </button>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" class="text-center py-5">
                        <i class="bi bi-inbox fs-1 text-muted"></i>
                        <p class="mt-2 mb-0">No rooms found. Click "Add New Room" to create one.</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($rooms->hasPages())
    <div class="p-3">
        {{ $rooms->links() }}
    </div>
    @endif
</div>

<!-- Add Room Modal -->
<div class="modal fade" id="addRoomModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="bi bi-plus-circle me-2"></i>Add New Room</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="addRoomForm">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Select Hostel <span class="text-danger">*</span></label>
                        <select name="hostel_id" id="add_hostel_id" class="form-select" required>
                            <option value="">Select Hostel</option>
                            @foreach($hostels as $hostel)
                                <option value="{{ $hostel->id }}">{{ $hostel->name }} ({{ ucfirst($hostel->type) }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Room Type <span class="text-danger">*</span></label>
                        <select name="room_type_id" id="add_room_type_id" class="form-select" required>
                            <option value="">Select Room Type</option>
                            @foreach($roomTypes as $type)
                                <option value="{{ $type->id }}">
                                    {{ $type->name }} - {{ $type->sharing }} Seater
                                    ({{ $type->normal_cot_count }} Normal + {{ $type->bunker_cot_count }} Bunker)
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Room Number <span class="text-danger">*</span></label>
                        <input type="text" name="room_number" class="form-control" placeholder="e.g., 101, A-01, Ground Floor" required>
                        <small class="text-muted">Room number must be unique within the selected hostel</small>
                    </div>

                    <div class="alert alert-info" id="roomTypeDetails">
                        <i class="bi bi-info-circle"></i> Select a room type to see details
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Room</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Room Modal -->
<div class="modal fade" id="editRoomModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title"><i class="bi bi-pencil-square me-2"></i>Edit Room</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="editRoomForm">
                @csrf
                @method('PUT')
                <input type="hidden" name="id" id="edit_id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Select Hostel <span class="text-danger">*</span></label>
                        <select name="hostel_id" id="edit_hostel_id" class="form-select" required>
                            <option value="">Select Hostel</option>
                            @foreach($hostels as $hostel)
                                <option value="{{ $hostel->id }}">{{ $hostel->name }} ({{ ucfirst($hostel->type) }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Room Type <span class="text-danger">*</span></label>
                        <select name="room_type_id" id="edit_room_type_id" class="form-select" required>
                            <option value="">Select Room Type</option>
                            @foreach($roomTypes as $type)
                                <option value="{{ $type->id }}">
                                    {{ $type->name }} - {{ $type->sharing }} Seater
                                    ({{ $type->normal_cot_count }} Normal + {{ $type->bunker_cot_count }} Bunker)
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Room Number <span class="text-danger">*</span></label>
                        <input type="text" name="room_number" id="edit_room_number" class="form-control" required>
                        <small class="text-muted">Room number must be unique within the selected hostel</small>
                    </div>

                    <div class="alert alert-warning" id="editRoomTypeWarning">
                        <i class="bi bi-exclamation-triangle"></i>
                        <strong>Warning:</strong> Changing room type will reset all bed assignments!
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Room</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteRoomModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="bi bi-exclamation-triangle-fill me-2"></i>Confirm Delete</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete room <strong id="delete_room_number"></strong> from <strong id="delete_hostel_name"></strong>?</p>
                <p class="text-danger mb-0"><small>This will also delete all beds in this room. This action cannot be undone.</small></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteBtn">Delete Permanently</button>
            </div>
        </div>
    </div>
</div>

<script>
// Display room type details when selected
$('#add_room_type_id').on('change', function() {
    const roomTypeId = $(this).val();
    if (roomTypeId) {
        const selectedOption = $(this).find('option:selected').text();
        $('#roomTypeDetails').html(`
            <i class="bi bi-check-circle"></i>
            <strong>Selected: ${selectedOption}</strong><br>
            <small>Beds will be automatically created based on this configuration.</small>
        `).removeClass('alert-info').addClass('alert-success');
    } else {
        $('#roomTypeDetails').html(`
            <i class="bi bi-info-circle"></i> Select a room type to see details
        `).removeClass('alert-success').addClass('alert-info');
    }
});

// Add Room
$('#addRoomForm').on('submit', function(e) {
    e.preventDefault();
    showLoader();

    $.ajax({
        url: "{{ route('rooms.store') }}",
        method: "POST",
        data: $(this).serialize(),
        success: function(response) {
            hideLoader();
            showToast(response.message, 'success');
            $('#addRoomModal').modal('hide');
            setTimeout(() => location.reload(), 1500);
        },
        error: function(xhr) {
            hideLoader();
            let errorMsg = xhr.responseJSON?.message || 'Error creating room';
            showToast(errorMsg, 'error');
        }
    });
});

// Edit Room
$('.edit-room').on('click', function() {
    $('#edit_id').val($(this).data('id'));
    $('#edit_hostel_id').val($(this).data('hostel_id'));
    $('#edit_room_type_id').val($(this).data('room_type_id'));
    $('#edit_room_number').val($(this).data('room_number'));
    $('#editRoomModal').modal('show');
});

// Update Room
$('#editRoomForm').on('submit', function(e) {
    e.preventDefault();
    showLoader();

    let id = $('#edit_id').val();
    let newRoomType = $('#edit_room_type_id').val();
    let oldRoomType = $('#edit_room_type_id').data('old-type');

    // Confirm if room type changed
    if (oldRoomType && oldRoomType != newRoomType) {
        if (!confirm('Warning: Changing room type will reset all bed assignments. Continue?')) {
            hideLoader();
            return;
        }
    }

    $.ajax({
        url: "{{ url('admin/rooms') }}/" + id,
        method: "POST",
        data: $(this).serialize(),
        success: function(response) {
            hideLoader();
            showToast(response.message, 'success');
            $('#editRoomModal').modal('hide');
            setTimeout(() => location.reload(), 1500);
        },
        error: function(xhr) {
            hideLoader();
            let errorMsg = xhr.responseJSON?.message || 'Error updating room';
            showToast(errorMsg, 'error');
        }
    });
});

// Delete Room
let deleteId = null;
$('.delete-room').on('click', function() {
    deleteId = $(this).data('id');
    $('#delete_room_number').text($(this).data('room_number'));
    $('#delete_hostel_name').text($(this).data('hostel'));
    $('#deleteRoomModal').modal('show');
});

$('#confirmDeleteBtn').on('click', function() {
    if (!deleteId) return;
    showLoader();

    $.ajax({
        url: "{{ url('admin/rooms') }}/" + deleteId,
        method: "DELETE",
        data: {
            _token: "{{ csrf_token() }}"
        },
        success: function(response) {
            hideLoader();
            showToast(response.message, 'success');
            $('#deleteRoomModal').modal('hide');
            setTimeout(() => location.reload(), 1000);
        },
        error: function(xhr) {
            hideLoader();
            let errorMsg = xhr.responseJSON?.message || 'Error deleting room';
            showToast(errorMsg, 'error');
        }
    });
});

// Toast and Loader functions
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
</style>
@endsection
