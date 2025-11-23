# 🔐 Test Credentials

## Super Admin Account

Use these credentials to test the full dashboard:

```
Email: admin@emuria.com
Password: @dm1n2@25
```

### What You'll Have Access To:

✅ **Full Dashboard Access**
- View all statistics (properties, inspections, projects, invoices)
- Access all menu items
- See admin section (Users, Tiers, Reports)

✅ **All Permissions**
- Create, Read, Update, Delete everything
- Manage users and roles
- View all reports
- Access admin settings

✅ **Role**: Super Admin
- Highest level access
- Can see everything
- Can do everything

## Testing Steps

### 1. Clear Browser Cache
- Press `Ctrl + Shift + Delete`
- Clear cookies and cached files
- Or use Incognito/Private mode

### 2. Visit Login Page
```
http://localhost/login
```

### 3. Enter Credentials
- Email: **admin@emuria.com**
- Password: **@dm1n2@25**

### 4. After Login
You should be redirected to:
```
http://localhost/dashboard
```

### 5. What to Check

**Dashboard Page:**
- ✅ Stats cards show (Properties, Inspections, Projects, Invoices)
- ✅ Subscription info card displays
- ✅ Quick actions buttons appear
- ✅ Sidebar navigation visible

**Sidebar Menu:**
- ✅ Dashboard
- ✅ Properties
- ✅ Inspections
- ✅ Projects
- ✅ Invoices
- ✅ My Subscription
- ✅ **Admin Section** (Super Admin only):
  - Users
  - Tiers
  - Reports

**Navbar (Top Bar):**
- ✅ Search bar
- ✅ Quick Actions dropdown
- ✅ Theme toggle (light/dark)
- ✅ Notifications bell
- ✅ Profile dropdown with logout

**Test Light/Dark Mode:**
- Click the theme toggle icon (top right)
- Page should switch between light and dark theme
- Refresh page - theme should persist

## Troubleshooting

### Issue: "These credentials do not match our records"

**Solution 1: Re-run Seeder**
```bash
php artisan db:seed --class=SuperAdminSeeder
```

**Solution 2: Check Users Table**
```bash
php artisan tinker
User::where('email', 'admin@emuria.com')->first();
```

**Solution 3: Manually Create**
```bash
php artisan tinker
$user = User::create([
    'name' => 'Super Administrator',
    'email' => 'admin@emuria.com',
    'password' => Hash::make('password'),
    'email_verified_at' => now()
]);
$user->assignRole('Super Admin');
```

### Issue: Dashboard Shows 404

**Check Routes:**
```bash
php artisan route:list --name=dashboard
```

**Clear Cache:**
```bash
php artisan route:clear
php artisan view:clear
php artisan config:clear
php artisan cache:clear
```

### Issue: Sidebar/Navbar Not Showing

**Check Blade Files:**
- resources/views/admin/layout.blade.php
- resources/views/admin/partials/sidebar.blade.php
- resources/views/admin/partials/navbar.blade.php

**Check Assets:**
```
public/admin/assets/css/style.css (should exist)
public/admin/assets/js/off-canvas.js (should exist)
```

### Issue: Stats Showing Zero

**This is Normal!** You haven't created any:
- Properties
- Inspections
- Projects
- Invoices

**To Add Test Data:**
```bash
php artisan tinker
// Create test property
Property::create([
    'client_id' => 1,
    'property_name' => 'Test Property',
    'property_type' => 'residential',
    'address' => '123 Test St',
    'city' => 'Kampala',
    'country' => 'Uganda'
]);
```

## Additional Test Accounts

### Want to Test Different Roles?

**Create a Client User:**
```bash
php artisan tinker
$user = User::create([
    'name' => 'Test Client',
    'email' => 'client@test.com',
    'password' => Hash::make('password'),
    'email_verified_at' => now()
]);
$user->assignRole('Client');
```

**Create an Inspector:**
```bash
$user = User::create([
    'name' => 'Test Inspector',
    'email' => 'inspector@test.com',
    'password' => Hash::make('password'),
    'email_verified_at' => now()
]);
$user->assignRole('Inspector');
```

**Create a Project Manager:**
```bash
$user = User::create([
    'name' => 'Test PM',
    'email' => 'pm@test.com',
    'password' => Hash::make('password'),
    'email_verified_at' => now()
]);
$user->assignRole('Project Manager');
```

## Security Note

⚠️ **IMPORTANT**: These are TEST credentials only!

**Before going live:**
1. Change the super admin password
2. Use strong passwords (min 12 characters)
3. Enable two-factor authentication
4. Remove test accounts
5. Update .env with production values

## Need Help?

If you encounter any issues:
1. Check Laravel logs: `storage/logs/laravel.log`
2. Check browser console (F12)
3. Run: `php artisan config:clear && php artisan cache:clear`
4. Restart web server

---

**Ready to Login!** 🚀
Visit: http://localhost/login
