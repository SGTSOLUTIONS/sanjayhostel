{{-- resources/views/admin/payments/index.blade.php --}}
@extends('layouts.admin')

@section('title', 'Payment Management')
@section('page-title', 'Payments Management')

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
    <div>
        <h2 class="fw-bold mb-1">Payment Management</h2>
        <p class="text-muted mb-0">Manage member payments and track dues</p>
    </div>
    <button class="btn btn-primary rounded-pill" data-bs-toggle="modal" data-bs-target="#addPaymentModal">
        <i class="bi bi-plus-lg"></i> Record Payment
    </button>
</div>

<!-- Stats Cards -->
<div class="row g-4 mb-4">
    <div class="col-md-3">
        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <p class="text-muted mb-1">Total Collected</p>
                    <h3 class="fw-bold mb-0 text-success">₹{{ number_format($totalCollected ?? 0) }}</h3>
                    <small class="text-muted">Money Received</small>
                </div>
                <div class="stat-icon bg-success bg-opacity-10 rounded-circle p-2">
                    <i class="bi bi-cash-stack fs-4 text-success"></i>
                </div>
            </div>
            <div class="mt-2">
                <small class="text-muted">Full: ₹{{ number_format($totalFullPayments ?? 0) }} | Partial: ₹{{ number_format($totalPartialPayments ?? 0) }}</small>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <p class="text-muted mb-1">Pending Dues</p>
                    <h3 class="fw-bold mb-0 text-danger">₹{{ number_format($totalPendingDues ?? 0) }}</h3>
                    <small class="text-muted">Still Owed</small>
                </div>
                <div class="stat-icon bg-danger bg-opacity-10 rounded-circle p-2">
                    <i class="bi bi-clock-history fs-4 text-danger"></i>
                </div>
            </div>
            <div class="mt-2">
                <small class="text-muted">{{ count($membersWithDues ?? []) }} members have pending dues</small>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <p class="text-muted mb-1">Collection Rate</p>
                    <h3 class="fw-bold mb-0 text-info">{{ $collectionRate ?? 0 }}%</h3>
                    <small class="text-muted">{{ $fullyPaidCount ?? 0 }}/{{ $totalMembers ?? 0 }} members</small>
                </div>
                <div class="stat-icon bg-info bg-opacity-10 rounded-circle p-2">
                    <i class="bi bi-graph-up fs-4 text-info"></i>
                </div>
            </div>
            <div class="progress mt-2" style="height: 6px;">
                <div class="progress-bar bg-info" style="width: {{ $collectionRate ?? 0 }}%"></div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <p class="text-muted mb-1">This Month</p>
                    <h3 class="fw-bold mb-0 text-primary">₹{{ number_format($thisMonthCollected ?? 0) }}</h3>
                    <small class="text-muted">{{ date('F Y') }}</small>
                </div>
                <div class="stat-icon bg-primary bg-opacity-10 rounded-circle p-2">
                    <i class="bi bi-calendar-check fs-4 text-primary"></i>
                </div>
            </div>
            <div class="mt-2">
                <small class="text-muted">{{ $totalPayments ?? 0 }} total transactions</small>
            </div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="admin-card mb-4">
    <div class="card-header-custom">
        <i class="bi bi-funnel me-2"></i> Filter Payments
    </div>
    <div class="p-3">
        <form method="GET" action="{{ route('admin.payments.index') }}" class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Select Member</label>
                <select name="member_id" class="form-select">
                    <option value="">All Members</option>
                    @foreach($members as $member)
                        <option value="{{ $member->id }}" {{ request('member_id') == $member->id ? 'selected' : '' }}>
                            {{ $member->name }} ({{ $member->phone }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Month</label>
                <input type="month" name="month" class="form-control" value="{{ request('month', date('Y-m')) }}">
            </div>
            <div class="col-md-3">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="">All</option>
                    <option value="paid" {{ request('status') == 'paid' ? 'selected' : '' }}>Fully Paid</option>
                    <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Partial Payment</option>
                </select>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100">Apply Filter</button>
            </div>
        </form>
    </div>
</div>

<!-- Pending Dues Summary -->
@if(count($membersWithDues ?? []) > 0)
<div class="admin-card mb-4 border-warning">
    <div class="card-header-custom bg-warning bg-opacity-10">
        <i class="bi bi-exclamation-triangle-fill me-2 text-warning"></i> Members with Pending Dues - {{ date('F Y') }}
    </div>
    <div class="table-responsive">
        <table class="table table-sm mb-0">
            <thead>
                <tr>
                    <th>Member Name</th>
                    <th>Room / Bed</th>
                    <th>Monthly Rent</th>
                    <th>Paid Amount</th>
                    <th>Pending Amount</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @foreach($membersWithDues as $due)
                <tr>
                    <td class="fw-semibold">{{ $due['member']->name }}</td>
                    <td>
                        Room: {{ $due['member']->room->room_number ?? 'N/A' }}<br>
                        <small class="text-muted">Bed: {{ $due['member']->room->beds->where('is_occupied', true)->pluck('bed_number')->implode(', ') ?: 'N/A' }}</small>
                    </td>
                    <td>₹{{ number_format($due['monthly_rent']) }}</td>
                    <td class="text-success">₹{{ number_format($due['paid_amount']) }}</td>
                    <td class="text-danger fw-bold">₹{{ number_format($due['pending_amount']) }}</td>
                    <td>
                        <button class="btn btn-sm btn-primary record-payment-btn"
                                data-member-id="{{ $due['member']->id }}"
                                data-member-name="{{ $due['member']->name }}"
                                data-monthly-rent="{{ $due['monthly_rent'] }}">
                            Record Payment
                        </button>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

<!-- Payments Table -->
<div class="admin-card">
    <div class="card-header-custom">
        <i class="bi bi-list-ul me-2"></i> Payment Records
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>ID</th>
                    <th>Member Name</th>
                    <th>Hostel</th>
                    <th>Room/Bed</th>
                    <th>Month</th>
                    <th>Monthly Rent</th>
                    <th>Amount Paid</th>
                    <th>Pending Balance</th>
                    <th>Status</th>
                    <th>Paid Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($payments as $payment)
                @php
                    $monthlyRent = $payment->member->rent_amount ?? 0;
                    $totalPaidThisMonth = App\Models\Payment::where('member_id', $payment->member_id)
                        ->where('month', $payment->month)
                        ->whereIn('status', ['paid', 'pending'])
                        ->sum('amount');
                    $pendingBalance = max(0, $monthlyRent - $totalPaidThisMonth);
                    $bedNumbers = $payment->member->room->beds->where('is_occupied', true)->pluck('bed_number')->implode(', ');
                @endphp
                <tr>
                    <td>{{ $payment->id }}</td>
                    <td class="fw-semibold">{{ $payment->member->name ?? 'N/A' }}</td>
                    <td>{{ $payment->member->hostel->name ?? 'N/A' }}</td>
                    <td>
                        Room: {{ $payment->member->room->room_number ?? 'N/A' }}<br>
                        <small class="text-muted">Bed: {{ $bedNumbers ?: 'N/A' }}</small>
                    </td>
                    <td><span class="badge bg-secondary">{{ date('F Y', strtotime($payment->month . '-01')) }}</span></td>
                    <td class="fw-bold">₹{{ number_format($monthlyRent) }}</td>
                    <td class="text-success fw-bold">₹{{ number_format($payment->amount) }}</td>
                    <td>
                        @if($pendingBalance > 0)
                            <span class="badge bg-danger">₹{{ number_format($pendingBalance) }}</span>
                        @else
                            <span class="badge bg-success">₹0</span>
                        @endif
                    </td>
                    <td>
                        @if($payment->status == 'paid')
                            <span class="badge bg-success"><i class="bi bi-check-circle"></i> Fully Paid</span>
                        @else
                            <span class="badge bg-warning"><i class="bi bi-hourglass-split"></i> Partial</span>
                        @endif
                    </td>
                    <td>{{ $payment->paid_date ? date('d M Y', strtotime($payment->paid_date)) : '—' }}</td>
                    <td>
                        <button class="btn btn-sm btn-outline-info view-payment" data-id="{{ $payment->id }}">
                            <i class="bi bi-eye"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-primary edit-payment" data-id="{{ $payment->id }}">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-danger delete-payment"
                                data-id="{{ $payment->id }}"
                                data-name="{{ $payment->member->name ?? 'N/A' }}"
                                data-month="{{ $payment->month }}">
                            <i class="bi bi-trash"></i>
                        </button>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="11" class="text-center py-5">
                        <i class="bi bi-inbox fs-1 text-muted"></i>
                        <p class="mt-2 mb-0">No payment records found. Click "Record Payment" to add one.</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($payments->hasPages())
    <div class="p-3">
        {{ $payments->links() }}
    </div>
    @endif
</div>

<!-- ==================== ADD PAYMENT MODAL ==================== -->
<div class="modal fade" id="addPaymentModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="bi bi-cash-stack me-2"></i>Record New Payment</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="addPaymentForm">
                @csrf
                <div class="modal-body">
                    <!-- Step 1: Select Hostel -->
                    <div class="mb-3">
                        <label class="form-label">Select Hostel <span class="text-danger">*</span></label>
                        <select name="hostel_id" id="add_hostel_id" class="form-select" required>
                            <option value="">Select Hostel</option>
                            @foreach($hostels as $hostel)
                                <option value="{{ $hostel->id }}">{{ $hostel->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Step 2: Search/Filters -->
                    <div id="memberFilters" style="display: none;">
                        <div class="row mb-3">
                            <div class="col-md-8">
                                <label class="form-label">Search Member</label>
                                <input type="text" id="member_search" class="form-control"
                                       placeholder="Search by name, phone, room number, or bed number...">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Filter by Room</label>
                                <select id="room_filter" class="form-select">
                                    <option value="">All Rooms</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Step 3: Select Member -->
                    <div class="mb-3">
                        <label class="form-label">Select Member <span class="text-danger">*</span></label>
                        <select name="member_id" id="add_member_id" class="form-select" required disabled>
                            <option value="">First select hostel</option>
                        </select>
                        <small class="text-muted" id="memberCountInfo"></small>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Month <span class="text-danger">*</span></label>
                            <input type="month" name="month" id="add_month" class="form-control" value="{{ date('Y-m') }}" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Amount Paid (₹) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" name="amount" id="add_amount" class="form-control" placeholder="Enter amount paid" required>
                        </div>
                    </div>

                    <div class="alert alert-info" id="memberInfo">
                        <i class="bi bi-info-circle"></i> Select a member to see details
                    </div>

                    <div class="alert alert-warning" id="partialInfo" style="display: none;">
                        <i class="bi bi-exclamation-triangle"></i> <strong>Partial Payment</strong><br>
                        Pending amount after this payment: <strong id="pendingAmountDisplay">₹0</strong>
                    </div>

                    <div class="alert alert-success" id="fullInfo" style="display: none;">
                        <i class="bi bi-check-circle"></i> <strong>Full Payment</strong><br>
                        This member will have no pending dues after this payment!
                    </div>

                    <div id="paymentExistsWarning" class="alert alert-danger" style="display: none;">
                        <i class="bi bi-exclamation-triangle"></i> <strong>Warning:</strong> Payment for this month already exists!
                    </div>

                    <div id="previousDuesWarning" class="alert alert-danger" style="display: none;">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                        <strong>Previous Dues Alert!</strong>
                        <div id="previousDuesDetails"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Record Payment</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ==================== VIEW PAYMENT MODAL ==================== -->
<div class="modal fade" id="viewPaymentModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title"><i class="bi bi-receipt me-2"></i>Payment Details</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="viewPaymentContent">
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

<!-- ==================== EDIT PAYMENT MODAL ==================== -->
<div class="modal fade" id="editPaymentModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title"><i class="bi bi-pencil-square me-2"></i>Edit Payment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="editPaymentForm">
                @csrf
                @method('PUT')
                <input type="hidden" name="id" id="edit_id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Member</label>
                        <input type="text" id="edit_member_name" class="form-control" readonly disabled>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Monthly Rent</label>
                        <input type="text" id="edit_monthly_rent" class="form-control" readonly disabled>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Month</label>
                        <input type="text" id="edit_month" class="form-control" readonly disabled>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Amount Paid (₹) <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" name="amount" id="edit_amount" class="form-control" required>
                    </div>

                    <div class="alert alert-warning" id="editPartialInfo" style="display: none;">
                        <i class="bi bi-exclamation-triangle"></i> After update, pending amount: <strong id="editPendingAmount">₹0</strong>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Paid Date</label>
                        <input type="date" name="paid_date" id="edit_paid_date" class="form-control">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Payment</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ==================== DELETE CONFIRMATION MODAL ==================== -->
<div class="modal fade" id="deletePaymentModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="bi bi-exclamation-triangle-fill me-2"></i>Confirm Delete</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete payment for <strong id="delete_payment_name"></strong> for month <strong id="delete_payment_month"></strong>?</p>
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
// ==================== ADD PAYMENT - DYNAMIC LOADING WITH SEARCH ====================
let currentMonthlyRent = 0;
let currentTotalPaid = 0;
let currentMembersData = [];

// Load members when hostel is selected
$('#add_hostel_id').on('change', function() {
    const hostelId = $(this).val();
    const memberSelect = $('#add_member_id');

    if (hostelId) {
        $('#memberFilters').show();
        memberSelect.prop('disabled', false);
        memberSelect.html('<option value="">Loading members...</option>');
        $('#member_search').val('');
        $('#room_filter').html('<option value="">All Rooms</option>');
        loadMembers(hostelId);
    } else {
        $('#memberFilters').hide();
        memberSelect.prop('disabled', true);
        memberSelect.html('<option value="">First select hostel</option>');
        $('#memberInfo').html('<i class="bi bi-info-circle"></i> Select a member to see details').removeClass('alert-success').addClass('alert-info');
    }
});

// Load members function
function loadMembers(hostelId, searchTerm = '', roomNumber = '') {
    const memberSelect = $('#add_member_id');

    $.ajax({
        url: "{{ url('admin/payments/members') }}/" + hostelId,
        method: "GET",
        data: { search: searchTerm, room_number: roomNumber },
        success: function(response) {
            memberSelect.html('<option value="">Select Member</option>');

            if (response.data && response.data.length) {
                currentMembersData = response.data;

                // Populate room filter dropdown
                const rooms = [...new Map(response.data.map(member =>
                    [member.room_number, { room_number: member.room_number }]
                )).values()];

                const roomFilter = $('#room_filter');
                const currentRoomFilter = roomFilter.val();
                roomFilter.html('<option value="">All Rooms</option>');
                rooms.sort((a, b) => a.room_number.localeCompare(b.room_number));
                rooms.forEach(room => {
                    roomFilter.append(`<option value="${room.room_number}" ${currentRoomFilter === room.room_number ? 'selected' : ''}>Room ${room.room_number}</option>`);
                });

                // Populate members dropdown
                response.data.forEach(member => {
                    let statusBadge = '';
                    let previousDuesBadge = '';

                    if (member.has_previous_dues) {
                        previousDuesBadge = '⚠️ Previous Dues!';
                    }

                    if (member.payment_status === 'paid') {
                        statusBadge = '✓ Fully Paid';
                    } else if (member.payment_status === 'partial') {
                        statusBadge = `⟳ ₹${member.current_pending.toLocaleString('en-IN')} pending`;
                    } else {
                        statusBadge = '✗ Unpaid';
                    }

                    memberSelect.append(`<option value="${member.id}"
                        data-rent="${member.monthly_rent}"
                        data-room="${member.room_number}"
                        data-bed="${member.bed_numbers}"
                        data-paid="${member.total_paid_current}"
                        data-pending="${member.current_pending}"
                        data-previous-dues="${member.previous_dues_total}"
                        data-previous-months='${JSON.stringify(member.previous_dues_months)}'
                        data-status="${member.payment_status}"
                        data-has-previous="${member.has_previous_dues}">
                        ${member.name} (${member.phone}) - Room ${member.room_number} | Bed: ${member.bed_numbers} | Rent: ₹${member.monthly_rent} ${previousDuesBadge} - ${statusBadge}
                    </option>`);
                });

                $('#memberCountInfo').html(`<i class="bi bi-people"></i> ${response.data.length} members found`);
            } else {
                memberSelect.append('<option value="">No members available in this hostel</option>');
                $('#memberCountInfo').html('<i class="bi bi-exclamation-circle"></i> No members found');
            }
        },
        error: function() {
            memberSelect.html('<option value="">Error loading members</option>');
            $('#memberCountInfo').html('<i class="bi bi-exclamation-triangle"></i> Error loading members');
        }
    });
}

// Search member with debounce
let searchTimeout;
$('#member_search').on('input', function() {
    clearTimeout(searchTimeout);
    const searchTerm = $(this).val();
    const hostelId = $('#add_hostel_id').val();
    const roomFilter = $('#room_filter').val();

    searchTimeout = setTimeout(() => {
        if (hostelId) {
            loadMembers(hostelId, searchTerm, roomFilter);
        }
    }, 500);
});

// Filter by room
$('#room_filter').on('change', function() {
    const roomNumber = $(this).val();
    const hostelId = $('#add_hostel_id').val();
    const searchTerm = $('#member_search').val();

    if (hostelId) {
        loadMembers(hostelId, searchTerm, roomNumber);
    }
});

// Show member info when member is selected
$('#add_member_id').on('change', function() {
    const selected = $(this).find('option:selected');
    const memberId = $(this).val();
    const month = $('#add_month').val();
    currentMonthlyRent = selected.data('rent') || 0;
    currentTotalPaid = selected.data('paid') || 0;
    const roomNumber = selected.data('room') || 'N/A';
    const bedNumber = selected.data('bed') || 'N/A';
    const memberName = selected.text().split(' - ')[0];
    const previousDues = selected.data('previous-dues') || 0;
    const hasPreviousDues = selected.data('has-previous') || false;
    const previousMonths = selected.data('previous-months') || [];

    if (memberId && currentMonthlyRent > 0) {
        if (hasPreviousDues && previousDues > 0) {
            let monthsList = '';
            if (previousMonths.length > 0) {
                monthsList = '<ul class="mb-0 mt-1">';
                previousMonths.forEach(month => {
                    monthsList += `<li>${month.month_name}: ₹${month.pending.toLocaleString('en-IN')}</li>`;
                });
                monthsList += '</ul>';
            }
            const previousDuesHtml = `
                <i class="bi bi-exclamation-triangle-fill"></i>
                <strong>Previous Dues: ₹${previousDues.toLocaleString('en-IN')}</strong>
                ${monthsList}
                <small class="d-block mt-1">Please clear previous dues before paying current month!</small>
            `;
            $('#previousDuesWarning').show();
            $('#previousDuesDetails').html(previousDuesHtml);
            $('#add_amount').prop('disabled', true);
            $('#add_amount').val(previousDues);
        } else {
            $('#previousDuesWarning').hide();
            $('#add_amount').prop('disabled', false);
            const pendingBalance = Math.max(0, currentMonthlyRent - currentTotalPaid);
            if (pendingBalance > 0 && pendingBalance < currentMonthlyRent) {
                $('#add_amount').val(pendingBalance);
            } else if (pendingBalance === 0) {
                $('#add_amount').val(0);
            } else {
                $('#add_amount').val(currentMonthlyRent);
            }
        }

        $('#memberInfo').html(`
            <i class="bi bi-person-badge"></i>
            <strong>${memberName}</strong><br>
            Room: ${roomNumber} | Bed: ${bedNumber} | Monthly Rent: ₹${currentMonthlyRent.toLocaleString('en-IN')}<br>
            Already Paid This Month: <strong>₹${currentTotalPaid.toLocaleString('en-IN')}</strong>
            ${currentTotalPaid > 0 ? `<br>Pending Balance: <strong class="text-danger">₹${Math.max(0, currentMonthlyRent - currentTotalPaid).toLocaleString('en-IN')}</strong>` : ''}
        `).removeClass('alert-info').addClass('alert-success');

        checkPaymentStatus(currentTotalPaid, currentMonthlyRent);

        // Check if payment already exists
        $.ajax({
            url: "{{ url('admin/payments/check') }}/" + memberId + "/" + month,
            method: "GET",
            success: function(response) {
                if (response.exists) {
                    $('#paymentExistsWarning').html(`
                        <i class="bi bi-exclamation-triangle"></i>
                        <strong>Warning:</strong> Payment for this month already exists!
                        Total paid so far: ₹${response.total_paid.toLocaleString('en-IN')}
                    `).show();
                    $('#addPaymentForm button[type="submit"]').prop('disabled', true);
                } else {
                    $('#paymentExistsWarning').hide();
                    $('#addPaymentForm button[type="submit"]').prop('disabled', false);
                }
            }
        });
    } else {
        $('#memberInfo').html('<i class="bi bi-info-circle"></i> Select a member to see details')
            .removeClass('alert-success').addClass('alert-info');
        $('#add_amount').val('');
        $('#partialInfo').hide();
        $('#fullInfo').hide();
        $('#previousDuesWarning').hide();
        $('#add_amount').prop('disabled', false);
    }
});

// Check payment status
function checkPaymentStatus(totalPaid, monthlyRent) {
    const amountPaid = parseFloat($('#add_amount').val()) || 0;
    const newTotalPaid = totalPaid + amountPaid;

    if (newTotalPaid >= monthlyRent) {
        $('#partialInfo').hide();
        $('#fullInfo').show();
    } else if (amountPaid > 0) {
        const pending = monthlyRent - newTotalPaid;
        $('#pendingAmountDisplay').text('₹' + pending.toLocaleString('en-IN'));
        $('#partialInfo').show();
        $('#fullInfo').hide();
    } else {
        $('#partialInfo').hide();
        $('#fullInfo').hide();
    }
}

// Check payment status when amount changes
$('#add_amount').on('input', function() {
    checkPaymentStatus(currentTotalPaid, currentMonthlyRent);
});

// Check payment exists when month changes
$('#add_month').on('change', function() {
    const memberId = $('#add_member_id').val();
    const month = $(this).val();

    if (memberId) {
        $.ajax({
            url: "{{ url('admin/payments/check') }}/" + memberId + "/" + month,
            method: "GET",
            success: function(response) {
                if (response.exists) {
                    $('#paymentExistsWarning').html(`
                        <i class="bi bi-exclamation-triangle"></i>
                        <strong>Warning:</strong> Payment for this month already exists!
                        Total paid so far: ₹${response.total_paid.toLocaleString('en-IN')}
                    `).show();
                    $('#addPaymentForm button[type="submit"]').prop('disabled', true);
                } else {
                    $('#paymentExistsWarning').hide();
                    $('#addPaymentForm button[type="submit"]').prop('disabled', false);
                }
            }
        });
    }
});

// Add Payment Form Submit
$('#addPaymentForm').on('submit', function(e) {
    e.preventDefault();
    showLoader();

    const amountPaid = parseFloat($('#add_amount').val()) || 0;
    const newTotalPaid = currentTotalPaid + amountPaid;
    const status = newTotalPaid >= currentMonthlyRent ? 'paid' : 'pending';

    let formData = $(this).serialize();
    formData += '&status=' + status;
    formData += '&paid_date=' + (status === 'paid' ? new Date().toISOString().split('T')[0] : '');

    $.ajax({
        url: "{{ route('admin.payments.store') }}",
        method: "POST",
        data: formData,
        success: function(response) {
            hideLoader();
            showToast(response.message, 'success');
            $('#addPaymentModal').modal('hide');
            $('#addPaymentForm')[0].reset();
            $('#add_member_id').prop('disabled', true).html('<option value="">First select hostel</option>');
            $('#memberFilters').hide();
            $('#paymentExistsWarning').hide();
            $('#partialInfo').hide();
            $('#fullInfo').hide();
            $('#previousDuesWarning').hide();
            $('#memberInfo').html('<i class="bi bi-info-circle"></i> Select a member to see details')
                .removeClass('alert-success').addClass('alert-info');
            setTimeout(() => location.reload(), 1000);
        },
        error: function(xhr) {
            hideLoader();
            let errorMsg = xhr.responseJSON?.message || 'Error recording payment';
            showToast(errorMsg, 'error');
        }
    });
});

// Record payment from pending dues table
$('.record-payment-btn').on('click', function() {
    const memberId = $(this).data('member-id');
    const memberName = $(this).data('member-name');

    $('#addPaymentModal').modal('show');
    showToast('Please select the hostel and find the member: ' + memberName, 'info');
});

// ==================== VIEW PAYMENT ====================
$('.view-payment').on('click', function() {
    const paymentId = $(this).data('id');

    $('#viewPaymentContent').html(`
        <div class="text-center py-4">
            <div class="spinner-border text-primary"></div>
            <p>Loading payment details...</p>
        </div>
    `);
    $('#viewPaymentModal').modal('show');

    $.ajax({
        url: "{{ url('admin/payments') }}/" + paymentId,
        method: "GET",
        success: function(response) {
            if (response.success) {
                const p = response.data;
                let previousDuesHtml = '';
                if (p.previous_dues_total > 0) {
                    let monthsList = '';
                    p.previous_dues_months.forEach(month => {
                        monthsList += `<li>${month.month_name}: ₹${month.pending.toLocaleString('en-IN')}</li>`;
                    });
                    previousDuesHtml = `
                        <tr class="table-danger">
                            <th>Previous Pending Dues</th>
                            <td colspan="3">
                                <strong class="text-danger">₹${p.previous_dues_total.toLocaleString('en-IN')}</strong>
                                ${monthsList ? `<ul class="mb-0 mt-1">${monthsList}</ul>` : ''}
                            </td>
                        </tr>
                    `;
                }

                let pendingHtml = '';
                if (p.pending_amount > 0) {
                    pendingHtml = `
                        <tr class="table-warning">
                            <th>Current Month Pending</th>
                            <td colspan="3" class="text-danger fw-bold">₹${p.pending_amount.toLocaleString('en-IN')}</td>
                        </tr>
                    `;
                }

                let html = `
                    <div class="card mb-3">
                        <div class="card-body">
                            <h6 class="card-title text-primary">Member Information</h6>
                            <table class="table table-sm table-borderless">
                                <tr><th width="35%">Member Name</th><td>${p.member_name}</td><th>Phone</th><td>${p.member_phone}</td></tr>
                                <tr><th>Email</th><td>${p.member_email || '—'}</td><th>Hostel</th><td>${p.hostel_name}</td></tr>
                                <tr><th>Room Number</th><td>${p.room_number}</td><th>Bed Numbers</th><td>${p.bed_numbers}</td></tr>
                            </table>
                        </div>
                    </div>
                    <div class="card mb-3">
                        <div class="card-body">
                            <h6 class="card-title text-success">Payment Details</h6>
                            <table class="table table-sm table-borderless">
                                <tr><th>Month</th><td colspan="3"><strong>${p.month_name}</strong></td></tr>
                                <tr><th>Monthly Rent</th><td>₹${p.monthly_rent.toLocaleString('en-IN')}</td><th>Amount Paid</th><td class="text-success fw-bold">₹${p.amount_paid.toLocaleString('en-IN')}</td></tr>
                                <tr><th>Total Paid This Month</th><td colspan="3" class="text-primary fw-bold">₹${p.total_paid_all.toLocaleString('en-IN')}</td></tr>
                                ${pendingHtml}
                                ${previousDuesHtml}
                                <tr><th>Status</th><td colspan="3">${p.status === 'paid' ? '<span class="badge bg-success">Fully Paid</span>' : '<span class="badge bg-warning">Partial Payment</span>'}</td></tr>
                                <tr><th>Paid Date</th><td colspan="3">${p.paid_date}</td></tr>
                                <tr><th>Recorded On</th><td colspan="3">${p.created_at}</td></tr>
                            </table>
                        </div>
                    </div>
                `;
                $('#viewPaymentContent').html(html);
            } else {
                $('#viewPaymentContent').html('<div class="alert alert-danger">Error loading payment details</div>');
            }
        },
        error: function() {
            $('#viewPaymentContent').html('<div class="alert alert-danger">Error loading payment details</div>');
        }
    });
});

// ==================== EDIT PAYMENT ====================
$('.edit-payment').on('click', function() {
    const paymentId = $(this).data('id');
    showLoader();

    $.ajax({
        url: "{{ url('admin/payments') }}/" + paymentId + "/edit",
        method: "GET",
        success: function(response) {
            hideLoader();
            if (response.success) {
                const payment = response.payment;
                $('#edit_id').val(payment.id);
                $('#edit_member_name').val(payment.member_name);
                $('#edit_monthly_rent').val('₹' + payment.monthly_rent.toLocaleString('en-IN'));
                $('#edit_month').val(payment.month);
                $('#edit_amount').val(payment.amount);
                $('#edit_paid_date').val(payment.paid_date || '');

                if (payment.amount < payment.monthly_rent) {
                    const pending = payment.monthly_rent - payment.amount;
                    $('#editPendingAmount').text('₹' + pending.toLocaleString('en-IN'));
                    $('#editPartialInfo').show();
                } else {
                    $('#editPartialInfo').hide();
                }

                $('#editPaymentModal').modal('show');
            }
        },
        error: function() {
            hideLoader();
            showToast('Error loading payment data', 'error');
        }
    });
});

// Edit amount change handler
$('#edit_amount').on('input', function() {
    const monthlyRent = parseFloat($('#edit_monthly_rent').val().replace('₹', '').replace(/,/g, '')) || 0;
    const amountPaid = parseFloat($(this).val()) || 0;

    if (amountPaid < monthlyRent && amountPaid > 0) {
        const pending = monthlyRent - amountPaid;
        $('#editPendingAmount').text('₹' + pending.toLocaleString('en-IN'));
        $('#editPartialInfo').show();
    } else if (amountPaid >= monthlyRent) {
        $('#editPartialInfo').hide();
    } else {
        $('#editPartialInfo').hide();
    }
});

// Edit Payment Form Submit
$('#editPaymentForm').on('submit', function(e) {
    e.preventDefault();
    showLoader();

    let id = $('#edit_id').val();
    const monthlyRent = parseFloat($('#edit_monthly_rent').val().replace('₹', '').replace(/,/g, '')) || 0;
    const amountPaid = parseFloat($('#edit_amount').val()) || 0;
    const status = amountPaid >= monthlyRent ? 'paid' : 'pending';

    let formData = $(this).serialize();
    formData += '&status=' + status;

    $.ajax({
        url: "{{ url('admin/payments') }}/" + id,
        method: "POST",
        data: formData,
        success: function(response) {
            hideLoader();
            showToast(response.message, 'success');
            $('#editPaymentModal').modal('hide');
            setTimeout(() => location.reload(), 1000);
        },
        error: function(xhr) {
            hideLoader();
            let errorMsg = xhr.responseJSON?.message || 'Error updating payment';
            showToast(errorMsg, 'error');
        }
    });
});

// ==================== DELETE PAYMENT ====================
let deletePaymentId = null;

$('.delete-payment').on('click', function() {
    deletePaymentId = $(this).data('id');
    $('#delete_payment_name').text($(this).data('name'));
    $('#delete_payment_month').text($(this).data('month'));
    $('#deletePaymentModal').modal('show');
});

$('#confirmDeleteBtn').on('click', function() {
    if (!deletePaymentId) return;
    showLoader();

    $.ajax({
        url: "{{ url('admin/payments') }}/" + deletePaymentId,
        method: "DELETE",
        data: { _token: "{{ csrf_token() }}" },
        success: function(response) {
            hideLoader();
            showToast(response.message, 'success');
            $('#deletePaymentModal').modal('hide');
            setTimeout(() => location.reload(), 1000);
        },
        error: function(xhr) {
            hideLoader();
            let errorMsg = xhr.responseJSON?.message || 'Error deleting payment';
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
    if ($('#globalLoader').length === 0) {
        $('body').append('<div id="globalLoader" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);z-index:9999;"><div style="position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);"><div class="spinner-border text-light" style="width:3rem;height:3rem;"></div></div></div>');
    }
    $('#globalLoader').fadeIn(200);
}

function hideLoader() {
    $('#globalLoader').fadeOut(200);
}

// Reset add modal when closed
$('#addPaymentModal').on('hidden.bs.modal', function() {
    $('#addPaymentForm')[0].reset();
    $('#add_member_id').prop('disabled', true).html('<option value="">First select hostel</option>');
    $('#memberFilters').hide();
    $('#paymentExistsWarning').hide();
    $('#partialInfo').hide();
    $('#fullInfo').hide();
    $('#previousDuesWarning').hide();
    $('#memberInfo').html('<i class="bi bi-info-circle"></i> Select a member to see details')
        .removeClass('alert-success').addClass('alert-info');
    $('#addPaymentForm button[type="submit"]').prop('disabled', false);
    currentMonthlyRent = 0;
    currentTotalPaid = 0;
});

// Add styles
$('head').append(`
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
.progress {
    border-radius: 10px;
}
</style>
`);
</script>
@endsection
