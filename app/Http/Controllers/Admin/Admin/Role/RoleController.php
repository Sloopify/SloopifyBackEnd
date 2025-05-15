<?php

namespace App\Http\Controllers\admin\Admin\Role;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Role;
use App\Models\Permission;
use App\Models\RolePermission;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class RoleController extends Controller
{
    public function index(Request $request)
    {
        $query = Role::query();

        // Search functionality
        if ($request->has('search') && $request->search != '') {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'like', '%' . $searchTerm . '%')
                  ->orWhere('description', 'like', '%' . $searchTerm . '%');
            });
        }

        // Filter by status
        if ($request->has('status') && $request->status != '') {
            $status = ($request->status == 1) ? 1 : 0;
            $query->where('is_active', $status);
        }

        // Filter by type
        if ($request->has('type') && $request->type != '') {
            $query->where('type', $request->type);
        }

        // Filter by date range
        if ($request->has('from_date') && $request->from_date != '') {
            $query->whereDate('created_at', '>=', $request->from_date);
        }

        if ($request->has('to_date') && $request->to_date != '') {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        // Set number of items per page from request or default to 10
        $perPage = $request->get('per_page', 10);

        $roles = $query->paginate($perPage)->appends($request->all());
        
        // Get all permissions grouped by type
        $adminPermissions = Permission::where('is_active', 1)
            ->where('type', 'admin')
            ->get();
            
        $employeePermissions = Permission::where('is_active', 1)
            ->where('type', 'employee')
            ->get();
            
        $allPermissions = Permission::where('is_active', 1)->get();

        return view('admin.admin.role.index', compact('roles', 'adminPermissions', 'employeePermissions', 'allPermissions'));
    }

    public function store(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'name' => 'required|string|unique:roles,name',
                'description' => 'nullable|string',
                'is_active' => 'required|in:0,1',
                'type' => 'required|string|in:admin,employee',
                'permissions' => 'required|array',
                'permissions.*' => 'exists:permissions,id'
            ]);

            // Create role
            $role = Role::create([
                'name' => $validatedData['name'],
                'slug' => Str::slug($validatedData['name']),
                'description' => $validatedData['description'] ?? null,
                'is_active' => $validatedData['is_active'],
                'type' => $validatedData['type'],
            ]);

            // Attach permissions
            foreach ($validatedData['permissions'] as $permissionId) {
                RolePermission::create([
                    'role_id' => $role->id,
                    'permission_id' => $permissionId,
                    'is_active' => 1
                ]);
            }

            return redirect()->route('admin.admin.role.index')->with('success_message_create', 'Role created successfully');
        } catch (\Exception $e) {
            return redirect()->back()->withInput()->with('error_message_create', 'Failed to create role: ' . $e->getMessage());
        }
    }

    public function edit($id)
    {
        try {
            $role = Role::findOrFail($id);
            
            // Get all permissions grouped by type
            $adminPermissions = Permission::where('is_active', 1)
                ->where('type', 'admin')
                ->get();
                
            $employeePermissions = Permission::where('is_active', 1)
                ->where('type', 'employee')
                ->get();
                
            $allPermissions = Permission::where('is_active', 1)->get();
            
            // Get assigned permissions
            $assignedPermissions = RolePermission::where('role_id', $id)
                ->where('is_active', 1)
                ->pluck('permission_id')
                ->toArray();
            
            return view('admin.admin.role.edit', compact('role', 'adminPermissions', 'employeePermissions', 'allPermissions', 'assignedPermissions'));
        } catch (\Exception $e) {
            return redirect()->route('admin.admin.role.index')->with('error_message', 'Role not found: ' . $e->getMessage());
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $role = Role::findOrFail($id);
            
            $validatedData = $request->validate([
                'name' => [
                    'required',
                    'string',
                    Rule::unique('roles')->ignore($id)
                ],
                'description' => 'nullable|string',
                'is_active' => 'required|in:0,1',
                'type' => 'required|string|in:admin,employee',
                'permissions' => 'required|array',
                'permissions.*' => 'exists:permissions,id'
            ]);
            
            // Update role
            $role->update([
                'name' => $validatedData['name'],
                'slug' => Str::slug($validatedData['name']),
                'description' => $validatedData['description'] ?? null,
                'is_active' => $validatedData['is_active'],
                'type' => $validatedData['type'],
            ]);
            
            // Update permissions
            // First deactivate all existing permissions
            RolePermission::where('role_id', $id)->update(['is_active' => 0]);
            
            // Then activate or create the selected ones
            foreach ($validatedData['permissions'] as $permissionId) {
                $rolePermission = RolePermission::where('role_id', $id)
                    ->where('permission_id', $permissionId)
                    ->first();
                
                if ($rolePermission) {
                    $rolePermission->update(['is_active' => 1]);
                } else {
                    RolePermission::create([
                        'role_id' => $id,
                        'permission_id' => $permissionId,
                        'is_active' => 1
                    ]);
                }
            }
            
            return redirect()->route('admin.admin.role.index')->with('success_message', 'Role updated successfully');
        } catch (\Exception $e) {
            return redirect()->back()->withInput()->with('error_message', 'Failed to update role: ' . $e->getMessage());
        }
    }

    public function delete($id)
    {
        try {
            $role = Role::findOrFail($id);
            
            // Check if the role is being used by any admins
            $adminCount = \App\Models\Admin::where('role_id', $id)->count();
            if ($adminCount > 0) {
                return redirect()->route('admin.admin.role.index')
                    ->with('error_message', 'This role cannot be deleted because it is assigned to ' . $adminCount . ' admin(s).');
            }
            
            // Deactivate all related role permissions
            RolePermission::where('role_id', $id)->update(['is_active' => 0]);
            
            // Delete the role
            $role->delete();
            
            return redirect()->route('admin.admin.role.index')->with('success_message', 'Role deleted successfully');
        } catch (\Exception $e) {
            return redirect()->route('admin.admin.role.index')->with('error_message', 'Failed to delete role: ' . $e->getMessage());
        }
    }

    public function export(Request $request)
    {
        // Export functionality will be implemented here
        // Similar to the admin export functionality
    }
} 