{{-- Top Navbar --}}
@php
  $unreadNotificationsCount = auth()->user()->unreadNotifications->count();
  $unreadNotificationsLabel = $unreadNotificationsCount > 99 ? '99+' : (string) $unreadNotificationsCount;
  $notificationsIndexRoute = auth()->user()->hasRole('Client') ? route('client.notifications.index') : route('notifications.index');
  $notificationsOpenRouteName = auth()->user()->hasRole('Client') ? 'client.notifications.open' : 'notifications.open';
@endphp
<nav class="navbar p-0 fixed-top d-flex flex-row">
  <div class="navbar-brand-wrapper d-flex d-lg-none align-items-center justify-content-center">
    <a class="navbar-brand brand-logo-mini" href="{{ route('dashboard') }}">
      <span style="color: #FFB800; font-size: 1.5rem; font-weight: 700;">E</span>
    </a>
  </div>
  <div class="navbar-menu-wrapper flex-grow d-flex align-items-stretch">
    <button class="navbar-toggler navbar-toggler align-self-center" type="button" data-toggle="minimize">
      <span class="mdi mdi-menu"></span>
    </button>
    <ul class="navbar-nav w-100">
      <li class="nav-item w-100">
        <form class="nav-link mt-2 mt-md-0 d-none d-lg-flex search" action="{{ route('search') }}" method="GET">
          <input type="text" class="form-control" name="q" placeholder="Search properties, projects, invoices...">
        </form>
      </li>
    </ul>
    <ul class="navbar-nav navbar-nav-right">
      {{-- Quick Action --}}
      @can('create properties')
      <li class="nav-item dropdown d-none d-lg-block">
        <a class="nav-link btn btn-success create-new-button" id="createbuttonDropdown" data-bs-toggle="dropdown" aria-expanded="false" href="#">
          + Add New
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
        <a class="nav-link" href="/home/index.html" target="_blank" title="Visit Website">
          <i class="mdi mdi-web"></i>
        </a>
      </li>

      {{-- Theme Toggle (Light/Dark Mode) --}}
      <li class="nav-item nav-settings d-none d-lg-block">
        <a class="nav-link" href="#" id="theme-toggle">
          <i class="mdi mdi-theme-light-dark"></i>
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
      <li class="nav-item dropdown">
        <a class="nav-link d-flex align-items-center" id="profileDropdown" href="#" data-bs-toggle="dropdown" style="gap: 0.5rem;">
          <img class="img-xs rounded-circle" src="{{ Auth::user()->profile_photo_url }}" alt="{{ Auth::user()->name }}" style="width: 32px; height: 32px;">
          <span class="d-none d-md-inline-block" style="font-weight: 500; color: #1e293b;">{{ Auth::user()->name }}</span>
          <i class="mdi mdi-chevron-down" style="font-size: 1.2rem; color: #64748b;"></i>
        </a>
        <div class="dropdown-menu dropdown-menu-right navbar-dropdown preview-list" aria-labelledby="profileDropdown">
          <h6 class="p-3 mb-0">Profile</h6>
          <div class="dropdown-divider"></div>
          <a class="dropdown-item preview-item" href="{{ route('profile.settings') }}">
            <div class="preview-thumbnail">
              <div class="preview-icon bg-dark rounded-circle">
                <i class="mdi mdi-settings text-success"></i>
              </div>
            </div>
            <div class="preview-item-content">
              <p class="preview-subject mb-1">Settings</p>
            </div>
          </a>
          <div class="dropdown-divider"></div>
          <form method="POST" action="{{ route('logout') }}" class="dropdown-item preview-item" style="padding: 0;">
            @csrf
            <button type="submit" style="border: none; background: none; width: 100%; text-align: left; padding: 0.75rem 1.5rem; display: flex; align-items: center;">
              <div class="preview-thumbnail">
                <div class="preview-icon bg-dark rounded-circle">
                  <i class="mdi mdi-logout text-danger"></i>
                </div>
              </div>
              <div class="preview-item-content">
                <p class="preview-subject mb-1">Logout</p>
              </div>
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
.notification-attention {
  padding: 0.7rem 0.9rem !important;
}

.notification-bell-wrap {
  position: relative;
  display: inline-flex;
  align-items: center;
  justify-content: center;
}

.notification-bell-icon {
  font-size: 1.7rem !important;
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

@keyframes notificationPulse {
  0%, 100% { transform: scale(1); }
  50% { transform: scale(1.12); }
}
</style>
