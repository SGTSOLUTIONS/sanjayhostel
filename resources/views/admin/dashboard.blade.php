{{-- resources/views/admin/dashboard.blade.php --}}
@extends('layouts.admin')

@section('title', 'Dashboard - Hostel Management')
@section('page-title', 'Analytics Dashboard')

@section('content')
<!-- Welcome Row -->
<div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
    <div>
        <h2 class="fw-bold mb-1">Welcome back, {{ auth()->user()->name ?? 'Admin' }}! 👋</h2>
        <p class="text-muted mb-0">Here's what's happening with your hostels today.</p>
    </div>
    <div class="d-flex gap-2">
        <button class="btn btn-outline-primary rounded-pill" id="refreshData">
            <i class="bi bi-arrow-repeat"></i> Refresh
        </button>
        <button class="btn btn-primary rounded-pill" id="exportReport">
            <i class="bi bi-download"></i> Export
        </button>
    </div>
</div>

<!-- Stats Cards Row -->
<div class="row g-4 mb-4">
    <div class="col-xl-3 col-md-6">
        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div class="bg-primary bg-opacity-10 rounded-3 p-3">
                    <i class="bi bi-building fs-3 text-primary"></i>
                </div>
                <span class="badge bg-success bg-opacity-10 text-success">
                    <i class="bi bi-arrow-up"></i> {{ $totalHostels > 0 ? '+'.rand(5,15) : 0 }}%
                </span>
            </div>
            <h3 class="fw-bold mb-1">{{ number_format($totalHostels ?? 0) }}</h3>
            <p class="text-muted mb-0">Total Hostels</p>
            <small class="text-muted">Across all locations</small>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div class="bg-info bg-opacity-10 rounded-3 p-3">
                    <i class="bi bi-door-closed fs-3 text-info"></i>
                </div>
                <span class="badge bg-success bg-opacity-10 text-success">
                    <i class="bi bi-arrow-up"></i> +8%
                </span>
            </div>
            <h3 class="fw-bold mb-1">{{ number_format($totalRooms ?? 0) }}</h3>
            <p class="text-muted mb-0">Total Rooms</p>
            <small class="text-muted">{{ $totalBeds > 0 ? round($totalBeds/$totalRooms, 1) : 0 }} beds/room avg</small>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div class="bg-warning bg-opacity-10 rounded-3 p-3">
                    <i class="bi bi-bed fs-3 text-warning"></i>
                </div>
                <span class="badge bg-danger bg-opacity-10 text-danger">
                    <i class="bi bi-arrow-down"></i> -{{ $vacantBeds > 0 ? round(($vacantBeds/$totalBeds)*100) : 0 }}%
                </span>
            </div>
            <h3 class="fw-bold mb-1">{{ number_format($totalBeds ?? 0) }}</h3>
            <p class="text-muted mb-0">Total Beds</p>
            <small class="text-muted">{{ $occupiedBeds }} occupied | {{ $vacantBeds }} vacant</small>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div class="bg-success bg-opacity-10 rounded-3 p-3">
                    <i class="bi bi-people fs-3 text-success"></i>
                </div>
                <span class="badge bg-success bg-opacity-10 text-success">
                    <i class="bi bi-arrow-up"></i> +5%
                </span>
            </div>
            <h3 class="fw-bold mb-1">{{ number_format($totalResidents ?? 0) }}</h3>
            <p class="text-muted mb-0">Active Residents</p>
            <small class="text-muted">{{ $totalBeds > 0 ? round(($occupiedBeds/$totalBeds)*100) : 0 }}% occupancy rate</small>
        </div>
    </div>
</div>

<!-- Financial Overview & Occupancy Row -->
<div class="row g-4 mb-4">
    <div class="col-xl-6">
        <div class="admin-card">
            <div class="card-header-custom">
                <i class="bi bi-currency-dollar me-2"></i> Financial Overview - {{ \Carbon\Carbon::parse($currentMonth)->format('F Y') }}
            </div>
            <div class="p-4">
                <div class="row g-3">
                    <div class="col-6">
                        <div class="p-3 bg-success bg-opacity-10 rounded-4 text-center">
                            <i class="bi bi-graph-up-arrow text-success fs-3"></i>
                            <h3 class="fw-bold mt-2 mb-0 text-success">₹{{ number_format($monthlyIncome ?? 0) }}</h3>
                            <small class="text-muted">Total Collected</small>
                            <div class="mt-2">
                                <small class="text-muted">From {{ count($membersWithPending) }} members</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-3 bg-danger bg-opacity-10 rounded-4 text-center">
                            <i class="bi bi-exclamation-triangle text-danger fs-3"></i>
                            <h3 class="fw-bold mt-2 mb-0 text-danger">₹{{ number_format($totalPendingDues ?? 0) }}</h3>
                            <small class="text-muted">Pending Balance</small>
                            <div class="mt-2">
                                <small class="text-muted">Remaining to collect</small>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="mt-4">
                    <div class="d-flex justify-content-between mb-2">
                        <small>Collection Rate</small>
                        <small class="fw-bold">{{ $collectionRate ?? 0 }}%</small>
                    </div>
                    <div class="progress" style="height: 10px;">
                        <div class="progress-bar bg-success" style="width: {{ $collectionRate ?? 0 }}%"></div>
                    </div>
                    <div class="mt-3 text-center">
                        <small class="text-muted">
                            {{ $fullyPaidCount ?? 0 }} out of {{ $totalResidents ?? 0 }} members fully paid
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-6">
        <div class="admin-card">
            <div class="card-header-custom">
                <i class="bi bi-pie-chart me-2"></i> Bed Occupancy Overview
            </div>
            <div class="p-4">
                <div class="mb-4">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="fw-medium">Occupancy Rate</span>
                        <span class="fw-bold">{{ $totalBeds > 0 ? round(($occupiedBeds / $totalBeds) * 100) : 0 }}%</span>
                    </div>
                    <div class="progress" style="height: 12px; border-radius: 10px;">
                        <div class="progress-bar bg-success" style="width: {{ $totalBeds > 0 ? ($occupiedBeds / $totalBeds) * 100 : 0 }}%; border-radius: 10px;"></div>
                    </div>
                </div>
                <div class="row text-center g-3">
                    <div class="col-6">
                        <div class="p-3 bg-light rounded-4">
                            <i class="bi bi-check-circle-fill text-success fs-4"></i>
                            <h4 class="fw-bold mt-2 mb-0">{{ number_format($occupiedBeds ?? 0) }}</h4>
                            <small class="text-muted">Occupied Beds</small>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-3 bg-light rounded-4">
                            <i class="bi bi-x-circle-fill text-secondary fs-4"></i>
                            <h4 class="fw-bold mt-2 mb-0">{{ number_format($vacantBeds ?? 0) }}</h4>
                            <small class="text-muted">Vacant Beds</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Payment Status Table - Main Section -->
<div class="row g-4 mb-4">
    <div class="col-12">
        <div class="admin-card">
            <div class="card-header-custom d-flex justify-content-between align-items-center flex-wrap">
                <div>
                    <i class="bi bi-exclamation-triangle-fill me-2 text-warning"></i>
                    Payment Status - {{ \Carbon\Carbon::parse($currentMonth)->format('F Y') }}
                </div>
                <div>
                    <span class="badge bg-info rounded-pill me-2">{{ count($membersWithPending) }} Total Members</span>
                    <span class="badge bg-success rounded-pill me-2">{{ $fullyPaidCount }} Fully Paid</span>
                    <span class="badge bg-warning rounded-pill">{{ count($membersWithPending) - $fullyPaidCount }} Pending</span>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0" id="paymentTable">
                    <thead class="table-light">
                        <tr>
                            <th><i class="bi bi-person"></i> Member</th>
                            <th><i class="bi bi-door-closed"></i> Room</th>
                            <th><i class="bi bi-currency-rupee"></i> Monthly Rent</th>
                            <th><i class="bi bi-wallet2"></i> Amount Paid</th>
                            <th><i class="bi bi-exclamation-triangle"></i> Pending Balance</th>
                            <th><i class="bi bi-percent"></i> Payment Status</th>
                            <th><i class="bi bi-gear"></i> Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($membersWithPending as $member)
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="rounded-circle bg-primary bg-opacity-10 d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                                        <i class="bi bi-person text-primary"></i>
                                    </div>
                                    <div>
                                        <div class="fw-semibold">{{ $member['name'] }}</div>
                                        <small class="text-muted">ID: {{ $member['id'] }}</small>
                                    </div>
                                </div>
                            </td>
                            <td><span class="badge bg-secondary">{{ $member['room_number'] }}</span></td>
                            <td class="fw-bold">₹{{ number_format($member['monthly_rent']) }}</td>
                            <td class="text-success fw-bold">₹{{ number_format($member['total_paid']) }}</td>
                            <td class="bg-danger bg-opacity-10">
                                @if($member['pending_amount'] > 0)
                                    <strong class="text-danger fs-6">₹{{ number_format($member['pending_amount']) }}</strong>
                                @else
                                    <strong class="text-success">₹0</strong>
                                @endif
                            </td>
                            <td>
                                @php
                                    $percent = $member['payment_percentage'];
                                @endphp
                                <div class="d-flex flex-column">
                                    <div class="progress mb-1" style="height: 6px; width: 120px;">
                                        <div class="progress-bar
                                            @if($percent >= 100) bg-success
                                            @elseif($percent >= 50) bg-warning
                                            @else bg-danger
                                            @endif"
                                            style="width: {{ $percent }}%">
                                        </div>
                                    </div>
                                    <small class="text-muted">
                                        @if($percent >= 100)
                                            <span class="text-success"><i class="bi bi-check-circle"></i> Fully Paid</span>
                                        @elseif($percent >= 50)
                                            <span class="text-warning"><i class="bi bi-exclamation-triangle"></i> Partial ({{ $percent }}%)</span>
                                        @elseif($percent > 0)
                                            <span class="text-warning"><i class="bi bi-hourglass-split"></i> Partial ({{ $percent }}%)</span>
                                        @else
                                            <span class="text-danger"><i class="bi bi-x-circle"></i> Unpaid</span>
                                        @endif
                                    </small>
                                </div>
                            </tr>
                            <td>
                                @if($member['pending_amount'] > 0)
                                    <button class="btn btn-sm btn-primary record-payment"
                                            data-id="{{ $member['id'] }}"
                                            data-name="{{ $member['name'] }}"
                                            data-rent="{{ $member['monthly_rent'] }}"
                                            data-pending="{{ $member['pending_amount'] }}">
                                        <i class="bi bi-cash-stack"></i> Pay ₹{{ number_format($member['pending_amount']) }}
                                    </button>
                                @else
                                    <button class="btn btn-sm btn-success" disabled>
                                        <i class="bi bi-check-circle"></i> Paid
                                    </button>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center py-5">
                                <i class="bi bi-check-circle-fill text-success fs-1"></i>
                                <h5 class="mt-3 mb-0">No Members Found! 🎉</h5>
                                <p class="text-muted">All members are up to date</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Recent Activity Row -->
<div class="row g-4 mb-4">
    <div class="col-xl-6">
        <div class="admin-card">
            <div class="card-header-custom d-flex justify-content-between align-items-center">
                <span><i class="bi bi-person-plus me-2"></i> Recent Resident Registrations</span>
                <a href="#" class="text-decoration-none small">View All <i class="bi bi-arrow-right"></i></a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Member</th>
                            <th>Hostel</th>
                            <th>Room</th>
                            <th>Joined Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentResidents as $resident)
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <i class="bi bi-person-circle fs-4 text-secondary"></i>
                                    {{ $resident->name }}
                                </div>
                            </td>
                            <td>{{ $resident->hostel->name ?? 'N/A' }}</td>
                            <td>{{ $resident->room->room_number ?? 'N/A' }}</td>
                            <td>{{ $resident->created_at->format('d M Y') }}</td>
                            <td><span class="status-pill status-active">Active</span></td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center py-4 text-muted">
                                <i class="bi bi-inbox fs-1"></i>
                                <p class="mt-2">No residents found</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-xl-6">
        <div class="admin-card">
            <div class="card-header-custom">
                <i class="bi bi-clock-history me-2"></i> Recent Payment Activity
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Member</th>
                            <th>Amount</th>
                            <th>Month</th>
                            <th>Payment Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentPayments as $payment)
                        <tr>
                            <td>{{ $payment->member->name ?? 'Unknown' }}</td>
                            <td class="text-success fw-bold">+ ₹{{ number_format($payment->amount) }}</td>
                            <td><span class="badge bg-info">{{ $payment->month }}</span></td>
                            <td>{{ $payment->paid_date ? \Carbon\Carbon::parse($payment->paid_date)->format('d M Y') : $payment->created_at->format('d M Y') }}</td>
                            <td>
                                @if($payment->status == 'paid')
                                    <span class="badge bg-success">Full</span>
                                @else
                                    <span class="badge bg-warning">Partial</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center py-4 text-muted">
                                <i class="bi bi-credit-card fs-1"></i>
                                <p class="mt-2">No recent payments</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Charts Row -->
<div class="row g-4">
    <div class="col-xl-8">
        <div class="admin-card">
            <div class="card-header-custom">
                <i class="bi bi-graph-up me-2"></i> Monthly Income Trend - {{ date('Y') }}
            </div>
            <div class="p-4">
                <canvas id="incomeChart" height="120"></canvas>
            </div>
        </div>
    </div>

    <div class="col-xl-4">
        <div class="admin-card">
            <div class="card-header-custom">
                <i class="bi bi-pie-chart me-2"></i> Hostel Distribution
            </div>
            <div class="p-4">
                <canvas id="hostelChart" height="200"></canvas>
            </div>
        </div>
    </div>
</div>



<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
// Initialize Charts
document.addEventListener('DOMContentLoaded', function() {
    // Income Chart
    const incomeCtx = document.getElementById('incomeChart')?.getContext('2d');
    if(incomeCtx) {
        new Chart(incomeCtx, {
            type: 'line',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
                datasets: [{
                    label: 'Monthly Income (₹)',
                    data: @json($monthlyTrend),
                    borderColor: '#3b82f6',
                    backgroundColor: 'rgba(59,130,246,0.05)',
                    tension: 0.3,
                    fill: true,
                    pointBackgroundColor: '#3b82f6',
                    pointBorderColor: 'white',
                    pointRadius: 5,
                    pointHoverRadius: 7,
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: { position: 'top', labels: { font: { size: 11 } } },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return '₹ ' + context.raw.toLocaleString('en-IN');
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '₹' + value.toLocaleString('en-IN');
                            }
                        }
                    }
                }
            }
        });
    }

    // Hostel Distribution Chart
    const hostelCtx = document.getElementById('hostelChart')?.getContext('2d');
    if(hostelCtx && @json($hostelDistribution) && {{ count($hostelDistribution) > 0 }}) {
        const hostelData = @json($hostelDistribution);
        const labels = hostelData.map(h => h.name);
        const data = hostelData.map(h => h.total_rooms);
        const colors = ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#ec489a', '#06b6d4', '#84cc16'];

        new Chart(hostelCtx, {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    data: data,
                    backgroundColor: colors.slice(0, data.length),
                    borderWidth: 0,
                    hoverOffset: 15
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            font: { size: 10 },
                            boxWidth: 10
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const total = data.reduce((a, b) => a + b, 0);
                                const percentage = Math.round((context.raw / total) * 100);
                                return `${context.label}: ${context.raw} rooms (${percentage}%)`;
                            }
                        }
                    }
                },
                cutout: '60%'
            }
        });
    }
});

// Record Payment Function


// Quick Actions
$('.quick-action').on('click', function() {
    const action = $(this).data('action');
    const messages = {
        resident: 'Opening Add Resident Form',
        payment: 'Opening Payment Recording Form',
        room: 'Opening Room Assignment Portal',
        report: 'Generating Report...'
    };
    showToast(messages[action] || 'Action triggered', 'info');
});

// Refresh Data
$('#refreshData').on('click', function() {
    showLoader();
    setTimeout(() => location.reload(), 500);
});

// Export Report
$('#exportReport').on('click', function() {
    showLoader();
    setTimeout(() => {
        hideLoader();
        showToast('Report exported successfully!', 'success');
    }, 1000);
});

// Toast and Loader Functions
window.showToast = function(message, type = 'success') {
    const bgClass = type === 'success' ? 'bg-success' : (type === 'error' ? 'bg-danger' : 'bg-info');
    const toast = $(`
        <div class="toast align-items-center text-white border-0 ${bgClass}" role="alert" style="position: fixed; top: 20px; right: 20px; z-index: 9999; min-width: 300px;">
            <div class="d-flex">
                <div class="toast-body">${message}</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>
    `);
    $('body').append(toast);
    const bsToast = new bootstrap.Toast(toast[0], { autohide: true, delay: 3000 });
    bsToast.show();
    toast.on('hidden.bs.toast', () => toast.remove());
};

window.showLoader = function() {
    $('#globalLoader').fadeIn(200);
};

window.hideLoader = function() {
    $('#globalLoader').fadeOut(200);
};
</script>

<style>
.stat-card {
    background: white;
    border-radius: 20px;
    padding: 1.25rem;
    transition: all 0.3s ease;
    cursor: pointer;
    border: 1px solid #eef2ff;
}
.stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
}
.admin-card {
    background: white;
    border-radius: 20px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.04);
    overflow: hidden;
    border: 1px solid #eef2ff;
}
.card-header-custom {
    padding: 1.25rem 1.5rem;
    background: white;
    border-bottom: 2px solid #f0f2f5;
    font-weight: 600;
}
.progress {
    border-radius: 10px;
    background-color: #e9ecef;
}
.table th {
    font-weight: 600;
    color: #495057;
    border-bottom: 2px solid #dee2e6;
}
.table td {
    vertical-align: middle;
}
.status-pill {
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
}
.status-active {
    background: #d1fae5;
    color: #065f46;
}
.quick-action {
    transition: all 0.2s ease;
}
.quick-action:hover {
    transform: translateY(-3px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}
.btn-primary {
    background: #3b82f6;
    border: none;
}
.btn-primary:hover {
    background: #2563eb;
}
@media (max-width: 768px) {
    .stat-card h3 {
        font-size: 1.3rem;
    }
    .card-header-custom {
        padding: 1rem;
    }
    .table th, .table td {
        font-size: 0.75rem;
        padding: 0.5rem;
    }
}
</style>
@endsection
