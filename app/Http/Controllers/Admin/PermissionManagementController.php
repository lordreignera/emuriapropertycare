<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionManagementController extends Controller
{
    /**
     * Check if user is authorized
     */
    private function checkAuthorization()
    {
        if (!auth()->user()->hasRole(['Super Admin', 'Administrator'])) {
            abort(403, 'Unauthorized access.');
        }
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $this->checkAuthorization();
        $permissions = Permission::with('roles')->get();
        
        // Group permissions by resource type (the second word in permission name)
        $groupedPermissions = $permissions->groupBy(function($permission) {
            $parts = explode(' ', $permission->name);
            return $parts[1] ?? 'Other';
        });

        return view('admin.permissions.index', compact('groupedPermissions'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $this->checkAuthorization();
        return view('admin.permissions.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $this->checkAuthorization();
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:permissions,name',
            'guard_name' => 'nullable|string|max:255',
        ]);

        $permission = Permission::create([
            'name' => $validated['name'],
            'guard_name' => $validated['guard_name'] ?? 'web',
        ]);

        return redirect()
            ->route('admin.permissions.show', $permission)
            ->with('success', 'Permission created successfully!');
    }

    /**
     * Display the specified resource.
     */
    public function show(Permission $permission)
    {
        $this->checkAuthorization();
        $permission->load('roles');
        $allRoles = Role::all();

        return view('admin.permissions.show', compact('permission', 'allRoles'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Permission $permission)
    {
        $this->checkAuthorization();
        return view('admin.permissions.edit', compact('permission'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Permission $permission)
    {
        $this->checkAuthorization();
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:permissions,name,' . $permission->id,
        ]);

        $permission->update([
            'name' => $validated['name'],
        ]);

        return redirect()
            ->route('admin.permissions.show', $permission)
            ->with('success', 'Permission updated successfully!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Permission $permission)
    {
        $this->checkAuthorization();
        // Check if permission is assigned to any roles
        if ($permission->roles()->count() > 0) {
            return back()->with('error', 'Cannot delete permission that is assigned to roles. Remove from all roles first.');
        }

        $permission->delete();

        return redirect()
            ->route('admin.permissions.index')
            ->with('success', 'Permission deleted successfully!');
    }

    /**
     * Assign permission to a role
     */
    public function assignToRole(Request $request, Permission $permission)
    {
        $this->checkAuthorization();
        $validated = $request->validate([
            'role' => 'required|exists:roles,name',
        ]);

        $role = Role::findByName($validated['role']);
        $role->givePermissionTo($permission);

        return back()->with('success', "Permission assigned to {$role->name} role successfully!");
    }

    /**
     * Remove permission from a role
     */
    public function removeFromRole(Permission $permission, Role $role)
    {
        $this->checkAuthorization();
        $role->revokePermissionTo($permission);

        return back()->with('success', "Permission removed from {$role->name} role successfully!");
    }
}
