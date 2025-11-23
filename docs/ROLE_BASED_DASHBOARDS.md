# Role-Based Dashboard System

## Overview
The Emuria Property Care system now features fully customized dashboards for each user role, providing relevant information and quick actions based on their responsibilities.

## Implementation Date
November 24, 2025

## Supported Roles

### 1. 🔍 Inspector Dashboard
**Route**: `/dashboard` (auto-detected for users with Inspector role)  
**View**: `resources/views/admin/inspector-dashboard.blade.php`  
**Controller Method**: `DashboardController@index`

#### Features:
- **Statistics Cards**:
  - Assigned Properties (awaiting inspection)
  - Scheduled Inspections (with dates set)
  - Unscheduled Inspections (needs scheduling)
  - Completed Inspections (total finished)

- **Upcoming Inspections Table**:
  - Shows next 5 scheduled inspections
  - Date, time, property details, owner contact
  - Quick view action

- **Quick Actions**:
  - View All Assignments
  - Schedule Inspections
  - View Schedule

- **All Assigned Properties Table**:
  - Complete list with DataTables integration
  - Property code, name, location, owner
  - Project Manager assignment
  - Assignment and inspection dates
  - Schedule button for unscheduled properties
  - View and start inspection actions

#### Data Shown:
- Only properties where `inspector_id = current_user_id`
- Status: `awaiting_inspection`
- Ordered by inspection date, then assignment date

---

### 2. 👷 Project Manager Dashboard
**Route**: `/dashboard` (auto-detected for users with Project Manager role)  
**View**: `resources/views/admin/pm-dashboard.blade.php`  
**Controller Method**: `DashboardController@index`

#### Features:
- **Statistics Cards**:
  - Assigned Properties (under management)
  - Scheduled Inspections
  - Unscheduled Inspections
  - Active Projects (in progress)

- **Upcoming Inspections Table**:
  - Next 5 scheduled inspections
  - Property and inspector details
  - Quick view action

- **Quick Actions**:
  - View All Properties
  - Check Properties Needing Scheduling
  - View Schedule

- **Property Overview**:
  - Total properties count
  - Scheduled vs unscheduled breakdown
  - Active projects count

- **All Managed Properties Table**:
  - Complete list with DataTables
  - Property and owner details
  - Inspector assignments
  - Inspection scheduling status
  - View and edit actions

#### Data Shown:
- Only properties where `project_manager_id = current_user_id`
- Status: `awaiting_inspection`
- Includes inspector relationship data

---

### 3. 🔧 Technician Dashboard
**Route**: `/dashboard` (auto-detected for users with Technician role)  
**View**: `resources/views/admin/technician-dashboard.blade.php`  
**Controller Method**: `DashboardController@index`

#### Features:
- **Statistics Cards**:
  - Active Projects (currently working)
  - Pending Projects (awaiting start)
  - Completed Projects (total finished)
  - Today's Work Logs (logged today)

- **Upcoming Projects Table**:
  - Next 5 pending projects
  - Start date, project name, property
  - Client contact information
  - Status and quick view

- **Quick Actions**:
  - View My Projects
  - Log Work
  - View Work History

- **Project Overview**:
  - Active projects count
  - Pending start count
  - On hold count
  - Completed count

- **All Assigned Projects Table**:
  - Complete list with DataTables
  - Project name and description
  - Property and client details
  - Start/end dates
  - Status badges
  - Progress bars (0-100%)
  - View project and log work actions

#### Data Shown:
- Only projects where `assigned_to = current_user_id`
- All project statuses: active, pending, completed, on_hold
- Work logs for current user's projects

---

### 4. 💰 Finance Dashboard
**Route**: `/dashboard` (auto-detected for users with Finance role)  
**View**: `resources/views/admin/finance-dashboard.blade.php`  
**Controller Method**: `DashboardController@index`

#### Features:
- **Revenue Statistics Cards**:
  - Total Revenue (all time paid invoices)
  - Monthly Revenue (current month)
  - Pending Revenue (unpaid invoices amount)
  - Subscription Revenue (active subscriptions)

- **Invoice Statistics Panel**:
  - Visual icon boxes for: Paid, Pending, Overdue, Total
  - Doughnut chart showing distribution
  - Color-coded by status

- **Quick Actions**:
  - View All Invoices
  - Create Invoice
  - View Pending Invoices
  - View Overdue Invoices

- **Financial Summary**:
  - Collection Rate (percentage)
  - Average Invoice Value
  - Active Subscriptions Count
  - Monthly Recurring Revenue

- **Overdue Invoices Alert** (if any):
  - Red bordered card
  - Shows top 5 overdue invoices
  - Days overdue calculation
  - Quick view/edit actions

- **Recent Invoices Table**:
  - Last 10 invoices with DataTables
  - Invoice number, client, amount
  - Issue and due dates
  - Status badges
  - View and edit actions

#### Data Shown:
- All invoices in system
- Subscription data and tier pricing
- Revenue calculations and analytics
- Overdue tracking with days past due

---

### 5. 👨‍💼 Administrator/Super Admin Dashboard
**Route**: `/dashboard` (for Admin roles)  
**View**: `resources/views/admin/index.blade.php`  
**Controller Method**: `DashboardController@index`

#### Features:
- **Statistics Cards**:
  - Total Properties (all registered)
  - Total Inspections
  - Active Projects
  - Total Invoices

- **System Overview**:
  - Pending Approvals count
  - Total Users count
  - Active Inspections count
  - Unpaid Invoices count

- **Quick Actions**:
  - Add New Property
  - Schedule Inspection
  - Create Project
  - View Invoices

- **Recent Activity Table**:
  - System-wide activity log
  - Property activities
  - Status updates

#### Data Shown:
- All system data (no filters)
- Global statistics
- System-wide overview

---

### 6. 👤 Client Dashboard
**Route**: `/dashboard` (for Client role)  
**View**: `resources/views/client/dashboard.blade.php`  
**Controller Method**: `DashboardController@index`

#### Features:
- **Statistics Cards**:
  - My Properties count
  - My Inspections count
  - Active Projects count
  - Total Invoices count

- **Client Metrics**:
  - Unpaid Invoices count
  - Pending Inspections count
  - Active subscription details

- **Recent Properties**:
  - Last 5 submitted properties
  - Quick view access

#### Data Shown:
- Only properties owned by user (`user_id = current_user_id`)
- Projects related to user's properties
- Inspections for user's projects
- User's invoices and subscriptions

---

## Database Schema Requirements

### Properties Table
```sql
- inspector_id (foreign key to users)
- project_manager_id (foreign key to users)
- assigned_at (timestamp)
- inspection_scheduled_at (timestamp, nullable)
- status (enum: pending_approval, approved, rejected, awaiting_inspection)
```

### Projects Table
```sql
- assigned_to (foreign key to users) -- for Technicians
- status (enum: active, pending, completed, on_hold)
- progress (integer 0-100)
- start_date (date)
- end_date (date)
```

### Invoices Table
```sql
- user_id (foreign key to users)
- amount (decimal)
- status (enum: paid, pending, overdue, cancelled)
- due_date (date)
- invoice_number (string)
```

### Work Logs Table
```sql
- project_id (foreign key to projects)
- created_at (timestamp)
```

---

## Role Detection Order

The `DashboardController` checks roles in this specific order:

1. **Technician** → `admin.technician-dashboard`
2. **Finance** → `admin.finance-dashboard`
3. **Inspector** → `admin.inspector-dashboard`
4. **Project Manager** → `admin.pm-dashboard`
5. **Super Admin / Administrator** → `admin.index`
6. **Client** → `client.dashboard`
7. **Default** → `admin.index` (fallback)

---

## Key Features Across All Dashboards

### Common Elements:
✅ **DataTables Integration** - Sortable, searchable tables  
✅ **Role-Based Filtering** - Users see only their data  
✅ **Quick Actions** - Role-specific action buttons  
✅ **Responsive Design** - Bootstrap 5 mobile-friendly  
✅ **Light Theme Support** - Proper styling for visibility  
✅ **Real-time Counts** - Live statistics  
✅ **Status Badges** - Color-coded status indicators  

### Performance Optimizations:
- Eager loading relationships (`.with()`)
- Limited result sets for overview sections (`.take(5)`)
- Pagination for large datasets
- Efficient query filtering by user ID

---

## Navigation Flow

```
User Login
    ↓
Authentication Check
    ↓
Role Detection (DashboardController)
    ↓
    ├─ Technician → Projects & Work Logs
    ├─ Finance → Invoices & Revenue
    ├─ Inspector → Assigned Properties
    ├─ Project Manager → Managed Properties
    ├─ Admin → System Overview
    └─ Client → My Properties
```

---

## Integration Points

### Properties Workflow:
1. **Client** submits property
2. **Admin** approves property
3. **Admin** assigns **Project Manager** + **Inspector**
4. Status changes to `awaiting_inspection`
5. **Inspector** schedules inspection
6. **Inspector** conducts inspection
7. **Project Manager** creates project
8. **Admin** assigns **Technician** to project
9. **Technician** logs work
10. **Finance** creates invoices

### Key Routes:
- `/dashboard` - Main dashboard (role-detected)
- `/properties` - Property management
- `/inspections` - Inspection management
- `/projects` - Project management
- `/invoices` - Invoice management
- `/work-logs` - Work log management

---

## Files Modified/Created

### Controllers:
- `app/Http/Controllers/DashboardController.php` - Role detection logic

### Views:
- `resources/views/admin/inspector-dashboard.blade.php` - New
- `resources/views/admin/pm-dashboard.blade.php` - New
- `resources/views/admin/technician-dashboard.blade.php` - New
- `resources/views/admin/finance-dashboard.blade.php` - New
- `resources/views/admin/index.blade.php` - Existing (admin)
- `resources/views/client/dashboard.blade.php` - Existing (client)

### Migrations:
- `2025_11_23_211555_add_staff_assignments_to_properties_table.php`
- `2025_11_23_211727_add_awaiting_inspection_status_to_properties.php`

---

## Testing Checklist

### Inspector Role:
- [ ] Shows only assigned properties
- [ ] Displays scheduled vs unscheduled counts
- [ ] Can schedule inspections from dashboard
- [ ] Quick access to property details

### Project Manager Role:
- [ ] Shows only managed properties
- [ ] Displays inspector assignments
- [ ] Views inspection scheduling status
- [ ] Access to property editing

### Technician Role:
- [ ] Shows only assigned projects
- [ ] Displays project status breakdown
- [ ] Can log work from dashboard
- [ ] Progress bars display correctly

### Finance Role:
- [ ] Revenue calculations accurate
- [ ] Invoice statistics correct
- [ ] Overdue invoices highlighted
- [ ] Chart displays properly
- [ ] Subscription revenue calculated

### All Roles:
- [ ] DataTables search/sort functional
- [ ] Quick actions redirect correctly
- [ ] Statistics update in real-time
- [ ] Mobile responsive design works
- [ ] Light theme styling applied

---

## Future Enhancements

### Potential Additions:
1. **Notifications** - Real-time alerts for role-specific events
2. **Calendar View** - For Inspectors/PMs to see schedule
3. **Analytics Charts** - Trend analysis for Finance/Admin
4. **Export Features** - PDF/Excel reports per role
5. **Task Management** - To-do lists per role
6. **Communication Hub** - Internal messaging system
7. **Mobile App** - Native mobile dashboards
8. **Time Tracking** - Detailed time logs for Technicians
9. **Performance Metrics** - KPIs per role
10. **Automated Reports** - Scheduled email reports

---

## Security Considerations

### Access Control:
✅ Role-based authentication via `hasRole()`  
✅ Data filtering by user ID  
✅ No cross-role data exposure  
✅ Middleware protection on routes  
✅ Permission checks via Spatie  

### Best Practices:
- Never show data from other users without proper authorization
- Always filter queries by authenticated user ID for non-admin roles
- Use eager loading to prevent N+1 query issues
- Validate user permissions before displaying actions
- Sanitize all user inputs in search/filter forms

---

## Support & Maintenance

### Common Issues:

**Issue**: Dashboard shows no data  
**Solution**: Check role assignment, verify user has correct role in database

**Issue**: Wrong dashboard displayed  
**Solution**: Clear Laravel cache: `php artisan view:clear && php artisan config:clear`

**Issue**: Statistics showing zero  
**Solution**: Verify relationships exist, check foreign keys in database

**Issue**: DataTables not loading  
**Solution**: Ensure jQuery and DataTables scripts loaded before custom JS

---

## Conclusion

The role-based dashboard system provides a tailored experience for each user type, ensuring they see only relevant information and have quick access to their most common tasks. This improves efficiency, reduces confusion, and enhances the overall user experience.

**Last Updated**: November 24, 2025  
**Version**: 1.0  
**Status**: ✅ Fully Implemented
