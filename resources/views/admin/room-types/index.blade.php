{{-- resources/views/admin/room-types/index.blade.php --}}
@extends('layouts.admin')

@section('title', 'Room Types Management')
@section('page-title', 'Room Types')

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
    <div>
        <h2 class="fw-bold mb-1">Room Types Management</h2>
        <p class="text-muted mb-0">Manage room configurations with mixed cot types</p>
    </div>
    <button class="btn btn-primary rounded-pill" data-bs-toggle="modal" data-bs-target="#addRoomTypeModal">
        <i class="bi bi-plus-lg"></i> Add Room Type
    </button>
</div>

<!-- Stats Cards -->
<div class="row g-4 mb-4">
    <div class="col-md-3">
        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <p class="text-muted mb-1">Total Room Types</p>
                    <h3 class="fw-bold mb-0">{{ $totalTypes ?? 0 }}</h3>
                </div>
                <i class="bi bi-grid-3x3-gap-fill fs-1 text-primary opacity-50"></i>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <p class="text-muted mb-1">AC / Non-AC</p>
                    <h3 class="fw-bold mb-0">{{ $acTypes ?? 0 }} / {{ $nonAcTypes ?? 0 }}</h3>
                </div>
                <i class="bi bi-snow fs-1 text-info opacity-50"></i>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <p class="text-muted mb-1">Normal / Bunker Cots</p>
                    <h3 class="fw-bold mb-0">{{ $totalNormalCots ?? 0 }} / {{ $totalBunkerCots ?? 0 }}</h3>
                </div>
                <i class="bi bi-bed fs-1 text-success opacity-50"></i>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <p class="text-muted mb-1">Total Capacity</p>
                    <h3 class="fw-bold mb-0">{{ $totalCots ?? 0 }}</h3>
                </div>
                <i class="bi bi-people fs-1 text-warning opacity-50"></i>
            </div>
        </div>
    </div>
</div>

<!-- Room Types Table -->
<div class="admin-card">
    <div class="card-header-custom">
        <i class="bi bi-list-ul me-2"></i> All Room Types
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0" id="roomTypesTable">
            <thead class="table-light">
                <tr>
                    <th>ID</th>
                    <th>Room Type</th>
                    <th>AC Status</th>
                    <th>Sharing</th>
                    <th>Cot Configuration</th>
                    <th>Rent (with Food)</th>
                    <th>Rent (without Food)</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($roomTypes as $type)
                <tr>
                    <td>{{ $type->id }}</td>
                    <td class="fw-semibold">{{ $type->name }}</td>
                    <td>
                        @if($type->is_ac)
                            <span class="badge bg-info"><i class="bi bi-snow"></i> AC</span>
                        @else
                            <span class="badge bg-secondary"><i class="bi bi-sun"></i> Non-AC</span>
                        @endif
                    </td>
                    <td>{{ $type->sharing }}-Seater</td>
                    <td>
                        <div class="d-flex gap-2">
                            @if($type->normal_cot_count > 0)
                                <span class="badge bg-success">🛏️ {{ $type->normal_cot_count }} Normal</span>
                            @endif
                            @if($type->bunker_cot_count > 0)
                                <span class="badge bg-warning">🪜 {{ $type->bunker_cot_count }} Bunker</span>
                            @endif
                        </div>
                     </td>
                    <td class="text-success fw-bold">₹{{ number_format($type->rent_with_food) }}</td>
                    <td class="text-info fw-bold">₹{{ number_format($type->rent_without_food) }}</td>
                    <td>
                        <button class="btn btn-sm btn-outline-primary edit-room-type"
                                data-id="{{ $type->id }}"
                                data-name="{{ $type->name }}"
                                data-is_ac="{{ $type->is_ac }}"
                                data-sharing="{{ $type->sharing }}"
                                data-normal_cot_count="{{ $type->normal_cot_count }}"
                                data-bunker_cot_count="{{ $type->bunker_cot_count }}"
                                data-rent_with_food="{{ $type->rent_with_food }}"
                                data-rent_without_food="{{ $type->rent_without_food }}"
                                data-description="{{ $type->description }}">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-danger delete-room-type"
                                data-id="{{ $type->id }}"
                                data-name="{{ $type->name }}">
                            <i class="bi bi-trash"></i>
                        </button>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="text-center py-5">
                        <i class="bi bi-inbox fs-1 text-muted"></i>
                        <p class="mt-2 mb-0">No room types found. Click "Add Room Type" to create one.</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($roomTypes->hasPages())
    <div class="p-3">
        {{ $roomTypes->links() }}
    </div>
    @endif
</div>

<!-- Add Room Type Modal -->
<div class="modal fade" id="addRoomTypeModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="bi bi-plus-circle me-2"></i>Add New Room Type</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="addRoomTypeForm">
                @csrf
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Room Type Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" placeholder="e.g., Deluxe Room" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">AC Status <span class="text-danger">*</span></label>
                            <select name="is_ac" class="form-select" required>
                                <option value="0">Non-AC</option>
                                <option value="1">AC</option>
                            </select>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Total Sharing (Beds) <span class="text-danger">*</span></label>
                            <input type="number" name="sharing" id="total_sharing" class="form-control" placeholder="Total beds in room" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Normal Cots <span class="text-danger">*</span></label>
                            <input type="number" name="normal_cot_count" id="normal_cot" class="form-control" value="0" min="0" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Bunker Cots <span class="text-danger">*</span></label>
                            <input type="number" name="bunker_cot_count" id="bunker_cot" class="form-control" value="0" min="0" required>
                        </div>
                    </div>

                    <div class="alert alert-info small" id="cotValidationMsg">
                        <i class="bi bi-info-circle"></i> Total cots should equal sharing count
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Rent (with Food) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" name="rent_with_food" class="form-control" placeholder="₹" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Rent (without Food) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" name="rent_without_food" class="form-control" placeholder="₹" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="2" placeholder="Additional details about this room type"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Room Type</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Room Type Modal -->
<div class="modal fade" id="editRoomTypeModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title"><i class="bi bi-pencil-square me-2"></i>Edit Room Type</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="editRoomTypeForm">
                @csrf
                @method('PUT')
                <input type="hidden" name="id" id="edit_id">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Room Type Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="edit_name" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">AC Status <span class="text-danger">*</span></label>
                            <select name="is_ac" id="edit_is_ac" class="form-select" required>
                                <option value="0">Non-AC</option>
                                <option value="1">AC</option>
                            </select>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Total Sharing (Beds) <span class="text-danger">*</span></label>
                            <input type="number" name="sharing" id="edit_sharing" class="form-control" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Normal Cots</label>
                            <input type="number" name="normal_cot_count" id="edit_normal_cot" class="form-control" min="0" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Bunker Cots</label>
                            <input type="number" name="bunker_cot_count" id="edit_bunker_cot" class="form-control" min="0" required>
                        </div>
                    </div>

                    <div class="alert alert-info small" id="editCotValidationMsg">
                        <i class="bi bi-info-circle"></i> Total cots should equal sharing count
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Rent (with Food) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" name="rent_with_food" id="edit_rent_with_food" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Rent (without Food) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" name="rent_without_food" id="edit_rent_without_food" class="form-control" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" id="edit_description" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Room Type</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteRoomTypeModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="bi bi-exclamation-triangle-fill me-2"></i>Confirm Delete</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete <strong id="delete_type_name"></strong>?</p>
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
// Validate cot counts
function validateCots(normal, bunker, sharing, isEdit = false) {
    const total = parseInt(normal) + parseInt(bunker);
    const msgDiv = isEdit ? $('#editCotValidationMsg') : $('#cotValidationMsg');

    if (total === parseInt(sharing)) {
        msgDiv.removeClass('alert-danger').addClass('alert-info');
        msgDiv.html('<i class="bi bi-check-circle"></i> ✓ Perfect! Total cots match sharing count.');
        return true;
    } else if (total < parseInt(sharing)) {
        msgDiv.removeClass('alert-info').addClass('alert-warning');
        msgDiv.html(`<i class="bi bi-exclamation-triangle"></i> ⚠️ Total cots (${total}) is less than sharing (${sharing}). Add ${sharing - total} more cot(s).`);
        return false;
    } else {
        msgDiv.removeClass('alert-info').addClass('alert-danger');
        msgDiv.html(`<i class="bi bi-x-circle"></i> ❌ Total cots (${total}) exceeds sharing (${sharing}). Remove ${total - sharing} cot(s).`);
        return false;
    }
}

// Real-time validation for add form
$('#normal_cot, #bunker_cot, #total_sharing').on('input', function() {
    validateCots($('#normal_cot').val(), $('#bunker_cot').val(), $('#total_sharing').val(), false);
});

// Real-time validation for edit form
$('#edit_normal_cot, #edit_bunker_cot, #edit_sharing').on('input', function() {
    validateCots($('#edit_normal_cot').val(), $('#edit_bunker_cot').val(), $('#edit_sharing').val(), true);
});

// Add Room Type
$('#addRoomTypeForm').on('submit', function(e) {
    e.preventDefault();

    // Validate cot counts before submit
    if (!validateCots($('#normal_cot').val(), $('#bunker_cot').val(), $('#total_sharing').val(), false)) {
        showToast('Please fix cot configuration before submitting', 'error');
        return;
    }

    showLoader();

    $.ajax({
        url: "{{ route('room-types.store') }}",
        method: "POST",
        data: $(this).serialize(),
        success: function(response) {
            hideLoader();
            showToast(response.message, 'success');
            $('#addRoomTypeModal').modal('hide');
            setTimeout(() => location.reload(), 1000);
        },
        error: function(xhr) {
            hideLoader();
            let errorMsg = xhr.responseJSON?.message || 'Error creating room type';
            showToast(errorMsg, 'error');
        }
    });
});

// Edit Room Type
$('.edit-room-type').on('click', function() {
    $('#edit_id').val($(this).data('id'));
    $('#edit_name').val($(this).data('name'));
    $('#edit_is_ac').val($(this).data('is_ac'));
    $('#edit_sharing').val($(this).data('sharing'));
    $('#edit_normal_cot').val($(this).data('normal_cot_count'));
    $('#edit_bunker_cot').val($(this).data('bunker_cot_count'));
    $('#edit_rent_with_food').val($(this).data('rent_with_food'));
    $('#edit_rent_without_food').val($(this).data('rent_without_food'));
    $('#edit_description').val($(this).data('description') || '');

    validateCots($(this).data('normal_cot_count'), $(this).data('bunker_cot_count'), $(this).data('sharing'), true);
    $('#editRoomTypeModal').modal('show');
});

// Update Room Type
$('#editRoomTypeForm').on('submit', function(e) {
    e.preventDefault();

    if (!validateCots($('#edit_normal_cot').val(), $('#edit_bunker_cot').val(), $('#edit_sharing').val(), true)) {
        showToast('Please fix cot configuration before submitting', 'error');
        return;
    }

    showLoader();

    let id = $('#edit_id').val();

    $.ajax({
        url: "{{ url('admin/room-types') }}/" + id,
        method: "POST",
        data: $(this).serialize(),
        success: function(response) {
            hideLoader();
            showToast(response.message, 'success');
            $('#editRoomTypeModal').modal('hide');
            setTimeout(() => location.reload(), 1000);
        },
        error: function(xhr) {
            hideLoader();
            let errorMsg = xhr.responseJSON?.message || 'Error updating room type';
            showToast(errorMsg, 'error');
        }
    });
});

// Delete Room Type
let deleteId = null;
$('.delete-room-type').on('click', function() {
    deleteId = $(this).data('id');
    $('#delete_type_name').text($(this).data('name'));
    $('#deleteRoomTypeModal').modal('show');
});

$('#confirmDeleteBtn').on('click', function() {
    if (!deleteId) return;
    showLoader();

    $.ajax({
        url: "{{ url('admin/room-types') }}/" + deleteId,
        method: "DELETE",
        data: {
            _token: "{{ csrf_token() }}"
        },
        success: function(response) {
            hideLoader();
            showToast(response.message, 'success');
            $('#deleteRoomTypeModal').modal('hide');
            setTimeout(() => location.reload(), 1000);
        },
        error: function(xhr) {
            hideLoader();
            let errorMsg = xhr.responseJSON?.message || 'Error deleting room type';
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
</style>
@endsection
