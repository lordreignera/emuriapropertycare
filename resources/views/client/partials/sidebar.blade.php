{{-- Client Sidebar Navigation --}}
<nav class="sidebar sidebar-offcanvas" id="sidebar">
  <div class="sidebar-brand-wrapper d-none d-lg-flex align-items-center justify-content-center fixed-top">
    <a class="sidebar-brand brand-logo" href="{{ route('dashboard') }}">
      <span style="color: #fff; font-size: 1.5rem; font-weight: 700;">
        EMURIA<span style="color: #FFB800;">PropertyCare</span>
      </span>
    </a>
    <a class="sidebar-brand brand-logo-mini" href="{{ route('dashboard') }}">
      <span style="color: #FFB800; font-size: 1.2rem; font-weight: 700;">E</span>
    </a>
  </div>
  <ul class="nav">
    {{-- Profile Section --}}
    <li class="nav-item profile">
      <div class="profile-desc">
        <div class="profile-pic">
          <div class="count-indicator">
            <img class="img-xs rounded-circle" src="{{ Auth::user()->profile_photo_url }}" alt="{{ Auth::user()->name }}">
            <span class="count bg-success"></span>
          </div>
          <div class="profile-name">
            <h5 class="mb-0 font-weight-normal">{{ Auth::user()->name }}</h5>
            <span>Client Account</span>
          </div>
        </div>
        <a href="#" id="profile-dropdown" data-bs-toggle="dropdown"><i class="mdi mdi-dots-vertical"></i></a>
        <div class="dropdown-menu dropdown-menu-right sidebar-dropdown preview-list" aria-labelledby="profile-dropdown">
          <a href="{{ route('profile.show') }}" class="dropdown-item preview-item">
            <div class="preview-thumbnail">
              <div class="preview-icon bg-dark rounded-circle">
                <i class="mdi mdi-settings text-primary"></i>
              </div>
            </div>
            <div class="preview-item-content">
              <p class="preview-subject ellipsis mb-1 text-small">Account settings</p>
            </div>
          </a>
          <div class="dropdown-divider"></div>
          <a href="{{ route('profile.show') }}" class="dropdown-item preview-item">
            <div class="preview-thumbnail">
              <div class="preview-icon bg-dark rounded-circle">
                <i class="mdi mdi-onepassword text-info"></i>
              </div>
            </div>
            <div class="preview-item-content">
              <p class="preview-subject ellipsis mb-1 text-small">Change Password</p>
            </div>
          </a>
          <div class="dropdown-divider"></div>
          <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="dropdown-item preview-item" style="border: none; background: none; width: 100%; text-align: left;">
              <div class="preview-thumbnail">
                <div class="preview-icon bg-dark rounded-circle">
                  <i class="mdi mdi-logout text-danger"></i>
                </div>
              </div>
              <div class="preview-item-content">
                <p class="preview-subject ellipsis mb-1 text-small">Logout</p>
              </div>
            </button>
          </form>
        </div>
      </div>
    </li>

    <li class="nav-item nav-category">
      <span class="nav-link">Navigation</span>
    </li>

    {{-- Dashboard --}}
    <li class="nav-item menu-items {{ request()->routeIs('dashboard') ? 'active' : '' }}">
      <a class="nav-link" href="{{ route('dashboard') }}">
        <span class="menu-icon">
          <i class="mdi mdi-speedometer"></i>
        </span>
        <span class="menu-title">Dashboard</span>
      </a>
    </li>

    {{-- Properties Section --}}
    <li class="nav-item nav-category">
      <span class="nav-link">Property Management</span>
    </li>

    {{-- Add Property --}}
    <li class="nav-item menu-items {{ request()->routeIs('client.properties.create') ? 'active' : '' }}">
      <a class="nav-link" href="{{ route('client.properties.create') }}">
        <span class="menu-icon">
          <i class="mdi mdi-home-plus"></i>
        </span>
        <span class="menu-title">Add Property</span>
      </a>
    </li>

    {{-- My Properties --}}
    <li class="nav-item menu-items {{ request()->routeIs('client.properties.index') || request()->routeIs('client.properties.show') || request()->routeIs('client.properties.edit') ? 'active' : '' }}">
      <a class="nav-link" href="{{ route('client.properties.index') }}">
        <span class="menu-icon">
          <i class="mdi mdi-home-modern"></i>
        </span>
        <span class="menu-title">My Properties</span>
      </a>
    </li>

    {{-- Tenants --}}
    <li class="nav-item menu-items {{ request()->routeIs('client.tenants.*') ? 'active' : '' }}">
      <a class="nav-link" href="{{ route('client.tenants.index') }}">
        <span class="menu-icon">
          <i class="mdi mdi-account-group"></i>
        </span>
        <span class="menu-title">Tenants</span>
      </a>
    </li>

    {{-- Services Section --}}
    <li class="nav-item nav-category">
      <span class="nav-link">Services</span>
    </li>

    {{-- Inspections --}}
    <li class="nav-item menu-items {{ request()->routeIs('client.inspections.*') ? 'active' : '' }}">
      <a class="nav-link" href="{{ route('client.inspections.index') }}">
        <span class="menu-icon">
          <i class="mdi mdi-clipboard-check"></i>
        </span>
        <span class="menu-title">Inspections</span>
        @php
            $userPropertyIds = \App\Models\Property::where('user_id', Auth::id())->pluck('id');
            $userProjectIds = \App\Models\Project::whereIn('property_id', $userPropertyIds)->pluck('id');
            $pendingInspections = \App\Models\Inspection::whereIn('project_id', $userProjectIds)
                ->where('status', 'scheduled')->count();
        @endphp
        @if($pendingInspections > 0)
        <span class="badge badge-pill badge-warning ms-auto">{{ $pendingInspections }}</span>
        @endif
      </a>
    </li>

    {{-- Projects --}}
    <li class="nav-item menu-items {{ request()->routeIs('client.projects.*') ? 'active' : '' }}">
      <a class="nav-link" href="{{ route('client.projects.index') }}">
        <span class="menu-icon">
          <i class="mdi mdi-briefcase"></i>
        </span>
        <span class="menu-title">Projects</span>
        @php
            $userPropertyIds = \App\Models\Property::where('user_id', Auth::id())->pluck('id');
            $activeProjects = \App\Models\Project::whereIn('property_id', $userPropertyIds)
                ->where('status', 'active')->count();
        @endphp
        @if($activeProjects > 0)
        <span class="badge badge-pill badge-success ms-auto">{{ $activeProjects }}</span>
        @endif
      </a>
    </li>

    {{-- Billing Section --}}
    <li class="nav-item nav-category">
      <span class="nav-link">Billing</span>
    </li>

    {{-- Invoices --}}
    <li class="nav-item menu-items {{ request()->routeIs('client.invoices.*') ? 'active' : '' }}">
      <a class="nav-link" href="{{ route('client.invoices.index') }}">
        <span class="menu-icon">
          <i class="mdi mdi-file-document"></i>
        </span>
        <span class="menu-title">Invoices</span>
        @php
            $unpaidInvoices = \App\Models\Invoice::where('user_id', Auth::id())
                ->where('status', 'pending')->count();
        @endphp
        @if($unpaidInvoices > 0)
        <span class="badge badge-pill badge-danger ms-auto">{{ $unpaidInvoices }}</span>
        @endif
      </a>
    </li>

    {{-- Subscription --}}
    <li class="nav-item menu-items {{ request()->routeIs('client.subscription.*') ? 'active' : '' }}">
      <a class="nav-link" href="{{ route('client.subscription.show') }}">
        <span class="menu-icon">
          <i class="mdi mdi-crown"></i>
        </span>
        <span class="menu-title">My Subscription</span>
      </a>
    </li>

    {{-- Support Section --}}
    <li class="nav-item nav-category">
      <span class="nav-link">Support</span>
    </li>

    {{-- Complaints --}}
    <li class="nav-item menu-items {{ request()->routeIs('client.complaints.*') ? 'active' : '' }}">
      <a class="nav-link" href="{{ route('client.complaints.index') }}">
        <span class="menu-icon">
          <i class="mdi mdi-alert-circle"></i>
        </span>
        <span class="menu-title">Complaints</span>
      </a>
    </li>

    {{-- Emergency Reports --}}
    <li class="nav-item menu-items {{ request()->routeIs('client.emergency-reports.*') ? 'active' : '' }}">
      <a class="nav-link" href="{{ route('client.emergency-reports.index') }}">
        <span class="menu-icon">
          <i class="mdi mdi-alarm-light"></i>
        </span>
        <span class="menu-title">Emergency Reports</span>
      </a>
    </li>

    {{-- Help & Support --}}
    <li class="nav-item menu-items">
      <a class="nav-link" href="{{ route('client.support') }}">
        <span class="menu-icon">
          <i class="mdi mdi-help-circle"></i>
        </span>
        <span class="menu-title">Help & Support</span>
      </a>
    </li>

  </ul>
</nav>

<style>
/* Override sidebar green background with white - More specific selectors */
body .sidebar,
body.light-theme .sidebar,
.sidebar.sidebar-offcanvas {
    background: #ffffff !important;
    background-image: none !important;
    background-color: #ffffff !important;
    box-shadow: 0 0 20px rgba(0, 0, 0, 0.1) !important;
}

/* Update brand wrapper to match */
body .sidebar .sidebar-brand-wrapper,
body.light-theme .sidebar .sidebar-brand-wrapper {
    background: #ffffff !important;
    background-image: none !important;
    border-bottom: 1px solid #e5e5e5 !important;
}

/* Change brand text colors for visibility on white */
.sidebar .sidebar-brand-wrapper .sidebar-brand span {
    color: #2c3e50 !important;
}

.sidebar .sidebar-brand-wrapper .brand-logo-mini span {
    color: #FFB800 !important;
}

/* Update navigation text colors */
.sidebar .nav .nav-item .nav-link {
    color: #2c3e50 !important;
}

.sidebar .nav .nav-item .nav-link .menu-icon {
    color: #6c757d !important;
}

.sidebar .nav .nav-item .nav-link .menu-title {
    color: #2c3e50 !important;
}

/* Active menu item */
.sidebar .nav .nav-item.active > .nav-link {
    background: #f8f9fa !important;
    color: #191c24 !important;
}

.sidebar .nav .nav-item.active > .nav-link .menu-icon {
    color: #FFB800 !important;
}

/* Hover effect */
.sidebar .nav .nav-item .nav-link:hover {
    background: #f8f9fa !important;
}

/* Category headers */
.sidebar .nav .nav-category {
    color: #6c757d !important;
}

/* Profile section */
.sidebar .nav .nav-item.profile {
    border-bottom: 1px solid #e5e5e5;
}

.sidebar .nav .nav-item.profile .profile-name h5 {
    color: #2c3e50 !important;
}

.sidebar .nav .nav-item.profile .profile-name span {
    color: #6c757d !important;
}

/* Badges */
.sidebar .badge {
    background-color: #FFB800 !important;
    color: #000000 !important;
}

.sidebar .badge-warning {
    background-color: #ffc107 !important;
}

.sidebar .badge-success {
    background-color: #28a745 !important;
    color: #ffffff !important;
}

.sidebar .badge-danger {
    background-color: #dc3545 !important;
    color: #ffffff !important;
}
</style>
