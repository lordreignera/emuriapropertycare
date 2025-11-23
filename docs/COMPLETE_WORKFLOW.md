# 🏢 EMURIA PropertyCare - Complete Workflow Documentation

## System Overview

EMURIA PropertyCare is a comprehensive property management system with role-based access control and a structured workflow for managing properties, inspections, projects, and payments.

---

## 👥 User Roles & Responsibilities

### 1. **Super Admin** 🔑
**Created By:** System (Seeded)
**Needs Subscription:** ❌ No
**Access Level:** Full system control

**Responsibilities:**
- ✅ Full control over the entire system
- ✅ Create and manage all user accounts
- ✅ Assign roles to users
- ✅ Access all modules and data
- ✅ System configuration and settings
- ✅ View all reports and analytics
- ✅ Override any permissions

**Default Credentials:**
```
Email: admin@emuria.com
Password: @dm1n2@25
```

---

### 2. **Administrator** 👨‍💼
**Created By:** Super Admin
**Needs Subscription:** ❌ No
**Access Level:** Administrative access

**Responsibilities:**
- ✅ Create and manage staff users (PM, Inspector, Technician, Finance Officer)
- ✅ Assign roles to users
- ✅ View all system data
- ✅ Manage client accounts
- ✅ Generate reports
- ✅ Cannot delete Super Admin

---

### 3. **Project Manager** 📋
**Created By:** Super Admin or Administrator
**Needs Subscription:** ❌ No
**Access Level:** Project oversight and coordination

**Responsibilities:**
1. **Review Client Properties:**
   - View properties registered by clients
   - Assess property details and requirements
   - Validate property information

2. **Create Projects:**
   - Convert approved properties into projects
   - Set project parameters and timelines
   - Define project scope and requirements

3. **Assign Inspections:**
   - Assign projects to available Inspectors
   - Monitor inspector assignments
   - Track inspection progress

4. **Review Inspection Reports:**
   - Receive inspection reports from Inspectors
   - Review findings and recommendations
   - Await client approval

5. **Create Scope of Work:**
   - After client approval, create detailed Scope of Work
   - Define tasks, materials, and requirements
   - Assign Technicians to scope items
   - Allocate equipment and resources

6. **Project Monitoring:**
   - Track project progress
   - Monitor technician work logs
   - Ensure project milestones are met
   - Generate progress reports

**Workflow:**
```
Client Property → PM Reviews → Create Project → Assign to Inspector → 
Receive Inspection Report → Client Approves → Create Scope of Work → 
Assign Technicians → Monitor Progress
```

---

### 4. **Inspector** 🔍
**Created By:** Super Admin or Administrator
**Needs Subscription:** ❌ No
**Access Level:** Assigned projects only
**Payment:** Hourly basis

**Responsibilities:**
1. **View Assigned Projects:**
   - Only see projects assigned to them by Project Manager
   - Cannot view unassigned projects
   - Access project details and property information

2. **Conduct Inspections:**
   - Visit properties for inspections
   - Document findings and observations
   - Take photos and measurements
   - Note issues and recommendations

3. **Create Inspection Reports:**
   - Generate detailed inspection reports
   - Include findings, photos, and recommendations
   - Estimate repair/maintenance costs
   - Submit report to Project Manager

4. **Collaborate on Scope of Work:**
   - After client approval, work with PM on Scope of Work
   - Provide technical input
   - Help assign appropriate Technicians
   - Define work requirements

5. **Track Work Hours:**
   - Log inspection hours
   - Record time spent on each project
   - Submit timesheets to Finance Officer

**Workflow:**
```
Receive Assignment from PM → Conduct Inspection → 
Create Report → Submit to PM → Client Reviews → 
Collaborate on Scope of Work → Assign Technicians
```

---

### 5. **Technician** 🔧
**Created By:** Super Admin or Administrator
**Needs Subscription:** ❌ No
**Access Level:** Assigned scopes only
**Payment:** Hourly basis

**Responsibilities:**
1. **View Assigned Work:**
   - Only see Scope of Work items assigned to them
   - Cannot view other technicians' assignments
   - Access work details and requirements

2. **Execute Tasks:**
   - Perform assigned maintenance/repair work
   - Follow Scope of Work specifications
   - Use assigned equipment and materials

3. **Log Work Progress:**
   - Create work logs for each task
   - Record time spent on activities
   - Document completed work
   - Upload photos of work done

4. **Update Progress:**
   - Mark tasks as in-progress or completed
   - Report any issues or delays
   - Request additional materials if needed

5. **Track Work Hours:**
   - Log daily work hours
   - Record break times
   - Submit timesheets to Finance Officer

**Workflow:**
```
Receive Scope of Work Assignment → View Tasks → 
Execute Work → Log Progress → Mark Complete → 
Submit Timesheet
```

---

### 6. **Finance Officer** 💰
**Created By:** Super Admin or Administrator
**Needs Subscription:** ❌ No
**Access Level:** Financial data and payments

**Responsibilities:**
1. **Manage Client Subscriptions:**
   - View all client subscription plans
   - Monitor subscription status (active, expired, cancelled)
   - Process subscription renewals
   - Handle subscription upgrades/downgrades
   - Track subscription revenue

2. **Invoice Management:**
   - Generate invoices for completed projects
   - Send invoices to clients
   - Track payment status
   - Process payments
   - Handle overdue invoices

3. **Pay Staff (Hourly Basis):**
   - **Project Managers:** Review timesheets and approve payments
   - **Inspectors:** Calculate hours worked × hourly rate
   - **Technicians:** Calculate hours worked × hourly rate
   - Process payroll based on work logs
   - Generate payment reports

4. **Financial Reports:**
   - Generate revenue reports
   - Track expenses (staff payments)
   - Calculate profit margins per project
   - Monitor cash flow
   - Create financial forecasts

5. **Budget Management:**
   - Track project budgets
   - Monitor spending vs budget
   - Approve budget changes
   - Alert when budget exceeded

**Payment Workflow:**
```
Staff Submits Timesheet → Finance Reviews → 
Verify Work Logs → Calculate Payment (Hours × Rate) → 
Process Payment → Update Records
```

---

### 7. **Client** 👤
**Created By:** Self-registration through website
**Needs Subscription:** ✅ Yes (REQUIRED)
**Access Level:** Own properties and projects only

**Responsibilities:**
1. **Subscribe to a Tier:**
   - Choose appropriate subscription plan
   - Complete payment via Stripe
   - Must have active subscription to access dashboard

2. **Register Properties:**
   - Complete 8-step property onboarding form
   - Provide property details and requirements
   - Upload property documents
   - Set property care goals

3. **Review Inspection Reports:**
   - Receive inspection reports from PM
   - Review findings and recommendations
   - Ask questions or request clarification
   - **Approve or Reject** the report

4. **Sign Approved Reports:**
   - Digitally sign approved inspection reports
   - Authorize work to proceed
   - Confirm understanding of scope

5. **Monitor Project Progress:**
   - View assigned technicians
   - Track project milestones
   - See work logs and updates
   - View progress photos

6. **Manage Invoices:**
   - View project invoices
   - Make payments
   - Download receipts
   - Track payment history

**Workflow:**
```
Register & Subscribe → Add Property → PM Creates Project → 
Assigned to Inspector → Receive Inspection Report → 
Review & Approve → Sign Report → View Scope of Work → 
Monitor Progress → Receive Invoice → Make Payment
```

---

## 🔄 Complete System Workflow

### **Phase 1: Client Onboarding**
1. Client visits website and chooses subscription tier
2. Completes registration form with payment
3. Stripe processes payment
4. Account created with "Client" role
5. Subscription activated
6. Access granted to dashboard

### **Phase 2: Property Registration**
1. Client logs into dashboard
2. Completes 8-step property onboarding form:
   - Step 1: Property Vision
   - Step 2: Home Profile
   - Step 3: Care Goals
   - Step 4: Location & Details
   - Step 5: Property Features
   - Step 6: Current Issues
   - Step 7: Maintenance History
   - Step 8: Documents & Photos
3. Property saved to database
4. Client can add multiple properties

### **Phase 3: Project Creation & Assignment**
1. **Project Manager** reviews new properties
2. PM creates project from property
3. PM sets project parameters:
   - Project name and description
   - Priority level
   - Expected timeline
   - Budget estimate
4. PM assigns project to available **Inspector**
5. Inspector receives notification

### **Phase 4: Inspection Process**
1. **Inspector** views assigned project
2. Inspector schedules property visit
3. Conducts on-site inspection:
   - Documents findings
   - Takes photos
   - Measures and notes issues
   - Estimates costs
4. Inspector creates detailed inspection report:
   - Summary of findings
   - Issues identified
   - Recommendations
   - Cost estimates
   - Photos and documentation
5. Inspector submits report to **Project Manager**
6. PM forwards report to **Client**

### **Phase 5: Client Review & Approval**
1. **Client** receives inspection report notification
2. Client reviews report in dashboard:
   - Reads findings
   - Views photos
   - Reviews recommendations
   - Checks cost estimates
3. Client can:
   - **Approve:** Proceed with work
   - **Reject:** Request changes or cancel
   - **Ask Questions:** Communicate with PM/Inspector
4. If approved:
   - Client digitally signs report
   - Report forwarded back to PM and Inspector

### **Phase 6: Scope of Work Creation**
1. **Project Manager** and **Inspector** collaborate
2. Create detailed Scope of Work:
   - Break down tasks
   - Define materials needed
   - Allocate equipment
   - Estimate time per task
   - Set milestones
3. PM assigns **Technicians** to specific tasks:
   - Match skills to requirements
   - Consider availability
   - Distribute workload
4. Technicians receive assignments and notifications

### **Phase 7: Work Execution**
1. **Technicians** view assigned Scope of Work items
2. For each task, technicians:
   - Review task requirements
   - Gather materials and equipment
   - Perform work
   - Log work hours and progress
   - Take before/after photos
   - Mark tasks as complete
3. PM monitors progress in real-time
4. Client can view progress updates

### **Phase 8: Progress Tracking**
1. **Project Manager** tracks:
   - Task completion percentage
   - Hours logged vs estimated
   - Budget spent vs allocated
   - Milestone achievements
2. **Inspector** may conduct follow-up inspections
3. **Client** views progress dashboard:
   - See completed tasks
   - View work photos
   - Track timeline
   - Monitor budget

### **Phase 9: Financial Processing**
1. **Staff Timesheets:**
   - Inspectors submit hours worked
   - Technicians submit hours worked
   - PM reviews and approves timesheets

2. **Finance Officer** processes payments:
   - Calculate: Hours × Hourly Rate
   - Inspector payment (e.g., 8 hours × $50/hr = $400)
   - Technician payment (e.g., 40 hours × $30/hr = $1,200)
   - PM commission (if applicable)
   - Process payroll
   - Update payment records

3. **Client Invoicing:**
   - Finance generates project invoice
   - Includes: Labor + Materials + Equipment + Overhead
   - Sends invoice to client
   - Client views invoice in dashboard
   - Client makes payment via Stripe
   - Finance records payment

### **Phase 10: Project Completion**
1. All tasks marked complete
2. Final inspection by Inspector
3. Client reviews completed work
4. Client approves final work
5. Project marked as completed
6. Final invoice settled
7. Project archived with full documentation

---

## 🔐 Access Control Summary

### **No Subscription Required (Staff):**
- ✅ Super Admin
- ✅ Administrator
- ✅ Project Manager
- ✅ Inspector
- ✅ Technician
- ✅ Finance Officer

### **Subscription Required:**
- ❌ Client (Must have active subscription)

### **Data Access Levels:**

| Role | Access Scope |
|------|-------------|
| Super Admin | Everything |
| Administrator | Everything except Super Admin management |
| Project Manager | All projects, can assign to inspectors |
| Inspector | Only assigned projects |
| Technician | Only assigned scope of work items |
| Finance Officer | All financial data, subscriptions, payroll |
| Client | Only their own properties and projects |

---

## 💰 Payment Structure

### **Subscription Revenue (from Clients):**
```
Tier 1: $199/month or $1,990/year (17% discount)
Tier 2: $349/month or $3,490/year (17% discount)
Tier 3: $549/month or $5,490/year (17% discount)
Tier 4: $849/month or $8,490/year (17% discount)
Tier 5: $1,499/month or $14,990/year (17% discount)
```

### **Staff Payments (Hourly - Example Rates):**
```
Project Manager: $XX/hour (based on management time)
Inspector: $XX/hour (inspection + report creation)
Technician: $XX/hour (hands-on work)
```

**Payment Calculation:**
```
Total Staff Cost = (PM Hours × PM Rate) + 
                  (Inspector Hours × Inspector Rate) + 
                  (Technician Hours × Technician Rate)
```

### **Project Invoice (to Client):**
```
Labor Costs: Total Staff Payments
Materials: Actual cost + markup
Equipment: Rental/usage fees
Overhead: Company overhead %
Profit Margin: XX%
---
Total Invoice: Sum of above
```

---

## 📊 Reporting & Analytics

### **Super Admin/Administrator Reports:**
- Total clients and subscriptions
- Revenue breakdown by tier
- Active projects overview
- Staff utilization rates
- System-wide analytics

### **Project Manager Reports:**
- Projects by status (pending, active, completed)
- Inspector workload distribution
- Project timeline tracking
- Budget vs actual spending

### **Finance Officer Reports:**
- Subscription revenue
- Invoice status (paid, pending, overdue)
- Staff payroll summaries
- Profit & loss per project
- Cash flow analysis

### **Client Dashboard:**
- Property overview
- Active projects
- Inspection history
- Invoice history
- Payment records

---

## 🎯 Key Features

1. **Role-Based Access Control (RBAC)**
   - Powered by Spatie Laravel Permission
   - 7 roles with specific permissions
   - Granular permission management

2. **Subscription Management**
   - Stripe integration via Laravel Cashier
   - Automatic payment processing
   - Subscription status tracking
   - Client-only requirement

3. **Project Workflow**
   - Structured approval process
   - Clear assignment chain
   - Progress tracking
   - Document management

4. **Time Tracking**
   - Hourly work logs
   - Automated timesheet calculations
   - Transparent billing

5. **Financial Management**
   - Automated invoicing
   - Staff payroll processing
   - Budget tracking
   - Revenue reporting

---

## 🚀 Next Steps

1. ✅ **Completed:**
   - User roles and permissions
   - Subscription middleware
   - Dashboard with access control
   - Database structure

2. 🔄 **In Progress:**
   - Document complete workflow

3. ⏳ **Coming Next:**
   - User management interface (Super Admin/Admin)
   - 8-step property onboarding form
   - Project management dashboard
   - Inspection report creation
   - Scope of Work builder
   - Timesheet system
   - Invoice generation
   - Payment processing

---

**Last Updated:** November 13, 2025
**Version:** 1.0
**System:** EMURIA PropertyCare
