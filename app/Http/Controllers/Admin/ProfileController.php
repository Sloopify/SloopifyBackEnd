<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Admin;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    /**
     * Display the admin profile page
     */
    public function index()
    {
        $admin = Auth::guard('admin')->user();
        return view('admin.profile.index', compact('admin'));
    }

    /**
     * Update the admin profile
     */
    public function update(Request $request)
    {
        try {
            $admin = Auth::guard('admin')->user();
            
            $validatedData = $request->validate([
                'name' => 'required',
                'email' => [
                    'required',
                    'email',
                    Rule::unique('admins')->ignore($admin->id)
                ],
                'phone' => [
                    'required',
                    Rule::unique('admins')->ignore($admin->id),
                    'regex:/^(?:\+971|971|0)?5[0-9]{8}$|^(?:\+963|963|0)?9[0-9]{8}$/'
                ],
                'birthday' => [
                    'required',
                    'date',
                    function ($attribute, $value, $fail) {
                        if (\Carbon\Carbon::parse($value)->diffInYears(now()) < 18) {
                            $fail('The ' . $attribute . ' indicates you are not at least 18 years old.');
                        }
                    }
                ],
                'gender' => 'required|in:male,female',
                'img' => 'nullable|image|max:10240',
            ]);
            
            $updateData = [
                'name' => $validatedData['name'],
                'email' => $validatedData['email'],
                'phone' => $validatedData['phone'],
                'birthday' => $validatedData['birthday'],
                'gender' => $validatedData['gender'],
                'updated_by' => $admin->id,
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
            
            return redirect()->route('admin.profile.index')->with('success_message', 'Profile updated successfully');
        } catch (\Exception $e) {
            return redirect()->back()->withInput()->with('error_message', 'Failed to update profile: ' . $e->getMessage());
        }
    }
    
    /**
     * Update the admin password
     */
    public function updatePassword(Request $request)
    {
        try {
            $admin = Auth::guard('admin')->user();
            
            $validatedData = $request->validate([
                'current_password' => [
                    'required',
                    function ($attribute, $value, $fail) use ($admin) {
                        if (!Hash::check($value, $admin->password)) {
                            $fail('The current password is incorrect.');
                        }
                    },
                ],
                'password' => 'required|min:8|max:20|string|regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,20}$/',
                'password_confirmation' => 'required|same:password',
            ]);
            
            $admin->update([
                'password' => Hash::make($validatedData['password']),
                'updated_by' => $admin->id,
            ]);
            
            return redirect()->route('admin.profile.index')->with('success_message', 'Password updated successfully');
        } catch (\Exception $e) {
            return redirect()->back()->withInput()->with('error_message', 'Failed to update password: ' . $e->getMessage());
        }
    }
} 