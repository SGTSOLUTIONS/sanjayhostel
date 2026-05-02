@extends('layouts.admin')

@section('title', 'Financial Report')
@section('page-title', 'Financial Report')

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
    <div>
        <h2 class="fw-bold mb-1">Financial Report</h2>
        <p class="text-muted mb-0">Detailed financial analysis with date range filtering</p>
    </div>
    <div>
        <button class="btn btn-success rounded-pill" id="exportExcelBtn">
            <i class="bi bi-file-excel"></i> Export to Excel
        </button>
        <button class="btn btn-danger rounded-pill" id="exportPdfBtn">
            <i class="bi bi-file-pdf"></i> Export to PDF
        </button>
    </div>
</div>

<!-- Date Range Filter -->
<div class="admin-card mb-4">
    <div class="card-header-custom">
        <i class="bi bi-funnel me-2"></i> Filter Report
    </div>
    <div class="p-3">
        <form method="GET" action="{{ route('admin.reports.financial') }}" class="row g-3" id="filterForm">
            <div class="col-md-3">
                <label class="form-label">Start Date</label>
                <input type="date" name="start_date" class="form-control" value="{{ $startDate }}" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">End Date</label>
                <input type="date" name="end_date" class="form-control" value="{{ $endDate }}" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">Hostel</label>
                <select name="hostel_id" class="form-select">
                    <option value="">All Hostels</option>
                    @foreach($hostels as $hostel)
                        <option value="{{ $hostel->id }}" {{ $selectedHostelId == $hostel->id ? 'selected' : '' }}>
                            {{ $hostel->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100">Generate Report</button>
            </div>
        </form>
    </div>
</div>

<!-- Summary Cards -->
<div class="row g-4 mb-4">
    <div class="col-md-3">
        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <p class="text-muted mb-1">Total Income</p>
                    <h3 class="fw-bold mb-0 text-success">₹{{ number_format($reportData['summary']['total_income']) }}</h3>
                    <small class="text-muted">{{ $reportData['summary']['collection_rate'] }}% collection rate</small>
                </div>
                <div class="stat-icon bg-success bg-opacity-10 rounded-circle p-2">
                    <i class="bi bi-cash-stack fs-4 text-success"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <p class="text-muted mb-1">Total Expenses</p>
                    <h3 class="fw-bold mb-0 text-danger">₹{{ number_format($reportData['summary']['total_expenses']) }}</h3>
                    <small class="text-muted">Including staff salary</small>
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
                    <p class="text-muted mb-1">Net Profit</p>
                    <h3 class="fw-bold mb-0 text-info">₹{{ number_format($reportData['summary']['net_profit']) }}</h3>
                    <small class="text-muted">{{ $reportData['summary']['profit_margin'] }}% margin</small>
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
                    <p class="text-muted mb-1">Pending Income</p>
                    <h3 class="fw-bold mb-0 text-warning">₹{{ number_format($reportData['summary']['pending_income']) }}</h3>
                    <small class="text-muted">{{ $reportData['pending_count'] }} members pending</small>
                </div>
                <div class="stat-icon bg-warning bg-opacity-10 rounded-circle p-2">
                    <i class="bi bi-clock-history fs-4 text-warning"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Detailed Stats Row -->
<div class="row g-4 mb-4">
    <div class="col-md-6">
        <div class="admin-card h-100">
            <div class="card-header-custom">
                <i class="bi bi-pie-chart me-2"></i> Expense Breakdown by Category
            </div>
            <div class="p-3">
                <canvas id="expenseChart" height="200"></canvas>
                <div class="mt-3">
                    <table class="table table-sm">
                        <thead>
                            <tr><th>Category</th><th>Amount</th><th>%</th></tr>
                        </thead>
                        <tbody>
                            @foreach($reportData['expenses_by_category'] as $expense)
                            <tr>
                                <td>{{ ucfirst($expense->category ?? 'Other') }}</td>
                                <td>₹{{ number_format($expense->total) }}</td>
                                <td>{{ $reportData['summary']['total_expenses'] > 0 ? round(($expense->total / $reportData['summary']['total_expenses']) * 100, 1) : 0 }}%</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="admin-card h-100">
            <div class="card-header-custom">
                <i class="bi bi-calendar-week me-2"></i> Daily Income vs Expense
            </div>
            <div class="p-3">
                <canvas id="dailyChart" height="200"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Income Table -->
<div class="admin-card mb-4">
    <div class="card-header-custom">
        <i class="bi bi-cash-stack me-2"></i> Income Details
        <button class="btn btn-sm btn-outline-primary float-end" onclick="copyTableToClipboard('incomeTable')">Copy</button>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0" id="incomeTable">
            <thead class="table-light">
                <tr>
                    <th>S.No</th>
                    <th>Member Name</th>
                    <th>Phone</th>
                    <th>Room</th>
                    <th>Amount Paid</th>
                </tr>
            </thead>
            <tbody>
                @forelse($reportData['member_payments'] as $payment)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $payment->member->name ?? 'N/A' }}</td>
                    <td>{{ $payment->member->phone ?? 'N/A' }}</td>
                    <td>{{ $payment->member->room->room_number ?? 'N/A' }}</td>
                    <td class="text-success fw-bold">₹{{ number_format($payment->total_paid) }}</td>
                </tr>
                @empty
                <tr><td colspan="5" class="text-center">No income records found</td></tr>
                @endforelse
            </tbody>
            <tfoot class="table-light">
                <tr>
                    <td colspan="4" class="text-end fw-bold">Total:</td>
                    <td class="text-success fw-bold">₹{{ number_format($reportData['summary']['total_income']) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

<!-- Expense Table -->
<div class="admin-card mb-4">
    <div class="card-header-custom">
        <i class="bi bi-receipt me-2"></i> Expense Details
        <button class="btn btn-sm btn-outline-primary float-end" onclick="copyTableToClipboard('expenseTable')">Copy</button>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0" id="expenseTable">
            <thead class="table-light">
                </tr>
                    <th>Date</th>
                    <th>Expense Name</th>
                    <th>Category</th>
                    <th>Amount</th>
                    <th>Hostel</th>
                    <th>Note</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $expenses = \App\Models\Expense::whereBetween('expense_date', [$startDate, $endDate])
                        ->when($selectedHostelId, function($q) use ($selectedHostelId) {
                            return $q->where(function($sq) use ($selectedHostelId) {
                                $sq->where('hostel_id', $selectedHostelId)->orWhereNull('hostel_id');
                            });
                        })
                        ->orderBy('expense_date', 'desc')
                        ->get();
                @endphp
                @forelse($expenses as $expense)
                <tr>
                    <td>{{ $expense->expense_date->format('d M Y') }}</td>
                    <td>{{ $expense->expense_name }}</td>
                    <td><span class="badge bg-secondary">{{ ucfirst($expense->category ?? 'Other') }}</span></td>
                    <td class="text-danger fw-bold">₹{{ number_format($expense->amount) }}</td>
                    <td>{{ $expense->hostel->name ?? 'Global' }}</td>
                    <td>{{ Str::limit($expense->note, 30) }}</td>
                </tr>
                @empty
                <tr><td colspan="6" class="text-center">No expense records found</td></tr>
                @endforelse
            </tbody>
            <tfoot class="table-light">
                <tr>
                    <td colspan="3" class="text-end fw-bold">Total:</td>
                    <td class="text-danger fw-bold">₹{{ number_format($reportData['summary']['total_expenses']) }}</td>
                    <td></td><td></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

<!-- Staff Salary Table -->
<div class="admin-card mb-4">
    <div class="card-header-custom">
        <i class="bi bi-people me-2"></i> Staff Salary Details
        <button class="btn btn-sm btn-outline-primary float-end" onclick="copyTableToClipboard('salaryTable')">Copy</button>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0" id="salaryTable">
            <thead class="table-light">
                <tr>
                    <th>S.No</th>
                    <th>Staff Name</th>
                    <th>Position</th>
                    <th>Hostel</th>
                    <th>Monthly Salary</th>
                    <th>Present Days</th>
                    <th>Leave Days</th>
                    <th>Salary for Period</th>
                </tr>
            </thead>
            <tbody>
                @forelse($reportData['staff_salary']['staff_list'] as $staff)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $staff['name'] }}</td>
                    <td>{{ ucfirst($staff['position']) }}</td>
                    <td>{{ $staff['hostel'] }}</td>
                    <td>₹{{ number_format($staff['monthly_salary']) }}</td>
                    <td>{{ $staff['present_days'] }}</td>
                    <td>{{ $staff['leave_days'] }}</td>
                    <td class="text-primary fw-bold">₹{{ number_format($staff['salary_for_period']) }}</td>
                </tr>
                @empty
                <tr><td colspan="8" class="text-center">No staff members found</td></tr>
                @endforelse
            </tbody>
            <tfoot class="table-light">
                <tr>
                    <td colspan="7" class="text-end fw-bold">Total Salary:</td>
                    <td class="text-primary fw-bold">₹{{ number_format($reportData['staff_salary']['total_salary']) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

<!-- Pending Dues Table -->
@if(count($reportData['pending_members']) > 0)
<div class="admin-card mb-4">
    <div class="card-header-custom bg-warning bg-opacity-10">
        <i class="bi bi-exclamation-triangle me-2 text-warning"></i> Members with Pending Dues
        <button class="btn btn-sm btn-outline-primary float-end" onclick="copyTableToClipboard('pendingTable')">Copy</button>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0" id="pendingTable">
            <thead class="table-light">
                <tr>
                    <th>S.No</th>
                    <th>Member Name</th>
                    <th>Phone</th>
                    <th>Room</th>
                    <th>Monthly Rent</th>
                    <th>Paid Amount</th>
                    <th>Pending Amount</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @foreach($reportData['pending_members'] as $pending)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $pending['member']->name }}</td>
                    <td>{{ $pending['member']->phone ?? 'N/A' }}</td>
                    <td>{{ $pending['member']->room->room_number ?? 'N/A' }}</td>
                    <td>₹{{ number_format($pending['monthly_rent']) }}</td>
                    <td class="text-success">₹{{ number_format($pending['total_paid']) }}</td>
                    <td class="text-danger fw-bold">₹{{ number_format($pending['pending_amount']) }}</td>
                    <td>
                        <button class="btn btn-sm btn-primary record-payment"
                                data-member-id="{{ $pending['member']->id }}"
                                data-member-name="{{ $pending['member']->name }}">
                            Record Payment
                        </button>
                    </td>
                </tr>
                @endforeach
            </tbody>
            <tfoot class="table-light">
                <tr>
                    <td colspan="6" class="text-end fw-bold">Total Pending:</td>
                    <td class="text-danger fw-bold">₹{{ number_format($reportData['summary']['pending_income']) }}</td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
@endif

<!-- Daily Breakdown Table -->
<div class="admin-card mb-4">
    <div class="card-header-custom">
        <i class="bi bi-calendar-day me-2"></i> Daily Breakdown
        <button class="btn btn-sm btn-outline-primary float-end" onclick="copyTableToClipboard('dailyTable')">Copy</button>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0" id="dailyTable">
            <thead class="table-light">
                <tr>
                    <th>Date</th>
                    <th>Income (₹)</th>
                    <th>Expense (₹)</th>
                    <th>Profit (₹)</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($reportData['daily_breakdown'] as $day)
                <tr>
                    <td>{{ date('d M Y', strtotime($day['date'])) }}</td>
                    <td class="text-success">₹{{ number_format($day['income']) }}</td>
                    <td class="text-danger">₹{{ number_format($day['expense']) }}</td>
                    <td class="{{ $day['profit'] >= 0 ? 'text-success' : 'text-danger' }} fw-bold">₹{{ number_format($day['profit']) }}</td>
                    <td>
                        @if($day['profit'] > 0)
                            <span class="badge bg-success">Profit</span>
                        @elseif($day['profit'] < 0)
                            <span class="badge bg-danger">Loss</span>
                        @else
                            <span class="badge bg-secondary">Break-even</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
            <tfoot class="table-light">
                <tr>
                    <td class="fw-bold">Total</td>
                    <td class="text-success fw-bold">₹{{ number_format($reportData['summary']['total_income']) }}</td>
                    <td class="text-danger fw-bold">₹{{ number_format($reportData['summary']['total_expenses']) }}</td>
                    <td class="{{ $reportData['summary']['net_profit'] >= 0 ? 'text-success' : 'text-danger' }} fw-bold">₹{{ number_format($reportData['summary']['net_profit']) }}</td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Expense Chart
const expenseCtx = document.getElementById('expenseChart').getContext('2d');
const expenseData = @json($reportData['expenses_by_category']);
new Chart(expenseCtx, {
    type: 'pie',
    data: {
        labels: expenseData.map(e => e.category ? e.category.toUpperCase() : 'Other'),
        datasets: [{
            data: expenseData.map(e => e.total),
            backgroundColor: ['#ef4444', '#f59e0b', '#10b981', '#3b82f6', '#8b5cf6', '#ec489a', '#14b8a6', '#f97316']
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { position: 'bottom' },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        const total = expenseData.reduce((sum, e) => sum + e.total, 0);
                        const percentage = ((context.raw / total) * 100).toFixed(1);
                        return `${context.label}: ₹${context.raw.toLocaleString()} (${percentage}%)`;
                    }
                }
            }
        }
    }
});

// Daily Chart
const dailyCtx = document.getElementById('dailyChart').getContext('2d');
const dailyData = @json($reportData['daily_breakdown']);
new Chart(dailyCtx, {
    type: 'line',
    data: {
        labels: dailyData.map(d => d.date),
        datasets: [
            {
                label: 'Income',
                data: dailyData.map(d => d.income),
                borderColor: '#10b981',
                backgroundColor: 'rgba(16, 185, 129, 0.1)',
                tension: 0.4,
                fill: true
            },
            {
                label: 'Expense',
                data: dailyData.map(d => d.expense),
                borderColor: '#ef4444',
                backgroundColor: 'rgba(239, 68, 68, 0.1)',
                tension: 0.4,
                fill: true
            }
        ]
    },
    options: {
        responsive: true,
        plugins: {
            tooltip: {
                callbacks: {
                    label: function(context) {
                        return `${context.dataset.label}: ₹${context.raw.toLocaleString()}`;
                    }
                }
            }
        }
    }
});

// Export to Excel
document.getElementById('exportExcelBtn').addEventListener('click', function() {
    const formData = new FormData(document.getElementById('filterForm'));
    const params = new URLSearchParams(formData);
    window.location.href = "{{ route('admin.reports.export.excel') }}?" + params.toString();
});

// Copy table to clipboard
function copyTableToClipboard(tableId) {
    const table = document.getElementById(tableId);
    const range = document.createRange();
    range.selectNode(table);
    window.getSelection().removeAllRanges();
    window.getSelection().addRange(range);
    document.execCommand('copy');
    window.getSelection().removeAllRanges();
    showToast('Table copied to clipboard', 'success');
}

// Record payment from pending dues
document.querySelectorAll('.record-payment').forEach(btn => {
    btn.addEventListener('click', function() {
        const memberId = this.dataset.memberId;
        const memberName = this.dataset.memberName;
        showToast(`Open payment form for ${memberName}`, 'info');
        // You can redirect to payment page or open modal
        // window.location.href = "{{ route('admin.payments.index') }}?member_id=" + memberId;
    });
});

function showToast(message, type) {
    const toast = document.createElement('div');
    toast.className = 'position-fixed top-0 end-0 p-3';
    toast.style.zIndex = '9999';
    toast.innerHTML = `
        <div class="toast show" role="alert">
            <div class="toast-header bg-${type === 'success' ? 'success' : 'info'} text-white">
                <strong class="me-auto">${type === 'success' ? 'Success' : 'Info'}</strong>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast"></button>
            </div>
            <div class="toast-body">${message}</div>
        </div>
    `;
    document.body.appendChild(toast);
    setTimeout(() => toast.remove(), 3000);
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
</style>
@endsection
