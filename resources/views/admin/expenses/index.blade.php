@extends('layouts.admin')

@section('title', 'Expense Management')
@section('page-title', 'Expense Management')

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
    <div>
        <h2 class="fw-bold mb-1">Expense Management</h2>
        <p class="text-muted mb-0">Track and manage all expenses</p>
    </div>
    <button class="btn btn-primary rounded-pill" data-bs-toggle="modal" data-bs-target="#addExpenseModal">
        <i class="bi bi-plus-lg"></i> Add Expense
    </button>
</div>

<!-- Stats Cards -->
<div class="row g-4 mb-4">
    <div class="col-md-3">
        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <p class="text-muted mb-1">Total Expenses</p>
                    <h3 class="fw-bold mb-0 text-danger">₹{{ number_format($totalExpenses ?? 0) }}</h3>
                    <small class="text-muted">All time</small>
                </div>
                <div class="stat-icon bg-danger bg-opacity-10 rounded-circle p-2">
                    <i class="bi bi-calculator fs-4 text-danger"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <p class="text-muted mb-1">This Month</p>
                    <h3 class="fw-bold mb-0 text-warning">₹{{ number_format($currentMonthExpenses ?? 0) }}</h3>
                    <small class="text-muted">{{ date('F Y') }}</small>
                </div>
                <div class="stat-icon bg-warning bg-opacity-10 rounded-circle p-2">
                    <i class="bi bi-calendar-month fs-4 text-warning"></i>
                </div>
            </div>
            @if(isset($momChange))
            <div class="mt-2">
                <small class="{{ $momChange > 0 ? 'text-danger' : ($momChange < 0 ? 'text-success' : 'text-muted') }}">
                    <i class="bi bi-arrow-{{ $momChange > 0 ? 'up' : ($momChange < 0 ? 'down' : 'right') }}"></i>
                    {{ abs($momChange) }}% vs last month
                </small>
            </div>
            @endif
        </div>
    </div>

    <div class="col-md-3">
        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <p class="text-muted mb-1">Monthly Average</p>
                    <h3 class="fw-bold mb-0 text-info">₹{{ number_format(($totalExpenses ?? 0) / max(1, 12)) }}</h3>
                    <small class="text-muted">Last 12 months</small>
                </div>
                <div class="stat-icon bg-info bg-opacity-10 rounded-circle p-2">
                    <i class="bi bi-graph-up fs-4 text-info"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <p class="text-muted mb-1">Total Records</p>
                    <h3 class="fw-bold mb-0 text-primary">{{ $expenses->total() ?? 0 }}</h3>
                    <small class="text-muted">Expense entries</small>
                </div>
                <div class="stat-icon bg-primary bg-opacity-10 rounded-circle p-2">
                    <i class="bi bi-receipt fs-4 text-primary"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Charts Row -->
<div class="row g-4 mb-4">
    <div class="col-md-8">
        <div class="admin-card">
            <div class="card-header-custom">
                <i class="bi bi-graph-up me-2"></i> Monthly Expense Trend
            </div>
            <div class="p-3">
                <canvas id="expenseTrendChart" height="100"></canvas>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="admin-card">
            <div class="card-header-custom">
                <i class="bi bi-pie-chart me-2"></i> Category Breakdown - {{ date('F Y') }}
            </div>
            <div class="p-3">
                <canvas id="categoryChart" height="200"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="admin-card mb-4">
    <div class="card-header-custom">
        <i class="bi bi-funnel me-2"></i> Filter Expenses
    </div>
    <div class="p-3">
        <form method="GET" action="{{ route('admin.expenses.index') }}" class="row g-3">
            <div class="col-md-3">
                <label class="form-label">Month</label>
                <input type="month" name="month" class="form-control" value="{{ request('month', date('Y-m')) }}">
            </div>
            <div class="col-md-3">
                <label class="form-label">Category</label>
                <select name="category" class="form-select">
                    <option value="">All Categories</option>
                    @foreach($categories ?? [] as $category)
                        <option value="{{ $category }}" {{ request('category') == $category ? 'selected' : '' }}>
                            {{ ucfirst($category) }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Search</label>
                <input type="text" name="search" class="form-control" placeholder="Search by name or note..." value="{{ request('search') }}">
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100">Apply Filter</button>
                <a href="{{ route('admin.expenses.index') }}" class="btn btn-secondary w-100 ms-2">Reset</a>
            </div>
        </form>
    </div>
</div>

<!-- Expenses Table -->
<div class="admin-card">
    <div class="card-header-custom">
        <i class="bi bi-list-ul me-2"></i> Expense Records
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>ID</th>
                    <th>Expense Name</th>
                    <th>Category</th>
                    <th>Amount</th>
                    <th>Month</th>
                    <th>Date</th>
                    <th>Payment Method</th>
                    <th>Hostel</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($expenses as $expense)
                <tr>
                    <td>{{ $expense->id }}</td>
                    <td class="fw-semibold">
                        {{ $expense->expense_name }}
                        @if($expense->note)
                            <i class="bi bi-chat-text text-muted ms-1" title="{{ $expense->note }}"></i>
                        @endif
                        @if($expense->receipt)
                            <i class="bi bi-paperclip text-muted ms-1" title="Has receipt"></i>
                        @endif
                    </td>
                    <td>
                        @if($expense->category)
                            <span class="badge bg-secondary">{{ ucfirst($expense->category) }}</span>
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td class="text-danger fw-bold">₹{{ number_format($expense->amount) }}</td>
                    <td><span class="badge bg-info">{{ date('M Y', strtotime($expense->month . '-01')) }}</span></td>
                    <td>{{ $expense->expense_date->format('d M Y') }}</td>
                    <td>
                        @if($expense->payment_method)
                            <span class="badge bg-light text-dark">{{ ucfirst($expense->payment_method) }}</span>
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td>{{ $expense->hostel->name ?? 'Global' }}</td>
                    <td>
                        <button class="btn btn-sm btn-outline-info view-expense" data-id="{{ $expense->id }}">
                            <i class="bi bi-eye"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-primary edit-expense" data-id="{{ $expense->id }}">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-danger delete-expense"
                                data-id="{{ $expense->id }}"
                                data-name="{{ $expense->expense_name }}">
                            <i class="bi bi-trash"></i>
                        </button>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" class="text-center py-5">
                        <i class="bi bi-inbox fs-1 text-muted"></i>
                        <p class="mt-2 mb-0">No expense records found. Click "Add Expense" to record one.</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($expenses->hasPages())
    <div class="p-3">
        {{ $expenses->links() }}
    </div>
    @endif
</div>

<!-- ==================== ADD EXPENSE MODAL ==================== -->
<div class="modal fade" id="addExpenseModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="bi bi-calculator me-2"></i>Add New Expense</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="addExpenseForm" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Expense Name <span class="text-danger">*</span></label>
                            <input type="text" name="expense_name" id="expense_name" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Amount (₹) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" name="amount" id="amount" class="form-control" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Month <span class="text-danger">*</span></label>
                            <input type="month" name="month" id="month" class="form-control" value="{{ date('Y-m') }}" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Expense Date <span class="text-danger">*</span></label>
                            <input type="date" name="expense_date" id="expense_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Category</label>
                            <select name="category" id="category" class="form-select">
                                <option value="">Select Category</option>
                                <option value="electricity">Electricity Bill</option>
                                <option value="water">Water Bill</option>
                                <option value="maintenance">Maintenance</option>
                                <option value="salary">Staff Salary</option>
                                <option value="food">Food Supplies</option>
                                <option value="cleaning">Cleaning Supplies</option>
                                <option value="furniture">Furniture</option>
                                <option value="repair">Repairs</option>
                                <option value="internet">Internet/ Wi-Fi</option>
                                <option value="vegetables">Vegetables</option>
                                <option value="groceries">Groceries</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Payment Method</label>
                            <select name="payment_method" id="payment_method" class="form-select">
                                <option value="">Select Payment Method</option>
                                <option value="cash">Cash</option>
                                <option value="bank_transfer">Bank Transfer</option>
                                <option value="upi">UPI</option>
                                <option value="cheque">Cheque</option>
                                <option value="card">Card</option>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Hostel (Optional)</label>
                            <select name="hostel_id" id="hostel_id" class="form-select">
                                <option value="">Global Expense (All Hostels)</option>
                                @foreach($hostels ?? [] as $hostel)
                                    <option value="{{ $hostel->id }}">{{ $hostel->name }}</option>
                                @endforeach
                            </select>
                            <small class="text-muted">Select specific hostel or leave empty for global expense</small>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Receipt (Optional)</label>
                            <input type="file" name="receipt" id="receipt" class="form-control" accept="image/*,.pdf">
                            <small class="text-muted">Max 2MB (JPG, PNG, PDF)</small>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Note</label>
                        <textarea name="note" id="note" class="form-control" rows="3" placeholder="Additional notes about this expense..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Expense</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ==================== VIEW EXPENSE MODAL ==================== -->
<div class="modal fade" id="viewExpenseModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title"><i class="bi bi-receipt me-2"></i>Expense Details</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="viewExpenseContent">
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

<!-- ==================== EDIT EXPENSE MODAL ==================== -->
<div class="modal fade" id="editExpenseModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title"><i class="bi bi-pencil-square me-2"></i>Edit Expense</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="editExpenseForm" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <input type="hidden" name="id" id="edit_id">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Expense Name <span class="text-danger">*</span></label>
                            <input type="text" name="expense_name" id="edit_expense_name" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Amount (₹) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" name="amount" id="edit_amount" class="form-control" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Month <span class="text-danger">*</span></label>
                            <input type="month" name="month" id="edit_month" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Expense Date <span class="text-danger">*</span></label>
                            <input type="date" name="expense_date" id="edit_expense_date" class="form-control" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Category</label>
                            <select name="category" id="edit_category" class="form-select">
                                <option value="">Select Category</option>
                                <option value="electricity">Electricity Bill</option>
                                <option value="water">Water Bill</option>
                                <option value="maintenance">Maintenance</option>
                                <option value="salary">Staff Salary</option>
                                <option value="food">Food Supplies</option>
                                <option value="cleaning">Cleaning Supplies</option>
                                <option value="furniture">Furniture</option>
                                <option value="repair">Repairs</option>
                                <option value="internet">Internet/ Wi-Fi</option>
                                <option value="vegetables">Vegetables</option>
                                <option value="groceries">Groceries</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Payment Method</label>
                            <select name="payment_method" id="edit_payment_method" class="form-select">
                                <option value="">Select Payment Method</option>
                                <option value="cash">Cash</option>
                                <option value="bank_transfer">Bank Transfer</option>
                                <option value="upi">UPI</option>
                                <option value="cheque">Cheque</option>
                                <option value="card">Card</option>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Hostel (Optional)</label>
                            <select name="hostel_id" id="edit_hostel_id" class="form-select">
                                <option value="">Global Expense (All Hostels)</option>
                                @foreach($hostels ?? [] as $hostel)
                                    <option value="{{ $hostel->id }}">{{ $hostel->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">New Receipt (Optional)</label>
                            <input type="file" name="receipt" id="edit_receipt" class="form-control" accept="image/*,.pdf">
                            <small class="text-muted">Leave empty to keep current receipt</small>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Note</label>
                        <textarea name="note" id="edit_note" class="form-control" rows="3"></textarea>
                    </div>
                    <div id="currentReceipt" class="alert alert-info" style="display: none;">
                        <i class="bi bi-paperclip"></i> Current receipt attached
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Expense</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ==================== DELETE CONFIRMATION MODAL ==================== -->
<div class="modal fade" id="deleteExpenseModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="bi bi-exclamation-triangle-fill me-2"></i>Confirm Delete</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete expense: <strong id="delete_expense_name"></strong>?</p>
                <p class="text-danger mb-0"><small>This action cannot be undone.</small></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteBtn">Delete Permanently</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Charts
let expenseTrendChart, categoryChart;

// Load charts
const monthlyTrendData = @json($monthlyTrend ?? []);
const categoryData = @json($categoryBreakdown ?? []);

// Expense Trend Chart
if (monthlyTrendData.length) {
    const ctx = document.getElementById('expenseTrendChart').getContext('2d');
    expenseTrendChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: monthlyTrendData.map(d => d.month_name),
            datasets: [{
                label: 'Monthly Expenses (₹)',
                data: monthlyTrendData.map(d => d.total),
                borderColor: '#ef4444',
                backgroundColor: 'rgba(239, 68, 68, 0.1)',
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: { position: 'top' },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return '₹' + context.raw.toLocaleString('en-IN');
                        }
                    }
                }
            }
        }
    });
}

// Category Chart
if (categoryData.length) {
    const ctx = document.getElementById('categoryChart').getContext('2d');
    const colors = ['#ef4444', '#f59e0b', '#10b981', '#3b82f6', '#8b5cf6', '#ec489a', '#14b8a6', '#f97316', '#06b6d4', '#d946ef'];
    categoryChart = new Chart(ctx, {
        type: 'pie',
        data: {
            labels: categoryData.map(d => d.category ? d.category.toUpperCase() : 'Other'),
            datasets: [{
                data: categoryData.map(d => d.total),
                backgroundColor: colors,
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: { position: 'bottom' },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const label = context.label || '';
                            const value = context.raw;
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = ((value / total) * 100).toFixed(1);
                            return `${label}: ₹${value.toLocaleString('en-IN')} (${percentage}%)`;
                        }
                    }
                }
            }
        }
    });
}

// ==================== ADD EXPENSE ====================
$('#addExpenseForm').on('submit', function(e) {
    e.preventDefault();
    showLoader();

    const formData = new FormData(this);

    $.ajax({
        url: "{{ route('admin.expenses.store') }}",
        method: "POST",
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            hideLoader();
            showToast(response.message, 'success');
            $('#addExpenseModal').modal('hide');
            $('#addExpenseForm')[0].reset();
            setTimeout(() => location.reload(), 1000);
        },
        error: function(xhr) {
            hideLoader();
            let errorMsg = xhr.responseJSON?.message || 'Error recording expense';
            showToast(errorMsg, 'error');
        }
    });
});

// ==================== VIEW EXPENSE ====================
$('.view-expense').on('click', function() {
    const expenseId = $(this).data('id');

    $('#viewExpenseContent').html(`
        <div class="text-center py-4">
            <div class="spinner-border text-primary"></div>
            <p>Loading...</p>
        </div>
    `);
    $('#viewExpenseModal').modal('show');

    $.ajax({
        url: "{{ url('admin/expenses') }}/" + expenseId,
        method: "GET",
        success: function(response) {
            if (response.success) {
                const e = response.data;
                let receiptHtml = '';
                if (e.receipt_url) {
                    receiptHtml = `
                        <tr>
                            <th>Receipt</th>
                            <td><a href="${e.receipt_url}" target="_blank" class="btn btn-sm btn-outline-primary">View Receipt</a></td>
                        </tr>
                    `;
                }

                let html = `
                    <table class="table table-bordered">
                        <tr><th width="40%">Expense Name</th><td>${e.expense_name}</td></tr>
                        <tr><th>Amount</th><td class="text-danger fw-bold">${e.formatted_amount}</td></tr>
                        <tr><th>Month</th><td>${e.month_name}</td></tr>
                        <tr><th>Expense Date</th><td>${e.expense_date}</td></tr>
                        <tr><th>Category</th><td>${e.category ? e.category.toUpperCase() : '—'}</td></tr>
                        <tr><th>Payment Method</th><td>${e.payment_method ? e.payment_method.toUpperCase() : '—'}</td></tr>
                        <tr><th>Hostel</th><td>${e.hostel_name}</td></tr>
                        <tr><th>Added By</th><td>${e.created_by}</td></tr>
                        ${receiptHtml}
                        <tr><th>Note</th><td>${e.note || '—'}</td></tr>
                        <tr><th>Recorded On</th><td>${e.created_at}</td></tr>
                    </table>
                `;
                $('#viewExpenseContent').html(html);
            }
        },
        error: function() {
            $('#viewExpenseContent').html('<div class="alert alert-danger">Error loading expense details</div>');
        }
    });
});

// ==================== EDIT EXPENSE ====================
$('.edit-expense').on('click', function() {
    const expenseId = $(this).data('id');
    showLoader();

    $.ajax({
        url: "{{ url('admin/expenses') }}/" + expenseId + "/edit",
        method: "GET",
        success: function(response) {
            hideLoader();
            if (response.success) {
                const e = response.expense;
                $('#edit_id').val(e.id);
                $('#edit_expense_name').val(e.expense_name);
                $('#edit_amount').val(e.amount);
                $('#edit_month').val(e.month);
                $('#edit_expense_date').val(e.expense_date);
                $('#edit_category').val(e.category);
                $('#edit_payment_method').val(e.payment_method);
                $('#edit_hostel_id').val(e.hostel_id);
                $('#edit_note').val(e.note);

                if (e.receipt) {
                    $('#currentReceipt').show();
                } else {
                    $('#currentReceipt').hide();
                }

                $('#editExpenseModal').modal('show');
            }
        },
        error: function() {
            hideLoader();
            showToast('Error loading expense data', 'error');
        }
    });
});

// ==================== EDIT EXPENSE SUBMIT ====================
$('#editExpenseForm').on('submit', function(e) {
    e.preventDefault();
    showLoader();

    const id = $('#edit_id').val();
    const formData = new FormData(this);
    formData.append('_method', 'PUT');

    $.ajax({
        url: "{{ url('admin/expenses') }}/" + id,
        method: "POST",
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            hideLoader();
            showToast(response.message, 'success');
            $('#editExpenseModal').modal('hide');
            setTimeout(() => location.reload(), 1000);
        },
        error: function(xhr) {
            hideLoader();
            let errorMsg = xhr.responseJSON?.message || 'Error updating expense';
            showToast(errorMsg, 'error');
        }
    });
});

// ==================== DELETE EXPENSE ====================
let deleteExpenseId = null;

$('.delete-expense').on('click', function() {
    deleteExpenseId = $(this).data('id');
    $('#delete_expense_name').text($(this).data('name'));
    $('#deleteExpenseModal').modal('show');
});

$('#confirmDeleteBtn').on('click', function() {
    if (!deleteExpenseId) return;
    showLoader();

    $.ajax({
        url: "{{ url('admin/expenses') }}/" + deleteExpenseId,
        method: "DELETE",
        data: { _token: "{{ csrf_token() }}" },
        success: function(response) {
            hideLoader();
            showToast(response.message, 'success');
            $('#deleteExpenseModal').modal('hide');
            setTimeout(() => location.reload(), 1000);
        },
        error: function(xhr) {
            hideLoader();
            let errorMsg = xhr.responseJSON?.message || 'Error deleting expense';
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

// Reset modals when closed
$('#addExpenseModal, #editExpenseModal').on('hidden.bs.modal', function() {
    $(this).find('form')[0]?.reset();
    $(this).find('.alert').hide();
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
