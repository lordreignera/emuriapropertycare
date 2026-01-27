{{-- Sidebar Navigation --}}
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
            <span>
              @if(Auth::user()->hasRole('Super Admin'))
                Super Admin
              @elseif(Auth::user()->hasRole('Administrator'))
                Administrator
              @elseif(Auth::user()->hasRole('Inspector'))
                Inspector
              @elseif(Auth::user()->hasRole('Project Manager'))
                Project Manager
              @elseif(Auth::user()->hasRole('Technician'))
                Technician
              @elseif(Auth::user()->hasRole('Finance'))
                Finance
              @elseif(Auth::user()->client && Auth::user()->client->subscription)
                {{ Auth::user()->client->subscription->tier->name }}
              @else
                Member
              @endif
            </span>
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

    {{-- Property Management Submenu --}}
    @can('view-all-properties')
    <li class="nav-item menu-items {{ request()->routeIs('properties.*') ? 'active' : '' }}">
      <a class="nav-link" data-bs-toggle="collapse" href="#property-management" aria-expanded="{{ request()->routeIs('properties.*') ? 'true' : 'false' }}" aria-controls="property-management">
        <span class="menu-icon">
          <i class="mdi mdi-home-modern"></i>
        </span>
        <span class="menu-title">Property Management</span>
        <i class="menu-arrow"></i>
      </a>
      <div class="collapse {{ request()->routeIs('properties.*') ? 'show' : '' }}" id="property-management">
        <ul class="nav flex-column sub-menu">
          <li class="nav-item">
            <a class="nav-link {{ request()->get('status') == 'awaiting_inspection' ? 'active' : '' }}" href="{{ route('properties.index') }}?status=awaiting_inspection">
              <i class="mdi mdi-calendar-check"></i> Scheduled & Paid
              @php
                  // Properties with scheduled and paid inspections
                  $scheduledPaidCount = \App\Models\Inspection::where('inspection_fee_status', 'paid')
                      ->where('status', 'scheduled')
                      ->whereNull('inspector_id')
                      ->count();
              @endphp
              @if($scheduledPaidCount > 0)
              <span class="badge badge-pill badge-success ms-auto">{{ $scheduledPaidCount }}</span>
              @endif
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link {{ request()->get('status') == 'active' ? 'active' : '' }}" href="{{ route('properties.index') }}?status=active">
              <i class="mdi mdi-home-alert"></i> Not Scheduled
              @php
                  // Properties without inspections yet (newly added by clients)
                  $unscheduledCount = \App\Models\Property::where('status', 'active')
                      ->whereDoesntHave('inspections')
                      ->count();
              @endphp
              @if($unscheduledCount > 0)
              <span class="badge badge-pill badge-warning ms-auto">{{ $unscheduledCount }}</span>
              @endif
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link {{ !request()->has('status') ? 'active' : '' }}" href="{{ route('properties.index') }}">
              <i class="mdi mdi-view-list"></i> All Properties
            </a>
          </li>
        </ul>
      </div>
    </li>
    @endcan

    {{-- Inspection Workflow Submenu - For Inspectors, Project Managers, and Admins --}}
    @if(Auth::user()->hasRole(['Inspector', 'Project Manager', 'Super Admin', 'Administrator']) || Auth::user()->can('view-inspections'))
    <li class="nav-item menu-items {{ request()->routeIs('inspections.*') ? 'active' : '' }}">
      <a class="nav-link" data-bs-toggle="collapse" href="#inspection-workflow" aria-expanded="{{ request()->routeIs('inspections.*') ? 'true' : 'false' }}" aria-controls="inspection-workflow">
        <span class="menu-icon">
          <i class="mdi mdi-clipboard-check"></i>
        </span>
        <span class="menu-title">Inspection Workflow</span>
        <i class="menu-arrow"></i>
      </a>
      <div class="collapse {{ request()->routeIs('inspections.*') ? 'show' : '' }}" id="inspection-workflow">
        <ul class="nav flex-column sub-menu">
          @php
              $user = Auth::user();
              // Calculate role-specific counts
              if ($user->hasRole('Inspector')) {
                  $scheduledInspectionsCount = \App\Models\Property::where('inspector_id', $user->id)
                      ->where('status', 'awaiting_inspection')
                      ->whereNotNull('inspection_scheduled_at')
                      ->count();
                  $unscheduledInspectionsCount = \App\Models\Property::where('inspector_id', $user->id)
                      ->where('status', 'awaiting_inspection')
                      ->whereNull('inspection_scheduled_at')
                      ->count();
              } elseif ($user->hasRole('Project Manager')) {
                  $scheduledInspectionsCount = \App\Models\Property::where('project_manager_id', $user->id)
                      ->where('status', 'awaiting_inspection')
                      ->whereNotNull('inspection_scheduled_at')
                      ->count();
                  $unscheduledInspectionsCount = \App\Models\Property::where('project_manager_id', $user->id)
                      ->where('status', 'awaiting_inspection')
                      ->whereNull('inspection_scheduled_at')
                      ->count();
              } else {
                  // Admin sees all
                  $propertyIds = \App\Models\Property::where('status', 'approved')->pluck('id');
                  $projectIds = \App\Models\Project::whereIn('property_id', $propertyIds)->pluck('id');
                  $scheduledInspectionsCount = \App\Models\Inspection::whereIn('project_id', $projectIds)
                      ->where('status', 'scheduled')->count();
                  $inProgressInspectionsCount = \App\Models\Inspection::whereIn('project_id', $projectIds)
                      ->where('status', 'in_progress')->count();
              }
          @endphp
          @if(Auth::user()->hasRole(['Super Admin', 'Administrator']))
          {{-- Admin sees full inspection workflow --}}
          <li class="nav-item">
            <a class="nav-link {{ request()->get('status') == 'scheduled' ? 'active' : '' }}" href="{{ route('inspections.index') }}?status=scheduled">
              <i class="mdi mdi-calendar-clock"></i> Awaiting Inspection
              @if($scheduledInspectionsCount > 0)
              <span class="badge badge-pill badge-info ms-auto">{{ $scheduledInspectionsCount }}</span>
              @endif
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link {{ request()->get('status') == 'in_progress' ? 'active' : '' }}" href="{{ route('inspections.index') }}?status=in_progress">
              <i class="mdi mdi-clipboard-text"></i> In Progress
              @if($inProgressInspectionsCount > 0)
              <span class="badge badge-pill badge-primary ms-auto">{{ $inProgressInspectionsCount }}</span>
              @endif
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link {{ request()->get('status') == 'completed' ? 'active' : '' }}" href="{{ route('inspections.index') }}?status=completed">
              <i class="mdi mdi-clipboard-check"></i> Completed
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link {{ !request()->has('status') ? 'active' : '' }}" href="{{ route('inspections.index') }}">
              <i class="mdi mdi-view-list"></i> All Inspections
            </a>
          </li>
          @else
          {{-- Inspector/PM sees simplified menu --}}
          <li class="nav-item">
            <a class="nav-link {{ request()->get('status') == 'scheduled' ? 'active' : '' }}" href="{{ route('inspections.index') }}?status=scheduled">
              <i class="mdi mdi-calendar-clock"></i> Scheduled
              @if($scheduledInspectionsCount > 0)
              <span class="badge badge-pill badge-success ms-auto">{{ $scheduledInspectionsCount }}</span>
              @endif
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link {{ request()->get('status') == 'unscheduled' ? 'active' : '' }}" href="{{ route('inspections.index') }}?status=unscheduled">
              <i class="mdi mdi-calendar-alert"></i> Unscheduled
              @if($unscheduledInspectionsCount > 0)
              <span class="badge badge-pill badge-warning ms-auto">{{ $unscheduledInspectionsCount }}</span>
              @endif
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link {{ !request()->has('status') ? 'active' : '' }}" href="{{ route('inspections.index') }}">
              <i class="mdi mdi-view-list"></i> All Inspections
            </a>
          </li>
          @endif
        </ul>
      </div>
    </li>
    @endif

    {{-- Project Management Submenu - For Technicians, Project Managers, and Admins --}}
    @if(Auth::user()->hasRole(['Technician', 'Project Manager', 'Super Admin', 'Administrator']) || Auth::user()->can('view-all-projects'))
    <li class="nav-item menu-items {{ request()->routeIs('projects.*') || request()->routeIs('work-logs.*') || request()->routeIs('milestones.*') ? 'active' : '' }}">
      <a class="nav-link" data-bs-toggle="collapse" href="#project-management" aria-expanded="{{ request()->routeIs('projects.*') || request()->routeIs('work-logs.*') || request()->routeIs('milestones.*') ? 'true' : 'false' }}" aria-controls="project-management">
        <span class="menu-icon">
          <i class="mdi mdi-briefcase"></i>
        </span>
        <span class="menu-title">Project Management</span>
        <i class="menu-arrow"></i>
      </a>
      <div class="collapse {{ request()->routeIs('projects.*') || request()->routeIs('work-logs.*') || request()->routeIs('milestones.*') ? 'show' : '' }}" id="project-management">
        <ul class="nav flex-column sub-menu">
          @if(Auth::user()->hasRole(['Project Manager', 'Super Admin', 'Administrator']) || Auth::user()->can('view-all-projects'))
          <li class="nav-item">
            <a class="nav-link {{ request()->get('has_scope') == 'true' ? 'active' : '' }}" href="{{ route('projects.index') }}?has_scope=true">
              <i class="mdi mdi-file-document-edit"></i> Scope of Work & Quotes
              @php
                  $projectsWithScopeCount = \App\Models\Project::whereHas('scopeOfWorks')->count();
              @endphp
              @if($projectsWithScopeCount > 0)
              <span class="badge badge-pill badge-info ms-auto">{{ $projectsWithScopeCount }}</span>
              @endif
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('projects.index') && request()->get('view') == 'scheduling' ? 'active' : '' }}" href="{{ route('projects.index') }}?view=scheduling">
              <i class="mdi mdi-calendar-clock"></i> Project Scheduling
            </a>
          </li>
          @endif
          <li class="nav-item">
            <a class="nav-link {{ request()->get('status') == 'active' ? 'active' : '' }}" href="{{ route('projects.index') }}?status=active">
              <i class="mdi mdi-briefcase-check"></i> Active Projects
              @php
                  $user = Auth::user();
                  if ($user->hasRole('Technician')) {
                      $activeProjectsCount = \App\Models\Project::where('assigned_to', $user->id)
                          ->where('status', 'active')->count();
                  } else {
                      $activeProjectsCount = \App\Models\Project::where('status', 'active')->count();
                  }
              @endphp
              @if($activeProjectsCount > 0)
              <span class="badge badge-pill badge-success ms-auto">{{ $activeProjectsCount }}</span>
              @endif
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('work-logs.*') ? 'active' : '' }}" href="{{ route('work-logs.index') }}">
              <i class="mdi mdi-notebook"></i> Work Logs & Progress
            </a>
          </li>
          @if(Auth::user()->hasRole(['Project Manager', 'Super Admin', 'Administrator']) || Auth::user()->can('view-all-projects'))
          <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('milestones.*') ? 'active' : '' }}" href="{{ route('milestones.index') }}">
              <i class="mdi mdi-flag-checkered"></i> Milestones & Budget
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('change-orders.*') ? 'active' : '' }}" href="{{ route('change-orders.index') }}">
              <i class="mdi mdi-swap-horizontal"></i> Change Orders
            </a>
          </li>
          @endif
          <li class="nav-item">
            <a class="nav-link {{ !request()->has('status') && !request()->has('has_scope') && !request()->has('view') ? 'active' : '' }}" href="{{ route('projects.index') }}">
              <i class="mdi mdi-view-list"></i> All Projects
            </a>
          </li>
        </ul>
      </div>
    </li>
    @endif

    {{-- Billing & Financial Management Submenu --}}
    @can('view-invoices')
    <li class="nav-item menu-items {{ request()->routeIs('invoices.*') || request()->routeIs('budgets.*') ? 'active' : '' }}">
      <a class="nav-link" data-bs-toggle="collapse" href="#billing" aria-expanded="{{ request()->routeIs('invoices.*') || request()->routeIs('budgets.*') ? 'true' : 'false' }}" aria-controls="billing">
        <span class="menu-icon">
          <i class="mdi mdi-cash-multiple"></i>
        </span>
        <span class="menu-title">Billing & Finance</span>
        <i class="menu-arrow"></i>
      </a>
      <div class="collapse {{ request()->routeIs('invoices.*') || request()->routeIs('budgets.*') ? 'show' : '' }}" id="billing">
        <ul class="nav flex-column sub-menu">
          <li class="nav-item">
            <a class="nav-link {{ request()->get('status') == 'pending' ? 'active' : '' }}" href="{{ route('invoices.index') }}?status=pending">
              <i class="mdi mdi-file-document-alert"></i> Unpaid Invoices
              @php
                  $unpaidInvoicesCount = \App\Models\Invoice::where('status', 'pending')->count();
              @endphp
              @if($unpaidInvoicesCount > 0)
              <span class="badge badge-pill badge-danger ms-auto">{{ $unpaidInvoicesCount }}</span>
              @endif
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('invoices.index') && !request()->has('status') ? 'active' : '' }}" href="{{ route('invoices.index') }}">
              <i class="mdi mdi-file-document"></i> All Invoices
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('budgets.*') ? 'active' : '' }}" href="{{ route('budgets.index') }}">
              <i class="mdi mdi-chart-line"></i> Budget Management
            </a>
          </li>
        </ul>
      </div>
    </li>
    @endcan
    
    {{-- Communications & Reports --}}
    @can('view-communications')
    <li class="nav-item menu-items {{ request()->routeIs('communications.*') ? 'active' : '' }}">
      <a class="nav-link" href="{{ route('communications.index') }}">
        <span class="menu-icon">
          <i class="mdi mdi-message-text"></i>
        </span>
        <span class="menu-title">Communications</span>
      </a>
    </li>
    @endcan
    
    @can('view-reports')
    <li class="nav-item menu-items {{ request()->routeIs('reports.*') || request()->routeIs('savings.*') ? 'active' : '' }}">
      <a class="nav-link" data-bs-toggle="collapse" href="#reports-savings" aria-expanded="{{ request()->routeIs('reports.*') || request()->routeIs('savings.*') ? 'true' : 'false' }}" aria-controls="reports-savings">
        <span class="menu-icon">
          <i class="mdi mdi-chart-areaspline"></i>
        </span>
        <span class="menu-title">Reports & Savings</span>
        <i class="menu-arrow"></i>
      </a>
      <div class="collapse {{ request()->routeIs('reports.*') || request()->routeIs('savings.*') ? 'show' : '' }}" id="reports-savings">
        <ul class="nav flex-column sub-menu">
          <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('reports.index') ? 'active' : '' }}" href="{{ route('reports.index') }}">
              <i class="mdi mdi-file-chart"></i> Performance Reports
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('savings.*') ? 'active' : '' }}" href="{{ route('savings.index') }}">
              <i class="mdi mdi-cash-usd"></i> Cost & Savings Analysis
            </a>
          </li>
        </ul>
      </div>
    </li>
    @endcan

    {{-- Admin Section --}}
    @role('Super Admin|Administrator')
    <li class="nav-item nav-category">
      <span class="nav-link">Admin</span>
    </li>

    {{-- Access Control Submenu --}}
    <li class="nav-item menu-items {{ request()->routeIs('admin.users.*') || request()->routeIs('admin.roles.*') || request()->routeIs('admin.permissions.*') ? 'active' : '' }}">
      <a class="nav-link" data-bs-toggle="collapse" href="#access-control" aria-expanded="{{ request()->routeIs('admin.users.*') || request()->routeIs('admin.roles.*') || request()->routeIs('admin.permissions.*') ? 'true' : 'false' }}" aria-controls="access-control">
        <span class="menu-icon">
          <i class="mdi mdi-shield-account"></i>
        </span>
        <span class="menu-title">Access Control</span>
        <i class="menu-arrow"></i>
      </a>
      <div class="collapse {{ request()->routeIs('admin.users.*') || request()->routeIs('admin.roles.*') || request()->routeIs('admin.permissions.*') ? 'show' : '' }}" id="access-control">
        <ul class="nav flex-column sub-menu">
          <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}" href="{{ route('admin.users.index') }}">
              <i class="mdi mdi-account-multiple"></i> User Management
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.roles.*') ? 'active' : '' }}" href="{{ route('admin.roles.index') }}">
              <i class="mdi mdi-account-key"></i> Role Management
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.permissions.*') ? 'active' : '' }}" href="{{ route('admin.permissions.index') }}">
              <i class="mdi mdi-lock"></i> Permission Management
            </a>
          </li>
        </ul>
      </div>
    </li>

    {{-- Client & Subscription Management --}}

    {{-- CPI Pricing System Management --}}
    <li class="nav-item menu-items {{ 
        request()->routeIs('admin.pricing-packages.*') || 
        request()->routeIs('admin.property-types.*') ||
        request()->routeIs('admin.cpi-bands.*') ||
        request()->routeIs('admin.cpi-multipliers.*') ||
        request()->routeIs('admin.cpi-domains.*') ||
        request()->routeIs('admin.supply-materials.*') ||
        request()->routeIs('admin.age-brackets.*') ||
        request()->routeIs('admin.containment-categories.*') ||
        request()->routeIs('admin.crawl-access.*') ||
        request()->routeIs('admin.roof-access.*') ||
        request()->routeIs('admin.equipment-requirements.*') ||
        request()->routeIs('admin.complexity-categories.*') ||
        request()->routeIs('admin.residential-tiers.*') ||
        request()->routeIs('admin.commercial-settings.*') ||
        request()->routeIs('admin.mixed-use-settings.*') ||
        request()->routeIs('admin.pricing-config.*')
        ? 'active' : '' }}">
      <a class="nav-link" data-bs-toggle="collapse" href="#cpi-pricing-system" aria-expanded="{{ 
        request()->routeIs('admin.pricing-packages.*') || 
        request()->routeIs('admin.property-types.*') ||
        request()->routeIs('admin.cpi-bands.*') ||
        request()->routeIs('admin.cpi-multipliers.*') ||
        request()->routeIs('admin.cpi-domains.*') ||
        request()->routeIs('admin.supply-materials.*') ||
        request()->routeIs('admin.age-brackets.*') ||
        request()->routeIs('admin.containment-categories.*') ||
        request()->routeIs('admin.crawl-access.*') ||
        request()->routeIs('admin.roof-access.*') ||
        request()->routeIs('admin.equipment-requirements.*') ||
        request()->routeIs('admin.complexity-categories.*') ||
        request()->routeIs('admin.residential-tiers.*') ||
        request()->routeIs('admin.commercial-settings.*') ||
        request()->routeIs('admin.mixed-use-settings.*') ||
        request()->routeIs('admin.pricing-config.*')
        ? 'true' : 'false' }}" aria-controls="cpi-pricing-system">
        <span class="menu-icon">
          <i class="mdi mdi-calculator"></i>
        </span>
        <span class="menu-title">CPI Pricing System</span>
        <i class="menu-arrow"></i>
      </a>
      <div class="collapse {{ 
        request()->routeIs('admin.pricing-packages.*') || 
        request()->routeIs('admin.property-types.*') ||
        request()->routeIs('admin.cpi-bands.*') ||
        request()->routeIs('admin.cpi-multipliers.*') ||
        request()->routeIs('admin.cpi-domains.*') ||
        request()->routeIs('admin.supply-materials.*') ||
        request()->routeIs('admin.age-brackets.*') ||
        request()->routeIs('admin.containment-categories.*') ||
        request()->routeIs('admin.crawl-access.*') ||
        request()->routeIs('admin.roof-access.*') ||
        request()->routeIs('admin.equipment-requirements.*') ||
        request()->routeIs('admin.complexity-categories.*') ||
        request()->routeIs('admin.residential-tiers.*') ||
        request()->routeIs('admin.commercial-settings.*') ||
        request()->routeIs('admin.mixed-use-settings.*') ||
        request()->routeIs('admin.pricing-config.*')
        ? 'show' : '' }}" id="cpi-pricing-system">
        <ul class="nav flex-column sub-menu">
          {{-- Package & Property Settings --}}
          <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.pricing-packages.*') ? 'active' : '' }}" href="{{ route('admin.pricing-packages.index') }}">
              <i class="mdi mdi-package-variant-closed"></i> Pricing Packages
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.property-types.*') ? 'active' : '' }}" href="{{ route('admin.property-types.index') }}">
              <i class="mdi mdi-home-variant"></i> Property Types
            </a>
          </li>
          
          {{-- CPI Band Settings --}}
          <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.cpi-bands.*') ? 'active' : '' }}" href="{{ route('admin.cpi-bands.index') }}">
              <i class="mdi mdi-chart-box"></i> CPI Band Ranges
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.cpi-multipliers.*') ? 'active' : '' }}" href="{{ route('admin.cpi-multipliers.index') }}">
              <i class="mdi mdi-percent"></i> CPI Multipliers
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.cpi-domains.*') ? 'active' : '' }}" href="{{ route('admin.cpi-domains.index') }}">
              <i class="mdi mdi-view-module"></i> CPI Domains
            </a>
          </li>
          
          {{-- Lookup Tables --}}
          <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.supply-materials.*') ? 'active' : '' }}" href="{{ route('admin.supply-materials.index') }}">
              <i class="mdi mdi-pipe"></i> Supply Materials
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.age-brackets.*') ? 'active' : '' }}" href="{{ route('admin.age-brackets.index') }}">
              <i class="mdi mdi-calendar-range"></i> Age Brackets
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.containment-categories.*') ? 'active' : '' }}" href="{{ route('admin.containment-categories.index') }}">
              <i class="mdi mdi-checkbox-marked-circle"></i> Containment Categories
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.crawl-access.*') ? 'active' : '' }}" href="{{ route('admin.crawl-access.index') }}">
              <i class="mdi mdi-arrow-down-bold"></i> Crawl Space Access
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.roof-access.*') ? 'active' : '' }}" href="{{ route('admin.roof-access.index') }}">
              <i class="mdi mdi-arrow-up-bold"></i> Roof Access
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.equipment-requirements.*') ? 'active' : '' }}" href="{{ route('admin.equipment-requirements.index') }}">
              <i class="mdi mdi-toolbox"></i> Equipment Requirements
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.complexity-categories.*') ? 'active' : '' }}" href="{{ route('admin.complexity-categories.index') }}">
              <i class="mdi mdi-puzzle"></i> Complexity Categories
            </a>
          </li>
          
          {{-- Size Settings --}}
          <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.residential-tiers.*') ? 'active' : '' }}" href="{{ route('admin.residential-tiers.index') }}">
              <i class="mdi mdi-home-group"></i> Residential Size Tiers
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.commercial-settings.*') ? 'active' : '' }}" href="{{ route('admin.commercial-settings.index') }}">
              <i class="mdi mdi-office-building"></i> Commercial Size Settings
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.mixed-use-settings.*') ? 'active' : '' }}" href="{{ route('admin.mixed-use-settings.index') }}">
              <i class="mdi mdi-home-city"></i> Mixed-Use Settings
            </a>
          </li>
          
          {{-- System Configuration --}}
          <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.pricing-config.*') ? 'active' : '' }}" href="{{ route('admin.pricing-config.index') }}">
              <i class="mdi mdi-cog"></i> System Configuration
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.reactive-costs.*') ? 'active' : '' }}" href="{{ route('admin.reactive-costs.index') }}">
              <i class="mdi mdi-currency-usd"></i> Reactive Cost Assumptions
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.stewardship-loss.*') ? 'active' : '' }}" href="{{ route('admin.stewardship-loss.index') }}">
              <i class="mdi mdi-shield-check"></i> Stewardship Loss Reduction
            </a>
          </li>
        </ul>
      </div>
    </li>

    <li class="nav-item menu-items {{ request()->routeIs('admin.reports.*') ? 'active' : '' }}">
      <a class="nav-link" href="{{ route('admin.reports.index') }}">
        <span class="menu-icon">
          <i class="mdi mdi-chart-bar"></i>
        </span>
        <span class="menu-title">Reports</span>
      </a>
    </li>
    @endrole

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
