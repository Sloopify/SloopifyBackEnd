<?php

namespace App\Http\Controllers\Api\V1\User\Profile;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Api\V1\User\Auth\AuthController;
use App\Models\Post;
use App\Models\Friendship;
use App\Models\UserEducation;
use Illuminate\Validation\ValidationException;
use Exception;

class ProfileController extends Controller
{
    //

    private function mapEducationDetails($educations)
    {
        return $educations->map(function ($education) {
            return [
                'id' => $education->id,
                'education_level' => $education->education_level,
                'education_level_display' => $education->education_level_display,
                'institution_name' => $education->institution_name,
                'field_of_study' => $education->field_of_study,
                'description' => $education->description,
                'status' => $education->status,
                'status_display' => $education->status_display,
                'start_year' => $education->start_year,
                'end_year' => $education->end_year,
                'duration' => $education->duration,
                'is_current' => $education->is_current,
                'sort_order' => $education->sort_order,
                'created_at' => $education->created_at,
                'updated_at' => $education->updated_at
            ];
        });
    }

    public function getMyInfo(Request $request)
    {
        $user = Auth::guard('user')->user();

        if (!$user) {
            return response()->json([
                'status_code' => 404,
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }

        $userDetails = app(AuthController::class)->mapUserDetails($user);

        return response()->json([
            'status_code' => 200,
            'success' => true,
            'message' => 'Profile fetched successfully',
            'data' => [
                'user' => $userDetails
            ]
        ], 200);
    }

    public function getTotalPosts(Request $request)
    {
        $user = Auth::guard('user')->user();

        if (!$user) {
            return response()->json([
                'status_code' => 404,
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }

        $totalPosts = Post::where('user_id', $user->id)->count();

        return response()->json([
            'status_code' => 200,
            'success' => true,
            'message' => 'Total posts fetched successfully',
            'data' => [
                'total_posts' => $totalPosts
            ]
        ], 200);
    }

    public function getTotalFriends(Request $request)
    {
        $user = Auth::guard('user')->user();

        if (!$user) {
            return response()->json([
                'status_code' => 404,
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }

        // Count accepted friendships (same logic as FriendController)
        $totalFriends = Friendship::where(function($query) use ($user) {
            $query->where('user_id', $user->id)
                  ->orWhere('friend_id', $user->id);
        })
        ->where('status', 'accepted')
        ->count();

        return response()->json([
            'status_code' => 200,
            'success' => true,
            'message' => 'Total friends fetched successfully',
            'data' => [
                'total_friends' => $totalFriends
            ]
        ], 200);
    }

    public function getMyEducations(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'page' => 'nullable|integer|min:1',
                'per_page' => 'nullable|integer|min:1|max:100',
                'sort_by' => 'nullable|string|in:education_level,start_year,end_year,created_at',
                'sort_order' => 'nullable|string|in:asc,desc',
                'status' => 'nullable|string|in:currently_studying,currently_enrolled,graduated,completed,did_not_graduate,dropped_out,all'
            ]);

            $user = Auth::guard('user')->user();
            $perPage = $validatedData['per_page'] ?? 20;
            $sortBy = $validatedData['sort_by'] ?? 'created_at';
            $sortOrder = $validatedData['sort_order'] ?? 'desc';
            $statusFilter = $validatedData['status'] ?? 'all';

            if (!$user) {
                return response()->json([
                    'status_code' => 404,
                    'success' => false,
                    'message' => 'User not found'
                ], 404);
            }

            // Build query
            $query = UserEducation::where('user_id', $user->id);

            // Apply status filter
            if ($statusFilter !== 'all') {
                $query->where('status', $statusFilter);
            }

            // Apply sorting
            if ($sortBy === 'education_level') {
                $query->orderBy('education_level', $sortOrder);
            } elseif ($sortBy === 'start_year') {
                $query->orderBy('start_year', $sortOrder);
            } elseif ($sortBy === 'end_year') {
                $query->orderBy('end_year', $sortOrder);
            } else {
                $query->orderBy('created_at', $sortOrder);
            }

            // Get educations with pagination
            $educations = $query->paginate($perPage);

            if ($educations->isEmpty()) {
                return response()->json([
                    'status_code' => 200,
                    'success' => true,
                    'message' => 'No educations found',
                    'data' => [
                        'educations' => [],
                        'total_educations' => 0,
                        'current_filter' => $statusFilter,
                        'sorting' => [
                            'sort_by' => $sortBy,
                            'sort_order' => $sortOrder
                        ],
                        'pagination' => [
                            'current_page' => $educations->currentPage(),
                            'last_page' => $educations->lastPage(),
                            'per_page' => $educations->perPage(),
                            'total' => $educations->total(),
                            'from' => $educations->firstItem(),
                            'to' => $educations->lastItem(),
                            'has_more_pages' => $educations->hasMorePages()
                        ]
                    ]
                ], 200);
            }

            // Map educations data with additional computed attributes
            $mappedEducations = $this->mapEducationDetails($educations->getCollection());

            return response()->json([
                'status_code' => 200,
                'success' => true,
                'message' => 'My educations fetched successfully',
                'data' => [
                    'educations' => $mappedEducations,
                    'total_educations' => $educations->total(),
                    'current_filter' => $statusFilter,
                    'sorting' => [
                        'sort_by' => $sortBy,
                        'sort_order' => $sortOrder
                    ],
                    'pagination' => [
                        'current_page' => $educations->currentPage(),
                        'last_page' => $educations->lastPage(),
                        'per_page' => $educations->perPage(),
                        'total' => $educations->total(),
                        'from' => $educations->firstItem(),
                        'to' => $educations->lastItem(),
                        'has_more_pages' => $educations->hasMorePages()
                    ]
                ]
            ], 200);

        } catch (ValidationException $e) {
            return response()->json([
                'status_code' => 422,
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'status_code' => 500,
                'success' => false,
                'message' => 'Failed to fetch educations',
                'error' => $e->getMessage()
            ], 500);
        }
    }

 
}
