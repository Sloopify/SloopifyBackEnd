<?php

namespace App\Http\Controllers\Admin\Interest;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Interest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class InterestController extends Controller
{
    public function index(Request $request)
    {
        $query = Interest::query();

        // Search functionality
        if ($request->has('search') && $request->search != '') {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'like', '%' . $searchTerm . '%')
                    ->orWhere('category', 'like', '%' . $searchTerm . '%');
            });
        }

        // Filter by category
        if ($request->has('category') && $request->category != '') {
            $query->where('category', $request->category);
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

        $interests = $query->paginate($perPage)->appends($request->all());

        $categories = [
            'Entertainment & Hobbies',
            'Education & Learning',
            'Technology & Digital',
            'Health & Fitness',
            'Career & Business',
            'Lifestyle & Travel',
            'Art & Creativity',
            'Science & Nature',
            'Food & Drink',
            'Social & Community',
            'Other'
        ];

        return view('admin.Interest.index', compact('interests', 'categories'));
    }

    public function store(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'name' => 'required|string|max:255|unique:interests',
                'category' => 'required|in:Entertainment & Hobbies,Education & Learning,Technology & Digital,Health & Fitness,Career & Business,Lifestyle & Travel,Art & Creativity,Science & Nature,Food & Drink,Social & Community,Other',
                'status' => 'required|in:active,inactive',
                'web_icon' => 'nullable|image|max:10240',
                'mobile_icon' => 'nullable|image|max:10240',
            ]);

            $createData = [
                'name' => $validatedData['name'],
                'category' => $validatedData['category'],
                'status' => $validatedData['status'],
            ];

            // Handle web icon upload
            if ($request->hasFile('web_icon')) {
                $webIcon = $request->file('web_icon')->getClientOriginalName();
                $webIconPath = $request->file('web_icon')->storeAs('InterestIcons/web', $webIcon, 'public');
                $createData['web_icon'] = $webIconPath;
            }

            // Handle mobile icon upload
            if ($request->hasFile('mobile_icon')) {
                $mobileIcon = $request->file('mobile_icon')->getClientOriginalName();
                $mobileIconPath = $request->file('mobile_icon')->storeAs('InterestIcons/mobile', $mobileIcon, 'public');
                $createData['mobile_icon'] = $mobileIconPath;
            }

            Interest::create($createData);

            return redirect()->route('admin.interest.index')->with('success_message_create', 'Interest created successfully');
        } catch (\Exception $e) {
            return redirect()->back()->withInput()->with('error_message_create', 'Failed to create interest: ' . $e->getMessage());
        }
    }

    public function edit($id)
    {
        try {
            $interest = Interest::findOrFail($id);

            $categories = [
                'Entertainment & Hobbies',
                'Education & Learning',
                'Technology & Digital',
                'Health & Fitness',
                'Career & Business',
                'Lifestyle & Travel',
                'Art & Creativity',
                'Science & Nature',
                'Food & Drink',
                'Social & Community',
                'Other'
            ];

            return view('admin.Interest.edit', compact('interest', 'categories'));
        } catch (\Exception $e) {
            return redirect()->route('admin.interest.index')->with('error_message', 'Interest not found: ' . $e->getMessage());
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $interest = Interest::findOrFail($id);

            $validatedData = $request->validate([
                'name' => [
                    'required',
                    'string',
                    'max:255',
                    Rule::unique('interests')->ignore($id)
                ],
                'category' => 'required|in:Entertainment & Hobbies,Education & Learning,Technology & Digital,Health & Fitness,Career & Business,Lifestyle & Travel,Art & Creativity,Science & Nature,Food & Drink,Social & Community,Other',
                'status' => 'required|in:active,inactive',
                'web_icon' => 'nullable|image|max:10240',
                'mobile_icon' => 'nullable|image|max:10240',
            ]);

            $updateData = [
                'name' => $validatedData['name'],
                'category' => $validatedData['category'],
                'status' => $validatedData['status'],
            ];

            // Handle web icon upload
            if ($request->hasFile('web_icon')) {
                // Delete old web icon if exists
                if ($interest->web_icon && Storage::disk('public')->exists($interest->web_icon)) {
                    Storage::disk('public')->delete($interest->web_icon);
                }

                $webIcon = $request->file('web_icon')->getClientOriginalName();
                $webIconPath = $request->file('web_icon')->storeAs('InterestIcons/web', $webIcon, 'public');
                $updateData['web_icon'] = $webIconPath;
            }

            // Handle mobile icon upload
            if ($request->hasFile('mobile_icon')) {
                // Delete old mobile icon if exists
                if ($interest->mobile_icon && Storage::disk('public')->exists($interest->mobile_icon)) {
                    Storage::disk('public')->delete($interest->mobile_icon);
                }

                $mobileIcon = $request->file('mobile_icon')->getClientOriginalName();
                $mobileIconPath = $request->file('mobile_icon')->storeAs('InterestIcons/mobile', $mobileIcon, 'public');
                $updateData['mobile_icon'] = $mobileIconPath;
            }

            $interest->update($updateData);

            return redirect()->route('admin.interest.index')->with('success_message', 'Interest updated successfully');
        } catch (\Exception $e) {
            return redirect()->back()->withInput()->with('error_message', 'Failed to update interest: ' . $e->getMessage());
        }
    }

    public function delete($id)
    {
        try {
            $interest = Interest::findOrFail($id);

            // Delete the icons if they exist
            if ($interest->web_icon && Storage::disk('public')->exists($interest->web_icon)) {
                Storage::disk('public')->delete($interest->web_icon);
            }

            if ($interest->mobile_icon && Storage::disk('public')->exists($interest->mobile_icon)) {
                Storage::disk('public')->delete($interest->mobile_icon);
            }

            $interest->delete();

            return redirect()->route('admin.interest.index')->with('success_message', 'Interest deleted successfully');
        } catch (\Exception $e) {
            return redirect()->route('admin.interest.index')->with('error_message', 'Failed to delete interest: ' . $e->getMessage());
        }
    }

    public function export(Request $request)
    {
        try {
            $query = Interest::query();

            // Apply the same filters as in index
            if ($request->has('search') && $request->search != '') {
                $searchTerm = $request->search;
                $query->where(function ($q) use ($searchTerm) {
                    $q->where('name', 'like', '%' . $searchTerm . '%')
                        ->orWhere('category', 'like', '%' . $searchTerm . '%');
                });
            }

            if ($request->has('category') && $request->category != '') {
                $query->where('category', $request->category);
            }

            if ($request->has('status') && $request->status != '') {
                $query->where('status', $request->status);
            }

            if ($request->has('from_date') && $request->from_date != '') {
                $query->whereDate('created_at', '>=', $request->from_date);
            }

            if ($request->has('to_date') && $request->to_date != '') {
                $query->whereDate('created_at', '<=', $request->to_date);
            }

            $interests = $query->get();

            $filename = 'interests_export_' . date('Y-m-d_H-i-s') . '.csv';
            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ];

            $callback = function () use ($interests) {
                $handle = fopen('php://output', 'w');
                fputcsv($handle, ['ID', 'Name', 'Category', 'Status', 'Created At', 'Updated At']);

                foreach ($interests as $interest) {
                    fputcsv($handle, [
                        $interest->id,
                        $interest->name,
                        $interest->category,
                        $interest->status,
                        $interest->created_at->format('Y-m-d H:i:s'),
                        $interest->updated_at->format('Y-m-d H:i:s'),
                    ]);
                }

                fclose($handle);
            };

            return response()->stream($callback, 200, $headers);
        } catch (\Exception $e) {
            return redirect()->back()->with('error_message', 'Failed to export interests: ' . $e->getMessage());
        }
    }
}
