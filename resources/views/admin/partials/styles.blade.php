{{-- Admin Layout Styles --}}
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

{{-- Force Light Mode Override --}}
<style>
    /* Override Bootstrap table hover variables */
    :root {
        --bs-table-hover-bg: #f1f5f9 !important;
        --bs-table-hover-color: #1a202c !important;
    }
    
    /* Force light theme colors */
    body.light-theme {
        background-color: #f4f5f7 !important;
        color: #343a40 !important;
    }
    
    body.light-theme .container-scroller {
        background-color: #f4f5f7 !important;
    }
    
    body.light-theme .sidebar {
        background: #ffffff !important;
        box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
    }
    
    /* Update sidebar text colors for white background */
    body.light-theme .sidebar .nav .nav-item .nav-link {
        color: #2c3e50 !important;
    }
    
    body.light-theme .sidebar .nav .nav-item .nav-link .menu-icon {
        color: #6c757d !important;
    }
    
    body.light-theme .sidebar .nav .nav-item.active .nav-link {
        background: #f8f9fa !important;
        color: #191c24 !important;
    }
    
    body.light-theme .sidebar .nav .nav-item.active .nav-link .menu-icon {
        color: #2ecc71 !important;
    }
    
    body.light-theme .sidebar .nav .nav-category {
        color: #6c757d !important;
    }
    
    body.light-theme .sidebar .sidebar-brand-wrapper {
        background: #ffffff !important;
        border-bottom: 1px solid #e5e5e5 !important;
    }
    
    body.light-theme .navbar {
        background-color: #ffffff !important;
        border-bottom: 1px solid #e3e6f0;
    }
    
    body.light-theme .main-panel {
        background-color: #f4f5f7 !important;
    }
    
    body.light-theme .content-wrapper {
        background-color: #f4f5f7 !important;
    }
    
    body.light-theme .card {
        background-color: #ffffff !important;
        color: #343a40 !important;
    }
    
    body.light-theme .navbar .navbar-menu-wrapper {
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
</style>

{{-- Custom Page Styles --}}
@stack('styles')

