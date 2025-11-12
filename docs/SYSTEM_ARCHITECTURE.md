# EMURIA REGENERATIVE PROPERTY CARE - SYSTEM ARCHITECTURE

## 🎯 System Overview

A comprehensive property care membership management platform that enables clients to subscribe to tiered maintenance services, manage properties, schedule inspections, track projects, and handle billing.

---

## 📋 SYSTEM WORKFLOW (16 Steps)

### Client Journey

```
1️⃣  Client visits home page → learns about services & tiers
   ↓
2️⃣  Client selects subscription plan (Tier 1–5)
   ↓
3️⃣  Payment screen → enter Mastercard / Visa → confirm
   ↓
4️⃣  Client registration (basic details)
   ↓
5️⃣  Client adds property (address, type, size, photos)
   ↓
6️⃣  System assigns inspection → Project Manager → Inspector
   ↓
7️⃣  Inspector performs inspection → uploads report
   ↓
8️⃣  System generates Scope of Work (based on inspection)
   ↓
9️⃣  Compare with client's Tier coverage:
       ✅ If covered → proceed
       ❌ If not → prompt upgrade or instant payment
   ↓
🔟  Generate Project Quote (covered + billable parts)
   ↓
11️⃣  Project Manager sets schedule & assigns technicians
   ↓
12️⃣  Daily Work Log (field reports)
   ↓
13️⃣  Progress Tracker (percent completion)
   ↓
14️⃣  Milestones, Budget Summary, Invoices, Savings, Change Orders
   ↓
15️⃣  Communication Logs → Updates → Final Reports
   ↓
16️⃣  Project Closed → Client reviews → Feedback
```

---

## 🏗️ CORE MODULES

| Module | Description | Who Uses It |
|--------|-------------|-------------|
| **Landing Page / Marketing** | Shows what services & tiers exist | Public |
| **Subscription / Payment** | Stripe integration for tier payments | Client |
| **Client Dashboard** | Manage properties, projects, payments | Client |
| **Property Management** | Each property has its own inspections & projects | Client / Admin |
| **Inspection Management** | Inspectors upload inspection reports | Inspector / PM |
| **Scope of Work & Quote** | Generated from inspection, tier coverage checked | PM / Finance |
| **Project Scheduling** | Assign technicians, timelines | PM |
| **Work Log & Progress Tracker** | Daily updates, progress % | Technicians / PM |
| **Milestones / Budget / Invoices** | Track financial and delivery health | PM / Finance |
| **Change Orders / Communications** | Request scope change, log messages | Client / PM |
| **Reports & Savings** | Summarize costs, performance | Admin / Client |
| **Role & Permission Control** | Manage who can access what | Super Admin |

---

## 🗄️ DATABASE SCHEMA

### Core Entities

#### **tiers**
```
id (PK)
name (Tier 1, Tier 2, etc.)
slug (basic-care, essential, etc.)
description
experience
benefits (JSON)
monthly_price (decimal)
annual_price (decimal)
coverage_limit (decimal - max covered per project)
features (JSON - list of services included)
created_at
updated_at
```

#### **subscriptions**
```
id (PK)
user_id (FK → users)
property_id (FK → properties)
tier_id (FK → tiers)
payment_cadence (enum: monthly, annual)
start_date
end_date
status (enum: active, expired, cancelled, paused)
stripe_subscription_id
next_billing_date
created_at
updated_at
```

#### **properties**
```
id (PK)
user_id (FK → users - property owner)
subscription_id (FK → subscriptions)
property_name
address
city
province
postal_code
country
type (enum: house, townhome, condo, duplex, multi-unit)
square_footage
year_built
occupied_by (enum: owner, family, tenants, mixed)
has_pets (boolean)
has_kids (boolean)
personality (enum: calm, busy, luxury, high-use)
known_problems (text)
sensitivities (JSON)
blueprint_file (nullable)
property_photos (JSON)
created_at
updated_at
```

#### **property_administrators**
```
id (PK)
property_id (FK → properties)
first_name
last_name
email
phone
role (string)
created_at
updated_at
```

### Project Management

#### **projects**
```
id (PK)
property_id (FK → properties)
subscription_id (FK → subscriptions)
project_number (unique)
title
description
status (enum: pending, inspection, scoping, quoted, scheduled, in_progress, completed, cancelled)
priority (enum: low, medium, high, urgent)
start_date
end_date
created_by (FK → users)
managed_by (FK → users - Project Manager)
created_at
updated_at
```

#### **inspections**
```
id (PK)
project_id (FK → projects)
inspector_id (FK → users)
scheduled_date
completed_date
summary (text)
findings (JSON)
report_file (string - path to PDF)
photos (JSON)
approved_by_client (boolean)
approved_at
status (enum: scheduled, completed, approved, revision_needed)
created_at
updated_at
```

#### **scope_of_work**
```
id (PK)
project_id (FK → projects)
inspection_id (FK → inspections)
description (text)
items (JSON - array of work items)
estimated_hours
cost_estimate (decimal)
tier_coverage_status (enum: fully_covered, partially_covered, not_covered)
covered_amount (decimal)
billable_amount (decimal)
approved_by_pm (boolean)
created_at
updated_at
```

#### **quotes**
```
id (PK)
project_id (FK → projects)
scope_id (FK → scope_of_work)
quote_number (unique)
covered_total (decimal)
billable_total (decimal)
tax_amount (decimal)
grand_total (decimal)
line_items (JSON)
approval_status (enum: pending, approved, rejected, revision_requested)
approved_at
valid_until
notes (text)
created_at
updated_at
```

#### **work_logs**
```
id (PK)
project_id (FK → projects)
user_id (FK → users - technician)
log_date
activity (text)
hours_worked (decimal)
materials_used (JSON)
photos (JSON)
remarks (text)
created_at
updated_at
```

#### **progress_trackers**
```
id (PK)
project_id (FK → projects)
updated_by (FK → users)
percent_complete (integer 0-100)
phase (string)
remarks (text)
created_at
updated_at
```

### Financial & Communication

#### **milestones**
```
id (PK)
project_id (FK → projects)
name
description
owner (FK → users)
target_date
completed_date
status (enum: pending, in_progress, completed, delayed)
sort_order
created_at
updated_at
```

#### **budgets**
```
id (PK)
project_id (FK → projects)
category (string: labor, materials, equipment, permits)
estimated_cost (decimal)
covered_value (decimal)
billable_value (decimal)
actual_cost (decimal)
variance (decimal)
notes (text)
created_at
updated_at
```

#### **invoices**
```
id (PK)
project_id (FK → projects)
user_id (FK → users - client)
invoice_number (unique)
type (enum: subscription, project, change_order, additional)
amount (decimal)
tax (decimal)
total (decimal)
paid_amount (decimal)
balance (decimal)
status (enum: draft, sent, paid, overdue, cancelled)
due_date
paid_at
stripe_invoice_id
created_at
updated_at
```

#### **savings**
```
id (PK)
user_id (FK → users)
year (integer)
total_subscription_cost (decimal)
total_services_value (decimal)
savings_amount (decimal)
notes (text)
created_at
updated_at
```

#### **change_orders**
```
id (PK)
project_id (FK → projects)
requested_by (FK → users)
description (text)
reason (text)
added_cost (decimal)
schedule_impact_days (integer)
status (enum: pending, approved, rejected)
approved_by (FK → users)
approved_at
created_at
updated_at
```

#### **communications**
```
id (PK)
project_id (FK → projects)
user_id (FK → users - sender)
channel (enum: email, phone, in_person, portal)
contact_person (string)
summary (text)
next_action (text)
created_at
updated_at
```

---

## 👥 USER ROLES & PERMISSIONS

### Roles

| Role | Responsibilities |
|------|------------------|
| **Super Admin** | Manages everything, users, payments, reports |
| **Project Manager (PM)** | Assigns inspections, reviews scope, schedules work |
| **Inspector** | Conducts inspections, uploads reports |
| **Technician** | Updates work log, progress tracker |
| **Finance Officer** | Handles quotes, invoices, payments |
| **Client** | Subscribes, adds property, views reports & invoices |

### Permission Structure (Spatie)

```php
// Super Admin
'manage-users', 'manage-roles', 'manage-permissions', 'view-all-projects', 
'manage-tiers', 'view-reports', 'manage-settings'

// Project Manager
'create-projects', 'assign-inspections', 'approve-scope', 'assign-technicians',
'update-schedules', 'view-budgets', 'manage-milestones'

// Inspector
'view-assigned-inspections', 'upload-reports', 'edit-inspections', 
'view-properties'

// Technician
'view-assigned-projects', 'update-work-logs', 'update-progress', 
'upload-photos'

// Finance Officer
'create-quotes', 'manage-invoices', 'view-budgets', 'approve-payments',
'generate-financial-reports'

// Client
'view-own-properties', 'view-own-projects', 'approve-quotes', 
'view-invoices', 'pay-invoices', 'request-change-orders', 
'view-progress', 'manage-subscription'
```

---

## 📐 BUSINESS RULES

| Rule | System Behavior |
|------|------------------|
| **Subscription Required** | Client cannot start a project without an active subscription |
| **Coverage Check** | If scope cost > tier coverage → Prompt upgrade or pay balance |
| **Payment Block** | Client cannot add second project if current one unpaid |
| **Auto-Expiry** | Subscription auto-expires after term → Cron job to deactivate |
| **Change Orders** | Change Orders trigger quote/invoice adjustments (linked updates) |
| **Inspection Required** | Projects cannot proceed to scoping without completed inspection |
| **PM Approval** | Scope of work must be approved by PM before quote generation |
| **Client Approval** | Quotes must be approved by client before scheduling |
| **Progress Tracking** | Work logs automatically update progress tracker |

---

## 🎨 SUBSCRIPTION TIERS

### Tier 1: Basic Care (🌿)
- **Experience**: Preventive Essentials & Peace-of-Mind Protection
- **Coverage**: Annual inspection, safety checks, filter replacements, minor maintenance
- **Best For**: New homes, low-use properties, peace-of-mind starters

### Tier 2: Essential Care (🧰)
- **Experience**: Cosmetic, Comfort & Minor Wear Restoration
- **Includes**: All Tier 1 + wall repairs, trim, door adjustments, paint touch-ups
- **Best For**: Busy homeowners & light-wear properties

### Tier 3: Enhanced (⚙️)
- **Experience**: Systems, Surfaces & Appliance Support
- **Includes**: All Tier 1-2 + appliance maintenance, plumbing fixes, flooring repairs
- **Best For**: Families, active homes, rental homes, older homes

### Tier 4: Premium Protection (🏛)
- **Experience**: Structural & Mechanical Support
- **Includes**: All Tier 1-3 + drywall rebuilds, subfloor leveling, HVAC support
- **Best For**: High-value homes, older homes, estate rentals, busy landlords

### Tier 5: Elite Estate Care (👑)
- **Experience**: White-Glove Home Stewardship
- **Includes**: All Tier 1-4 + concierge, emergency coverage, renovation credits, project management
- **Best For**: VIP properties, estates, luxury homes, investment portfolios

---

## 🔄 KEY WORKFLOWS

### Workflow 1: Onboarding Flow
```
1. Client visits landing page
2. Selects tier (1-5)
3. Enters payment details (Stripe)
4. Completes registration (8-step form)
5. Adds property details
6. System creates subscription
7. Welcome email sent
8. Dashboard access granted
```

### Workflow 2: Inspection to Project
```
1. PM creates project for property
2. System assigns inspector
3. Inspector receives notification
4. Inspector conducts inspection
5. Inspector uploads report
6. Client reviews/approves report
7. System generates scope of work
8. Tier coverage calculated
9. Quote generated
10. Client approves quote
11. Project scheduled
```

### Workflow 3: Project Execution
```
1. PM assigns technicians
2. Sets timeline & milestones
3. Technicians log daily work
4. Progress tracker updated
5. Client receives updates
6. Milestones completed
7. Final inspection
8. Project closed
9. Invoice generated
10. Client feedback collected
```

---

## 🛠️ TECHNOLOGY STACK

- **Framework**: Laravel 12
- **Authentication**: Laravel Jetstream (Inertia Stack) + Fortify
- **Frontend**: Livewire 3.6.4 + Alpine.js + Tailwind CSS
- **Permissions**: Spatie Laravel Permission
- **Payments**: Stripe (Mastercard/Visa)
- **Database**: MySQL
- **File Storage**: Laravel Storage (S3 compatible)
- **Queue**: Laravel Queue (for notifications, emails)
- **Notifications**: Laravel Notifications (Email, Database, SMS)

---

## 📱 USER INTERFACES

### Public Pages
- Landing page with tier showcase
- About services
- Contact

### Client Portal
- Dashboard (properties overview, active projects)
- Properties management
- Subscription management
- Project tracking
- Invoices & payments
- Communication center

### PM Dashboard
- Project overview
- Inspector assignment
- Scope approval
- Technician scheduling
- Budget tracking

### Inspector Portal
- Assigned inspections
- Inspection report upload
- Property details view

### Technician Portal
- Assigned projects
- Work log entry
- Progress updates
- Materials tracking

### Finance Portal
- Quote generation
- Invoice management
- Payment tracking
- Financial reports

### Admin Portal
- User management
- Role & permission management
- Tier management
- System reports
- Settings

---

## 🔐 SECURITY CONSIDERATIONS

1. **Authentication**: Two-factor authentication enabled
2. **Authorization**: Spatie permission-based access control
3. **Payment Security**: PCI-compliant via Stripe
4. **Data Protection**: Encrypted sensitive data
5. **File Upload**: Validated file types and sizes
6. **API Security**: Sanctum token-based authentication
7. **Audit Trail**: Track all critical actions

---

## 📊 REPORTING & ANALYTICS

### Client Reports
- Property maintenance history
- Annual savings report
- Project completion summary
- Invoice history

### PM Reports
- Active projects dashboard
- Resource allocation
- Timeline performance
- Budget vs actual

### Finance Reports
- Revenue by tier
- Outstanding invoices
- Payment trends
- Profitability by project

### Admin Reports
- User activity
- Subscription trends
- Service demand analysis
- Customer satisfaction metrics

---

## 🚀 IMPLEMENTATION PHASES

### Phase 1: Foundation (Weeks 1-2)
- Install Spatie Permission
- Configure Jetstream
- Create database migrations
- Build core models
- Set up roles & permissions

### Phase 2: Payment & Subscription (Weeks 3-4)
- Stripe integration
- Tier management system
- Subscription workflow
- Payment processing

### Phase 3: Onboarding & Properties (Weeks 5-6)
- Landing page
- 8-step onboarding form
- Property management
- Client dashboard

### Phase 4: Project Management (Weeks 7-9)
- Inspection system
- Scope of work generation
- Quote system
- Project scheduling

### Phase 5: Execution & Tracking (Weeks 10-11)
- Work logs
- Progress tracking
- Milestones
- Communication system

### Phase 6: Financial Management (Weeks 12-13)
- Budget tracking
- Invoicing
- Change orders
- Savings calculation

### Phase 7: Reporting & Polish (Weeks 14-15)
- Dashboards for all roles
- Reports & analytics
- Email notifications
- UI/UX refinements

### Phase 8: Testing & Launch (Weeks 16-17)
- Comprehensive testing
- Bug fixes
- Documentation
- Production deployment

---

## 📝 NEXT STEPS

1. ✅ Install Spatie Laravel Permission
2. ✅ Create all database migrations
3. ✅ Build Eloquent models with relationships
4. ✅ Set up roles and permissions
5. ✅ Configure Stripe payment gateway
6. ✅ Build landing page and tier showcase
7. ✅ Create subscription workflow
8. ✅ Build onboarding form
9. Continue with remaining modules...

---

**Document Version**: 1.0  
**Last Updated**: November 12, 2025  
**Maintained By**: Development Team
