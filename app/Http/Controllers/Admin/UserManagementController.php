<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class UserManagementController extends Controller
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

        $users = User::with('roles')->get(); // Get all users for DataTables
        
        // Calculate role-based statistics
        $totalClients = User::role('Client')->count();
        $totalInspectors = User::role('Inspector')->count();
        $totalProjectManagers = User::role('Project Manager')->count();
        $totalAdmins = User::role(['Super Admin', 'Administrator'])->count();
        $totalUsers = User::count();
        
        return view('admin.users.index', compact(
            'users',
            'totalClients',
            'totalInspectors',
            'totalProjectManagers',
            'totalAdmins',
            'totalUsers'
        ));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $this->checkAuthorization();

        $roles = Role::all();
        return view('admin.users.create', compact('roles'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $this->checkAuthorization();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'required|exists:roles,name',
            'phone' => 'nullable|string|max:20',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'email_verified_at' => now(), // Auto-verify staff accounts
        ]);

        // Assign role
        $user->assignRole($validated['role']);

        return redirect()->route('admin.users.index')
            ->with('success', 'User created successfully and assigned role: ' . $validated['role']);
    }

    /**
     * Display the specified resource.
     */
    public function show(User $user)
    {
        $this->checkAuthorization();
        
        $user->load(['roles', 'permissions']);
        return view('admin.users.show', compact('user'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(User $user)
    {
        $this->checkAuthorization();
        
        $roles = Role::all();
        $userRoles = $user->roles->pluck('name')->toArray();
        return view('admin.users.edit', compact('user', 'roles', 'userRoles'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, User $user)
    {
        $this->checkAuthorization();
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:8|confirmed',
        ]);

        $user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
        ]);

        if ($request->filled('password')) {
            $user->update(['password' => Hash::make($validated['password'])]);
        }

        return redirect()->route('admin.users.index')
            ->with('success', 'User updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user)
    {
        $this->checkAuthorization();
        
        // Prevent deleting yourself
        if ($user->id === auth()->id()) {
            return back()->with('error', 'You cannot delete your own account');
        }

        // Prevent deleting Super Admin if you're not Super Admin
        if ($user->hasRole('Super Admin') && !auth()->user()->hasRole('Super Admin')) {
            return back()->with('error', 'You cannot delete a Super Admin');
        }

        $user->delete();

        return redirect()->route('admin.users.index')
            ->with('success', 'User deleted successfully');
    }

    /**
     * Assign role to user
     */
    public function assignRole(Request $request, User $user)
    {
        $this->checkAuthorization();
        
        $validated = $request->validate([
            'role' => 'required|exists:roles,name',
        ]);

        $user->assignRole($validated['role']);

        return back()->with('success', 'Role assigned successfully');
    }

    /**
     * Remove role from user
     */
    public function removeRole(User $user, Role $role)
    {
        $this->checkAuthorization();
        
        $user->removeRole($role);

        return back()->with('success', 'Role removed successfully');
    }
}
