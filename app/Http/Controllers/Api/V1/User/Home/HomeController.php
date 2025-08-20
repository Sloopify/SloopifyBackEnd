<?php

namespace App\Http\Controllers\Api\V1\User\Home;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\DailyStatus;
use App\Models\User;
use Illuminate\Validation\ValidationException;
use Exception;
use App\Http\Controllers\Api\V1\User\Story\StoryController;
use App\Http\Controllers\Api\V1\User\Post\PostController;

class HomeController extends Controller
{
    
   
    private function buildIconUrl($icon)
    {
        if (!$icon) {
            return null;
        }

        // If it's a file name or storage path, build a full URL; if it's an emoji/text, return as-is
        $looksLikeFile = (bool) preg_match('/\.(png|jpe?g|webp|svg)$/i', $icon);
        $looksLikePath = strpos($icon, '/') !== false;

        if ($looksLikeFile) {
            return config('app.url') . asset('storage/' . ltrim($icon, '/'));
        }

        if ($looksLikePath) {
            return config('app.url') . asset(ltrim($icon, '/'));
        }

        return $icon;
    }

    private function mapDailyStatus($status, $user = null)
    {
        $isActiveStatus = false;
        $expiresAt = null;

        if ($user && $user->daily_status_id == $status->id) {
            $isActiveStatus = true;
            $expiresAt = $user->daily_status_expires_at;
        }

        return [
            'id' => $status->id,
            'name' => $status->name,
            'web_icon' => $this->buildIconUrl($status->web_icon),
            'mobile_icon' => $this->buildIconUrl($status->mobile_icon),
            'status' => (bool) $status->status,
            'is_user_active_status' => $isActiveStatus,
            'expires_at' => $expiresAt,
            'expires_at_human' => $expiresAt ? $expiresAt->diffForHumans() : null,
            'created_at' => $status->created_at,
            'updated_at' => $status->updated_at,
        ];
    }

    private function mapDailyStatuses($statuses, $user = null)
    {
        if (is_array($statuses)) {
            $statuses = collect($statuses);
        }

        return $statuses->map(function ($status) use ($user) {
            return $this->mapDailyStatus($status, $user);
        })->values();
    }
    
    public function getDailyStatuses(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'page' => 'nullable|integer|min:1',
                'per_page' => 'nullable|integer|min:1|max:100',
                'sort_by' => 'nullable|string|in:name,created_at',
                'sort_order' => 'nullable|string|in:asc,desc',
                'status' => 'nullable|boolean'
            ]);

            $user = Auth::guard('user')->user();
            $perPage = $validatedData['per_page'] ?? 20;
            $sortBy = $validatedData['sort_by'] ?? 'name';
            $sortOrder = $validatedData['sort_order'] ?? 'asc';
            $statusFilter = $validatedData['status'] ?? null;
            
            if (!$user) {
                return response()->json([
                    'status_code' => 404,
                    'success' => false,
                    'message' => 'User not found'
                ], 404);
            }

            // Build query
            $query = DailyStatus::query();

            // Apply status filter
            if ($statusFilter !== null) {
                $query->where('status', $statusFilter);
            } else {
                // Default to active statuses only
                $query->where('status', true);
            }

            // Apply sorting
            if ($sortBy === 'name') {
                $query->orderBy('name', $sortOrder);
            } elseif ($sortBy === 'created_at') {
                $query->orderBy('created_at', $sortOrder);
            }

            // Get daily statuses with pagination
            $dailyStatuses = $query->paginate($perPage);

            if($dailyStatuses->isEmpty()) {
                return response()->json([
                    'status_code' => 200,
                    'success' => true,
                    'message' => 'No daily statuses found',
                    'data' => [
                        'daily_statuses' => [],
                        'total_statuses' => 0,
                        'current_filter' => $statusFilter,
                        'sorting' => [
                            'sort_by' => $sortBy,
                            'sort_order' => $sortOrder
                        ],
                        'pagination' => [
                            'current_page' => $dailyStatuses->currentPage(),
                            'last_page' => $dailyStatuses->lastPage(),
                            'per_page' => $dailyStatuses->perPage(),
                            'total' => $dailyStatuses->total(),
                            'from' => $dailyStatuses->firstItem(),
                            'to' => $dailyStatuses->lastItem(),
                            'has_more_pages' => $dailyStatuses->hasMorePages()
                        ]
                    ]
                ], 200);
            }

            // Map daily statuses using helper
            $mappedStatuses = $this->mapDailyStatuses($dailyStatuses->getCollection(), $user);

            return response()->json([
                'status_code' => 200,
                'success' => true,
                'message' => 'Daily statuses retrieved successfully',
                'data' => [
                    'daily_statuses' => $mappedStatuses,
                    'total_statuses' => $dailyStatuses->total(),
                    'current_filter' => $statusFilter,
                    'sorting' => [
                        'sort_by' => $sortBy,
                        'sort_order' => $sortOrder
                    ],
                    'pagination' => [
                        'current_page' => $dailyStatuses->currentPage(),
                        'last_page' => $dailyStatuses->lastPage(),
                        'per_page' => $dailyStatuses->perPage(),
                        'total' => $dailyStatuses->total(),
                        'from' => $dailyStatuses->firstItem(),
                        'to' => $dailyStatuses->lastItem(),
                        'has_more_pages' => $dailyStatuses->hasMorePages()
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
                'message' => 'Failed to retrieve daily statuses',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getHome(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'stories_page' => 'nullable|integer|min:1',
                'stories_per_page' => 'nullable|integer|min:1|max:100',
                'feed_page' => 'nullable|integer|min:1',
                'feed_per_page' => 'nullable|integer|min:1|max:50',
                'daily_statuses_page' => 'nullable|integer|min:1',
                'daily_statuses_per_page' => 'nullable|integer|min:1|max:100'
            ]);

            $user = Auth::guard('user')->user();
            
            if (!$user) {
                return response()->json([
                    'status_code' => 404,
                    'success' => false,
                    'message' => 'User not found'
                ], 404);
            }

            // Initialize controllers
            $storyController = app(StoryController::class);
            $postController = app(PostController::class);

            // Prepare request objects for each controller method
            $storiesRequest = new Request([
                'page' => $validatedData['stories_page'] ?? 1,
                'per_page' => $validatedData['stories_per_page'] ?? 20
            ]);

            $feedRequest = new Request([
                'page' => $validatedData['feed_page'] ?? 1,
                'per_page' => $validatedData['feed_per_page'] ?? 20
            ]);

            $dailyStatusesRequest = new Request([
                'page' => $validatedData['daily_statuses_page'] ?? 1,
                'per_page' => $validatedData['daily_statuses_per_page'] ?? 20
            ]);

            $currentDailyStatusRequest = new Request();

            // Call each controller method
            $storiesResponse = $storyController->getStories($storiesRequest);
            $feedResponse = $postController->getFeed($feedRequest);
            $dailyStatusesResponse = $this->getDailyStatuses($dailyStatusesRequest);
            $currentDailyStatusResponse = $this->getCurrentDailyStatus($currentDailyStatusRequest);

            // Extract data from responses
            $storiesData = json_decode($storiesResponse->getContent(), true);
            $feedData = json_decode($feedResponse->getContent(), true);
            $dailyStatusesData = json_decode($dailyStatusesResponse->getContent(), true);
            $currentDailyStatusData = json_decode($currentDailyStatusResponse->getContent(), true);

            // Check if any of the calls failed
            $failedCalls = [];
            if ($storiesData['status_code'] !== 200) {
                $failedCalls[] = 'stories';
            }
            if ($feedData['status_code'] !== 200) {
                $failedCalls[] = 'feed';
            }
            if ($dailyStatusesData['status_code'] !== 200) {
                $failedCalls[] = 'daily_statuses';
            }
            if ($currentDailyStatusData['status_code'] !== 200) {
                $failedCalls[] = 'current_daily_status';
            }

            if (!empty($failedCalls)) {
                return response()->json([
                    'status_code' => 500,
                    'success' => false,
                    'message' => 'Some data could not be retrieved',
                    'failed_calls' => $failedCalls
                ], 500);
            }

            return response()->json([
                'status_code' => 200,
                'success' => true,
                'message' => 'Home data retrieved successfully',
                'data' => [
                    'stories' => $storiesData['data'] ?? [],
                    'feed' => $feedData['data'] ?? [],
                    'daily_statuses' => $dailyStatusesData['data'] ?? [],
                    'current_daily_status' => $currentDailyStatusData['data'] ?? [],
                    'requested_at' => now()->toISOString()
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
                'message' => 'Failed to retrieve home data',
                'error' => $e->getMessage()
            ], 500);
        }
    }
  
    public function searchDailyStatuses(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'search' => 'required|string|min:1|max:255',
                'page' => 'nullable|integer|min:1',
                'per_page' => 'nullable|integer|min:1|max:100',
                'sort_by' => 'nullable|string|in:name,created_at',
                'sort_order' => 'nullable|string|in:asc,desc',
                'status' => 'nullable|boolean'
            ]);

            $user = Auth::guard('user')->user();
            $searchQuery = $validatedData['search'];
            $perPage = $validatedData['per_page'] ?? 20;
            $sortBy = $validatedData['sort_by'] ?? 'name';
            $sortOrder = $validatedData['sort_order'] ?? 'asc';
            $statusFilter = $validatedData['status'] ?? null;
            
            if (!$user) {
                return response()->json([
                    'status_code' => 404,
                    'success' => false,
                    'message' => 'User not found'
                ], 404);
            }

            // Build query with search
            $query = DailyStatus::search($searchQuery);

            // Apply status filter
            if ($statusFilter !== null) {
                $query->where('status', $statusFilter);
            } else {
                // Default to active statuses only
                $query->where('status', true);
            }

            // Apply sorting
            if ($sortBy === 'name') {
                $query->orderBy('name', $sortOrder);
            } elseif ($sortBy === 'created_at') {
                $query->orderBy('created_at', $sortOrder);
            }

            // Get daily statuses with pagination
            $dailyStatuses = $query->paginate($perPage);

            if($dailyStatuses->isEmpty()) {
                return response()->json([
                    'status_code' => 200,
                    'success' => true,
                    'message' => 'No daily statuses found matching your search',
                    'data' => [
                        'daily_statuses' => [],
                        'total_statuses' => 0,
                        'search_query' => $searchQuery,
                        'current_filter' => $statusFilter,
                        'sorting' => [
                            'sort_by' => $sortBy,
                            'sort_order' => $sortOrder
                        ],
                        'pagination' => [
                            'current_page' => $dailyStatuses->currentPage(),
                            'last_page' => $dailyStatuses->lastPage(),
                            'per_page' => $dailyStatuses->perPage(),
                            'total' => $dailyStatuses->total(),
                            'from' => $dailyStatuses->firstItem(),
                            'to' => $dailyStatuses->lastItem(),
                            'has_more_pages' => $dailyStatuses->hasMorePages()
                        ]
                    ]
                ], 200);
            }

            // Map daily statuses using helper
            $mappedStatuses = $this->mapDailyStatuses($dailyStatuses->getCollection(), $user);

            return response()->json([
                'status_code' => 200,
                'success' => true,
                'message' => 'Daily statuses search results retrieved successfully',
                'data' => [
                    'daily_statuses' => $mappedStatuses,
                    'total_statuses' => $dailyStatuses->total(),
                    'search_query' => $searchQuery,
                    'current_filter' => $statusFilter,
                    'sorting' => [
                        'sort_by' => $sortBy,
                        'sort_order' => $sortOrder
                    ],
                    'pagination' => [
                        'current_page' => $dailyStatuses->currentPage(),
                        'last_page' => $dailyStatuses->lastPage(),
                        'per_page' => $dailyStatuses->perPage(),
                        'total' => $dailyStatuses->total(),
                        'from' => $dailyStatuses->firstItem(),
                        'to' => $dailyStatuses->lastItem(),
                        'has_more_pages' => $dailyStatuses->hasMorePages()
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
                'message' => 'Failed to search daily statuses',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function setDailyStatus(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'daily_status_id' => 'required|integer|exists:daily_statuses,id'
            ]);

            $user = Auth::guard('user')->user();
            
            if (!$user) {
                return response()->json([
                    'status_code' => 404,
                    'success' => false,
                    'message' => 'User not found'
                ], 404);
            }

            DB::beginTransaction();

            // Get the daily status
            $dailyStatus = DailyStatus::find($validatedData['daily_status_id']);

            if (!$dailyStatus) {
                return response()->json([
                    'status_code' => 404,
                    'success' => false,
                    'message' => 'Daily status not found'
                ], 404);
            }

            // Check if the status is active
            if (!$dailyStatus->status) {
                return response()->json([
                    'status_code' => 400,
                    'success' => false,
                    'message' => 'This daily status is not available'
                ], 400);
            }

            // Set expiration to 24 hours from now
            $expiresAt = now()->addDay();

            // Update user's daily status
            $user->update([
                'daily_status_id' => $validatedData['daily_status_id'],
                'daily_status_expires_at' => $expiresAt
            ]);

            DB::commit();

            return response()->json([
                'status_code' => 200,
                'success' => true,
                'message' => 'Daily status set successfully',
                'data' => [
                    'daily_status' => [
                        'id' => $dailyStatus->id,
                        'name' => $dailyStatus->name,
                        'web_icon' => $dailyStatus->web_icon,
                        'mobile_icon' => $dailyStatus->mobile_icon,
                        'status' => $dailyStatus->status
                    ],
                    'expires_at' => $expiresAt,
                    'expires_at_human' => $expiresAt->diffForHumans()
                ]
            ], 200);

        } catch (ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'status_code' => 422,
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'status_code' => 500,
                'success' => false,
                'message' => 'Failed to set daily status',
                'error' => $e->getMessage()
            ], 500);
        }
    }
   
    public function removeDailyStatus(Request $request)
    {
        try {
            $user = Auth::guard('user')->user();
            
            if (!$user) {
                return response()->json([
                    'status_code' => 404,
                    'success' => false,
                    'message' => 'User not found'
                ], 404);
            }

            DB::beginTransaction();

            // Check if user has an active daily status
            if (!$user->daily_status_id) {
                return response()->json([
                    'status_code' => 400,
                    'success' => false,
                    'message' => 'No active daily status to remove'
                ], 400);
            }

            // Remove user's daily status
            $user->update([
                'daily_status_id' => null,
                'daily_status_expires_at' => null
            ]);

            DB::commit();

            return response()->json([
                'status_code' => 200,
                'success' => true,
                'message' => 'Daily status removed successfully'
            ], 200);

        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'status_code' => 500,
                'success' => false,
                'message' => 'Failed to remove daily status',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getCurrentDailyStatus(Request $request)
    {
        try {
            $user = Auth::guard('user')->user();
            
            if (!$user) {
                return response()->json([
                    'status_code' => 404,
                    'success' => false,
                    'message' => 'User not found'
                ], 404);
            }

            // Check if user has an active daily status
            if (!$user->daily_status_id) {
                return response()->json([
                    'status_code' => 200,
                    'success' => true,
                    'message' => 'No active daily status',
                    'data' => [
                        'has_active_status' => false,
                        'daily_status' => null,
                        'expires_at' => null,
                        'expires_at_human' => null
                    ]
                ], 200);
            }

            // Check if the status has expired
            if ($user->daily_status_expires_at && $user->daily_status_expires_at->isPast()) {
                // Remove expired status
                $user->update([
                    'daily_status_id' => null,
                    'daily_status_expires_at' => null
                ]);

                return response()->json([
                    'status_code' => 200,
                    'success' => true,
                    'message' => 'Daily status has expired',
                    'data' => [
                        'has_active_status' => false,
                        'daily_status' => null,
                        'expires_at' => null,
                        'expires_at_human' => null
                    ]
                ], 200);
            }

            // Get the daily status details
            $dailyStatus = DailyStatus::find($user->daily_status_id);

            if (!$dailyStatus) {
                // Remove invalid status
                $user->update([
                    'daily_status_id' => null,
                    'daily_status_expires_at' => null
                ]);

                return response()->json([
                    'status_code' => 200,
                    'success' => true,
                    'message' => 'Daily status not found',
                    'data' => [
                        'has_active_status' => false,
                        'daily_status' => null,
                        'expires_at' => null,
                        'expires_at_human' => null
                    ]
                ], 200);
            }

            return response()->json([
                'status_code' => 200,
                'success' => true,
                'message' => 'Current daily status retrieved successfully',
                'data' => [
                    'has_active_status' => true,
                    'daily_status' => [
                        'id' => $dailyStatus->id,
                        'name' => $dailyStatus->name,
                        'web_icon' => $dailyStatus->web_icon,
                        'mobile_icon' => $dailyStatus->mobile_icon,
                        'status' => $dailyStatus->status
                    ],
                    'expires_at' => $user->daily_status_expires_at,
                    'expires_at_human' => $user->daily_status_expires_at ? $user->daily_status_expires_at->diffForHumans() : null
                ]
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'status_code' => 500,
                'success' => false,
                'message' => 'Failed to get current daily status',
                'error' => $e->getMessage()
            ], 500);
        }
    }

}
