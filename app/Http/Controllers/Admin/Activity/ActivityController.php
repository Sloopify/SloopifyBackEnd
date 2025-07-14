<?php

namespace App\Http\Controllers\Admin\Activity;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PostActivity;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ActivityController extends Controller
{
    public function index(Request $request)
    {
        $query = PostActivity::query();

        // Search functionality
        if ($request->has('search') && $request->search != '') {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'like', '%' . $searchTerm . '%')
                  ->orWhere('category', 'like', '%' . $searchTerm . '%');
            });
        }

        // Filter by status
        if ($request->has('status') && $request->status != '') {
            $query->where('status', $request->status);
        }

        // Filter by category
        if ($request->has('category') && $request->category != '') {
            $query->where('category', $request->category);
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

        $activities = $query->paginate($perPage)->appends($request->all());

        return view('admin.Activity.index', compact('activities'));
    }

    public function store(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'name' => 'required|string|max:255|unique:post_activities',
                'category' => 'required|string|max:255',
                'status' => 'required|in:active,inactive',
                'web_icon' => 'nullable|file|mimes:jpeg,jpg,png,gif,svg|max:10240',
                'mobile_icon' => 'nullable|file|mimes:jpeg,jpg,png,gif,svg|max:10240',
            ]);

            $createData = [
                'name' => $validatedData['name'],
                'category' => $validatedData['category'],
                'status' => $validatedData['status'],
                'created_by' => Auth::id(),
            ];

            // Handle web icon upload
            if ($request->hasFile('web_icon')) {
                $webIcon = $request->file('web_icon')->getClientOriginalName();
                $webIconPath = $request->file('web_icon')->storeAs('ActivityIcons/web', $webIcon, 'public');
                $createData['web_icon'] = $webIconPath;
            }

            // Handle mobile icon upload
            if ($request->hasFile('mobile_icon')) {
                $mobileIcon = $request->file('mobile_icon')->getClientOriginalName();
                $mobileIconPath = $request->file('mobile_icon')->storeAs('ActivityIcons/mobile', $mobileIcon, 'public');
                $createData['mobile_icon'] = $mobileIconPath;
            }

            PostActivity::create($createData);

            return redirect()->route('admin.activity.index')->with('success_message_create', 'Activity created successfully');
        } catch (\Exception $e) {
            return redirect()->back()->withInput()->with('error_message_create', 'Failed to create activity: ' . $e->getMessage());
        }
    }

    public function edit($id)
    {
        try {
            $activity = PostActivity::findOrFail($id);

            return view('admin.Activity.edit', compact('activity'));
        } catch (\Exception $e) {
            return redirect()->route('admin.activity.index')->with('error_message', 'Activity not found: ' . $e->getMessage());
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $activity = PostActivity::findOrFail($id);

            $validatedData = $request->validate([
                'name' => [
                    'required',
                    'string',
                    'max:255',
                    Rule::unique('post_activities')->ignore($id)
                ],
                'category' => 'required|string|max:255',
                'status' => 'required|in:active,inactive',
                'web_icon' => 'nullable|file|mimes:jpeg,jpg,png,gif,svg|max:10240',
                'mobile_icon' => 'nullable|file|mimes:jpeg,jpg,png,gif,svg|max:10240',
            ]);

            $updateData = [
                'name' => $validatedData['name'],
                'category' => $validatedData['category'],
                'status' => $validatedData['status'],
                'updated_by' => Auth::id(),
            ];

            // Handle web icon upload
            if ($request->hasFile('web_icon')) {
                // Delete old web icon if exists
                if ($activity->web_icon && Storage::disk('public')->exists($activity->web_icon)) {
                    Storage::disk('public')->delete($activity->web_icon);
                }

                $webIcon = $request->file('web_icon')->getClientOriginalName();
                $webIconPath = $request->file('web_icon')->storeAs('ActivityIcons/web', $webIcon, 'public');
                $updateData['web_icon'] = $webIconPath;
            }

            // Handle mobile icon upload
            if ($request->hasFile('mobile_icon')) {
                // Delete old mobile icon if exists
                if ($activity->mobile_icon && Storage::disk('public')->exists($activity->mobile_icon)) {
                    Storage::disk('public')->delete($activity->mobile_icon);
                }

                $mobileIcon = $request->file('mobile_icon')->getClientOriginalName();
                $mobileIconPath = $request->file('mobile_icon')->storeAs('ActivityIcons/mobile', $mobileIcon, 'public');
                $updateData['mobile_icon'] = $mobileIconPath;
            }

            $activity->update($updateData);

            return redirect()->route('admin.activity.index')->with('success_message', 'Activity updated successfully');
        } catch (\Exception $e) {
            return redirect()->back()->withInput()->with('error_message', 'Failed to update activity: ' . $e->getMessage());
        }
    }

    public function delete($id)
    {
        try {
            $activity = PostActivity::findOrFail($id);

            // Delete the icons if they exist
            if ($activity->web_icon && Storage::disk('public')->exists($activity->web_icon)) {
                Storage::disk('public')->delete($activity->web_icon);
            }

            if ($activity->mobile_icon && Storage::disk('public')->exists($activity->mobile_icon)) {
                Storage::disk('public')->delete($activity->mobile_icon);
            }

            $activity->delete();

            return redirect()->route('admin.activity.index')->with('success_message', 'Activity deleted successfully');
        } catch (\Exception $e) {
            return redirect()->route('admin.activity.index')->with('error_message', 'Failed to delete activity: ' . $e->getMessage());
        }
    }

    public function export(Request $request)
    {
        try {
            $query = PostActivity::query();

            // Apply the same filters as in index
            if ($request->has('search') && $request->search != '') {
                $searchTerm = $request->search;
                $query->where(function ($q) use ($searchTerm) {
                    $q->where('name', 'like', '%' . $searchTerm . '%')
                      ->orWhere('category', 'like', '%' . $searchTerm . '%');
                });
            }

            if ($request->has('status') && $request->status != '') {
                $query->where('status', $request->status);
            }

            if ($request->has('category') && $request->category != '') {
                $query->where('category', $request->category);
            }

            if ($request->has('from_date') && $request->from_date != '') {
                $query->whereDate('created_at', '>=', $request->from_date);
            }

            if ($request->has('to_date') && $request->to_date != '') {
                $query->whereDate('created_at', '<=', $request->to_date);
            }

            $activities = $query->get();

            $filename = 'activities_export_' . date('Y-m-d_H-i-s') . '.csv';
            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ];

            $callback = function () use ($activities) {
                $handle = fopen('php://output', 'w');
                fputcsv($handle, ['ID', 'Name', 'Category', 'Status', 'Created At', 'Updated At']);

                foreach ($activities as $activity) {
                    fputcsv($handle, [
                        $activity->id,
                        $activity->name,
                        $activity->category,
                        $activity->status,
                        $activity->created_at->format('Y-m-d H:i:s'),
                        $activity->updated_at->format('Y-m-d H:i:s'),
                    ]);
                }

                fclose($handle);
            };

            return response()->stream($callback, 200, $headers);
        } catch (\Exception $e) {
            return redirect()->back()->with('error_message', 'Failed to export activities: ' . $e->getMessage());
        }
    }
}
