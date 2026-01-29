{{-- Client Sidebar Navigation --}}
<nav class="sidebar sidebar-offcanvas" id="sidebar">
  <div class="sidebar-brand-wrapper d-none d-lg-flex align-items-center justify-content-center fixed-top">
    <a class="sidebar-brand brand-logo" href="{{ route('dashboard') }}">
      <span style="color: #fff; font-size: 1.5rem; font-weight: 700;">
        ETOGO<span style="color: #FFB800;"></span>
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

    {{-- Property Management Dropdown --}}
    <li class="nav-item menu-items {{ request()->routeIs('client.properties.*') || request()->routeIs('client.tenants.*') ? 'active' : '' }}">
      <a class="nav-link" data-bs-toggle="collapse" href="#property-management" aria-expanded="{{ request()->routeIs('client.properties.*') || request()->routeIs('client.tenants.*') ? 'true' : 'false' }}" aria-controls="property-management">
        <span class="menu-icon">
          <i class="mdi mdi-home-modern"></i>
        </span>
        <span class="menu-title">Property Management</span>
        <i class="menu-arrow"></i>
      </a>
      <div class="collapse {{ request()->routeIs('client.properties.*') || request()->routeIs('client.tenants.*') ? 'show' : '' }}" id="property-management">
        <ul class="nav flex-column sub-menu">
          <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('client.properties.create') ? 'active' : '' }}" href="{{ route('client.properties.create') }}">
              <i class="mdi mdi-home-plus"></i> Add Property
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('client.properties.index') || request()->routeIs('client.properties.show') || request()->routeIs('client.properties.edit') ? 'active' : '' }}" href="{{ route('client.properties.index') }}">
              <i class="mdi mdi-view-list"></i> My Properties
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('client.tenants.*') ? 'active' : '' }}" href="{{ route('client.tenants.index') }}">
              <i class="mdi mdi-account-group"></i> Tenants
            </a>
          </li>
        </ul>
      </div>
    </li>

    {{-- Services Section --}}
    <li class="nav-item nav-category">
      <span class="nav-link">Services</span>
    </li>

    {{-- Services Dropdown --}}
    <li class="nav-item menu-items {{ request()->routeIs('client.inspections.*') || request()->routeIs('client.projects.*') ? 'active' : '' }}">
      <a class="nav-link" data-bs-toggle="collapse" href="#services-menu" aria-expanded="{{ request()->routeIs('client.inspections.*') || request()->routeIs('client.projects.*') ? 'true' : 'false' }}" aria-controls="services-menu">
        <span class="menu-icon">
          <i class="mdi mdi-clipboard-check"></i>
        </span>
        <span class="menu-title">Services</span>
        <i class="menu-arrow"></i>
      </a>
      <div class="collapse {{ request()->routeIs('client.inspections.*') || request()->routeIs('client.projects.*') ? 'show' : '' }}" id="services-menu">
        <ul class="nav flex-column sub-menu">
          <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('client.inspections.*') ? 'active' : '' }}" href="{{ route('client.inspections.index') }}">
              <i class="mdi mdi-clipboard-check"></i> Inspections
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
          <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('client.projects.*') ? 'active' : '' }}" href="{{ route('client.projects.index') }}">
              <i class="mdi mdi-briefcase"></i> Projects
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
        </ul>
      </div>
    </li>

    {{-- Billing Dropdown --}}
    <li class="nav-item menu-items {{ request()->routeIs('client.invoices.*') || request()->routeIs('client.subscription.*') ? 'active' : '' }}">
      <a class="nav-link" data-bs-toggle="collapse" href="#billing-menu" aria-expanded="{{ request()->routeIs('client.invoices.*') || request()->routeIs('client.subscription.*') ? 'true' : 'false' }}" aria-controls="billing-menu">
        <span class="menu-icon">
          <i class="mdi mdi-cash-multiple"></i>
        </span>
        <span class="menu-title">Billing & Finance</span>
        <i class="menu-arrow"></i>
      </a>
      <div class="collapse {{ request()->routeIs('client.invoices.*') || request()->routeIs('client.subscription.*') ? 'show' : '' }}" id="billing-menu">
        <ul class="nav flex-column sub-menu">
          <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('client.invoices.*') ? 'active' : '' }}" href="{{ route('client.invoices.index') }}">
              <i class="mdi mdi-file-document"></i> Invoices
              @php
                  $unpaidInvoices = \App\Models\Invoice::where('user_id', Auth::id())
                      ->where('status', 'pending')->count();
              @endphp
              @if($unpaidInvoices > 0)
              <span class="badge badge-pill badge-danger ms-auto">{{ $unpaidInvoices }}</span>
              @endif
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('client.subscription.*') ? 'active' : '' }}" href="{{ route('client.subscription.show') }}">
              <i class="mdi mdi-crown"></i> My Subscription
            </a>
          </li>
        </ul>
      </divclass="nav-link" href="{{ route('client.subscription.show') }}">
        <span class="menu-icon">
          <i class="mdi mdi-crown"></i>
        </span>
        <span class="menu-title">My Subscription</span>
      </a>
    </li>

    {{-- Support Dropdown --}}
    <li class="nav-item menu-items {{ request()->routeIs('client.complaints.*') || request()->routeIs('client.emergency-reports.*') || request()->routeIs('client.support') ? 'active' : '' }}">
      <a class="nav-link" data-bs-toggle="collapse" href="#support-menu" aria-expanded="{{ request()->routeIs('client.complaints.*') || request()->routeIs('client.emergency-reports.*') || request()->routeIs('client.support') ? 'true' : 'false' }}" aria-controls="support-menu">
        <span class="menu-icon">
          <i class="mdi mdi-help-circle"></i>
        </span>
        <span class="menu-title">Help & Support</span>
        <i class="menu-arrow"></i>
      </a>
      <div class="collapse {{ request()->routeIs('client.complaints.*') || request()->routeIs('client.emergency-reports.*') || request()->routeIs('client.support') ? 'show' : '' }}" id="support-menu">
        <ul class="nav flex-column sub-menu">
          <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('client.complaints.*') ? 'active' : '' }}" href="{{ route('client.complaints.index') }}">
              <i class="mdi mdi-alert-circle"></i> Complaints
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('client.emergency-reports.*') ? 'active' : '' }}" href="{{ route('client.emergency-reports.index') }}">
              <i class="mdi mdi-alarm-light"></i> Emergency Reports
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('client.support') ? 'active' : '' }}" href="{{ route('client.support') }}">
              <i class="mdi mdi-lifebuoy"></i> Contact Support
            </a>
          </li>
        </ul>
      </divclass="nav-link" href="{{ route('client.support') }}">
        <span class="menu-icon">
          <i class="mdi mdi-help-circle"></i>
        </span>
        <span class="menu-title">Help & Support</span>
      </a>
    </li>

  </ul>
</nav>

<style>
/* CRITICAL: Sidebar with blue background - Maximum specificity */
body .sidebar,
body.light-theme .sidebar,
.sidebar.sidebar-offcanvas,
body .sidebar.sidebar-offcanvas,
body.light-theme .sidebar.sidebar-offcanvas,
html body .sidebar,
html body.light-theme .sidebar {
    background: linear-gradient(180deg, #5b67ca 0%, #4854b8 100%) !important;
    background-image: linear-gradient(180deg, #5b67ca 0%, #4854b8 100%) !important;
    background-color: #5b67ca !important;
    box-shadow: 0 0 20px rgba(0, 0, 0, 0.3) !important;
    border: none !important;
}

/* Update brand wrapper to match - Maximum specificity */
body .sidebar .sidebar-brand-wrapper,
body.light-theme .sidebar .sidebar-brand-wrapper,
html body .sidebar .sidebar-brand-wrapper,
.sidebar.sidebar-offcanvas .sidebar-brand-wrapper {
    background: rgba(255, 255, 255, 0.1) !important;
    background-image: none !important;
    border-bottom: 1px solid rgba(255, 255, 255, 0.2) !important;
}

/* Change brand text colors for visibility on blue */
.sidebar .sidebar-brand-wrapper .sidebar-brand span,
body .sidebar .sidebar-brand-wrapper .sidebar-brand span {
    color: #ffffff !important;
}

.sidebar .sidebar-brand-wrapper .brand-logo-mini span,
body .sidebar .sidebar-brand-wrapper .brand-logo-mini span {
    color: #FFB800 !important;
}

/* Update navigation text colors - Maximum specificity */
.sidebar .nav .nav-item .nav-link,
body .sidebar .nav .nav-item .nav-link,
body.light-theme .sidebar .nav .nav-item .nav-link {
    color: rgba(255, 255, 255, 0.9) !important;
}

.sidebar .nav .nav-item .nav-link .menu-icon,
body .sidebar .nav .nav-item .nav-link .menu-icon,
body.light-theme .sidebar .nav .nav-item .nav-link .menu-icon {
    color: rgba(255, 255, 255, 0.7) !important;
}

.sidebar .nav .nav-item .nav-link .menu-title,
body .sidebar .nav .nav-item .nav-link .menu-title,
body.light-theme .sidebar .nav .nav-item .nav-link .menu-title {
    color: rgba(255, 255, 255, 0.9) !important;
}

/* Active menu item - Maximum specificity */
.sidebar .nav .nav-item.active > .nav-link,
body .sidebar .nav .nav-item.active > .nav-link,
body.light-theme .sidebar .nav .nav-item.active > .nav-link {
    background: rgba(0, 0, 0, 0.2) !important;
    color: #ffffff !important;
}

.sidebar .nav .nav-item.active > .nav-link .menu-icon,
body .sidebar .nav .nav-item.active > .nav-link .menu-icon {
    color: #ffffff !important;
}

.sidebar .nav .nav-item.active > .nav-link .menu-title,
body .sidebar .nav .nav-item.active > .nav-link .menu-title {
    color: #ffffff !important;
}

/* Hover effect - Maximum specificity */
.sidebar .nav .nav-item .nav-link:hover,
body .sidebar .nav .nav-item .nav-link:hover,
body.light-theme .sidebar .nav .nav-item .nav-link:hover {
    background: rgba(255, 255, 255, 0.15) !important;
    color: #ffffff !important;
}

.sidebar .nav .nav-item .nav-link:hover .menu-icon,
.sidebar .nav .nav-item .nav-link:hover .menu-title,
body .sidebar .nav .nav-item .nav-link:hover .menu-icon,
body .sidebar .nav .nav-item .nav-link:hover .menu-title {
    color: #ffffff !important;
}

/* Submenu hover */
.sidebar .nav .nav-item .sub-menu .nav-item .nav-link:hover {
    background: rgba(255, 255, 255, 0.15) !important;
    color: #ffffff !important;
}

/* Submenu active */
.sidebar .nav .nav-item .sub-menu .nav-item .nav-link.active {
    background: rgba(0, 0, 0, 0.2) !important;
    color: #ffffff !important;
}

/* Category headers */
.sidebar .nav .nav-category,
body .sidebar .nav .nav-category,
body.light-theme .sidebar .nav .nav-category {
    color: rgba(255, 255, 255, 0.6) !important;
    text-transform: uppercase !important;
    font-size: 0.75rem !important;
    font-weight: 600 !important;
    letter-spacing: 0.5px !important;
    padding: 1.25rem 1.5625rem 0.625rem !important;
    margin-top: 0.5rem !important;
}

/* Dropdown Arrow */
.sidebar .nav .nav-item .nav-link .menu-arrow,
body .sidebar .nav .nav-item .nav-link .menu-arrow {
    color: rgba(255, 255, 255, 0.7) !important;
    margin-left: auto !important;
    font-size: 1rem !important;
    transition: transform 0.3s ease !important;
}

.sidebar .nav .nav-item .nav-link[aria-expanded="true"] .menu-arrow {
    transform: rotate(90deg) !important;
}

/* Submenu Styling */
.sidebar .nav .nav-item .sub-menu,
body .sidebar .nav .nav-item .sub-menu {
    background: rgba(0, 0, 0, 0.1) !important;
    padding: 0.5rem 0 !important;
    margin: 0.5rem 0.75rem !important;
    border-radius: 0.5rem !important;
}

.sidebar .nav .nav-item .sub-menu .nav-item,
body .sidebar .nav .nav-item .sub-menu .nav-item {
    padding: 0 !important;
}

.sidebar .nav .nav-item .sub-menu .nav-item .nav-link,
body .sidebar .nav .nav-item .sub-menu .nav-item .nav-link {
    padding: 0.75rem 1.25rem !important;
    color: rgba(255, 255, 255, 0.85) !important;
    font-size: 0.875rem !important;
    display: flex !important;
    align-items: center !important;
    white-space: normal !important;
    word-wrap: break-word !important;
    line-height: 1.4 !important;
}

.sidebar .nav .nav-item .sub-menu .nav-item .nav-link i,
body .sidebar .nav .nav-item .sub-menu .nav-item .nav-link i {
    margin-right: 0.75rem !important;
    font-size: 1rem !important;
    color: rgba(255, 255, 255, 0.7) !important;
    flex-shrink: 0 !important;
}

.sidebar .nav .nav-category .nav-link,
body .sidebar .nav .nav-category .nav-link {
    padding: 0 !important;
    color: rgba(255, 255, 255, 0.6) !important;
}

/* Menu items spacing and layout */
.sidebar .nav .nav-item.menu-items,
body .sidebar .nav .nav-item.menu-items {
    margin: 0 !important;
}

.sidebar .nav .nav-item.menu-items .nav-link,
body .sidebar .nav .nav-item.menu-items .nav-link {
    align-items: center !important;
    display: flex !important;
    padding: 0.875rem 1.5625rem !important;
    transition: all 0.3s ease !important;
    white-space: nowrap !important;
    height: auto !important;
}

.sidebar .nav .nav-item.menu-items .nav-link .menu-icon,
body .sidebar .nav .nav-item.menu-items .nav-link .menu-icon {
    font-size: 1.125rem !important;
    line-height: 1 !important;
    margin-right: 1.25rem !important;
    width: 1.125rem !important;
    flex-shrink: 0 !important;
}

.sidebar .nav .nav-item.menu-items .nav-link .menu-title,
body .sidebar .nav .nav-item.menu-items .nav-link .menu-title {
    flex-grow: 1 !important;
    font-size: 0.875rem !important;
    font-weight: 400 !important;
    line-height: 1 !important;
}

.sidebar .nav .nav-item.menu-items .nav-link .badge,
body .sidebar .nav .nav-item.menu-items .nav-link .badge {
    margin-left: auto !important;
}

/* Profile section */
.sidebar .nav .nav-item.profile {
    border-bottom: 1px solid rgba(255, 255, 255, 0.2);
}

.sidebar .nav .nav-item.profile .profile-name h5 {
    color: #ffffff !important;
}

.sidebar .nav .nav-item.profile .profile-name span {
    color: rgba(255, 255, 255, 0.7) !important;
}

/* Badges */
.sidebar .badge {
    background-color: #ffffff !important;
    color: #5b67ca !important;
    font-weight: 600 !important;
}

.sidebar .badge-warning {
    background-color: #ffffff !important;
    color: #5b67ca !important;
    font-weight: 600 !important;
}

.sidebar .badge-success {
    background-color: #ffffff !important;
    color: #5b67ca !important;
    font-weight: 600 !important;
}

.sidebar .badge-danger {
    background-color: #ffffff !important;
    color: #5b67ca !important;
    font-weight: 600 !important;
}

.sidebar .badge-info {
    background-color: #ffffff !important;
    color: #5b67ca !important;
    font-weight: 600 !important;
}

.sidebar .badge-primary {
    background-color: #ffffff !important;
    color: #5b67ca !important;
    font-weight: 600 !important;
}
</style>
