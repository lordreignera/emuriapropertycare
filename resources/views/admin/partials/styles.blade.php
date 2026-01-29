{{-- Admin Layout Styles --}}
{{-- Modern Google Fonts --}}
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

<!-- plugins:css -->
<link rel="stylesheet" href="{{ asset('admin/assets/vendors/mdi/css/materialdesignicons.min.css') }}">
<link rel="stylesheet" href="{{ asset('admin/assets/vendors/css/vendor.bundle.base.css') }}">
<!-- endinject -->
<!-- Plugin css for this page -->
<link rel="stylesheet" href="{{ asset('admin/assets/vendors/jvectormap/jquery-jvectormap.css') }}">
<link rel="stylesheet" href="{{ asset('admin/assets/vendors/flag-icon-css/css/flag-icon.min.css') }}">
<link rel="stylesheet" href="{{ asset('admin/assets/vendors/owl-carousel-2/owl.carousel.min.css') }}">
<link rel="stylesheet" href="{{ asset('admin/assets/vendors/owl-carousel-2/owl.theme.default.min.css') }}">
<!-- End plugin css for this page -->
<!-- inject:css -->
<!-- endinject -->
<!-- Layout styles -->
<link rel="stylesheet" href="{{ asset('admin/assets/css/style.css') }}">
<!-- End layout styles -->
<!-- DataTables CSS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
<link rel="shortcut icon" href="{{ asset('admin/assets/images/favicon.png') }}" />

{{-- Modern Professional Design System --}}
<style>
    /* ============================================
       MODERN DESIGN SYSTEM - Professional UI/UX
       ============================================ */
    
    /* Base Typography - Inter Font */
    * {
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif !important;
        -webkit-font-smoothing: antialiased;
        -moz-osx-font-smoothing: grayscale;
    }
    
    body {
        font-family: 'Inter', sans-serif !important;
        font-size: 14px;
        line-height: 1.6;
        letter-spacing: -0.01em;
    }
    
    h1, h2, h3, h4, h5, h6, .h1, .h2, .h3, .h4, .h5, .h6 {
        font-family: 'Inter', sans-serif !important;
        font-weight: 600;
        letter-spacing: -0.02em;
        margin-bottom: 0.75rem;
    }
    
    h1, .h1 { font-size: 2rem; font-weight: 700; }
    h2, .h2 { font-size: 1.75rem; font-weight: 700; }
    h3, .h3 { font-size: 1.5rem; font-weight: 600; }
    h4, .h4 { font-size: 1.25rem; font-weight: 600; }
    h5, .h5 { font-size: 1.125rem; font-weight: 600; }
    h6, .h6 { font-size: 1rem; font-weight: 600; }
    
    /* Modern Color Palette */
    :root {
        --primary-color: #5b67ca;
        --primary-dark: #4854b8;
        --secondary-color: #6c757d;
        --success-color: #10b981;
        --danger-color: #ef4444;
        --warning-color: #f59e0b;
        --info-color: #3b82f6;
        --light-bg: #f8fafc;
        --card-bg: #ffffff;
        --text-primary: #1e293b;
        --text-secondary: #64748b;
        --border-color: #e2e8f0;
        --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
        --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
    }
    
    /* Modern Color Palette */
    :root {
        --primary-color: #5b67ca;
        --primary-dark: #4854b8;
        --secondary-color: #6c757d;
        --success-color: #10b981;
        --danger-color: #ef4444;
        --warning-color: #f59e0b;
        --info-color: #3b82f6;
        --light-bg: #f8fafc;
        --card-bg: #ffffff;
        --text-primary: #1e293b;
        --text-secondary: #64748b;
        --border-color: #e2e8f0;
        --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
        --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
    }
    
    /* ============================================
       LAYOUT & STRUCTURE
       ============================================ */
    
    /* Container setup for fixed sidebar */
    .container-scroller {
        display: flex;
        width: 100%;
        min-height: 100vh;
        position: relative;
    }
    
    /* Force light theme colors */
    body, body.light-theme {
        background-color: var(--light-bg) !important;
        color: var(--text-primary) !important;
        overflow-x: hidden;
    }
    
    body.light-theme .container-scroller {
        background-color: var(--light-bg) !important;
    }
    
    /* Main Content Area - Scrollable */
    .main-panel {
        background-color: #ffffff !important;
        flex: 1;
        display: flex;
        flex-direction: column;
        width: 100%;
        min-height: 100vh;
        overflow-y: auto;
        overflow-x: hidden;
    }
    
    .page-body-wrapper {
        background-color: #ffffff !important;
        display: flex;
        flex-direction: column;
        flex: 1;
        width: 100%;
        overflow-y: auto;
        overflow-x: hidden;
    }
    
    .content-wrapper {
        background-color: #ffffff !important;
        padding: 1.5rem !important;
        flex: 1;
    }
    
    /* ============================================
       SIDEBAR STYLING
       ============================================ */
    
    /* Fixed Sidebar - Stays in place while content scrolls */
    body.light-theme .sidebar {
        background: linear-gradient(180deg, #ffffff 0%, #fafbfc 100%) !important;
        box-shadow: 2px 0 12px rgba(0, 0, 0, 0.08) !important;
        border-right: 1px solid var(--border-color);
        position: fixed !important;
        top: 0;
        left: 0;
        bottom: 0;
        width: 280px;
        height: 100vh;
        overflow-y: auto;
        overflow-x: hidden;
        z-index: 999;
        /* Custom scrollbar for sidebar */
        scrollbar-width: thin;
        scrollbar-color: #cbd5e1 transparent;
    }
    
    /* Webkit scrollbar styling for sidebar */
    body.light-theme .sidebar::-webkit-scrollbar {
        width: 6px;
    }
    
    body.light-theme .sidebar::-webkit-scrollbar-track {
        background: transparent;
    }
    
    body.light-theme .sidebar::-webkit-scrollbar-thumb {
        background-color: #cbd5e1;
        border-radius: 3px;
    }
    
    body.light-theme .sidebar::-webkit-scrollbar-thumb:hover {
        background-color: #94a3b8;
    }
    
    /* Adjust page-body-wrapper to account for fixed sidebar */
    .container-scroller .page-body-wrapper {
        margin-left: 280px;
        width: calc(100% - 280px);
    }
    
    /* Sidebar Navigation */
    body.light-theme .sidebar .nav .nav-item .nav-link {
        color: var(--text-primary) !important;
        font-weight: 500;
        padding: 0.875rem 1.25rem;
        margin: 0.25rem 0.75rem;
        border-radius: 0.5rem;
        transition: all 0.2s ease;
        display: flex !important;
        align-items: center !important;
        white-space: normal !important;
        word-wrap: break-word !important;
    }
    
    body.light-theme .sidebar .nav .nav-item .nav-link:hover {
        background-color: #f1f5f9 !important;
        color: var(--primary-color) !important;
        transform: translateX(4px);
    }
    
    body.light-theme .sidebar .nav .nav-item.active > .nav-link {
        background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%) !important;
        color: #ffffff !important;
        box-shadow: 0 4px 12px rgba(91, 103, 202, 0.3);
    }
    
    body.light-theme .sidebar .nav .nav-item .nav-link .menu-icon {
        color: var(--text-secondary) !important;
        font-size: 1.25rem;
        margin-right: 0.75rem;
        flex-shrink: 0;
    }
    
    body.light-theme .sidebar .nav .nav-item.active > .nav-link .menu-icon {
        color: #ffffff !important;
    }
    
    body.light-theme .sidebar .nav .nav-item .nav-link .menu-title {
        font-size: 0.875rem;
        font-weight: 500;
        line-height: 1.4;
        flex: 1;
    }
    
    /* Sidebar Brand */
    .sidebar .sidebar-brand-wrapper {
        background: #ffffff !important;
        border-bottom: 1px solid var(--border-color);
        padding: 1.25rem 1.5rem;
    }
    
    .sidebar .sidebar-brand-wrapper .sidebar-brand {
        font-size: 1.5rem;
        font-weight: 700;
        color: var(--primary-color) !important;
        letter-spacing: -0.02em;
    }
    
    /* ============================================
       NAVBAR STYLING
       ============================================ */
    
    /* Sticky Navbar - Stays at top while content scrolls */
    .navbar {
        background: #ffffff !important;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1) !important;
        border-bottom: 1px solid var(--border-color);
        padding: 1rem 1.5rem;
        position: sticky !important;
        top: 0;
        z-index: 998;
        width: 100%;
    }
    
    .navbar .navbar-brand-wrapper {
        background: transparent !important;
    }
    
    .navbar .navbar-menu-wrapper {
        background: transparent !important;
    }
    
    /* ============================================
       CARDS & PANELS
       ============================================ */
    
    .card {
        background: var(--card-bg) !important;
        border: 1px solid var(--border-color) !important;
        border-radius: 0.75rem !important;
        box-shadow: var(--shadow-sm) !important;
        margin-bottom: 1.5rem;
        transition: all 0.3s ease;
    }
    
    .card:hover {
        box-shadow: var(--shadow-md) !important;
        transform: translateY(-2px);
    }
    
    .card-header {
        background: linear-gradient(135deg, #f8fafc 0%, #ffffff 100%) !important;
        border-bottom: 1px solid var(--border-color) !important;
        padding: 1.25rem 1.5rem !important;
        border-radius: 0.75rem 0.75rem 0 0 !important;
    }
    
    .card-header h5, .card-header h4 {
        margin-bottom: 0 !important;
        font-weight: 600;
        color: var(--text-primary);
    }
    
    .card-body {
        padding: 1.5rem !important;
        color: var(--text-primary) !important;
    }
    
    .card-title {
        font-size: 1.125rem;
        font-weight: 600;
        color: var(--text-primary) !important;
        margin-bottom: 0.75rem;
    }
    
    /* ============================================
       BUTTONS - Modern Design
       ============================================ */
    
    .btn {
        font-weight: 500;
        padding: 0.625rem 1.25rem;
        border-radius: 0.5rem;
        font-size: 0.9375rem;
        letter-spacing: 0.01em;
        transition: all 0.2s ease;
        border: none;
        box-shadow: var(--shadow-sm);
    }
    
    .btn:hover {
        transform: translateY(-2px);
        box-shadow: var(--shadow-md);
    }
    
    .btn-primary {
        background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%) !important;
        color: #ffffff !important;
        border: none !important;
    }
    
    .btn-primary:hover {
        background: linear-gradient(135deg, var(--primary-dark) 0%, #3d48a0 100%) !important;
    }
    
    .btn-success {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%) !important;
        color: #ffffff !important;
    }
    
    .btn-danger {
        background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%) !important;
        color: #ffffff !important;
    }
    
    .btn-warning {
        background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%) !important;
        color: #ffffff !important;
    }
    
    .btn-info {
        background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%) !important;
        color: #ffffff !important;
    }
    
    .btn-sm {
        padding: 0.5rem 1rem;
        font-size: 0.875rem;
    }
    
    .btn-lg {
        padding: 0.75rem 1.5rem;
        font-size: 1rem;
    }
    
    /* ============================================
       FORMS - Modern Input Design
       ============================================ */
    
    .form-control, .form-select {
        border: 1px solid var(--border-color) !important;
        border-radius: 0.5rem !important;
        padding: 0.625rem 0.875rem !important;
        font-size: 0.9375rem !important;
        color: var(--text-primary) !important;
        background-color: #ffffff !important;
        transition: all 0.2s ease !important;
    }
    
    .form-control:focus, .form-select:focus {
        border-color: var(--primary-color) !important;
        box-shadow: 0 0 0 3px rgba(91, 103, 202, 0.1) !important;
        outline: none !important;
    }
    
    .form-label {
        font-weight: 500;
        color: var(--text-primary) !important;
        margin-bottom: 0.5rem;
        font-size: 0.9375rem;
    }
    
    .form-check-input:checked {
        background-color: var(--primary-color) !important;
        border-color: var(--primary-color) !important;
    }
    
    textarea.form-control {
        min-height: 100px;
    }
    
    /* ============================================
       TABLES - Modern Design
       ============================================ */
    
    .table {
        background: #ffffff !important;
        border-radius: 0.5rem;
        overflow: hidden;
    }
    
    .table thead th {
        background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%) !important;
        color: var(--text-primary) !important;
        font-weight: 600 !important;
        font-size: 0.875rem !important;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        padding: 1rem !important;
        border-bottom: 2px solid var(--border-color) !important;
    }
    
    .table tbody tr {
        transition: all 0.2s ease;
        border-bottom: 1px solid var(--border-color) !important;
    }
    
    .table tbody tr:hover {
        background-color: #f8fafc !important;
        transform: translateY(-1px);
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05) !important;
    }
    
    .table tbody td {
        padding: 1rem !important;
        color: var(--text-primary) !important;
        font-size: 0.9375rem;
        vertical-align: middle !important;
    }
    
    /* ============================================
       BADGES - Modern Style
       ============================================ */
    
    .badge {
        padding: 0.375rem 0.75rem;
        font-weight: 500;
        font-size: 0.8125rem;
        border-radius: 0.375rem;
        letter-spacing: 0.01em;
    }
    
    .badge.bg-success {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%) !important;
    }
    
    .badge.bg-danger {
        background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%) !important;
    }
    
    .badge.bg-warning {
        background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%) !important;
    }
    
    .badge.bg-info {
        background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%) !important;
    }
    
    .badge.bg-primary {
        background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%) !important;
    }
    
    /* ============================================
       ALERTS - Modern Design
       ============================================ */
    
    .alert {
        border-radius: 0.75rem !important;
        border: none !important;
        padding: 1rem 1.25rem !important;
        font-size: 0.9375rem;
        box-shadow: var(--shadow-sm);
    }
    
    .alert-success {
        background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%) !important;
        color: #065f46 !important;
        border-left: 4px solid #10b981 !important;
    }
    
    .alert-danger {
        background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%) !important;
        color: #991b1b !important;
        border-left: 4px solid #ef4444 !important;
    }
    
    .alert-warning {
        background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%) !important;
        color: #92400e !important;
        border-left: 4px solid #f59e0b !important;
    }
    
    .alert-info {
        background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%) !important;
        color: #1e40af !important;
        border-left: 4px solid #3b82f6 !important;
    }
    
    /* ============================================
       PAGINATION - Modern Style
       ============================================ */
    
    .pagination {
        margin: 1.5rem 0;
    }
    
    .page-link {
        color: var(--text-primary) !important;
        background-color: #ffffff !important;
        border: 1px solid var(--border-color) !important;
        padding: 0.5rem 0.875rem;
        margin: 0 0.125rem;
        border-radius: 0.375rem !important;
        font-weight: 500;
        transition: all 0.2s ease;
    }
    
    .page-link:hover {
        background-color: #f8fafc !important;
        border-color: var(--primary-color) !important;
        color: var(--primary-color) !important;
        transform: translateY(-2px);
        box-shadow: var(--shadow-sm);
    }
    
    .page-item.active .page-link {
        background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%) !important;
        border-color: var(--primary-color) !important;
        color: #ffffff !important;
        box-shadow: 0 4px 12px rgba(91, 103, 202, 0.3);
    }
    
    .page-item.disabled .page-link {
        background-color: #f8fafc !important;
        border-color: var(--border-color) !important;
        color: var(--text-secondary) !important;
    }
    
    /* ============================================
       BREADCRUMBS - Modern Style
       ============================================ */
    
    .breadcrumb {
        background: transparent !important;
        padding: 0.75rem 0 !important;
        margin-bottom: 1.5rem !important;
    }
    
    .breadcrumb-item {
        font-size: 0.875rem;
        font-weight: 500;
    }
    
    .breadcrumb-item + .breadcrumb-item::before {
        content: "›";
        color: var(--text-secondary);
        font-size: 1.25rem;
    }
    
    .breadcrumb-item a {
        color: var(--text-secondary) !important;
        text-decoration: none;
        transition: color 0.2s ease;
    }
    
    .breadcrumb-item a:hover {
        color: var(--primary-color) !important;
    }
    
    .breadcrumb-item.active {
        color: var(--text-primary) !important;
        font-weight: 600;
    }
    
    /* ============================================
       DROPDOWNS - Modern Style
       ============================================ */
    
    .dropdown-menu {
        background-color: #ffffff !important;
        border: 1px solid var(--border-color) !important;
        border-radius: 0.5rem !important;
        box-shadow: var(--shadow-lg) !important;
        padding: 0.5rem !important;
    }
    
    .dropdown-item {
        padding: 0.625rem 1rem !important;
        border-radius: 0.375rem !important;
        color: var(--text-primary) !important;
        font-size: 0.9375rem;
        font-weight: 500;
        transition: all 0.2s ease;
    }
    
    .dropdown-item:hover {
        background-color: #f8fafc !important;
        color: var(--primary-color) !important;
        transform: translateX(4px);
    }
    
    .dropdown-divider {
        border-color: var(--border-color) !important;
        margin: 0.5rem 0 !important;
    }
    
    /* ============================================
       MODALS - Modern Style
       ============================================ */
    
    .modal-content {
        border-radius: 0.75rem !important;
        border: none !important;
        box-shadow: var(--shadow-xl) !important;
    }
    
    .modal-header {
        background: linear-gradient(135deg, #f8fafc 0%, #ffffff 100%) !important;
        border-bottom: 1px solid var(--border-color) !important;
        border-radius: 0.75rem 0.75rem 0 0 !important;
        padding: 1.5rem !important;
    }
    
    .modal-title {
        font-weight: 600;
        color: var(--text-primary) !important;
        font-size: 1.25rem;
    }
    
    .modal-body {
        padding: 1.5rem !important;
        color: var(--text-primary) !important;
    }
    
    .modal-footer {
        border-top: 1px solid var(--border-color) !important;
        padding: 1.25rem !important;
        background-color: #f8fafc !important;
        border-radius: 0 0 0.75rem 0.75rem !important;
    }
    
    /* ============================================
       DATA TABLES - Modern Style
       ============================================ */
    
    .dataTables_wrapper {
        padding: 1rem 0;
    }
    
    .dataTables_filter input {
        border: 1px solid var(--border-color) !important;
        border-radius: 0.5rem !important;
        padding: 0.5rem 0.875rem !important;
        margin-left: 0.5rem !important;
    }
    
    .dataTables_length select {
        border: 1px solid var(--border-color) !important;
        border-radius: 0.5rem !important;
        padding: 0.5rem 0.875rem !important;
        margin: 0 0.5rem !important;
    }
    
    /* ============================================
       STATS CARDS - Dashboard
       ============================================ */
    
    .stat-card {
        background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
        border: 1px solid var(--border-color);
        border-radius: 0.75rem;
        transition: all 0.3s ease;
        box-shadow: var(--shadow-sm);
        overflow: hidden;
    }
    
    .stat-card:hover {
        transform: translateY(-4px);
        box-shadow: var(--shadow-lg);
    }
    
    .stat-card .card-body {
        padding: 1.5rem !important;
    }
    
    .stat-card h2 {
        font-size: 2rem;
        font-weight: 700;
        color: var(--text-primary);
        line-height: 1;
    }
    
    .stat-card .text-muted {
        font-size: 0.875rem;
        color: var(--text-secondary) !important;
        font-weight: 500;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .icon-wrapper {
        width: 56px;
        height: 56px;
        border-radius: 0.75rem;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .bg-primary-light {
        background-color: rgba(91, 103, 202, 0.1);
    }
    
    .bg-success-light {
        background-color: rgba(16, 185, 129, 0.1);
    }
    
    .bg-info-light {
        background-color: rgba(59, 130, 246, 0.1);
    }
    
    .bg-warning-light {
        background-color: rgba(245, 158, 11, 0.1);
    }
    
    .bg-danger-light {
        background-color: rgba(239, 68, 68, 0.1);
    }
    
    .stat-card .stat-icon {
        width: 48px;
        height: 48px;
        border-radius: 0.5rem;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        margin-bottom: 1rem;
    }
    
    .stat-card.primary .stat-icon {
        background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
        color: #ffffff;
    }
    
    .stat-card.success .stat-icon {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        color: #ffffff;
    }
    
    .stat-card.warning .stat-icon {
        background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
        color: #ffffff;
    }
    
    .stat-card.danger .stat-icon {
        background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
        color: #ffffff;
    }
    
    /* Keep old theme overrides for compatibility */
        background: #ffffff !important;
        color: #343a40 !important;
    }
    
    body.light-theme .navbar .navbar-menu-wrapper .navbar-nav .nav-item .nav-link {
        color: #343a40 !important;
    }
    
    /* Fix dropdown menus */
    body.light-theme .dropdown-menu {
        background-color: #ffffff !important;
        color: #343a40 !important;
        border: 1px solid #e3e6f0 !important;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1) !important;
    }
    
    body.light-theme .dropdown-item {
        color: #343a40 !important;
    }
    
    body.light-theme .dropdown-item:hover {
        background-color: #f8f9fa !important;
        color: #2ecc71 !important;
    }
    
    body.light-theme .dropdown-divider {
        border-color: #e3e6f0 !important;
    }
    
    /* Fix page header visibility */
    body.light-theme .page-header {
        background-color: transparent !important;
    }
    
    body.light-theme .page-header .page-title {
        color: #343a40 !important;
        font-weight: 600 !important;
    }
    
    body.light-theme .breadcrumb {
        background-color: transparent !important;
    }
    
    body.light-theme .breadcrumb-item {
        color: #6c757d !important;
    }
    
    body.light-theme .breadcrumb-item a {
        color: #2ecc71 !important;
        text-decoration: none !important;
    }
    
    body.light-theme .breadcrumb-item a:hover {
        color: #27ae60 !important;
        text-decoration: underline !important;
    }
    
    body.light-theme .breadcrumb-item.active {
        color: #343a40 !important;
    }
    
    body.light-theme .breadcrumb-item + .breadcrumb-item::before {
        color: #6c757d !important;
    }
    
    /* Sidebar badge styling */
    body.light-theme .sidebar .nav .nav-item .badge {
        font-size: 0.65rem !important;
        padding: 0.25rem 0.5rem !important;
        border-radius: 10px !important;
        font-weight: 600 !important;
    }
    
    body.light-theme .sidebar .nav .nav-item .badge-warning {
        background-color: #ffc107 !important;
        color: #343a40 !important;
    }
    
    body.light-theme .sidebar .nav .nav-item .badge-success {
        background-color: #28a745 !important;
        color: #ffffff !important;
    }
    
    body.light-theme .sidebar .nav .nav-item .badge-info {
        background-color: #17a2b8 !important;
        color: #ffffff !important;
    }
    
    body.light-theme .sidebar .nav .nav-item .badge-primary {
        background-color: #007bff !important;
        color: #ffffff !important;
    }
    
    body.light-theme .sidebar .nav .nav-item .badge-danger {
        background-color: #dc3545 !important;
        color: #ffffff !important;
    }
    
    /* Nav category styling */
    body.light-theme .sidebar .nav .nav-item.nav-category {
        margin-top: 1rem !important;
        padding-top: 1rem !important;
        border-top: 1px solid #e5e5e5 !important;
    }
    
    body.light-theme .sidebar .nav .nav-item.nav-category:first-child {
        border-top: none !important;
        margin-top: 0 !important;
        padding-top: 0.5rem !important;
    }
    
    body.light-theme .sidebar .nav .nav-item.nav-category .nav-link {
        font-weight: 700 !important;
        text-transform: uppercase !important;
        font-size: 0.7rem !important;
        letter-spacing: 0.5px !important;
        color: #495057 !important;
        padding-top: 0.5rem !important;
        padding-bottom: 0.5rem !important;
    }
    
    /* Align badges properly */
    body.light-theme .sidebar .nav .nav-item .nav-link {
        display: flex !important;
        align-items: center !important;
    }
    
    body.light-theme .sidebar .nav .nav-item .nav-link .menu-title {
        flex: 1 !important;
    }
    
    body.light-theme .sidebar .nav .nav-item .nav-link .badge {
        margin-left: auto !important;
    }
    
    /* Submenu styling */
    body.light-theme .sidebar .nav .sub-menu {
        background: #f8f9fa !important;
        padding: 0.5rem 0 !important;
    }
    
    body.light-theme .sidebar .nav .sub-menu .nav-item .nav-link {
        padding-left: 3.5rem !important;
        color: #495057 !important;
        display: flex !important;
        align-items: center !important;
    }
    
    body.light-theme .sidebar .nav .sub-menu .nav-item .nav-link:hover {
        background: #e9ecef !important;
        color: #2ecc71 !important;
    }
    
    body.light-theme .sidebar .nav .sub-menu .nav-item .nav-link.active {
        background: #e9ecef !important;
        color: #2ecc71 !important;
        font-weight: 600 !important;
    }
    
    body.light-theme .sidebar .nav .sub-menu .nav-item .nav-link i {
        margin-right: 0.5rem !important;
        font-size: 1rem !important;
    }
    
    body.light-theme .sidebar .nav .nav-item .nav-link .badge {
        margin-left: auto !important;
    }
    
    /* Fix table styling globally */
    body.light-theme .table {
        background-color: #ffffff !important;
        color: #343a40 !important;
    }
    
    body.light-theme .table thead th {
        background-color: #f8f9fa !important;
        color: #343a40 !important;
        font-weight: 600 !important;
        border-color: #dee2e6 !important;
    }
    
    body.light-theme .table tbody tr {
        background-color: #ffffff !important;
        color: #343a40 !important;
    }
    
    body.light-theme .table tbody td {
        background-color: transparent !important;
        color: #343a40 !important;
        border-color: #dee2e6 !important;
    }
    
    /* Fix hover effect - light gray background with dark text */
    body.light-theme .table-hover tbody tr:hover,
    body.light-theme .table-hover tbody tr:hover > * {
        background-color: #f1f3f5 !important;
        color: #343a40 !important;
    }
    
    body.light-theme .table-hover tbody tr:hover td {
        background-color: #f1f3f5 !important;
        color: #343a40 !important;
    }
    
    /* Ensure all nested elements stay visible on hover */
    body.light-theme .table-hover tbody tr:hover td * {
        color: #343a40 !important;
    }
    
    body.light-theme .table-hover tbody tr:hover td span {
        color: #343a40 !important;
    }
    
    body.light-theme .table-hover tbody tr:hover td .d-flex span {
        color: #343a40 !important;
    }
    
    /* Keep badges colored on hover */
    body.light-theme .table .badge {
        color: #ffffff !important;
        font-weight: 600 !important;
    }
    
    body.light-theme .table .badge-light {
        color: #343a40 !important;
    }
    
    /* ========================================
       GLOBAL FORM CONTROLS - ALL PAGES
       ======================================== */
    
    /* All input fields, textareas, and selects */
    body.light-theme input.form-control,
    body.light-theme textarea.form-control,
    body.light-theme select.form-control,
    body.light-theme .form-control {
        background-color: #ffffff !important;
        color: #343a40 !important;
        border: 1px solid #ced4da !important;
    }
    
    body.light-theme input.form-control:focus,
    body.light-theme textarea.form-control:focus,
    body.light-theme select.form-control:focus,
    body.light-theme .form-control:focus {
        background-color: #ffffff !important;
        color: #343a40 !important;
        border-color: #2ecc71 !important;
        box-shadow: 0 0 0 0.2rem rgba(46, 204, 113, 0.25) !important;
    }
    
    body.light-theme input.form-control::placeholder,
    body.light-theme textarea.form-control::placeholder,
    body.light-theme .form-control::placeholder {
        color: #6c757d !important;
        opacity: 0.7 !important;
    }
    
    body.light-theme input.form-control:disabled,
    body.light-theme textarea.form-control:disabled,
    body.light-theme select.form-control:disabled,
    body.light-theme .form-control:disabled,
    body.light-theme input.form-control[readonly],
    body.light-theme textarea.form-control[readonly],
    body.light-theme .form-control[readonly] {
        background-color: #e9ecef !important;
        color: #6c757d !important;
    }
    
    /* Select dropdown options */
    body.light-theme select.form-control option,
    body.light-theme .form-control option {
        background-color: #ffffff !important;
        color: #343a40 !important;
    }
    
    /* Form labels */
    body.light-theme .form-group label,
    body.light-theme .form-label,
    body.light-theme label {
        color: #343a40 !important;
        font-weight: 500 !important;
    }
    
    /* Form helper text */
    body.light-theme .form-text,
    body.light-theme .text-muted,
    body.light-theme small.text-muted {
        color: #6c757d !important;
    }
    
    /* Invalid feedback */
    body.light-theme .invalid-feedback {
        color: #dc3545 !important;
    }
    
    /* ========================================
       GLOBAL TABLE STYLES - ALL PAGES
       ======================================== */
    
    /* Base table styling */
    body.light-theme .table,
    body.light-theme table {
        background-color: #ffffff !important;
        color: #343a40 !important;
    }
    
    body.light-theme .table thead th,
    body.light-theme table thead th {
        background-color: #f8f9fa !important;
        color: #343a40 !important;
        font-weight: 600 !important;
        border-color: #dee2e6 !important;
    }
    
    body.light-theme .table tbody tr,
    body.light-theme table tbody tr {
        background-color: #ffffff !important;
        color: #343a40 !important;
    }
    
    body.light-theme .table tbody td,
    body.light-theme table tbody td {
        background-color: transparent !important;
        color: #343a40 !important;
        border-color: #dee2e6 !important;
    }
    
    /* Table hover effects - light gray, NOT black - MAXIMUM SPECIFICITY */
    body.light-theme .table-hover tbody tr:hover,
    body.light-theme .table.table-hover tbody tr:hover,
    body.light-theme table.table-hover tbody tr:hover,
    body.light-theme .table-responsive .table-hover tbody tr:hover,
    body.light-theme .table-responsive table tbody tr:hover,
    body.light-theme table tbody tr:hover {
        background-color: #f1f3f5 !important;
        color: #343a40 !important;
    }
    
    body.light-theme .table-hover tbody tr:hover > *,
    body.light-theme .table.table-hover tbody tr:hover > *,
    body.light-theme .table-responsive table tbody tr:hover > *,
    body.light-theme table tbody tr:hover > * {
        background-color: #f1f3f5 !important;
        color: #343a40 !important;
    }
    
    body.light-theme .table-hover tbody tr:hover td,
    body.light-theme .table.table-hover tbody tr:hover td,
    body.light-theme .table-responsive table tbody tr:hover td,
    body.light-theme table tbody tr:hover td,
    body.light-theme .table-hover tbody tr:hover th,
    body.light-theme table tbody tr:hover th {
        background-color: #f1f3f5 !important;
        color: #343a40 !important;
    }
    
    /* Keep ALL nested elements visible on hover */
    body.light-theme .table-hover tbody tr:hover td *,
    body.light-theme .table.table-hover tbody tr:hover td *,
    body.light-theme .table-responsive table tbody tr:hover td *,
    body.light-theme table tbody tr:hover td *,
    body.light-theme .table-hover tbody tr:hover th * {
        color: #343a40 !important;
    }
    
    body.light-theme .table-hover tbody tr:hover td span,
    body.light-theme .table-hover tbody tr:hover td a,
    body.light-theme .table-hover tbody tr:hover td div,
    body.light-theme .table-hover tbody tr:hover td p,
    body.light-theme .table tbody tr:hover td span,
    body.light-theme .table tbody tr:hover td a,
    body.light-theme .table tbody tr:hover td div {
        color: #343a40 !important;
    }
    
    /* Force background on hover - override any template styles */
    body.light-theme .table tbody tr:hover,
    body.light-theme .table tbody tr:hover td,
    body.light-theme .table tbody tr:hover th {
        background: #f1f3f5 !important;
        background-color: #f1f3f5 !important;
    }
    
    /* Ensure badges stay visible */
    body.light-theme .table tbody tr:hover .badge {
        color: #ffffff !important;
    }
    
    body.light-theme .table tbody tr:hover .badge-light {
        color: #343a40 !important;
        background-color: #f8f9fa !important;
    }
    
    /* ========================================
       CARDS AND CONTENT AREAS
       ======================================== */
    
    body.light-theme .card {
        background-color: #ffffff !important;
        color: #343a40 !important;
        border: 1px solid #e3e6f0 !important;
    }
    
    body.light-theme .card-title {
        color: #343a40 !important;
    }
    
    body.light-theme .card-description,
    body.light-theme .card-text {
        color: #6c757d !important;
    }
    
    body.light-theme .card-body {
        color: #343a40 !important;
    }
    
    /* ========================================
       BUTTONS - Ensure visibility
       ======================================== */
    
    body.light-theme .btn-link {
        color: #2ecc71 !important;
    }
    
    body.light-theme .btn-link:hover {
        color: #27ae60 !important;
    }
    
    /* ========================================
       FINAL OVERRIDE - Table Hover Fix
       This must be last to override template
       ======================================== */
    
    /* Ultimate table hover fix - applies to ALL tables */
    .table tbody tr:hover {
        background-color: #f1f5f9 !important;
        color: #1a202c !important;
        transform: translateY(-2px) !important;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1) !important;
    }
    
    /* Override any dark hover from template */
    .light-theme .table tbody tr:hover {
        background: #f1f5f9 !important;
        background-color: #f1f5f9 !important;
        color: #1a202c !important;
    }
    
    .light-theme .table tbody tr:hover td,
    .light-theme .table tbody tr:hover th {
        background: #f1f5f9 !important;
        background-color: #f1f5f9 !important;
        color: #1a202c !important;
    }
    
    .light-theme .table tbody tr:hover * {
        color: #1a202c !important;
    }
    
    /* Keep action buttons visible */
    .light-theme .table tbody tr:hover .btn {
        opacity: 1 !important;
        visibility: visible !important;
    }
    
    /* Keep badges with their colors */
    .light-theme .table tbody tr:hover .badge-danger { color: #ffffff !important; background-color: #dc3545 !important; }
    .light-theme .table tbody tr:hover .badge-warning { color: #ffffff !important; background-color: #ffc107 !important; }
    .light-theme .table tbody tr:hover .badge-success { color: #ffffff !important; background-color: #28a745 !important; }
    .light-theme .table tbody tr:hover .badge-primary { color: #ffffff !important; background-color: #007bff !important; }
    .light-theme .table tbody tr:hover .badge-info { color: #ffffff !important; background-color: #17a2b8 !important; }
    .light-theme .table tbody tr:hover .badge-secondary { color: #ffffff !important; background-color: #6c757d !important; }
    .light-theme .table tbody tr:hover .badge-dark { color: #ffffff !important; background-color: #343a40 !important; }
    .light-theme .table tbody tr:hover .badge-light { color: #343a40 !important; background-color: #f8f9fa !important; }
    
    /* ========================================
       DATATABLES - Light Theme Overrides
       ======================================== */
    
    /* DataTables wrapper styling */
    body.light-theme .dataTables_wrapper {
        color: #343a40 !important;
    }
    
    body.light-theme .dataTables_wrapper .dataTables_filter input,
    body.light-theme .dataTables_wrapper .dataTables_length select {
        background-color: #ffffff !important;
        color: #343a40 !important;
        border: 1px solid #d1d5db !important;
        padding: 0.375rem 0.75rem !important;
        border-radius: 0.25rem !important;
    }
    
    body.light-theme .dataTables_wrapper .dataTables_filter label,
    body.light-theme .dataTables_wrapper .dataTables_length label,
    body.light-theme .dataTables_wrapper .dataTables_info,
    body.light-theme .dataTables_wrapper .dataTables_paginate {
        color: #343a40 !important;
    }
    
    body.light-theme .dataTables_wrapper .dataTables_paginate .paginate_button {
        background-color: #ffffff !important;
        color: #343a40 !important;
        border: 1px solid #d1d5db !important;
        margin: 0 2px !important;
    }
    
    body.light-theme .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
        background-color: #f1f5f9 !important;
        color: #1a202c !important;
        border-color: #2ecc71 !important;
    }
    
    body.light-theme .dataTables_wrapper .dataTables_paginate .paginate_button.current {
        background-color: #2ecc71 !important;
        color: #ffffff !important;
        border-color: #2ecc71 !important;
    }
    
    body.light-theme .dataTables_wrapper .dataTables_paginate .paginate_button.disabled {
        opacity: 0.5 !important;
        cursor: not-allowed !important;
    }
    
    /* DataTables table styling */
    body.light-theme table.dataTable {
        background-color: #ffffff !important;
        color: #343a40 !important;
    }
    
    body.light-theme table.dataTable thead th {
        background-color: #f8f9fa !important;
        color: #343a40 !important;
        border-color: #dee2e6 !important;
    }
    
    body.light-theme table.dataTable tbody td {
        background-color: #ffffff !important;
        color: #343a40 !important;
        border-color: #dee2e6 !important;
    }
    
    /* ============================================
       FOOTER STYLING
       ============================================ */
    
    .footer {
        background-color: #ffffff !important;
        border-top: 1px solid var(--border-color) !important;
        padding: 1rem 1.5rem !important;
        margin-top: auto !important;
    }
    
    .footer .text-muted {
        color: var(--text-secondary) !important;
    }
    
    .footer a {
        color: var(--primary-color) !important;
        text-decoration: none !important;
    }
    
    .footer a:hover {
        color: var(--primary-dark) !important;
        text-decoration: underline !important;
    }
</style>

{{-- Custom Page Styles --}}
@stack('styles')

