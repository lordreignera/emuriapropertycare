<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <title>{{ config('app.name', 'ETOGO') }} - @yield('title', 'Dashboard')</title>
    <link rel="icon" type="image/png" href="{{ asset('ETOGO%20log.png') }}">
    
    @include('admin.partials.styles')
    
    {{-- CRITICAL: Inline styles for maximum priority to override dark theme --}}
    <style>
        /* Force light table hover effects - inline styles have highest priority */
        .table tbody tr:hover,
        .table-hover tbody tr:hover,
        body.light-theme .table tbody tr:hover,
        body.light-theme .table-hover tbody tr:hover,
        body.light-theme .table tbody tr:hover td,
        body.light-theme .table-hover tbody tr:hover td {
            background-color: #f8fafc !important;
            color: #1a202c !important;
            transform: none !important;
            box-shadow: none !important;
            transition: background-color 0.15s ease !important;
        }
        
        /* Force text color on hover */
        .table tbody tr:hover td,
        .table tbody tr:hover th,
        .table-hover tbody tr:hover td,
        .table-hover tbody tr:hover th {
            color: #1a202c !important;
            background-color: transparent !important;
        }
        
        /* Override Bootstrap table hover variables */
        :root {
            --bs-table-hover-bg: #f1f5f9 !important;
            --bs-table-hover-color: #1a202c !important;
        }
        
        /* Form controls light theme - additional inline override */
        body.light-theme input[type="text"],
        body.light-theme input[type="email"],
        body.light-theme input[type="password"],
        body.light-theme input[type="number"],
        body.light-theme input[type="tel"],
        body.light-theme input[type="url"],
        body.light-theme select,
        body.light-theme textarea,
        body.light-theme .form-control,
        body.light-theme .form-select {
            background-color: #ffffff !important;
            color: #1a202c !important;
            border: 1px solid #d1d5db !important;
        }

        .admin-table-toolbar {
            display: flex;
            justify-content: flex-end;
            gap: 0.5rem;
            margin-bottom: 0.75rem;
        }

        .admin-table-shell {
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            background: #ffffff;
        }

        .admin-table-shell .table {
            margin-bottom: 0;
            width: 100%;
        }

        .admin-table-shell .table th,
        .admin-table-shell .table td {
            max-width: 260px;
            white-space: normal;
            overflow-wrap: anywhere;
            vertical-align: middle;
        }

        .admin-table-shell .table th:first-child,
        .admin-table-shell .table td:first-child,
        .admin-table-shell .table th:last-child,
        .admin-table-shell .table td:last-child {
            width: 1%;
            max-width: 120px;
            white-space: nowrap;
        }

        .admin-row-actions .dropdown-toggle::after {
            display: none;
        }

        .admin-row-actions .dropdown-toggle {
            width: 36px;
            height: 32px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-color: #cbd5e1 !important;
            background: #ffffff !important;
            color: #1f2937 !important;
        }

        .admin-row-actions .dropdown-toggle:hover,
        .admin-row-actions .dropdown-toggle:focus,
        .admin-row-actions .dropdown-toggle.show {
            border-color: #64748b !important;
            background: #f1f5f9 !important;
            color: #0f172a !important;
            box-shadow: 0 0 0 3px rgba(100, 116, 139, .16) !important;
        }

        .admin-row-actions .dropdown-toggle i {
            color: currentColor !important;
            font-size: 1.2rem;
            line-height: 1;
        }

        .admin-row-actions .dropdown-menu {
            min-width: 10rem;
            padding: 0.35rem;
            background: #ffffff !important;
            border: 1px solid #d7deea !important;
            border-radius: 8px !important;
            box-shadow: 0 18px 42px rgba(15, 23, 42, .18) !important;
            overflow: hidden;
        }

        .admin-row-actions .dropdown-item {
            border-radius: 6px;
            font-size: 0.875rem;
            width: 100%;
            text-align: left;
            color: #111827 !important;
            background: transparent !important;
            font-weight: 600;
            padding: 0.55rem 0.7rem !important;
            opacity: 1 !important;
        }

        .admin-row-actions .dropdown-item:hover,
        .admin-row-actions .dropdown-item:focus,
        .admin-row-actions .dropdown-item:active {
            color: #0f172a !important;
            background: #e8f0ff !important;
            text-decoration: none;
        }

        .admin-row-actions .dropdown-item.text-danger {
            color: #b91c1c !important;
        }

        .admin-row-actions .dropdown-item.text-danger:hover,
        .admin-row-actions .dropdown-item.text-danger:focus,
        .admin-row-actions .dropdown-item.text-danger:active {
            color: #991b1b !important;
            background: #fee2e2 !important;
        }

        .admin-row-actions form {
            margin: 0;
        }

        .admin-table-expanded {
            position: fixed !important;
            inset: 72px 24px 24px 24px;
            z-index: 1080;
            overflow: auto !important;
            padding: 1rem;
            box-shadow: 0 20px 50px rgba(15, 23, 42, 0.25);
        }

        .admin-table-expanded .table th,
        .admin-table-expanded .table td {
            max-width: none;
            white-space: nowrap;
        }

        body.admin-table-expanded-open {
            overflow: hidden;
        }

        @media (max-width: 767.98px) {
            .admin-table-shell .table th,
            .admin-table-shell .table td {
                max-width: 180px;
                font-size: 0.8125rem;
            }
        }
    </style>
</head>
<body class="light-theme">
    <div class="container-scroller">
        {{-- Sidebar --}}
        @include('admin.partials.sidebar')

        {{-- Sidebar backdrop overlay (mobile only) --}}
        <div class="sidebar-backdrop" id="sidebarBackdrop"></div>
        
        <div class="container-fluid page-body-wrapper">
            {{-- Navbar --}}
            @include('admin.partials.navbar')
            
            {{-- Main Content --}}
            <div class="main-panel">
                <div class="content-wrapper">
                    {{-- Page Header --}}
                    @if(isset($header) || View::hasSection('header'))
                    <div class="page-header">
                        <h3 class="page-title">
                            @yield('header', $header ?? '')
                        </h3>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                                @yield('breadcrumbs')
                            </ol>
                        </nav>
                    </div>
                    @endif

                    {{-- Flash Messages --}}
                    @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <strong>Success!</strong> {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    @endif

                    @if(session('error'))
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <strong>Error!</strong> {{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    @endif

                    @if(session('info'))
                    <div class="alert alert-info alert-dismissible fade show" role="alert">
                        <strong>Info!</strong> {{ session('info') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    @endif

                    @if(isset($errors) && $errors->any())
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <strong>Whoops!</strong> There were some problems with your input.
                        <ul class="mb-0">
                            @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    @endif

                    {{-- Page Content --}}
                    @yield('content')
                </div>
                
                {{-- Footer --}}
                @include('admin.partials.footer')
            </div>
        </div>
    </div>

    @include('admin.partials.scripts')

    {{-- Mobile Sidebar Toggle --}}
    <script>
        (function () {
            var MOBILE_BREAKPOINT = 992;
            var STORAGE_KEY = 'ETOGO.sidebar.minimized';

            function isMobile() {
                return window.innerWidth < MOBILE_BREAKPOINT;
            }

            function shouldMinimizeSidebar() {
                try {
                    return localStorage.getItem(STORAGE_KEY) === '1';
                } catch (error) {
                    return false;
                }
            }

            function closeSidebar() {
                document.body.classList.remove('sidebar-mobile-open');
            }

            function removeLegacySidebarState() {
                document.body.classList.remove('sidebar-icon-only', 'sidebar-hidden');
            }

            function updateSidebarToggles() {
                var minimized = document.body.classList.contains('sidebar-minimized');

                document.querySelectorAll('[data-sidebar-minimize], .navbar-toggler[data-toggle="minimize"]').forEach(function (button) {
                    button.setAttribute('aria-expanded', minimized ? 'false' : 'true');
                    button.setAttribute('title', minimized ? 'Expand sidebar' : 'Minimize sidebar');

                    if (button.matches('[data-sidebar-minimize]')) {
                        button.setAttribute('aria-label', minimized ? 'Expand sidebar' : 'Minimize sidebar');
                        button.innerHTML = minimized
                            ? '<i class="mdi mdi-chevron-right"></i><span class="admin-sidebar-collapse-label">Expand sidebar</span>'
                            : '<i class="mdi mdi-chevron-left"></i><span class="admin-sidebar-collapse-label">Minimize sidebar</span>';
                    }
                });
            }

            function setSidebarMinimized(minimized) {
                removeLegacySidebarState();
                document.body.classList.toggle('sidebar-minimized', minimized);

                try {
                    localStorage.setItem(STORAGE_KEY, minimized ? '1' : '0');
                } catch (error) {
                    // Ignore storage failures; the current page state still changes.
                }

                updateSidebarToggles();
            }

            function applyDesktopSidebarState() {
                removeLegacySidebarState();

                if (isMobile()) {
                    document.body.classList.remove('sidebar-minimized');
                    updateSidebarToggles();
                    return;
                }

                document.body.classList.toggle('sidebar-minimized', shouldMinimizeSidebar());
                updateSidebarToggles();
            }

            document.addEventListener('DOMContentLoaded', function () {
                var backdrop = document.getElementById('sidebarBackdrop');
                applyDesktopSidebarState();

                // Mobile-only right toggler (data-toggle="offcanvas")
                var mobileToggler = document.querySelector('.navbar-toggler[data-toggle="offcanvas"]');
                if (mobileToggler) {
                    mobileToggler.addEventListener('click', function (e) {
                        e.preventDefault();
                        e.stopImmediatePropagation();
                        document.body.classList.toggle('sidebar-mobile-open');
                    });
                }

                // Desktop minimize togglers
                document.querySelectorAll('[data-sidebar-minimize], .navbar-toggler[data-toggle="minimize"]').forEach(function (desktopToggler) {
                    desktopToggler.addEventListener('click', function (e) {
                        e.preventDefault();
                        e.stopImmediatePropagation();

                        if (isMobile()) {
                            document.body.classList.toggle('sidebar-mobile-open');
                            return;
                        }

                        var minimized = !document.body.classList.contains('sidebar-minimized');
                        setSidebarMinimized(minimized);
                    });
                });

                if (backdrop) {
                    backdrop.addEventListener('click', closeSidebar);
                }

                // Close sidebar on ESC key
                document.addEventListener('keydown', function (e) {
                    if (e.key === 'Escape' && isMobile()) {
                        closeSidebar();
                    }
                });

                // Close sidebar when navigating (sidebar link click on mobile)
                var sidebar = document.getElementById('sidebar');
                if (sidebar) {
                    sidebar.querySelectorAll('a').forEach(function (link) {
                        link.addEventListener('click', function () {
                            if (isMobile()) { closeSidebar(); }
                        });
                    });
                }

                // Close when resizing to desktop
                window.addEventListener('resize', function () {
                    if (!isMobile()) { closeSidebar(); }
                    applyDesktopSidebarState();
                });
            });
        })();
    </script>

    {{-- Light/Dark Mode Toggle Script --}}
    <script>
        // Apply theme immediately (before DOMContentLoaded to prevent flash)
        (function() {
            const currentTheme = localStorage.getItem('theme') || 'light';
            document.body.classList.add(currentTheme + '-theme');
        })();
        
        document.addEventListener('DOMContentLoaded', function() {
            const themeToggle = document.getElementById('theme-toggle');
            const body = document.body;
            
            if (themeToggle) {
                themeToggle.addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    if (body.classList.contains('light-theme')) {
                        body.classList.remove('light-theme');
                        body.classList.add('dark-theme');
                        localStorage.setItem('theme', 'dark');
                    } else {
                        body.classList.remove('dark-theme');
                        body.classList.add('light-theme');
                        localStorage.setItem('theme', 'light');
                    }
                });
            }
        });
    </script>

    {{-- Admin table usability enhancements --}}
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            function enhanceTableActions(table) {
                table.querySelectorAll('tbody tr').forEach(function (row) {
                    var actionCell = row.querySelector('td:last-child');
                    if (!actionCell || actionCell.querySelector('.admin-row-actions')) {
                        return;
                    }

                    var actions = Array.from(actionCell.children).filter(function (child) {
                        return child.matches('a.btn, button.btn, form');
                    });

                    if (actions.length === 0) {
                        return;
                    }

                    var dropdown = document.createElement('div');
                    dropdown.className = 'dropdown admin-row-actions';

                    var toggle = document.createElement('button');
                    toggle.type = 'button';
                    toggle.className = 'btn btn-outline-secondary btn-sm dropdown-toggle';
                    toggle.setAttribute('data-bs-toggle', 'dropdown');
                    toggle.setAttribute('aria-expanded', 'false');
                    toggle.setAttribute('aria-label', 'Row options');
                    toggle.innerHTML = '<i class="mdi mdi-dots-vertical"></i>';

                    var menu = document.createElement('div');
                    menu.className = 'dropdown-menu dropdown-menu-end';

                    actions.forEach(function (action) {
                        if (action.matches('a.btn')) {
                            action.classList.remove('btn', 'btn-sm', 'btn-primary', 'btn-secondary', 'btn-info', 'btn-warning', 'btn-danger', 'btn-light', 'btn-outline-primary', 'btn-outline-secondary');
                            action.classList.add('dropdown-item');
                            menu.appendChild(action);
                            return;
                        }

                        if (action.matches('button.btn')) {
                            action.classList.remove('btn', 'btn-sm', 'btn-primary', 'btn-secondary', 'btn-info', 'btn-warning', 'btn-danger', 'btn-light', 'btn-outline-primary', 'btn-outline-secondary');
                            action.classList.add('dropdown-item');
                            menu.appendChild(action);
                            return;
                        }

                        if (action.matches('form')) {
                            var button = action.querySelector('button');
                            if (button) {
                                button.classList.remove('btn', 'btn-sm', 'btn-primary', 'btn-secondary', 'btn-info', 'btn-warning', 'btn-danger', 'btn-light', 'btn-outline-primary', 'btn-outline-secondary');
                                button.classList.add('dropdown-item', 'text-danger');
                            }
                            menu.appendChild(action);
                        }
                    });

                    actionCell.innerHTML = '';
                    dropdown.appendChild(toggle);
                    dropdown.appendChild(menu);
                    actionCell.appendChild(dropdown);
                });
            }

            function enhanceTableShell(wrapper) {
                if (wrapper.dataset.adminTableEnhanced === '1') {
                    return;
                }

                if (wrapper.dataset.adminTablePlain === '1') {
                    return;
                }

                var table = wrapper.querySelector('table');
                if (!table || table.dataset.adminTablePlain === '1') {
                    return;
                }

                wrapper.dataset.adminTableEnhanced = '1';
                wrapper.classList.add('admin-table-shell');
                enhanceTableActions(table);

                var toolbar = document.createElement('div');
                toolbar.className = 'admin-table-toolbar';

                var expandButton = document.createElement('button');
                expandButton.type = 'button';
                expandButton.className = 'btn btn-outline-secondary btn-sm';
                expandButton.innerHTML = '<i class="mdi mdi-arrow-expand"></i> Expand table';

                expandButton.addEventListener('click', function () {
                    var expanded = wrapper.classList.toggle('admin-table-expanded');
                    document.body.classList.toggle('admin-table-expanded-open', expanded);
                    expandButton.innerHTML = expanded
                        ? '<i class="mdi mdi-arrow-collapse"></i> Normal view'
                        : '<i class="mdi mdi-arrow-expand"></i> Expand table';
                });

                toolbar.appendChild(expandButton);
                wrapper.parentNode.insertBefore(toolbar, wrapper);
            }

            document.querySelectorAll('.table-responsive').forEach(enhanceTableShell);

            document.addEventListener('keydown', function (event) {
                if (event.key !== 'Escape') {
                    return;
                }

                document.querySelectorAll('.admin-table-expanded').forEach(function (wrapper) {
                    wrapper.classList.remove('admin-table-expanded');
                    var toolbarButton = wrapper.previousElementSibling?.querySelector('button');
                    if (toolbarButton) {
                        toolbarButton.innerHTML = '<i class="mdi mdi-arrow-expand"></i> Expand table';
                    }
                });
                document.body.classList.remove('admin-table-expanded-open');
            });
        });
    </script>
</body>
</html>
