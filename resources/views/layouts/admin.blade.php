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
        }

        .sidebar-header {
            padding: 1.5rem 1.25rem;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            margin-bottom: 1.5rem;
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
            padding: 0 1rem;
        }

        .nav-item-custom {
            margin-bottom: 0.5rem;
        }

        .nav-link-custom {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            color: rgba(255,255,255,0.75);
            text-decoration: none;
            border-radius: 12px;
            transition: all 0.2s;
            font-weight: 500;
            font-size: 0.9rem;
        }

        .nav-link-custom i {
            font-size: 1.3rem;
            width: 24px;
        }

        .nav-link-custom:hover {
            background: rgba(255,255,255,0.1);
            color: white;
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
            padding: 1rem 2rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
            position: sticky;
            top: 0;
            z-index: 1040;
        }

        /* Cards */
        .stat-card {
            background: white;
            border-radius: 20px;
            padding: 1.25rem;
            border: none;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
            transition: all 0.2s;
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

        /* Progress Bar */
        .progress-custom {
            height: 8px;
            background: #e2e8f0;
            border-radius: 10px;
            overflow: hidden;
        }

        /* Toast & Loader */
        .toast-custom {
            background: #1e293b;
            color: white;
            border-radius: 12px;
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
    </style>
    @yield('styles')
</head>
<body>

    <!-- Sidebar Overlay -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <!-- SIDEBAR -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <h4><i class="bi bi-shield-shaded me-2"></i>GovConnect</h4>
            <p>Administration Portal</p>
        </div>
        <div class="nav-menu">
            <div class="nav-item-custom">
                <a href="{{ route('admin.dashboard') }}" class="nav-link-custom @if(request()->routeIs('admin.dashboard')) active @endif">
                    <i class="bi bi-grid-1x2-fill"></i>
                    <span>Dashboard</span>
                </a>
            </div>
            <div class="nav-item-custom">
                <a href="{{ route('pgs.index')}}" class="nav-link-custom">
                    <i class="bi bi-building"></i>
                    <span>Hostels</span>
                </a>
            </div>
              <div class="nav-item-custom">
                <a href="{{ route('room-types.index')}}" class="nav-link-custom">
                    <i class="bi bi-door-closed"></i>
                    <span>Roomtype</span>
                </a>
            </div>

            <div class="nav-item-custom">
                <a href="{{route('rooms.index')}}" class="nav-link-custom">
                    <i class="bi bi-door-closed"></i>
                    <span>Rooms</span>
                </a>
            </div>
            <div class="nav-item-custom">
                <a href="{{ route('beds.index')}}" class="nav-link-custom">
                    <i class="bi bi-bed"></i>
                    <span>Beds</span>
                </a>
            </div>
            <div class="nav-item-custom">
                <a href="{{route('admin.members.index')}}" class="nav-link-custom">
                    <i class="bi bi-people"></i>
                    <span>Residents</span>
                </a>
            </div>
            <div class="nav-item-custom">
                <a href="{{route('admin.payments.index')}}" class="nav-link-custom">
                    <i class="bi bi-calculator"></i>
                    <span>Payments</span>
                </a>
            </div>
             <div class="nav-item-custom">
                <a href="{{route('admin.users.index')}}" class="nav-link-custom">
                    <i class="bi bi-user"></i>
                    <span>Users</span>
                </a>
            </div>
            <div class="nav-item-custom">
                <a href="{{route('admin.expenses.index')}}" class="nav-link-custom">
                    <i class="bi bi-bell"></i>
                    <span>Expenses</span>
                </a>
            </div>
            <div class="nav-item-custom">
                <a href="{{route('admin.staff.index')}}" class="nav-link-custom">
                    <i class="bi bi-bell"></i>
                    <span>Expenses</span>
                </a>
            </div>
            <div class="nav-item-custom">
                <a href="{{route('admin.reports.financial')}}" class="nav-link-custom">
                    <i class="bi bi-bell"></i>
                    <span>Financial</span>
                </a>
            </div>

            <div class="nav-item-custom">
                <a href="#" class="nav-link-custom">
                    <i class="bi bi-bell"></i>
                    <span>Notifications</span>
                </a>
            </div>
            <div class="nav-item-custom mt-4">
                <a href="#" class="nav-link-custom">
                    <i class="bi bi-gear"></i>
                    <span>Settings</span>
                </a>
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
                    <h5 class="mb-0 fw-semibold">@yield('page-title', 'Dashboard')</h5>
                </div>
                <div class="d-flex align-items-center gap-3">
                    <div class="dropdown">
                        <button class="btn btn-light rounded-circle p-2" data-bs-toggle="dropdown">
                            <i class="bi bi-bell"></i>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">3</span>
                        </button>
                    </div>
                    <div class="dropdown">
                        <button class="btn btn-light rounded-pill px-3 d-flex align-items-center gap-2" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle fs-5"></i>
                            <span>{{ auth()->user()->name ?? 'Admin' }}</span>
                            <i class="bi bi-chevron-down small"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="#"><i class="bi bi-person me-2"></i>Profile</a></li>
                            <li><a class="dropdown-item" href="#"><i class="bi bi-gear me-2"></i>Settings</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="#"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Page Content -->
        <div class="p-4">
            @yield('content')
        </div>
    </div>

    <!-- Loader -->
    <div class="loader-overlay" id="loaderOverlay">
        <div class="bg-white rounded-4 p-4 d-flex flex-column align-items-center shadow-lg">
            <div class="spinner-border text-primary mb-3"></div>
            <span>Loading...</span>
        </div>
    </div>

    <!-- Toast Container -->
    <div class="position-fixed bottom-0 end-0 p-3" style="z-index: 1080;" id="toastContainer"></div>

    <script>
        // Sidebar Toggle
        $('#sidebarToggle, #sidebarOverlay').on('click', function() {
            $('#sidebar').toggleClass('active');
            $('#sidebarOverlay').toggleClass('active');
        });

        // Global Toast
        window.showToast = function(message, type = 'success') {
            const bgMap = { success: '#10b981', error: '#ef4444', info: '#3b82f6', warning: '#f59e0b' };
            const iconMap = { success: 'bi-check-circle-fill', error: 'bi-exclamation-triangle-fill', info: 'bi-info-circle-fill', warning: 'bi-exclamation-triangle-fill' };
            const toast = $(`
                <div class="toast-custom shadow rounded-3 p-3 mb-2 d-flex align-items-center justify-content-between" style="background: ${bgMap[type]}; min-width: 260px;">
                    <div class="d-flex gap-2 align-items-center">
                        <i class="bi ${iconMap[type]} text-white"></i>
                        <span class="text-white">${message}</span>
                    </div>
                    <i class="bi bi-x-lg text-white" style="cursor:pointer"></i>
                </div>
            `);
            $('#toastContainer').append(toast);
            toast.find('.bi-x-lg').click(() => toast.remove());
            setTimeout(() => toast.fadeOut(300, () => toast.remove()), 4000);
        };

        window.showLoader = () => $('#loaderOverlay').fadeIn(200).css('display', 'flex');
        window.hideLoader = () => $('#loaderOverlay').fadeOut(200);

        // Close sidebar on link click (mobile)
        $('.nav-link-custom').on('click', function() {
            if(window.innerWidth <= 992) {
                $('#sidebar').removeClass('active');
                $('#sidebarOverlay').removeClass('active');
            }
        });
    </script>
    @yield('scripts')
</body>
</html>
