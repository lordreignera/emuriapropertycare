{{-- Top Navbar --}}
@php
  $unreadNotificationsCount = auth()->user()->unreadNotifications->count();
  $unreadNotificationsLabel = $unreadNotificationsCount > 99 ? '99+' : (string) $unreadNotificationsCount;
  $notificationsIndexRoute = auth()->user()->hasRole('Client') ? route('client.notifications.index') : route('notifications.index');
  $notificationsOpenRouteName = auth()->user()->hasRole('Client') ? 'client.notifications.open' : 'notifications.open';
@endphp
<nav class="navbar p-0 fixed-top d-flex flex-row">
  <div class="navbar-brand-wrapper d-flex d-lg-none align-items-center justify-content-center">
    <a class="navbar-brand brand-logo-mini" href="{{ route('dashboard') }}" aria-label="ETOGO dashboard">
      <img src="{{ asset('etogo%20log.png') }}" alt="ETOGO" class="admin-navbar-logo-mini">
    </a>
  </div>
  <div class="navbar-menu-wrapper admin-topbar flex-grow d-flex align-items-center">
    <button class="navbar-toggler navbar-toggler align-self-center admin-topbar-toggle" type="button" data-toggle="minimize">
      <span class="mdi mdi-menu"></span>
    </button>
    <ul class="navbar-nav w-100">
      <li class="nav-item w-100">
        <form class="nav-link mt-2 mt-md-0 d-none d-lg-flex search admin-topbar-search" action="{{ route('search') }}" method="GET">
          <i class="mdi mdi-magnify"></i>
          <input type="text" class="form-control" name="q" placeholder="Search properties, projects, invoices...">
        </form>
      </li>
    </ul>
    <ul class="navbar-nav navbar-nav-right">
      {{-- Quick Action --}}
      @can('create properties')
      <li class="nav-item dropdown d-none d-lg-block">
        <a class="nav-link btn create-new-button admin-quick-button" id="createbuttonDropdown" data-bs-toggle="dropdown" aria-expanded="false" href="#">
          <i class="mdi mdi-plus-circle"></i>
          <span>Quick Actions</span>
        </a>
        <div class="dropdown-menu dropdown-menu-right navbar-dropdown preview-list" aria-labelledby="createbuttonDropdown">
          <h6 class="p-3 mb-0">Quick Actions</h6>
          <div class="dropdown-divider"></div>
          <a class="dropdown-item preview-item" href="{{ route('properties.create') }}">
            <div class="preview-thumbnail">
              <div class="preview-icon bg-dark rounded-circle">
                <i class="mdi mdi-home-plus text-primary"></i>
              </div>
            </div>
            <div class="preview-item-content">
              <p class="preview-subject ellipsis mb-1">Add Property</p>
            </div>
          </a>
          <div class="dropdown-divider"></div>
          <a class="dropdown-item preview-item" href="{{ route('inspections.create') }}">
            <div class="preview-thumbnail">
              <div class="preview-icon bg-dark rounded-circle">
                <i class="mdi mdi-clipboard-check text-info"></i>
              </div>
            </div>
            <div class="preview-item-content">
              <p class="preview-subject ellipsis mb-1">Schedule Inspection</p>
            </div>
          </a>
          <div class="dropdown-divider"></div>
          <a class="dropdown-item preview-item" href="{{ route('projects.create') }}">
            <div class="preview-thumbnail">
              <div class="preview-icon bg-dark rounded-circle">
                <i class="mdi mdi-briefcase text-warning"></i>
              </div>
            </div>
            <div class="preview-item-content">
              <p class="preview-subject ellipsis mb-1">Create Project</p>
            </div>
          </a>
        </div>
      </li>
      @endcan

      {{-- Visit Website --}}
      <li class="nav-item nav-settings d-none d-lg-block">
        <a class="nav-link admin-topbar-icon" href="/home/index.html" target="_blank" title="Visit Website">
          <i class="mdi mdi-view-grid"></i>
        </a>
      </li>

      {{-- Messages placeholder --}}
      <li class="nav-item nav-settings d-none d-lg-block">
        <a class="nav-link admin-topbar-icon" href="#" title="Messages">
          <i class="mdi mdi-email"></i>
        </a>
      </li>

      {{-- Notifications --}}
      <li class="nav-item dropdown border-left">
        <a class="nav-link count-indicator dropdown-toggle notification-attention {{ $unreadNotificationsCount > 0 ? 'has-unread' : '' }}" id="notificationDropdown" href="#" data-bs-toggle="dropdown" aria-label="Notifications">
          <span class="notification-bell-wrap">
            <i class="mdi mdi-bell notification-bell-icon"></i>
            @if($unreadNotificationsCount > 0)
              <span class="notification-count-pill">{{ $unreadNotificationsLabel }}</span>
            @endif
          </span>
        </a>
        <div class="dropdown-menu dropdown-menu-right navbar-dropdown preview-list" aria-labelledby="notificationDropdown">
          <h6 class="p-3 mb-0">Notifications</h6>
          <div class="dropdown-divider"></div>
          @forelse(auth()->user()->unreadNotifications->take(5) as $notification)
          <a href="{{ route($notificationsOpenRouteName, $notification->id) }}" class="dropdown-item preview-item">
            <div class="preview-thumbnail">
              <div class="preview-icon bg-dark rounded-circle">
                <i class="mdi mdi-bell text-info"></i>
              </div>
            </div>
            <div class="preview-item-content">
              <p class="preview-subject mb-1">{{ $notification->data['title'] ?? 'Notification' }}</p>
              <p class="text-muted ellipsis mb-0">{{ $notification->data['message'] ?? '' }}</p>
            </div>
          </a>
          <div class="dropdown-divider"></div>
          @empty
          <p class="p-3 mb-0 text-center text-muted">No new notifications</p>
          @endforelse
          <a href="{{ $notificationsIndexRoute }}" class="p-3 mb-0 text-center d-block">See all notifications</a>
        </div>
      </li>

      {{-- Profile Dropdown --}}
      <li class="nav-item dropdown admin-profile-nav">
        <a class="nav-link admin-profile-trigger" id="profileDropdown" href="#" data-bs-toggle="dropdown" aria-expanded="false">
          <span class="admin-profile-initials">{{ strtoupper(substr(Auth::user()->name ?? 'U', 0, 1)) }}</span>
          <span class="admin-profile-name d-none d-md-inline-block">{{ Auth::user()->name }}</span>
          <i class="mdi mdi-chevron-down"></i>
        </a>
        <div class="dropdown-menu dropdown-menu-right admin-profile-menu" aria-labelledby="profileDropdown">
          <div class="admin-profile-card">
            <img src="{{ Auth::user()->profile_photo_url }}" alt="{{ Auth::user()->name }}">
            <div>
              <div class="admin-profile-card-name">{{ Auth::user()->name }}</div>
              <div class="admin-profile-card-email">{{ Auth::user()->email }}</div>
            </div>
          </div>
          <a class="admin-profile-item" href="{{ route('profile.settings') }}">
            <i class="mdi mdi-account-outline text-primary"></i>
            <span>My Profile</span>
          </a>
          <a class="admin-profile-item" href="{{ route('profile.settings') }}">
            <i class="mdi mdi-cog-outline text-success"></i>
            <span>Settings</span>
          </a>
          <a class="admin-profile-item" href="/home/index.html#contact" target="_blank">
            <i class="mdi mdi-help-circle-outline text-warning"></i>
            <span>Help &amp; Support</span>
          </a>
          <div class="admin-profile-divider"></div>
          <form method="POST" action="{{ route('logout') }}" class="m-0">
            @csrf
            <button type="submit" class="admin-profile-item admin-profile-logout">
              <i class="mdi mdi-logout text-danger"></i>
              <span>Logout</span>
            </button>
          </form>
        </div>
      </li>
    </ul>
    <button class="navbar-toggler navbar-toggler-right d-lg-none align-self-center" type="button" data-toggle="offcanvas" aria-label="Toggle navigation">
      <span class="mdi mdi-menu" style="font-size:1.5rem;"></span>
    </button>
  </div>
</nav>

<style>
.admin-topbar {
  height: 72px !important;
  background: #ffffff !important;
  border-bottom: 1px solid #e5e7eb !important;
  box-shadow: 0 8px 18px rgba(15, 23, 42, .08) !important;
  padding: 0 22px !important;
}

.admin-topbar-toggle {
  width: 38px !important;
  height: 38px !important;
  border: 1px solid #dbe3ef !important;
  border-radius: 8px !important;
  color: #64748b !important;
  display: inline-flex !important;
  align-items: center !important;
  justify-content: center !important;
  margin-right: 18px !important;
}

.admin-topbar-search {
  position: relative !important;
  max-width: 520px !important;
  margin: 0 !important;
  padding: 0 !important;
}

.admin-topbar-search i {
  position: absolute !important;
  left: 14px !important;
  top: 50% !important;
  transform: translateY(-50%) !important;
  color: #0f172a !important;
  font-size: 1rem !important;
  z-index: 2 !important;
}

.admin-topbar-search .form-control {
  height: 42px !important;
  padding-left: 46px !important;
  border: 1px solid #cfd8e3 !important;
  border-radius: 7px !important;
  background: #ffffff !important;
  color: #172033 !important;
  box-shadow: 0 0 0 3px rgba(37, 99, 235, .035) !important;
}

.admin-quick-button {
  height: 38px !important;
  min-width: 176px !important;
  display: inline-flex !important;
  align-items: center !important;
  justify-content: center !important;
  gap: .45rem !important;
  background: #2458d6 !important;
  color: #ffffff !important;
  border-radius: 6px !important;
  font-weight: 800 !important;
  text-transform: uppercase !important;
  letter-spacing: 0 !important;
  padding: 0 18px !important;
  box-shadow: none !important;
}

.admin-quick-button:hover,
.admin-quick-button:focus {
  background: #1f4fc4 !important;
  color: #ffffff !important;
}

.admin-topbar-icon {
  width: 42px !important;
  height: 42px !important;
  margin: 0 6px !important;
  display: inline-flex !important;
  align-items: center !important;
  justify-content: center !important;
  border-radius: 999px !important;
  background: #f5f7fb !important;
  border: 1px solid #e6edf5 !important;
  color: #0f172a !important;
}

.admin-topbar-icon i {
  font-size: 1.25rem !important;
}

.notification-attention {
  width: 42px !important;
  height: 42px !important;
  margin: 0 6px !important;
  padding: 0 !important;
  display: inline-flex !important;
  align-items: center !important;
  justify-content: center !important;
  border-radius: 999px !important;
  background: #f5f7fb !important;
  border: 1px solid #e6edf5 !important;
}

.notification-bell-wrap {
  position: relative;
  display: inline-flex;
  align-items: center;
  justify-content: center;
}

.notification-bell-icon {
  font-size: 1.35rem !important;
  color: #ef4444 !important;
  filter: drop-shadow(0 3px 8px rgba(239, 68, 68, 0.35));
}

.notification-attention.has-unread .notification-bell-icon {
  animation: notificationPulse 1.8s ease-in-out infinite;
}

.notification-count-pill {
  position: absolute;
  top: -0.55rem;
  right: -0.95rem;
  min-width: 1.55rem;
  height: 1.55rem;
  padding: 0 0.35rem;
  border-radius: 999px;
  background: linear-gradient(135deg, #ef4444, #b91c1c);
  color: #ffffff;
  font-size: 0.75rem;
  font-weight: 800;
  line-height: 1.55rem;
  text-align: center;
  box-shadow: 0 6px 16px rgba(185, 28, 28, 0.35);
}

.admin-profile-nav {
  margin-left: 10px !important;
}

.admin-profile-trigger {
  min-width: 184px !important;
  height: 48px !important;
  display: inline-flex !important;
  align-items: center !important;
  justify-content: center !important;
  gap: .65rem !important;
  padding: 0 15px !important;
  border: 1px solid #e2e8f0 !important;
  border-radius: 999px !important;
  background: #f8fafc !important;
  color: #172033 !important;
  font-weight: 700 !important;
}

.admin-profile-initials {
  width: 32px !important;
  height: 32px !important;
  display: inline-flex !important;
  align-items: center !important;
  justify-content: center !important;
  border-radius: 999px !important;
  background: #eef5ff !important;
  border: 1px solid #cddcf0 !important;
  color: #2458d6 !important;
  font-size: .85rem !important;
  font-weight: 800 !important;
}

.admin-profile-name {
  max-width: 145px !important;
  overflow: hidden !important;
  text-overflow: ellipsis !important;
  white-space: nowrap !important;
}

.admin-profile-trigger .mdi-chevron-down {
  color: #475467 !important;
  font-size: 1.1rem !important;
}

.admin-profile-menu {
  width: 300px !important;
  padding: 0 !important;
  border: 1px solid #e5e7eb !important;
  border-radius: 8px !important;
  box-shadow: 0 18px 38px rgba(15, 23, 42, .16) !important;
  overflow: hidden !important;
}

.admin-profile-card {
  display: flex !important;
  align-items: center !important;
  gap: 12px !important;
  padding: 16px 18px !important;
  background: #f8fafc !important;
  border-bottom: 1px solid #e5e7eb !important;
}

.admin-profile-card img {
  width: 50px !important;
  height: 50px !important;
  border-radius: 999px !important;
  object-fit: cover !important;
  border: 2px solid #d1f3ef !important;
}

.admin-profile-card-name {
  color: #172033 !important;
  font-size: .95rem !important;
  font-weight: 800 !important;
  line-height: 1.2 !important;
}

.admin-profile-card-email {
  color: #667085 !important;
  font-size: .78rem !important;
  line-height: 1.2 !important;
}

.admin-profile-item {
  width: 100% !important;
  display: flex !important;
  align-items: center !important;
  gap: 14px !important;
  padding: 13px 20px !important;
  border: 0 !important;
  background: #ffffff !important;
  color: #172033 !important;
  text-decoration: none !important;
  font-size: .95rem !important;
  font-weight: 500 !important;
}

.admin-profile-item:hover {
  background: #f8fafc !important;
  color: #172033 !important;
}

.admin-profile-item i {
  width: 20px !important;
  font-size: 1.2rem !important;
}

.admin-profile-divider {
  height: 1px !important;
  background: #e5e7eb !important;
  margin: 0 !important;
}

.admin-profile-logout {
  color: #dc2626 !important;
  cursor: pointer !important;
}

@media (max-width: 991px) {
  .admin-topbar {
    padding: 0 12px !important;
  }

  .admin-profile-trigger {
    min-width: auto !important;
    padding: 0 10px !important;
  }

  .admin-profile-name {
    display: none !important;
  }
}

@keyframes notificationPulse {
  0%, 100% { transform: scale(1); }
  50% { transform: scale(1.12); }
}
</style>
