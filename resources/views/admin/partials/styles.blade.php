{{-- Admin Layout Styles --}}
{{-- Dashboard font stack --}}

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">

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
<link rel="stylesheet" href="{{ asset('admin/assets/css/style.css') }}?v={{ filemtime(public_path('admin/assets/css/style.css')) }}">
<!-- End layout styles -->
<!-- DataTables CSS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
<link rel="shortcut icon" href="{{ asset('admin/assets/images/favicon.png') }}" />

{{-- ETOGO base typography and layout --}}
<style>
    :root {
        --ETOGO-font-sans: "Intel Clear", "Inter", ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
        --ETOGO-font-weight-regular: 400;
        --ETOGO-font-weight-medium: 500;
        --ETOGO-font-weight-semibold: 600;
        --ETOGO-font-weight-bold: 700;
    }

    * {
        font-family: var(--ETOGO-font-sans) !important;
        -webkit-font-smoothing: antialiased;
        -moz-osx-font-smoothing: grayscale;
    }

    html,
    body {
        min-height: 100%;
        background: #f4f7fb !important;
    }

    body {
        font-size: 14px;
        line-height: 1.55;
        letter-spacing: 0;
        font-weight: var(--ETOGO-font-weight-regular);
        overflow-x: hidden;
    }

    h1, h2, h3, h4, h5, h6,
    .h1, .h2, .h3, .h4, .h5, .h6 {
        font-weight: var(--ETOGO-font-weight-semibold);
        letter-spacing: 0;
    }

    h1, .h1 { font-size: 2rem; font-weight: var(--ETOGO-font-weight-bold); }
    h2, .h2 { font-size: 1.75rem; font-weight: var(--ETOGO-font-weight-bold); }
    h3, .h3 { font-size: 1.5rem; }
    h4, .h4 { font-size: 1.25rem; }
    h5, .h5 { font-size: 1.125rem; }
    h6, .h6 { font-size: 1rem; }

    .container-scroller {
        display: flex;
        width: 100%;
        min-height: 100vh;
        position: relative;
    }

    .page-body-wrapper,
    .main-panel {
        min-height: 100vh;
    }
</style>

{{-- ETOGO clean operations interface: final visual layer --}}
<style>
    :root {
        --ETOGO-page: #f4f7fb;
        --ETOGO-sidebar: #031b46;
        --ETOGO-sidebar-line: rgba(255, 255, 255, .09);
        --ETOGO-card: #ffffff;
        --ETOGO-ink: #071426;
        --ETOGO-text: #172033;
        --ETOGO-muted: #667085;
        --ETOGO-line: #dfe6ef;
        --ETOGO-blue: #2458d6;
        --ETOGO-blue-soft: #e8f0ff;
        --ETOGO-radius: 8px;
    }

    body,
    body.light-theme,
    body.light-theme .container-scroller,
    body.light-theme .page-body-wrapper,
    body.light-theme .main-panel,
    body.light-theme .content-wrapper {
        background: var(--ETOGO-page) !important;
        color: var(--ETOGO-text) !important;
        letter-spacing: 0 !important;
    }

    .content-wrapper {
        padding: 24px 28px !important;
        width: 100% !important;
        max-width: 100% !important;
        min-width: 0 !important;
        flex-grow: 1 !important;
        background: var(--ETOGO-page) !important;
    }

    .content-wrapper > .content-wrapper {
        padding: 0 !important;
        background: transparent !important;
    }

    .page-header {
        background: var(--ETOGO-card) !important;
        border: 1px solid var(--ETOGO-line) !important;
        border-radius: var(--ETOGO-radius) !important;
        box-shadow: 0 4px 14px rgba(16, 24, 40, .05) !important;
        padding: 18px 22px !important;
        margin-bottom: 26px !important;
        align-items: center !important;
    }

    .page-title {
        color: var(--ETOGO-ink) !important;
        font-size: 1.45rem !important;
        font-weight: 600 !important;
        line-height: 1.2 !important;
    }

    .breadcrumb-item,
    .breadcrumb-item a {
        color: var(--ETOGO-muted) !important;
        font-weight: 500 !important;
    }

    .navbar,
    .navbar.fixed-top {
        height: 72px !important;
        background: rgba(255,255,255,.98) !important;
        border-bottom: 1px solid var(--ETOGO-line) !important;
        box-shadow: 0 8px 22px rgba(16, 24, 40, .07) !important;
    }

    .navbar .navbar-toggler {
        border-color: var(--ETOGO-line) !important;
        color: var(--ETOGO-muted) !important;
    }

    .navbar .form-control {
        height: 40px !important;
        border-radius: 7px !important;
        border-color: #cfd8e6 !important;
        color: var(--ETOGO-text) !important;
    }

    .navbar .create-new-button,
    .navbar .btn-success {
        background: var(--ETOGO-blue) !important;
        color: #fff !important;
        border-radius: 7px !important;
        min-height: 38px !important;
        padding: 0 18px !important;
    }

    .card {
        border: 1px solid var(--ETOGO-line) !important;
        border-radius: var(--ETOGO-radius) !important;
        box-shadow: 0 6px 18px rgba(16, 24, 40, .055) !important;
        background: var(--ETOGO-card) !important;
    }

    .card-body {
        color: var(--ETOGO-text) !important;
    }

    .card-title {
        color: var(--ETOGO-ink) !important;
        font-size: 1rem !important;
        font-weight: 600 !important;
        text-transform: none !important;
    }

    .table thead th {
        background: #f3f6fa !important;
        color: #344054 !important;
        font-weight: 600 !important;
    }

    .table tbody td {
        color: var(--ETOGO-text) !important;
    }

    .btn:hover,
    .card:hover,
    .table tbody tr:hover {
        transform: none !important;
    }
</style>

@include('shared.sidebar-design-system')

{{-- Custom Page Styles --}}
@stack('styles')

