<?php

namespace App\Http\Controllers\Admin\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Admin;
use App\Models\Role;
use App\Models\RolePermission;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class AdminController extends Controller
{
    //
    public function index(Request $request)
    {
        $query = Admin::query();

        // Search functionality
        if ($request->has('search') && $request->search != '') {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'like', '%' . $searchTerm . '%')
                    ->orWhere('email', 'like', '%' . $searchTerm . '%')
                    ->orWhere('phone', 'like', '%' . $searchTerm . '%');
            });
        }

        // Filter by gender
        if ($request->has('gender') && $request->gender != '') {
            $query->where('gender', $request->gender);
        }

        // Filter by status
        if ($request->has('status') && $request->status != '') {
            $query->where('status', $request->status);
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

        $admins = $query->paginate($perPage)->appends($request->all());
        $rolesIds = RolePermission::where('is_active', 1)->pluck('role_id')->toArray();
        $roles = Role::whereIn('id', $rolesIds)->get();

        return view('admin.admin.index', compact('admins', 'roles'));
    }

    public function store(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'name' => 'required',
                'email' => 'required|email|unique:admins',
                'password' => 'required|min:8|max:20|string|regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,20}$/',
                'phone' => [
                    'required',
                    'unique:admins',
                    'regex:/^(?:\+971|971|0)?5[0-9]{8}$|^(?:\+963|963|0)?9[0-9]{8}$/'
                ],
                'role_id' => 'required|exists:roles,id',
                'birthday' => [
                    'required',
                    'date',
                    function ($attribute, $value, $fail) {
                        if (\Carbon\Carbon::parse($value)->diffInYears(now()) < 18) {
                            $fail('The ' . $attribute . ' indicates the admin is not at least 18 years old.');
                        }
                    }
                ],
                'gender' => 'required|in:male,female',
                'status' => 'required|in:active,inactive',
                'img' => 'required|image|max:10240',
            ]);

            $password = Hash::make($validatedData['password']);
            $image = $request->file('img')->getClientOriginalName();
            $path = $request->file('img')->storeAs('AdminImage', $image, 'public');
            
            Admin::create([
                'name' => $validatedData['name'],
                'email' => $validatedData['email'],
                'password' => $password,
                'phone' => $validatedData['phone'],
                'role_id' => $validatedData['role_id'],
                'birthday' => $validatedData['birthday'],
                'gender' => $validatedData['gender'],
                'status' => $validatedData['status'],
                'img' => $path,
                'created_by' => Auth::guard('admin')->user()->id,
                'updated_by' => Auth::guard('admin')->user()->id,
            ]);
            return redirect()->route('admin.admin.index')->with('success_message_create', 'Admin created successfully');
        } catch (\Exception $e) {
            return redirect()->back()->withInput()->with('error_message_create', 'Failed to create admin: ' . $e->getMessage());
        }
    }
    
    public function edit($id)
    {
        try {
            $admin = Admin::findOrFail($id);
            $rolesIds = RolePermission::where('is_active', 1)->pluck('role_id')->toArray();
            $roles = Role::whereIn('id', $rolesIds)->get();
            
            return view('admin.admin.edit', compact('admin', 'roles'));
        } catch (\Exception $e) {
            return redirect()->route('admin.admin.index')->with('error_message', 'Admin not found: ' . $e->getMessage());
        }
    }
    
    public function update(Request $request, $id)
    {
        try {
            $admin = Admin::findOrFail($id);
            
            $validatedData = $request->validate([
                'name' => 'required',
                'email' => [
                    'required',
                    'email',
                    Rule::unique('admins')->ignore($id)
                ],
                'phone' => [
                    'required',
                    Rule::unique('admins')->ignore($id),
                    'regex:/^(?:\+971|971|0)?5[0-9]{8}$|^(?:\+963|963|0)?9[0-9]{8}$/'
                ],
                'role_id' => 'required|exists:roles,id',
                'birthday' => [
                    'required',
                    'date',
                    function ($attribute, $value, $fail) {
                        if (\Carbon\Carbon::parse($value)->diffInYears(now()) < 18) {
                            $fail('The ' . $attribute . ' indicates the admin is not at least 18 years old.');
                        }
                    }
                ],
                'gender' => 'required|in:male,female',
                'status' => 'required|in:active,inactive',
                'img' => 'nullable|image|max:10240',
            ]);
            
            $updateData = [
                'name' => $validatedData['name'],
                'email' => $validatedData['email'],
                'phone' => $validatedData['phone'],
                'role_id' => $validatedData['role_id'],
                'birthday' => $validatedData['birthday'],
                'gender' => $validatedData['gender'],
                'status' => $validatedData['status'],
                'updated_by' => Auth::guard('admin')->user()->id,
            ];
            
            // Handle image upload if a new image is provided
            if ($request->hasFile('img')) {
                // Delete old image if exists
                if ($admin->img && Storage::disk('public')->exists($admin->img)) {
                    Storage::disk('public')->delete($admin->img);
                }
                
                $image = $request->file('img')->getClientOriginalName();
                $path = $request->file('img')->storeAs('AdminImage', $image, 'public');
                $updateData['img'] = $path;
            }
            
            $admin->update($updateData);
            
            return redirect()->route('admin.admin.index')->with('success_message', 'Admin updated successfully');
        } catch (\Exception $e) {
            return redirect()->back()->withInput()->with('error_message', 'Failed to update admin: ' . $e->getMessage());
        }
    }
    
    public function updatePassword(Request $request, $id)
    {
        try {
            $admin = Admin::findOrFail($id);
            
            $validatedData = $request->validate([
                'password' => 'required|min:8|max:20|string|regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,20}$/',
                'password_confirmation' => 'required|same:password',
            ]);
            
            $admin->update([
                'password' => Hash::make($validatedData['password']),
                'updated_by' => Auth::guard('admin')->user()->id,
            ]);
            
            return redirect()->route('admin.admin.index')->with('success_message', 'Password updated successfully');
        } catch (\Exception $e) {
            return redirect()->back()->withInput()->with('error_message', 'Failed to update password: ' . $e->getMessage());
        }
    }
    
    public function delete($id)
    {
        try {
            $admin = Admin::findOrFail($id);
            
            // Delete the image if exists
            if ($admin->img && Storage::disk('public')->exists($admin->img)) {
                Storage::disk('public')->delete($admin->img);
            }
            
            $admin->delete();
            
            return redirect()->route('admin.admin.index')->with('success_message', 'Admin deleted successfully');
        } catch (\Exception $e) {
            return redirect()->route('admin.admin.index')->with('error_message', 'Failed to delete admin: ' . $e->getMessage());
        }
    }
}
