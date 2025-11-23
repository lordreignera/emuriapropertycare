<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <title>{{ config('app.name', 'EMURIA Property Care') }} - @yield('title', 'Dashboard')</title>
    
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
            background-color: #f1f5f9 !important;
            color: #1a202c !important;
            transform: translateY(-2px) !important;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1) !important;
            transition: all 0.3s ease !important;
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
    </style>
</head>
<body class="light-theme">
    <div class="container-scroller">
        {{-- Sidebar --}}
        @include('admin.partials.sidebar')
        
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

                    @if($errors->any())
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
</body>
</html>
