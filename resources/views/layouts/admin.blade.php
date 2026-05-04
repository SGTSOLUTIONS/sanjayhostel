{{-- resources/views/layouts/admin.blade.php --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes, viewport-fit=cover">
    <meta name="theme-color" content="#0f2b4d">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <title>@yield('title', 'Admin Dashboard') | Governance Portal</title>

    <!-- Bootstrap 5 + Icons + Fonts -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: #f0f2f6;
            overflow-x: hidden;
        }

        /* SIDEBAR STYLES */
        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            height: 100%;
            width: 280px;
            background: linear-gradient(180deg, #0f2b4d 0%, #0a1f38 100%);
            color: white;
            z-index: 1050;
            transition: all 0.3s ease;
            box-shadow: 4px 0 20px rgba(0,0,0,0.08);
            overflow-y: auto;
            overflow-x: hidden;
        }

        /* Custom Scrollbar */
        .sidebar::-webkit-scrollbar {
            width: 5px;
        }
        .sidebar::-webkit-scrollbar-track {
            background: rgba(255,255,255,0.1);
        }
        .sidebar::-webkit-scrollbar-thumb {
            background: rgba(255,255,255,0.3);
            border-radius: 10px;
        }

        .sidebar-header {
            border-bottom: 1px solid rgba(255,255,255,0.1);
            margin-bottom: 1rem;
            text-align: center;
        }

        .sidebar-logo {
            width: 200px;
            height: 100px;
            margin: 0 auto 0.75rem auto;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .sidebar-logo img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
            border-radius: 12px;
        }

        .sidebar-header h4 {
            font-weight: 700;
            font-size: 1.3rem;
            margin: 0;
            letter-spacing: -0.5px;
        }

        .sidebar-header p {
            font-size: 0.7rem;
            opacity: 0.7;
            margin: 0;
        }

        .nav-menu {
            padding: 0 1rem 1rem 1rem;
        }

        .nav-section-label {
            padding: 0.5rem 0.75rem;
            margin-top: 0.5rem;
        }

        .nav-section-label small {
            font-size: 0.65rem;
            letter-spacing: 0.5px;
            font-weight: 600;
        }

        .nav-item-custom {
            margin-bottom: 0.25rem;
        }

        .nav-link-custom {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 12px;
            color: rgba(255,255,255,0.75);
            text-decoration: none;
            border-radius: 10px;
            transition: all 0.2s;
            font-weight: 500;
            font-size: 0.9rem;
        }

        .nav-link-custom i {
            font-size: 1.2rem;
            width: 24px;
        }

        .nav-link-custom:hover {
            background: rgba(255,255,255,0.1);
            color: white;
            transform: translateX(5px);
        }

        .nav-link-custom.active {
            background: linear-gradient(135deg, #2563eb, #1e4a76);
            color: white;
            box-shadow: 0 4px 12px rgba(37,99,235,0.3);
        }

        /* MAIN CONTENT */
        .main-content {
            margin-left: 280px;
            min-height: 100vh;
            transition: all 0.3s ease;
        }

        /* TOP NAVBAR */
        .top-navbar {
            background: white;
            padding: 0.75rem 1.5rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
            position: sticky;
            top: 0;
            z-index: 1040;
        }

        /* Top Navbar Logo for Mobile */
        .mobile-logo {
            display: none;
            width: 32px;
            height: 32px;
        }

        .mobile-logo img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            border-radius: 8px;
        }

        /* Cards */
        .stat-card {
            background: white;
            border-radius: 20px;
            padding: 1.25rem;
            border: none;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
            transition: all 0.2s;
            cursor: pointer;
        }

        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.08);
        }

        .admin-card {
            background: white;
            border-radius: 20px;
            border: none;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
            margin-bottom: 1.5rem;
            overflow: hidden;
        }

        .card-header-custom {
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid #eef2ff;
            font-weight: 700;
            background: white;
        }

        /* Mobile Toggle Button */
        .sidebar-toggle {
            display: none;
            background: transparent;
            border: none;
            font-size: 1.5rem;
            color: #1e293b;
            cursor: pointer;
            padding: 0.5rem;
            border-radius: 10px;
            transition: background 0.2s;
        }

        .sidebar-toggle:hover {
            background: #f0f2f6;
        }

        /* Overlay for mobile */
        .sidebar-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            z-index: 1045;
        }

        /* Responsive */
        @media (max-width: 992px) {
            .sidebar {
                transform: translateX(-100%);
                width: 280px;
            }
            .sidebar.active {
                transform: translateX(0);
            }
            .main-content {
                margin-left: 0;
            }
            .sidebar-toggle {
                display: block;
            }
            .sidebar-overlay.active {
                display: block;
            }
            .mobile-logo {
                display: block;
            }
        }

        /* Small devices */
        @media (max-width: 576px) {
            .top-navbar {
                padding: 0.75rem 1rem;
            }
            .main-content .p-4 {
                padding: 1rem !important;
            }
            .stat-card {
                margin-bottom: 1rem;
            }
        }

        /* Status Badges */
        .status-pill {
            padding: 4px 12px;
            border-radius: 30px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        .status-active { background: #dcfce7; color: #15803d; }
        .status-pending { background: #fff3e3; color: #b45309; }
        .status-inactive { background: #fee2e2; color: #b91c1c; }
        .status-success { background: #d1fae5; color: #065f46; }
        .status-warning { background: #fed7aa; color: #92400e; }

        /* Progress Bar */
        .progress-custom {
            height: 8px;
            background: #e2e8f0;
            border-radius: 10px;
            overflow: hidden;
        }

        /* Table Responsive */
        .table-responsive-custom {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        /* Toast & Loader */
        .toast-custom {
            background: #1e293b;
            color: white;
            border-radius: 12px;
            animation: slideInRight 0.3s ease;
        }

        @keyframes slideInRight {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        .loader-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            z-index: 9999;
            display: none;
            align-items: center;
            justify-content: center;
        }

        /* Utility Classes */
        .cursor-pointer {
            cursor: pointer;
        }

        .text-truncate-custom {
            max-width: 200px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        @media (max-width: 768px) {
            .text-truncate-custom {
                max-width: 150px;
            }
        }
    </style>
    @yield('styles')
</head>
<body>

    <!-- Sidebar Overlay -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <!-- SIDEBAR -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <div class="sidebar-logo">
                <img src="{{ asset('logo.png') }}" alt="Logo">
            </div>

        </div>
        <div class="nav-menu">
            <!-- Dashboard -->
            <div class="nav-item-custom">
                <a href="{{ route('admin.dashboard') }}" class="nav-link-custom @if(request()->routeIs('admin.dashboard')) active @endif">
                    <i class="bi bi-grid-1x2-fill"></i>
                    <span>Dashboard</span>
                </a>
            </div>

            <!-- PROPERTY MANAGEMENT SECTION -->
            <div class="nav-section-label">
                <small class="text-white-50 text-uppercase">Property Management</small>
            </div>

            <div class="nav-item-custom">
                <a href="{{ route('pgs.index') }}" class="nav-link-custom">
                    <i class="bi bi-building"></i>
                    <span>Hostels</span>
                </a>
            </div>
            <div class="nav-item-custom">
                <a href="{{ route('room-types.index') }}" class="nav-link-custom">
                    <i class="bi bi-layout-text-window"></i>
                    <span>Room Types</span>
                </a>
            </div>
            <div class="nav-item-custom">
                <a href="{{ route('rooms.index') }}" class="nav-link-custom">
                    <i class="bi bi-door-closed"></i>
                    <span>Rooms</span>
                </a>
            </div>
            <div class="nav-item-custom">
                <a href="{{ route('beds.index') }}" class="nav-link-custom">
                    <i class="bi bi-bed"></i>
                    <span>Beds</span>
                </a>
            </div>

            <!-- PEOPLE MANAGEMENT SECTION -->
            <div class="nav-section-label">
                <small class="text-white-50 text-uppercase">People Management</small>
            </div>

            <div class="nav-item-custom">
                <a href="{{ route('admin.members.index') }}" class="nav-link-custom">
                    <i class="bi bi-people"></i>
                    <span>Residents</span>
                </a>
            </div>
            <div class="nav-item-custom">
                <a href="{{ route('admin.users.index') }}" class="nav-link-custom">
                    <i class="bi bi-person-badge"></i>
                    <span>Users</span>
                </a>
            </div>
            <div class="nav-item-custom">
                <a href="{{ route('admin.staff.index') }}" class="nav-link-custom">
                    <i class="bi bi-person-workspace"></i>
                    <span>Staff</span>
                </a>
            </div>

            <!-- FINANCIAL MANAGEMENT SECTION -->
            <div class="nav-section-label">
                <small class="text-white-50 text-uppercase">Financial Management</small>
            </div>

            <div class="nav-item-custom">
                <a href="{{ route('admin.payments.index') }}" class="nav-link-custom">
                    <i class="bi bi-currency-dollar"></i>
                    <span>Payments</span>
                </a>
            </div>
            <div class="nav-item-custom">
                <a href="{{ route('admin.expenses.index') }}" class="nav-link-custom">
                    <i class="bi bi-receipt"></i>
                    <span>Expenses</span>
                </a>
            </div>
            <div class="nav-item-custom">
                <a href="{{ route('admin.reports.financial') }}" class="nav-link-custom">
                    <i class="bi bi-graph-up"></i>
                    <span>Financial Reports</span>
                </a>
            </div>

            <!-- SYSTEM SECTION -->
            <div class="nav-section-label">
                <small class="text-white-50 text-uppercase">System</small>
            </div>

            <div class="nav-item-custom">
                <a href="#" class="nav-link-custom">
                    <i class="bi bi-bell"></i>
                    <span>Notifications</span>
                </a>
            </div>
            <div class="nav-item-custom">
                <a href="#" class="nav-link-custom">
                    <i class="bi bi-gear"></i>
                    <span>Settings</span>
                </a>
            </div>

            <!-- Logout at bottom -->
            <div class="nav-item-custom mt-4 pt-3 border-top border-light">
                <a href="{{ route('logout') }}" class="nav-link-custom" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                    <i class="bi bi-box-arrow-right"></i>
                    <span>Logout</span>
                </a>
                <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                    @csrf
                </form>
            </div>
        </div>
    </div>

    <!-- MAIN CONTENT -->
    <div class="main-content">
        <!-- Top Navbar -->
        <div class="top-navbar">
            <div class="d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-3">
                    <button class="sidebar-toggle" id="sidebarToggle">
                        <i class="bi bi-list"></i>
                    </button>
                    <div class="mobile-logo">
                        <img src="{{ asset('logo.png') }}" alt="Logo">
                    </div>
                    <h5 class="mb-0 fw-semibold">@yield('page-title', 'Dashboard')</h5>
                </div>
                <div class="d-flex align-items-center gap-2 gap-sm-3">
                    <!-- Notification Dropdown -->
                    <div class="dropdown">
                        <button class="btn btn-light rounded-circle p-2 position-relative" data-bs-toggle="dropdown">
                            <i class="bi bi-bell"></i>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">3</span>
                        </button>
                        <div class="dropdown-menu dropdown-menu-end p-0" style="width: 300px;">
                            <div class="p-3 border-bottom">
                                <h6 class="mb-0">Notifications</h6>
                            </div>
                            <div class="list-group list-group-flush">
                                <a href="#" class="list-group-item list-group-item-action">
                                    <small class="text-muted">5 min ago</small>
                                    <div>New payment received</div>
                                </a>
                                <a href="#" class="list-group-item list-group-item-action">
                                    <small class="text-muted">1 hour ago</small>
                                    <div>Room booking request</div>
                                </a>
                                <a href="#" class="list-group-item list-group-item-action">
                                    <small class="text-muted">2 hours ago</small>
                                    <div>Maintenance reported</div>
                                </a>
                            </div>
                            <div class="p-2 text-center">
                                <a href="#" class="text-decoration-none small">View all notifications</a>
                            </div>
                        </div>
                    </div>

                    <!-- User Dropdown -->
                    <div class="dropdown">
                        <button class="btn btn-light rounded-pill px-2 px-sm-3 d-flex align-items-center gap-2" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle fs-5"></i>
                            <span class="d-none d-sm-inline">{{ auth()->user()->name ?? 'Admin' }}</span>
                            <i class="bi bi-chevron-down small"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="#"><i class="bi bi-person me-2"></i>Profile</a></li>
                            <li><a class="dropdown-item" href="#"><i class="bi bi-gear me-2"></i>Settings</a></li>
                            <li><a class="dropdown-item" href="#"><i class="bi bi-shield-lock me-2"></i>Security</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="#" onclick="event.preventDefault(); document.getElementById('logout-form-mobile').submit();"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Page Content -->
        <div class="p-3 p-sm-4">
            @yield('content')
        </div>
    </div>

    <!-- Loader -->
    <div class="loader-overlay" id="loaderOverlay">
        <div class="bg-white rounded-4 p-4 d-flex flex-column align-items-center shadow-lg">
            <div class="spinner-border text-primary mb-3" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <span>Loading...</span>
        </div>
    </div>

    <!-- Toast Container -->
    <div class="position-fixed bottom-0 end-0 p-3" style="z-index: 1080; max-width: 90%;" id="toastContainer"></div>

    <script>
        // Sidebar Toggle for Mobile
        $(document).ready(function() {
            $('#sidebarToggle, #sidebarOverlay').on('click', function() {
                $('#sidebar').toggleClass('active');
                $('#sidebarOverlay').toggleClass('active');
            });

            // Close sidebar when clicking on a link (mobile)
            $('.nav-link-custom').on('click', function(e) {
                if(window.innerWidth <= 992) {
                    // Don't close if it's the logout form trigger
                    if(!$(this).hasClass('logout-trigger')) {
                        setTimeout(function() {
                            $('#sidebar').removeClass('active');
                            $('#sidebarOverlay').removeClass('active');
                        }, 150);
                    }
                }
            });

            // Handle window resize
            $(window).on('resize', function() {
                if(window.innerWidth > 992) {
                    $('#sidebar').removeClass('active');
                    $('#sidebarOverlay').removeClass('active');
                }
            });
        });

        // Global Toast Notification
        window.showToast = function(message, type = 'success') {
            const bgMap = {
                success: '#10b981',
                error: '#ef4444',
                danger: '#ef4444',
                info: '#3b82f6',
                warning: '#f59e0b'
            };
            const iconMap = {
                success: 'bi-check-circle-fill',
                error: 'bi-exclamation-triangle-fill',
                danger: 'bi-exclamation-triangle-fill',
                info: 'bi-info-circle-fill',
                warning: 'bi-exclamation-triangle-fill'
            };

            const toastId = 'toast_' + Date.now();
            const toast = $(`
                <div id="${toastId}" class="toast-custom shadow rounded-3 p-3 mb-2 d-flex align-items-center justify-content-between" style="background: ${bgMap[type]}; min-width: 260px; max-width: 350px;">
                    <div class="d-flex gap-2 align-items-center">
                        <i class="bi ${iconMap[type]} text-white fs-5"></i>
                        <span class="text-white">${message}</span>
                    </div>
                    <i class="bi bi-x-lg text-white" style="cursor:pointer; font-size: 1.2rem;"></i>
                </div>
            `);

            $('#toastContainer').append(toast);

            toast.find('.bi-x-lg').click(function() {
                toast.fadeOut(300, function() { $(this).remove(); });
            });

            setTimeout(function() {
                toast.fadeOut(300, function() { $(this).remove(); });
            }, 4000);
        };

        // Global Loader
        window.showLoader = function() {
            $('#loaderOverlay').fadeIn(200).css('display', 'flex');
        };

        window.hideLoader = function() {
            $('#loaderOverlay').fadeOut(200);
        };

        // Confirm Dialog Helper
        window.confirmAction = function(message, callback) {
            if(confirm(message)) {
                callback();
            }
        };

        // Format Currency
        window.formatCurrency = function(amount) {
            return new Intl.NumberFormat('en-IN', {
                style: 'currency',
                currency: 'INR',
                minimumFractionDigits: 2
            }).format(amount);
        };

        // Format Date
        window.formatDate = function(dateString) {
            return new Date(dateString).toLocaleDateString('en-IN', {
                year: 'numeric',
                month: 'short',
                day: 'numeric'
            });
        };
    </script>

    @yield('scripts')
</body>
</html>
