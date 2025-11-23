<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleManagementController extends Controller
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
        $roles = Role::all();
        
        // Manually count users and permissions for each role
        foreach ($roles as $role) {
            $role->users_count = $role->users()->count();
            $role->permissions_count = $role->permissions()->count();
        }
        
        return view('admin.roles.index', compact('roles'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $this->checkAuthorization();
        return view('admin.roles.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $this->checkAuthorization();
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:roles,name',
            'guard_name' => 'nullable|string|max:255',
        ]);

        $role = Role::create([
            'name' => $validated['name'],
            'guard_name' => $validated['guard_name'] ?? 'web',
        ]);

        return redirect()->route('admin.roles.index')
            ->with('success', 'Role created successfully');
    }

    /**
     * Display the specified resource.
     */
    public function show(Role $role)
    {
        $this->checkAuthorization();
        
        // Manually load relationships
        $rolePermissions = $role->permissions()->get();
        $roleUsers = $role->users()->get();
        
        $allPermissions = Permission::all()->groupBy(function($permission) {
            $parts = explode('-', $permission->name);
            return $parts[1] ?? 'Other';
        });
        
        return view('admin.roles.show', compact('role', 'rolePermissions', 'roleUsers', 'allPermissions'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Role $role)
    {
        $this->checkAuthorization();
        return view('admin.roles.edit', compact('role'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Role $role)
    {
        $this->checkAuthorization();
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:roles,name,' . $role->id,
        ]);

        $role->update($validated);

        return redirect()->route('admin.roles.index')
            ->with('success', 'Role updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Role $role)
    {
        $this->checkAuthorization();
        // Prevent deleting system roles
        if (in_array($role->name, ['Super Admin', 'Administrator', 'Client'])) {
            return back()->with('error', 'Cannot delete system role: ' . $role->name);
        }

        // Check if role has users
        if ($role->users()->count() > 0) {
            return back()->with('error', 'Cannot delete role that has users assigned');
        }

        $role->delete();

        return redirect()->route('admin.roles.index')
            ->with('success', 'Role deleted successfully');
    }

    /**
     * Assign permission to role
     */
    public function assignPermission(Request $request, Role $role)
    {
        $this->checkAuthorization();
        $validated = $request->validate([
            'permission' => 'required|exists:permissions,name',
        ]);

        $role->givePermissionTo($validated['permission']);

        return back()->with('success', 'Permission assigned successfully');
    }

    /**
     * Remove permission from role
     */
    public function removePermission(Role $role, Permission $permission)
    {
        $this->checkAuthorization();
        $role->revokePermissionTo($permission);

        return back()->with('success', 'Permission removed successfully');
    }
}
