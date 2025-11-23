# Authentication & Authorization Flow Documentation

**Project:** EMURIA Regenerative Property Care  
**Last Updated:** November 23, 2025  
**Framework:** Laravel 12 + Jetstream + Fortify + Spatie Permission

---

## Table of Contents

1. [Overview](#overview)
2. [Authentication Stack](#authentication-stack)
3. [User Registration Flow](#user-registration-flow)
4. [Login & Session Management](#login--session-management)
5. [Role-Based Access Control (RBAC)](#role-based-access-control-rbac)
6. [Middleware Architecture](#middleware-architecture)
7. [Dashboard Routing Logic](#dashboard-routing-logic)
8. [Permission System](#permission-system)
9. [Business Model - Free Access](#business-model---free-access)
10. [Security Features](#security-features)

---

## Overview

The EMURIA Property Care system uses a **multi-layered authentication and authorization system** that combines:

- **Laravel Fortify** for authentication (login, registration, password reset)
- **Laravel Jetstream** for team management and UI scaffolding
- **Spatie Laravel Permission** for role-based access control (RBAC)
- **Custom Middleware** for subscription checks and access control

### Key Principles

✅ **Free Registration** - All clients can register without payment  
✅ **Role-Based Dashboards** - Different interfaces for Admins, Staff, and Clients  
✅ **Permission-Based Actions** - Granular control over what users can do  
✅ **Post-Inspection Payment** - Subscription required only after custom product offer  

---

## Authentication Stack

### 1. Laravel Fortify

**Purpose:** Handles authentication logic without dictating UI

**Configuration:** `config/fortify.php`

```php
'guard' => 'web',
'passwords' => 'users',
'username' => 'email',
'home' => '/dashboard',  // Redirect after successful login
```

**Features Enabled:**
- ✅ Registration
- ✅ Login with rate limiting (5 attempts per minute)
- ✅ Password reset
- ✅ Email verification
- ✅ Two-factor authentication
- ✅ Profile management

**Provider:** `app/Providers/FortifyServiceProvider.php`

```php
public function boot(): void
{
    Fortify::createUsersUsing(CreateNewUser::class);
    Fortify::updateUserProfileInformationUsing(UpdateUserProfileInformation::class);
    Fortify::updateUserPasswordsUsing(UpdateUserPassword::class);
    Fortify::resetUserPasswordsUsing(ResetUserPassword::class);
    
    // Rate limiting
    RateLimiter::for('login', function (Request $request) {
        $throttleKey = Str::transliterate(
            Str::lower($request->input(Fortify::username())).'|'.$request->ip()
        );
        return Limit::perMinute(5)->by($throttleKey);
    });
}
```

### 2. Laravel Jetstream

**Purpose:** Provides team management and UI scaffolding

**Configuration:** `config/jetstream.php`

**Features Used:**
- ✅ Profile management
- ✅ Team management (multi-tenancy support)
- ✅ API token management (Sanctum)

**Provider:** `app/Providers/JetstreamServiceProvider.php`

```php
public function boot(): void
{
    $this->configurePermissions();
    
    Jetstream::createTeamsUsing(CreateTeam::class);
    Jetstream::updateTeamNamesUsing(UpdateTeamName::class);
    Jetstream::addTeamMembersUsing(AddTeamMember::class);
    Jetstream::deleteTeamsUsing(DeleteTeam::class);
    Jetstream::deleteUsersUsing(DeleteUser::class);
}
```

### 3. Spatie Laravel Permission

**Purpose:** Provides role and permission management

**Installation:**
```bash
composer require spatie/laravel-permission
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"
php artisan migrate
```

**Configuration:** `config/permission.php`

**Key Features:**
- Roles (e.g., Super Admin, Client, Inspector)
- Permissions (e.g., view-properties, create-invoices)
- Role hierarchies
- Permission inheritance

---

## User Registration Flow

### Step-by-Step Process

```
1. User visits /register
   ↓
2. Fills registration form (name, email, password)
   ↓
3. Fortify validation (CreateNewUser.php)
   ↓
4. User account created in database
   ↓
5. Personal team automatically created
   ↓
6. "Client" role automatically assigned
   ↓
7. User redirected to /dashboard
   ↓
8. FREE access to client portal
```

### Registration Handler: `CreateNewUser.php`

```php
public function create(array $input): User
{
    // 1. Validate input
    Validator::make($input, [
        'name' => ['required', 'string', 'max:255'],
        'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
        'password' => $this->passwordRules(),
        'terms' => Jetstream::hasTermsAndPrivacyPolicyFeature() ? ['accepted', 'required'] : '',
    ])->validate();

    // 2. Create user in transaction
    return DB::transaction(function () use ($input) {
        return tap(User::create([
            'name' => $input['name'],
            'email' => $input['email'],
            'password' => Hash::make($input['password']),
        ]), function (User $user) {
            // 3. Create personal team
            $this->createTeam($user);
            
            // 4. Assign "Client" role automatically
            $clientRole = Role::where('name', 'Client')->first();
            if ($clientRole) {
                $user->assignRole($clientRole);
            }
        });
    });
}
```

### Key Points

✅ **Automatic Role Assignment** - All public registrations get "Client" role  
✅ **No Payment Required** - Registration is completely free  
✅ **Team Creation** - Each user gets a personal team for future collaboration  
✅ **Transaction Safety** - All operations wrapped in database transaction  

---

## Login & Session Management

### Login Process

```
1. User visits /login
   ↓
2. Enters email and password
   ↓
3. Fortify validates credentials
   ↓
4. Rate limiting check (5 attempts/minute)
   ↓
5. Session created (auth:sanctum)
   ↓
6. Redirect to /dashboard
   ↓
7. DashboardController determines view based on role
```

### Session Configuration

**Guard:** `web` (default Laravel session-based authentication)  
**Driver:** `session` (stores auth ID in encrypted session)  
**Sanctum:** API token authentication for future mobile apps  

### Rate Limiting

**Login:** 5 attempts per minute per email/IP combination  
**Two-Factor:** 5 attempts per minute per session  

```php
RateLimiter::for('login', function (Request $request) {
    $throttleKey = Str::transliterate(
        Str::lower($request->input(Fortify::username())).'|'.$request->ip()
    );
    return Limit::perMinute(5)->by($throttleKey);
});
```

---

## Role-Based Access Control (RBAC)

### System Roles

| Role | Access Level | Dashboard | Use Case |
|------|--------------|-----------|----------|
| **Super Admin** | Full System Access | Admin Panel | System owner, full control |
| **Administrator** | Admin Access (no role management) | Admin Panel | Daily admin operations |
| **Project Manager** | Project & Team Management | Admin Panel | Oversee projects, assign work |
| **Inspector** | Inspection Tasks | Admin Panel | Conduct property inspections |
| **Technician** | Field Work | Admin Panel | Execute repair/maintenance work |
| **Finance Officer** | Financial Operations | Admin Panel | Manage invoices, payments, budgets |
| **Client** | Self-Service Portal | Client Portal | Manage properties, view projects |

### Role Seeding

**Seeder:** `database/seeders/RolePermissionSeeder.php`

All roles and permissions are seeded during initial setup:

```bash
php artisan db:seed --class=RolePermissionSeeder
```

### Super Admin Setup

**Seeder:** `database/seeders/SuperAdminSeeder.php`

Creates default Super Admin account:

```
Email: admin@emuria.com
Password: @dm1n2@25
```

**Security:** 
- ✅ Strong password with symbols, numbers, uppercase/lowercase
- ✅ Email verified by default
- ✅ Full system access
- ⚠️ **IMPORTANT:** Change password in production!

---

## Middleware Architecture

### Route Middleware Stack

All authenticated routes use this middleware chain:

```php
Route::middleware([
    'auth:sanctum',                    // 1. Verify user is logged in
    config('jetstream.auth_session'),  // 2. Validate session integrity
    'verified',                        // 3. Ensure email is verified
    'check.subscription',              // 4. Check subscription (currently FREE)
])->group(function () {
    // Protected routes here
});
```

### Middleware Breakdown

#### 1. `auth:sanctum`

**Purpose:** Verify user is authenticated

**Action:**
- Checks if user has valid session or API token
- Redirects to `/login` if not authenticated

#### 2. `jetstream.auth_session`

**Purpose:** Validate session hasn't been tampered with

**Action:**
- Confirms session token matches stored hash
- Prevents session hijacking

#### 3. `verified`

**Purpose:** Ensure email address is verified

**Action:**
- Checks if `email_verified_at` is not null
- Redirects to email verification page if needed

#### 4. `check.subscription`

**Purpose:** Enforce subscription requirements (CURRENTLY DISABLED FOR FREE MODEL)

**File:** `app/Http/Middleware/CheckActiveSubscription.php`

```php
public function handle(Request $request, Closure $next): Response
{
    $user = $request->user();
    
    // Redirect to login if not authenticated
    if (!$user) {
        return redirect()->route('login');
    }
    
    // FREE ACCESS FOR ALL AUTHENTICATED USERS
    // Subscription check happens at payment stage, not at entry
    return $next($request);
}
```

**Current Business Model:**
- ✅ All authenticated users have FREE access
- ✅ Subscription required only AFTER inspection and custom product offer
- ✅ Payment enforced at checkout, not at dashboard entry

**Middleware Alias:** `bootstrap/app.php`

```php
->withMiddleware(function (Middleware $middleware): void {
    $middleware->alias([
        'check.subscription' => \App\Http\Middleware\CheckActiveSubscription::class,
    ]);
})
```

---

## Dashboard Routing Logic

### Entry Point: `/dashboard`

**Controller:** `app/Http/Controllers/DashboardController.php`

```php
public function index()
{
    $user = auth()->user();
    
    // Check role and route to appropriate dashboard
    if ($user->hasRole(['Super Admin', 'Administrator'])) {
        return view('admin.index', $adminData);
    }
    
    if ($user->hasRole('Client')) {
        return view('client.dashboard', $clientData);
    }
    
    // Default fallback
    return view('admin.index', $defaultData);
}
```

### Admin Dashboard: `resources/views/admin/index.blade.php`

**Who Sees This:**
- Super Admin
- Administrator
- Project Manager
- Inspector
- Technician
- Finance Officer

**Data Displayed:**
- System Overview card (pending approvals, total users, active inspections, unpaid invoices)
- All properties count
- All inspections count
- All active projects count
- All invoices count

**Code:**
```php
// Admins see ALL data
$propertiesCount = Property::count();
$inspectionsCount = Inspection::count();
$projectsCount = Project::where('status', 'active')->count();
$invoicesCount = Invoice::count();
```

### Client Dashboard: `resources/views/client/dashboard.blade.php`

**Who Sees This:**
- Client role only

**Data Displayed:**
- User's properties count
- User's projects count (for their properties)
- User's inspections count (via their projects)
- User's invoices count
- Unpaid invoices count
- Pending inspections count
- Recent properties list (last 5)

**Code:**
```php
// Clients see ONLY their data
$propertyIds = Property::where('user_id', $user->id)->pluck('id');
$propertiesCount = $propertyIds->count();

$projectsCount = Project::whereIn('property_id', $propertyIds)
    ->where('status', 'active')
    ->count();

$projectIds = Project::whereIn('property_id', $propertyIds)->pluck('id');
$inspectionsCount = Inspection::whereIn('project_id', $projectIds)->count();

$invoicesCount = Invoice::where('user_id', $user->id)->count();
```

---

## Permission System

### Permission Structure

**Format:** `action-resource` (using hyphens)

**Examples:**
- `view-all-properties`
- `create-invoices`
- `approve-inspections`
- `manage-users`

### Permission Categories

#### 1. User Management
```php
'manage-users'
'view-users'
'create-users'
'edit-users'
'delete-users'
```

#### 2. Role & Permission Management
```php
'manage-roles'
'manage-permissions'
```

#### 3. Property Management
```php
'view-own-properties'    // Clients can view their own
'view-all-properties'    // Admin/Staff can view all
'create-properties'
'edit-properties'
'delete-properties'
'approve-properties'     // Admin only
```

#### 4. Inspection Management
```php
'view-inspections'
'view-assigned-inspections'  // Inspectors see only assigned
'create-inspections'
'edit-inspections'
'upload-inspection-reports'
'approve-inspections'
```

#### 5. Project Management
```php
'view-own-projects'      // Clients
'view-all-projects'      // Admin/Staff
'create-projects'
'edit-projects'
'delete-projects'
'assign-projects'        // Project Manager
```

#### 6. Invoice Management
```php
'view-own-invoices'      // Clients
'view-invoices'          // Admin/Staff
'create-invoices'
'edit-invoices'
'send-invoices'
'approve-invoices'
'delete-invoices'
```

#### 7. Financial Operations
```php
'process-payments'
'refund-payments'
'view-payments'
'view-budgets'
'manage-budgets'
'view-financial-reports'
```

### Using Permissions in Blade Templates

**Check Permission:**
```blade
@can('view-all-properties')
    <a href="{{ route('admin.properties.index') }}">All Properties</a>
@endcan
```

**Check Multiple Permissions:**
```blade
@canany(['create-invoices', 'edit-invoices'])
    <button>Manage Invoices</button>
@endcanany
```

**Check Role:**
```blade
@role('Super Admin')
    <a href="{{ route('admin.roles.index') }}">Manage Roles</a>
@endrole
```

### Using Permissions in Controllers

**Check Permission:**
```php
if (!auth()->user()->can('view-all-properties')) {
    abort(403, 'Unauthorized access.');
}
```

**Check Role:**
```php
if (!auth()->user()->hasRole(['Super Admin', 'Administrator'])) {
    abort(403, 'Unauthorized access.');
}
```

**Authorization Method (All Admin Controllers):**
```php
private function checkAuthorization()
{
    if (!auth()->user()->hasRole(['Super Admin', 'Administrator'])) {
        abort(403, 'Unauthorized access.');
    }
}

public function index()
{
    $this->checkAuthorization();
    // ... rest of method
}
```

### Admin Sidebar Permission Checks

**File:** `resources/views/admin/partials/sidebar.blade.php`

All admin menu items use permission checks:

```blade
@can('view-all-properties')
    <li class="nav-item">
        <a href="{{ route('admin.properties.index') }}">
            <i class="mdi mdi-home-city"></i>
            <span class="menu-title">Properties</span>
        </a>
    </li>
@endcan

@can('view-inspections')
    <li class="nav-item">
        <a href="{{ route('admin.inspections.index') }}">
            <i class="mdi mdi-clipboard-check"></i>
            <span class="menu-title">Inspections</span>
        </a>
    </li>
@endcan
```

---

## Business Model - Free Access

### Current Implementation

**Philosophy:** Lower barrier to entry, monetize after value demonstration

### Free Access Includes:

✅ **Registration** - Anyone can create an account  
✅ **Dashboard Access** - View personalized dashboard  
✅ **Property Management** - Add and manage properties  
✅ **Inspection Viewing** - See inspection reports  
✅ **Project Tracking** - Monitor project progress  
✅ **Communication** - Contact support and teams  

### Payment Required For:

💰 **Custom Products** - After inspection, custom products offered via Stripe  
💰 **Scope of Work Approval** - Client accepts custom quote  
💰 **Project Execution** - Work begins after payment  

### Implementation: `CheckActiveSubscription` Middleware

```php
public function handle(Request $request, Closure $next): Response
{
    $user = $request->user();
    
    if (!$user) {
        return redirect()->route('login');
    }
    
    // FREE ACCESS FOR ALL AUTHENTICATED USERS
    // Subscription check happens at payment stage, not at entry
    return $next($request);
}
```

**Key Point:** The middleware currently allows all authenticated users through. Subscription enforcement happens at the payment/checkout stage, not at dashboard entry.

---

## Security Features

### 1. Password Security

**Requirements:**
- Minimum 8 characters
- At least one uppercase letter
- At least one lowercase letter
- At least one number
- At least one special character

**Hashing:** Bcrypt (default Laravel)

```php
'password' => Hash::make($input['password']),
```

### 2. Rate Limiting

**Login Attempts:** 5 per minute per email/IP  
**Two-Factor Auth:** 5 attempts per minute per session  

### 3. CSRF Protection

**All Forms:** Include `@csrf` token  
**Automatic Verification:** Laravel middleware validates tokens  

```blade
<form method="POST" action="/login">
    @csrf
    <!-- form fields -->
</form>
```

### 4. Session Security

**Configuration:** `config/session.php`

```php
'secure' => env('SESSION_SECURE_COOKIE', false),  // HTTPS only in production
'http_only' => true,                               // Prevent JavaScript access
'same_site' => 'lax',                              // CSRF protection
```

### 5. Email Verification

**Middleware:** `verified`  
**Verification Route:** `/email/verify`  
**Resend Route:** `/email/verification-notification`  

### 6. Two-Factor Authentication

**Enabled:** Yes (Jetstream feature)  
**Method:** Time-based One-Time Password (TOTP)  
**Apps:** Google Authenticator, Authy, 1Password  

---

## Route Structure

### Public Routes

```php
Route::get('/', function () {
    return redirect('/home/index.html');  // Public homepage
});

Route::get('/register', ...);  // Registration form
Route::get('/login', ...);     // Login form (Fortify)
```

### Authenticated Routes

```php
Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
    'check.subscription',
])->group(function () {
    
    // Main dashboard (role-based routing)
    Route::get('/dashboard', [DashboardController::class, 'index']);
    
    // Client routes (no additional role middleware - FREE access)
    Route::prefix('client')->name('client.')->group(function() {
        Route::resource('properties', Client\PropertyController::class);
        Route::get('/invoices', ...);
        Route::get('/projects', ...);
        Route::get('/inspections', ...);
    });
    
    // Admin routes (controller-level authorization)
    Route::prefix('admin')->name('admin.')->group(function() {
        Route::resource('users', Admin\UserManagementController::class);
        Route::resource('roles', Admin\RoleManagementController::class);
        Route::resource('permissions', Admin\PermissionManagementController::class);
    });
});
```

### Important Notes

❌ **No role middleware on routes** - Role checks happen in controllers  
✅ **Permission checks in Blade** - Using `@can` directives  
✅ **Controller authorization** - All admin controllers call `checkAuthorization()`  

**Why no route middleware?**
- Spatie role middleware requires registration: `Kernel.php` → `routeMiddleware`
- Laravel 12 uses `bootstrap/app.php` → `middleware->alias()`
- We removed role middleware to prevent "Target class [role] does not exist" errors
- Controller-level checks are more explicit and debuggable

---

## Testing Authentication

### Test Accounts

**Super Admin:**
```
Email: admin@emuria.com
Password: @dm1n2@25
Access: Full system access
```

**Create Test Client:**
```bash
php artisan tinker

$user = User::create([
    'name' => 'Test Client',
    'email' => 'client@test.com',
    'password' => Hash::make('password'),
    'email_verified_at' => now(),
]);

$user->assignRole('Client');
```

### Test Scenarios

1. **Register New User**
   - Visit `/register`
   - Fill form with valid data
   - Confirm "Client" role assigned
   - Redirected to `/dashboard`
   - See client dashboard

2. **Login as Admin**
   - Visit `/login`
   - Use admin@emuria.com credentials
   - Redirected to `/dashboard`
   - See admin dashboard with system overview

3. **Permission Check**
   - Login as client
   - Try to access `/admin/users`
   - Should see 403 Unauthorized

4. **Role-Based Sidebar**
   - Login as different roles
   - Verify sidebar menu items change based on permissions

---

## Troubleshooting

### Issue: "Target class [role] does not exist"

**Cause:** Spatie role middleware not registered in Laravel 12  
**Solution:** Removed role middleware from routes, added controller checks

```php
// DON'T DO THIS (causes error in Laravel 12)
Route::middleware('role:Super Admin')->group(function() { ... });

// DO THIS INSTEAD
public function index()
{
    if (!auth()->user()->hasRole('Super Admin')) {
        abort(403);
    }
    // ... rest of method
}
```

### Issue: Permission directives not working

**Cause:** Using wrong permission name format  
**Solution:** Use hyphens, not spaces

```blade
{{-- ❌ WRONG --}}
@can('view properties')

{{-- ✅ CORRECT --}}
@can('view-all-properties')
```

### Issue: Role relationship errors

**Cause:** Using `withCount()` on Spatie Permission models  
**Solution:** Use manual counting

```php
// ❌ WRONG
$roles = Role::withCount(['users', 'permissions'])->get();

// ✅ CORRECT
$roles = Role::all();
foreach ($roles as $role) {
    $role->users_count = $role->users()->count();
    $role->permissions_count = $role->permissions()->count();
}
```

---

## Best Practices

### ✅ Always Use Transactions for User Creation

```php
DB::transaction(function () use ($input) {
    $user = User::create([...]);
    $user->assignRole('Client');
    $this->createTeam($user);
    return $user;
});
```

### ✅ Check Permissions in Blade, Not Controllers

```blade
@can('view-all-properties')
    <a href="{{ route('admin.properties.index') }}">All Properties</a>
@endcan
```

### ✅ Use Controller Authorization Methods

```php
private function checkAuthorization()
{
    if (!auth()->user()->hasRole(['Super Admin', 'Administrator'])) {
        abort(403, 'Unauthorized access.');
    }
}
```

### ✅ Clear Caches After Permission Changes

```bash
php artisan cache:clear
php artisan config:clear
php artisan permission:cache-reset
```

### ✅ Seed Roles Before Running Tests

```bash
php artisan db:seed --class=RolePermissionSeeder
php artisan db:seed --class=SuperAdminSeeder
```

---

## Next Steps

1. ✅ Authentication system fully implemented
2. ✅ Role-based dashboards working
3. ✅ Permission system functional
4. ✅ Admin tables with DataTables (search, filter, pagination)
5. ⏳ Property approval workflow
6. ⏳ Inspection scheduling interface
7. ⏳ Scope of work management
8. ⏳ Custom product checkout flow

---

**Document Version:** 1.0  
**Last Updated:** November 23, 2025  
**Maintained By:** Development Team
