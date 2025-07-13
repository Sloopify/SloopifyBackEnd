<?php

namespace App\Http\Controllers\Admin\Feeling;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PostFeeling;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class FellingController extends Controller
{
    public function index(Request $request)
    {
        $query = PostFeeling::query();

        // Search functionality
        if ($request->has('search') && $request->search != '') {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'like', '%' . $searchTerm . '%');
            });
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

        $feelings = $query->paginate($perPage)->appends($request->all());

        return view('admin.Feeling.index', compact('feelings'));
    }

    public function store(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'name' => 'required|string|max:255|unique:post_feelings',
                'status' => 'required|in:active,inactive',
                'web_icon' => 'nullable|file|mimes:jpeg,jpg,png,gif,svg|max:10240',
                'mobile_icon' => 'nullable|file|mimes:jpeg,jpg,png,gif,svg|max:10240',
            ]);

            $createData = [
                'name' => $validatedData['name'],
                'status' => $validatedData['status'],
                'created_by' => Auth::id(),
            ];

            // Handle web icon upload
            if ($request->hasFile('web_icon')) {
                $webIcon = $request->file('web_icon')->getClientOriginalName();
                $webIconPath = $request->file('web_icon')->storeAs('FeelingIcons/web', $webIcon, 'public');
                $createData['web_icon'] = $webIconPath;
            }

            // Handle mobile icon upload
            if ($request->hasFile('mobile_icon')) {
                $mobileIcon = $request->file('mobile_icon')->getClientOriginalName();
                $mobileIconPath = $request->file('mobile_icon')->storeAs('FeelingIcons/mobile', $mobileIcon, 'public');
                $createData['mobile_icon'] = $mobileIconPath;
            }

            PostFeeling::create($createData);

            return redirect()->route('admin.feeling.index')->with('success_message_create', 'Feeling created successfully');
        } catch (\Exception $e) {
            return redirect()->back()->withInput()->with('error_message_create', 'Failed to create feeling: ' . $e->getMessage());
        }
    }

    public function edit($id)
    {
        try {
            $feeling = PostFeeling::findOrFail($id);

            return view('admin.Feeling.edit', compact('feeling'));
        } catch (\Exception $e) {
            return redirect()->route('admin.feeling.index')->with('error_message', 'Feeling not found: ' . $e->getMessage());
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $feeling = PostFeeling::findOrFail($id);

            $validatedData = $request->validate([
                'name' => [
                    'required',
                    'string',
                    'max:255',
                    Rule::unique('post_feelings')->ignore($id)
                ],
                'status' => 'required|in:active,inactive',
                'web_icon' => 'nullable|file|mimes:jpeg,jpg,png,gif,svg|max:10240',
                'mobile_icon' => 'nullable|file|mimes:jpeg,jpg,png,gif,svg|max:10240',
            ]);

            $updateData = [
                'name' => $validatedData['name'],
                'status' => $validatedData['status'],
                'updated_by' => Auth::id(),
            ];

            // Handle web icon upload
            if ($request->hasFile('web_icon')) {
                // Delete old web icon if exists
                if ($feeling->web_icon && Storage::disk('public')->exists($feeling->web_icon)) {
                    Storage::disk('public')->delete($feeling->web_icon);
                }

                $webIcon = $request->file('web_icon')->getClientOriginalName();
                $webIconPath = $request->file('web_icon')->storeAs('FeelingIcons/web', $webIcon, 'public');
                $updateData['web_icon'] = $webIconPath;
            }

            // Handle mobile icon upload
            if ($request->hasFile('mobile_icon')) {
                // Delete old mobile icon if exists
                if ($feeling->mobile_icon && Storage::disk('public')->exists($feeling->mobile_icon)) {
                    Storage::disk('public')->delete($feeling->mobile_icon);
                }

                $mobileIcon = $request->file('mobile_icon')->getClientOriginalName();
                $mobileIconPath = $request->file('mobile_icon')->storeAs('FeelingIcons/mobile', $mobileIcon, 'public');
                $updateData['mobile_icon'] = $mobileIconPath;
            }

            $feeling->update($updateData);

            return redirect()->route('admin.feeling.index')->with('success_message', 'Feeling updated successfully');
        } catch (\Exception $e) {
            return redirect()->back()->withInput()->with('error_message', 'Failed to update feeling: ' . $e->getMessage());
        }
    }

    public function delete($id)
    {
        try {
            $feeling = PostFeeling::findOrFail($id);

            // Delete the icons if they exist
            if ($feeling->web_icon && Storage::disk('public')->exists($feeling->web_icon)) {
                Storage::disk('public')->delete($feeling->web_icon);
            }

            if ($feeling->mobile_icon && Storage::disk('public')->exists($feeling->mobile_icon)) {
                Storage::disk('public')->delete($feeling->mobile_icon);
            }

            $feeling->delete();

            return redirect()->route('admin.feeling.index')->with('success_message', 'Feeling deleted successfully');
        } catch (\Exception $e) {
            return redirect()->route('admin.feeling.index')->with('error_message', 'Failed to delete feeling: ' . $e->getMessage());
        }
    }

    public function export(Request $request)
    {
        try {
            $query = PostFeeling::query();

            // Apply the same filters as in index
            if ($request->has('search') && $request->search != '') {
                $searchTerm = $request->search;
                $query->where(function ($q) use ($searchTerm) {
                    $q->where('name', 'like', '%' . $searchTerm . '%');
                });
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

            $feelings = $query->get();

            $filename = 'feelings_export_' . date('Y-m-d_H-i-s') . '.csv';
            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ];

            $callback = function () use ($feelings) {
                $handle = fopen('php://output', 'w');
                fputcsv($handle, ['ID', 'Name', 'Status', 'Created At', 'Updated At']);

                foreach ($feelings as $feeling) {
                    fputcsv($handle, [
                        $feeling->id,
                        $feeling->name,
                        $feeling->status,
                        $feeling->created_at->format('Y-m-d H:i:s'),
                        $feeling->updated_at->format('Y-m-d H:i:s'),
                    ]);
                }

                fclose($handle);
            };

            return response()->stream($callback, 200, $headers);
        } catch (\Exception $e) {
            return redirect()->back()->with('error_message', 'Failed to export feelings: ' . $e->getMessage());
        }
    }
}
