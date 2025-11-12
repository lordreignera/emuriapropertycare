<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create Permissions
        $permissions = [
            // User Management
            'manage-users',
            'view-users',
            'create-users',
            'edit-users',
            'delete-users',
            
            // Role & Permission Management
            'manage-roles',
            'manage-permissions',
            
            // Client Management
            'view-clients',
            'manage-clients',
            
            // Tier Management
            'view-tiers',
            'manage-tiers',
            
            // Subscription Management
            'view-subscriptions',
            'manage-subscriptions',
            'cancel-subscriptions',
            
            // Property Management
            'view-own-properties',
            'view-all-properties',
            'create-properties',
            'edit-properties',
            'delete-properties',
            'approve-properties',
            
            // Project Management
            'view-own-projects',
            'view-all-projects',
            'create-projects',
            'edit-projects',
            'delete-projects',
            'assign-projects',
            
            // Inspection Management
            'view-inspections',
            'create-inspections',
            'edit-inspections',
            'upload-inspection-reports',
            'approve-inspections',
            'view-assigned-inspections',
            
            // Scope of Work
            'view-scope',
            'create-scope',
            'edit-scope',
            'approve-scope',
            
            // Quote Management
            'view-quotes',
            'create-quotes',
            'edit-quotes',
            'approve-quotes',
            'send-quotes',
            
            // Work Logs
            'view-work-logs',
            'create-work-logs',
            'edit-work-logs',
            'view-assigned-work-logs',
            
            // Progress Tracking
            'view-progress',
            'update-progress',
            
            // Milestone Management
            'view-milestones',
            'manage-milestones',
            
            // Budget Management
            'view-budgets',
            'manage-budgets',
            
            // Invoice Management
            'view-invoices',
            'view-own-invoices',
            'create-invoices',
            'edit-invoices',
            'send-invoices',
            'approve-invoices',
            'delete-invoices',
            
            // Payment Management
            'process-payments',
            'refund-payments',
            'view-payments',
            
            // Change Order Management
            'view-change-orders',
            'create-change-orders',
            'approve-change-orders',
            'reject-change-orders',
            
            // Communication
            'view-communications',
            'create-communications',
            
            // Reports
            'view-reports',
            'generate-reports',
            'view-financial-reports',
            'view-savings-reports',
            
            // Settings
            'manage-settings',
            'view-settings',
            
            // Scheduling
            'manage-schedules',
            'assign-technicians',
            'view-schedules',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Create Roles and Assign Permissions

        // 1. Super Admin - Full Access
        $superAdmin = Role::firstOrCreate(['name' => 'Super Admin']);
        $superAdmin->givePermissionTo(Permission::all());

        // 2. Administrator - Almost Full Access (except role management)
        $admin = Role::firstOrCreate(['name' => 'Administrator']);
        $admin->givePermissionTo([
            'view-users', 'create-users', 'edit-users',
            'view-clients', 'manage-clients',
            'view-tiers', 'manage-tiers',
            'view-subscriptions', 'manage-subscriptions',
            'view-all-properties', 'approve-properties',
            'view-all-projects', 'create-projects', 'edit-projects', 'assign-projects',
            'view-inspections', 'approve-inspections',
            'view-scope', 'approve-scope',
            'view-quotes', 'approve-quotes',
            'view-budgets', 'manage-budgets',
            'view-invoices', 'create-invoices', 'approve-invoices',
            'view-reports', 'generate-reports', 'view-financial-reports',
            'manage-settings',
        ]);

        // 3. Project Manager - Project & Team Management
        $projectManager = Role::firstOrCreate(['name' => 'Project Manager']);
        $projectManager->givePermissionTo([
            'view-all-properties',
            'view-all-projects', 'create-projects', 'edit-projects', 'assign-projects',
            'view-inspections', 'create-inspections', 'approve-inspections',
            'view-scope', 'create-scope', 'approve-scope',
            'view-quotes', 'create-quotes',
            'view-work-logs',
            'view-progress', 'update-progress',
            'view-milestones', 'manage-milestones',
            'view-budgets', 'manage-budgets',
            'view-change-orders', 'approve-change-orders',
            'view-communications', 'create-communications',
            'manage-schedules', 'assign-technicians', 'view-schedules',
        ]);

        // 4. Inspector - Inspection Focused
        $inspector = Role::firstOrCreate(['name' => 'Inspector']);
        $inspector->givePermissionTo([
            'view-assigned-inspections',
            'create-inspections',
            'edit-inspections',
            'upload-inspection-reports',
            'view-communications', 'create-communications',
        ]);

        // 5. Technician - Field Work
        $technician = Role::firstOrCreate(['name' => 'Technician']);
        $technician->givePermissionTo([
            'view-assigned-work-logs',
            'create-work-logs',
            'edit-work-logs',
            'view-progress',
            'update-progress',
            'view-communications', 'create-communications',
        ]);

        // 6. Finance Officer - Financial Management
        $financeOfficer = Role::firstOrCreate(['name' => 'Finance Officer']);
        $financeOfficer->givePermissionTo([
            'view-subscriptions',
            'view-quotes', 'create-quotes', 'edit-quotes', 'send-quotes',
            'view-budgets', 'manage-budgets',
            'view-invoices', 'create-invoices', 'edit-invoices', 'send-invoices',
            'process-payments', 'refund-payments', 'view-payments',
            'view-financial-reports', 'view-savings-reports',
        ]);

        // 7. Client - Self-Service Portal
        $client = Role::firstOrCreate(['name' => 'Client']);
        $client->givePermissionTo([
            'view-own-properties',
            'create-properties',
            'edit-properties',
            'view-own-projects',
            'view-inspections',
            'approve-inspections',
            'view-scope',
            'view-quotes',
            'approve-quotes',
            'view-progress',
            'view-own-invoices',
            'process-payments',
            'view-change-orders',
            'create-change-orders',
            'view-communications',
            'create-communications',
            'view-savings-reports',
        ]);

        $this->command->info('Roles and Permissions created successfully!');
    }
}
