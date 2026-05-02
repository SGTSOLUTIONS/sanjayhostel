{{-- resources/views/layouts/app.blade.php --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes, viewport-fit=cover">
    <meta name="theme-color" content="#1e3a8a">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <title>@yield('title', 'GovConnect') - Government Services</title>

    {{-- Bootstrap 5 Mobile Optimized --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    {{-- Google Fonts --}}
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <style>
        :root {
            --gov-primary: #1e3a8a;
            --gov-secondary: #3b82f6;
            --gov-success: #10b981;
            --gov-warning: #f59e0b;
            --gov-danger: #ef4444;
            --gov-dark: #1f2937;
            --gov-light: #f3f4f6;
            --border-radius: 12px;
            --card-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            -webkit-tap-highlight-color: transparent;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: #f8fafc;
            overflow-x: hidden;
            padding-bottom: env(safe-area-inset-bottom);
        }

        /* Mobile Navigation Bar */
        .mobile-nav {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: rgba(255,255,255,0.95);
            backdrop-filter: blur(10px);
            border-top: 1px solid #e5e7eb;
            padding: 8px 20px 20px;
            z-index: 1000;
            box-shadow: 0 -2px 10px rgba(0,0,0,0.05);
        }

        .nav-items {
            display: flex;
            justify-content: space-around;
            align-items: center;
        }

        .nav-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-decoration: none;
            color: #6b7280;
            transition: all 0.3s;
            font-size: 12px;
            gap: 4px;
        }

        .nav-item i, .nav-item svg {
            font-size: 24px;
        }

        .nav-item.active {
            color: var(--gov-primary);
        }

        .nav-item span {
            font-size: 11px;
            font-weight: 500;
        }

        /* Main Content Area */
        .main-content {
            padding-bottom: 80px;
        }

        /* Header Styles */
        .app-header {
            background: linear-gradient(135deg, var(--gov-primary), var(--gov-secondary));
            color: white;
            padding: 20px;
            border-radius: 0 0 24px 24px;
            margin-bottom: 20px;
        }

        /* Card Styles */
        .gov-card {
            background: white;
            border-radius: var(--border-radius);
            border: none;
            box-shadow: var(--card-shadow);
            transition: transform 0.2s, box-shadow 0.2s;
            margin-bottom: 16px;
        }

        .gov-card:active {
            transform: scale(0.98);
        }

        /* Form Controls */
        .form-control, .form-select {
            border-radius: 10px;
            border: 1.5px solid #e5e7eb;
            padding: 12px 16px;
            font-size: 16px;
            transition: all 0.3s;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--gov-primary);
            box-shadow: 0 0 0 3px rgba(30,58,138,0.1);
        }

        /* Buttons */
        .btn-gov {
            background: linear-gradient(135deg, var(--gov-primary), var(--gov-secondary));
            border: none;
            padding: 14px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 16px;
            transition: all 0.3s;
        }

        .btn-gov:active {
            transform: scale(0.97);
        }

        /* Status Badges */
        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
        }

        .status-submitted { background: #e0e7ff; color: #3730a3; }
        .status-review { background: #fef3c7; color: #92400e; }
        .status-approved { background: #d1fae5; color: #065f46; }
        .status-rejected { background: #fee2e2; color: #991b1b; }

        /* Toast Notifications */
        .toast-container {
            position: fixed;
            top: 60px;
            left: 16px;
            right: 16px;
            z-index: 9999;
        }

        .toast-custom {
            background: white;
            border-radius: 12px;
            padding: 14px 16px;
            margin-bottom: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            border-left: 4px solid;
            animation: slideIn 0.3s ease;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        @keyframes slideIn {
            from {
                transform: translateY(-100px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        /* Loading Overlay */
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.7);
            backdrop-filter: blur(4px);
            z-index: 99999;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            gap: 16px;
        }

        .loading-spinner {
            width: 48px;
            height: 48px;
            border: 4px solid rgba(255,255,255,0.3);
            border-top-color: white;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* File Upload */
        .upload-area {
            border: 2px dashed #cbd5e1;
            border-radius: 12px;
            padding: 24px;
            text-align: center;
            background: #f8fafc;
            cursor: pointer;
            transition: all 0.3s;
        }

        .upload-area.active {
            border-color: var(--gov-primary);
            background: #eff6ff;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .container {
                padding-left: 16px;
                padding-right: 16px;
            }

            h1 { font-size: 24px; }
            h2 { font-size: 20px; }
            h3 { font-size: 18px; }
        }
    </style>

    @yield('styles')
</head>
<body>

    <div id="loadingOverlay" class="loading-overlay" style="display: none;">
        <div class="loading-spinner"></div>
        <div style="color: white; font-weight: 500;">Loading...</div>
    </div>

    <div id="toastContainer" class="toast-container"></div>

    <div class="main-content">
        @yield('content')
    </div>

    @if(Auth::check())
    <nav class="mobile-nav">
        <div class="nav-items">
            <a href="{{ route('admin.dashboard') }}" class="nav-item @if(request()->routeIs('dashboard')) active @endif">
                <i class="bi bi-house-door-fill"></i>
                <span>Home</span>
            </a>
            <a href="#" class="nav-item @if(request()->routeIs('applications*')) active @endif">
                <i class="bi bi-files"></i>
                <span>Applications</span>
            </a>
            <a href="#" class="nav-item @if(request()->routeIs('properties*')) active @endif">
                <i class="bi bi-building"></i>
                <span>Properties</span>
            </a>
            <a href="#" class="nav-item @if(request()->routeIs('notifications')) active @endif">
                <i class="bi bi-bell"></i>
                <span>Alerts</span>
            </a>
            <a href="#" class="nav-item @if(request()->routeIs('profile')) active @endif">
                <i class="bi bi-person"></i>
                <span>Profile</span>
            </a>
        </div>
    </nav>
    @endif


    <script>
        // Global Toast Function
        window.showToast = function(type, message, title = '') {
            const container = $('#toastContainer');
            const icons = {
                success: 'bi-check-circle-fill',
                error: 'bi-x-circle-fill',
                warning: 'bi-exclamation-triangle-fill',
                info: 'bi-info-circle-fill'
            };

            const colors = {
                success: '#10b981',
                error: '#ef4444',
                warning: '#f59e0b',
                info: '#3b82f6'
            };

            const toast = $(`
                <div class="toast-custom" style="border-left-color: ${colors[type]}">
                    <i class="bi ${icons[type]}" style="color: ${colors[type]}; font-size: 24px;"></i>
                    <div style="flex: 1;">
                        ${title ? `<div style="font-weight: 600; margin-bottom: 4px;">${title}</div>` : ''}
                        <div style="font-size: 14px; color: #4b5563;">${message}</div>
                    </div>
                    <i class="bi bi-x-lg" style="cursor: pointer; font-size: 18px; color: #9ca3af;"></i>
                </div>
            `);

            container.append(toast);

            toast.find('.bi-x-lg').click(function() {
                toast.remove();
            });

            setTimeout(() => {
                toast.fadeOut(300, function() { $(this).remove(); });
            }, 5000);
        };

        // Global Loading Functions
        window.showLoading = function() {
            $('#loadingOverlay').fadeIn(200);
        };

        window.hideLoading = function() {
            $('#loadingOverlay').fadeOut(200);
        };

        // Handle AJAX globally
        $(document).ajaxStart(function() {
            // showLoading();
        }).ajaxStop(function() {
            // hideLoading();
        });
    </script>

    @yield('scripts')
</body>
</html>
