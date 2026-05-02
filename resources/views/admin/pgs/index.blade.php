@extends('layouts.admin')

@section('title', 'Manage PGs - Hostel Management')
@section('page-title', 'PG Management')

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
    <div>
        <h2 class="fw-bold mb-1">PG Management</h2>
        <p class="text-muted mb-0">Manage Men's and Women's PGs</p>
    </div>
    <button class="btn btn-primary rounded-pill" data-bs-toggle="modal" data-bs-target="#addPGModal">
        <i class="bi bi-plus-lg"></i> Add New PG
    </button>
</div>

<!-- Stats Cards -->
<div class="row g-4 mb-4">
    <div class="col-md-4">
        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <p class="text-muted mb-1">Total PGs</p>
                    <h3 class="fw-bold mb-0">{{ $totalHostels ?? 0 }}</h3>
                </div>
                <i class="bi bi-building fs-1 text-primary opacity-50"></i>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <p class="text-muted mb-1">Men's PGs</p>
                    <h3 class="fw-bold mb-0 text-info">{{ $mensHostels ?? 0 }}</h3>
                </div>
                <i class="bi bi-gender-male fs-1 text-info opacity-50"></i>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <p class="text-muted mb-1">Women's PGs</p>
                    <h3 class="fw-bold mb-0 text-danger">{{ $womensHostels ?? 0 }}</h3>
                </div>
                <i class="bi bi-gender-female fs-1 text-danger opacity-50"></i>
            </div>
        </div>
    </div>
</div>

<!-- PGs Table -->
<div class="admin-card">
    <div class="card-header-custom">
        <i class="bi bi-list-ul me-2"></i> All PGs List
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0" id="pgsTable">
            <thead class="table-light">
                <tr>
                    <th>ID</th>
                    <th>PG Name</th>
                    <th>Type</th>
                    <th>Address</th>
                    <th>City</th>
                    <th>Created By</th>
                    <th>Created Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($hostels as $hostel)
                <tr>
                    <td>{{ $hostel->id }}</td>
                    <td class="fw-semibold">{{ $hostel->name }}</td>
                    <td>
                        @if($hostel->type == 'mens')
                            <span class="badge bg-info"><i class="bi bi-gender-male"></i> Men's PG</span>
                        @else
                            <span class="badge bg-danger"><i class="bi bi-gender-female"></i> Women's PG</span>
                        @endif
                    </td>
                    <td>{{ Str::limit($hostel->address, 50) ?? '—' }}</td>
                    <td>{{ $hostel->city ?? '—' }}</td>
                    <td>{{ $hostel->creator->name ?? '—' }}</td>
                    <td>{{ $hostel->created_at->format('d M Y') }}</td>
                    <td>
                        <button class="btn btn-sm btn-outline-primary edit-pg"
                                data-id="{{ $hostel->id }}"
                                data-name="{{ $hostel->name }}"
                                data-type="{{ $hostel->type }}"
                                data-address="{{ $hostel->address }}"
                                data-city="{{ $hostel->city }}">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-danger delete-pg"
                                data-id="{{ $hostel->id }}"
                                data-name="{{ $hostel->name }}">
                            <i class="bi bi-trash"></i>
                        </button>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="text-center py-5">
                        <i class="bi bi-building fs-1 text-muted"></i>
                        <p class="mt-2 mb-0">No PGs found. Click "Add New PG" to create one.</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($hostels->hasPages())
    <div class="p-3">
        {{ $hostels->links() }}
    </div>
    @endif
</div>

<!-- Add PG Modal -->
<div class="modal fade" id="addPGModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-plus-circle me-2"></i>Add New PG</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="addPGForm">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">PG Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" placeholder="Enter PG name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">PG Type <span class="text-danger">*</span></label>
                        <select name="type" class="form-select" required>
                            <option value="">Select Type</option>
                            <option value="mens">👨 Men's PG</option>
                            <option value="womens">👩 Women's PG</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Address</label>
                        <textarea name="address" class="form-control" rows="2" placeholder="Enter full address"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">City</label>
                        <input type="text" name="city" class="form-control" placeholder="Enter city name">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create PG</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit PG Modal -->
<div class="modal fade" id="editPGModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-pencil-square me-2"></i>Edit PG</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="editPGForm">
                @csrf
                @method('PUT')
                <input type="hidden" name="id" id="edit_id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">PG Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="edit_name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">PG Type <span class="text-danger">*</span></label>
                        <select name="type" id="edit_type" class="form-select" required>
                            <option value="mens">👨 Men's PG</option>
                            <option value="womens">👩 Women's PG</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Address</label>
                        <textarea name="address" id="edit_address" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">City</label>
                        <input type="text" name="city" id="edit_city" class="form-control">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update PG</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deletePGModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="bi bi-exclamation-triangle-fill me-2"></i>Confirm Delete</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete <strong id="delete_pg_name"></strong>?</p>
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
// Add PG
$('#addPGForm').on('submit', function(e) {
    e.preventDefault();
    showLoader();

    $.ajax({
        url: "{{ route('pgs.store') }}",
        method: "POST",
        data: $(this).serialize(),
        success: function(response) {
            hideLoader();
            showToast('PG created successfully!', 'success');
            $('#addPGModal').modal('hide');
            setTimeout(() => location.reload(), 1000);
        },
        error: function(xhr) {
            hideLoader();
            let errorMsg = xhr.responseJSON?.message || 'Error creating PG';
            showToast(errorMsg, 'error');
        }
    });
});

// Edit PG
$('.edit-pg').on('click', function() {
    $('#edit_id').val($(this).data('id'));
    $('#edit_name').val($(this).data('name'));
    $('#edit_type').val($(this).data('type'));
    $('#edit_address').val($(this).data('address'));
    $('#edit_city').val($(this).data('city'));
    $('#editPGModal').modal('show');
});

$('#editPGForm').on('submit', function(e) {
    e.preventDefault();
    showLoader();

    let id = $('#edit_id').val();

    $.ajax({
        url: "{{ url('admin/pgs') }}/" + id,
        method: "POST",
        data: $(this).serialize(),
        success: function(response) {
            hideLoader();
            showToast('PG updated successfully!', 'success');
            $('#editPGModal').modal('hide');
            setTimeout(() => location.reload(), 1000);
        },
        error: function(xhr) {
            hideLoader();
            let errorMsg = xhr.responseJSON?.message || 'Error updating PG';
            showToast(errorMsg, 'error');
        }
    });
});

// Delete PG
let deleteId = null;
$('.delete-pg').on('click', function() {
    deleteId = $(this).data('id');
    $('#delete_pg_name').text($(this).data('name'));
    $('#deletePGModal').modal('show');
});

$('#confirmDeleteBtn').on('click', function() {
    if (!deleteId) return;
    showLoader();

    $.ajax({
        url: "{{ url('admin/pgs') }}/" + deleteId,
        method: "DELETE",
        data: {
            _token: "{{ csrf_token() }}"
        },
        success: function(response) {
            hideLoader();
            showToast('PG deleted successfully!', 'success');
            $('#deletePGModal').modal('hide');
            setTimeout(() => location.reload(), 1000);
        },
        error: function(xhr) {
            hideLoader();
            let errorMsg = xhr.responseJSON?.message || 'Error deleting PG';
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
.btn-primary {
    background: #3b82f6;
    border: none;
}
.btn-primary:hover {
    background: #2563eb;
}
table.dataTable {
    border-collapse: collapse;
}
</style>
@endsection
