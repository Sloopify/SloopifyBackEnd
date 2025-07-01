<?php

namespace App\Http\Controllers\Api\V1\User\Friend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Friendship;
use Illuminate\Validation\ValidationException;
use Exception;
use App\Http\Controllers\Api\V1\User\Auth\AuthController;
use App\Services\SessionManagementService;
use App\Models\UserSession;

class FriendController extends Controller
{
    protected $authController;
    protected $sessionService;

    public function __construct(AuthController $authController, SessionManagementService $sessionService)
    {
        $this->authController = $authController;
        $this->sessionService = $sessionService;
    }

     private function getUserOnlineStatus($userId)
     {
        // Consider user online if they have active session with activity in last 5 minutes
        $onlineThreshold = now()->subMinutes(5);
        
        $recentSession = UserSession::where('user_id', $userId)
            ->active()
            ->where('last_activity', '>=', $onlineThreshold)
            ->orderBy('last_activity', 'desc')
            ->first();

        if ($recentSession) {
            return [
                'is_online' => true,
                'last_seen' => $recentSession->last_activity,
                'last_seen_human' => $recentSession->last_activity->diffForHumans(),
                'status' => 'online'
            ];
        }

        // Get the most recent session activity
        $lastSession = UserSession::where('user_id', $userId)
            ->orderBy('last_activity', 'desc')
            ->first();

        if ($lastSession) {
            return [
                'is_online' => false,
                'last_seen' => $lastSession->last_activity,
                'last_seen_human' => $lastSession->last_activity->diffForHumans(),
                'status' => 'offline'
            ];
        }

        return [
            'is_online' => false,
            'last_seen' => null,
            'last_seen_human' => 'Never',
            'status' => 'offline'
        ];
     }
    
     private function addOnlineStatusToUserDetails($userDetails, $userId)
     {
         $onlineStatus = $this->getUserOnlineStatus($userId);
         
         return array_merge($userDetails, [
             'online_status' => $onlineStatus
         ]);
     }

     private function getMutualFriends($currentUserId, $otherUserId)
     {
         // Get current user's friend IDs
         $currentUserFriends = Friendship::where(function($query) use ($currentUserId) {
             $query->where('user_id', $currentUserId)
                   ->orWhere('friend_id', $currentUserId);
         })
         ->where('status', 'accepted')
         ->get()
         ->map(function($friendship) use ($currentUserId) {
             return $friendship->user_id == $currentUserId ? $friendship->friend_id : $friendship->user_id;
         })
         ->toArray();

         // Get other user's friend IDs
         $otherUserFriends = Friendship::where(function($query) use ($otherUserId) {
             $query->where('user_id', $otherUserId)
                   ->orWhere('friend_id', $otherUserId);
         })
         ->where('status', 'accepted')
         ->get()
         ->map(function($friendship) use ($otherUserId) {
             return $friendship->user_id == $otherUserId ? $friendship->friend_id : $friendship->user_id;
         })
         ->toArray();

         // Find mutual friend IDs (intersection)
         $mutualFriendIds = array_intersect($currentUserFriends, $otherUserFriends);

         if (empty($mutualFriendIds)) {
             return [
                 'count' => 0,
                 'friends' => []
             ];
         }

         // Get mutual friends details (limit to first 5 for performance)
         $mutualFriends = User::whereIn('id', $mutualFriendIds)
             ->take(5)
             ->get()
             ->map(function($friend) {
                 return $this->authController->mapUserDetails($friend);
             });

         return [
             'count' => count($mutualFriendIds),
             'friends' => $mutualFriends
         ];
     }
     
     private function addMutualFriendsToUserDetails($userDetails, $currentUserId, $friendId)
     {
         $mutualFriends = $this->getMutualFriends($currentUserId, $friendId);
         
         return array_merge($userDetails, [
             'mutual_friends' => $mutualFriends
         ]);
     }

     private function getProfileImageUrl($user)
     {
         if ($user->provider === 'google') {
             return $user->img;
         }
         
         return $user->img ? config('app.url') . '/storage/' . $user->img : null;
     }

     public function getFriends(Request $request)
     {
        try {
            $validatedData = $request->validate([
                'page' => 'nullable|integer|min:1',
                'per_page' => 'nullable|integer|min:1|max:100',
                'sort_by' => 'nullable|string|in:name,date_accepted',
                'sort_order' => 'nullable|string|in:asc,desc'
            ]);

            $user = Auth::guard('user')->user();
            $perPage = $validatedData['per_page'] ?? 20;
            $sortBy = $validatedData['sort_by'] ?? 'name';
            $sortOrder = $validatedData['sort_order'] ?? 'asc';
            
            if (!$user) {
                return response()->json([
                    'status_code' => 404,
                    'success' => false,
                    'message' => 'User not found'
                ], 404);
            }

            // Build query to get friends with friendship metadata
            $query = User::join('friendships', function($join) use ($user) {
                $join->on(function($query) use ($user) {
                    $query->where(function($subQuery) use ($user) {
                        $subQuery->where('friendships.user_id', $user->id)
                                 ->whereColumn('friendships.friend_id', 'users.id');
                    })->orWhere(function($subQuery) use ($user) {
                        $subQuery->where('friendships.friend_id', $user->id)
                                 ->whereColumn('friendships.user_id', 'users.id');
                    });
                });
            })
            ->where('friendships.status', 'accepted')
            ->select('users.*', 'friendships.responded_at', 'friendships.requested_at', 'friendships.created_at as friendship_created_at');

            // Apply sorting
            if ($sortBy === 'name') {
                $query->orderByRaw("CONCAT(users.first_name, ' ', users.last_name) {$sortOrder}");
            } elseif ($sortBy === 'date_accepted') {
                $query->orderBy('friendships.responded_at', $sortOrder);
            }

            // Get friends with pagination
            $friends = $query->paginate($perPage);

            if($friends->isEmpty()) {
                return response()->json([
                    'status_code' => 404,
                    'success' => false,
                    'message' => 'No friends found'
                ], 404);
            }

            // Map friends data with online status and mutual friends
            $mappedFriends = $friends->getCollection()->map(function ($friend) use ($user) {
                $userDetails = $this->authController->mapUserDetails($friend);
                $userDetails = $this->addOnlineStatusToUserDetails($userDetails, $friend->id);
                $userDetails = $this->addMutualFriendsToUserDetails($userDetails, $user->id, $friend->id);
                
                // Add friendship metadata - Convert string dates to Carbon instances
                $respondedAt = $friend->responded_at ? \Carbon\Carbon::parse($friend->responded_at) : null;
                $requestedAt = $friend->requested_at ? \Carbon\Carbon::parse($friend->requested_at) : null;
                $friendshipCreatedAt = $friend->friendship_created_at ? \Carbon\Carbon::parse($friend->friendship_created_at) : null;
                
                $userDetails['friendship_info'] = [
                    'responded_at' => $respondedAt,
                    'responded_at_human' => $respondedAt ? $respondedAt->diffForHumans() : null,
                    'requested_at' => $requestedAt,
                    'requested_at_human' => $requestedAt ? $requestedAt->diffForHumans() : null,
                    'friendship_created_at' => $friendshipCreatedAt
                ];
                
                return $userDetails;
            });

            return response()->json([
                'status_code' => 200,
                'success' => true,
                'message' => 'Friends retrieved successfully',
                'data' => [
                    'friends' => $mappedFriends,
                    'total_friends' => $friends->total(),
                    'sorting' => [
                        'sort_by' => $sortBy,
                        'sort_order' => $sortOrder
                    ],
                    'pagination' => [
                        'current_page' => $friends->currentPage(),
                        'last_page' => $friends->lastPage(),
                        'per_page' => $friends->perPage(),
                        'total' => $friends->total(),
                        'from' => $friends->firstItem(),
                        'to' => $friends->lastItem(),
                        'has_more_pages' => $friends->hasMorePages()
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
                'message' => 'Failed to retrieve friends',
                'error' => $e->getMessage()
            ], 500);
        }
     }

     public function searchFriends(Request $request)
     {
        try {
            $validatedData = $request->validate([
                'search' => 'required|string|min:1|max:255',
                'page' => 'nullable|integer|min:1',
                'per_page' => 'nullable|integer|min:1|max:100',
                'sort_by' => 'nullable|string|in:name,date_accepted',
                'sort_order' => 'nullable|string|in:asc,desc'
            ]);

            $user = Auth::guard('user')->user();
            $searchQuery = $validatedData['search'];
            $perPage = $validatedData['per_page'] ?? 20;
            $sortBy = $validatedData['sort_by'] ?? 'name';
            $sortOrder = $validatedData['sort_order'] ?? 'asc';
            
            if (!$user) {
                return response()->json([
                    'status_code' => 404,
                    'success' => false,
                    'message' => 'User not found'
                ], 404);
            }

            // Build query to search friends with friendship metadata
            $query = User::join('friendships', function($join) use ($user) {
                $join->on(function($query) use ($user) {
                    $query->where(function($subQuery) use ($user) {
                        $subQuery->where('friendships.user_id', $user->id)
                                 ->whereColumn('friendships.friend_id', 'users.id');
                    })->orWhere(function($subQuery) use ($user) {
                        $subQuery->where('friendships.friend_id', $user->id)
                                 ->whereColumn('friendships.user_id', 'users.id');
                    });
                });
            })
            ->where('friendships.status', 'accepted')
            ->where(function($query) use ($searchQuery) {
                $query->where('users.first_name', 'LIKE', '%' . $searchQuery . '%')
                      ->orWhere('users.last_name', 'LIKE', '%' . $searchQuery . '%')
                      ->orWhere('users.email', 'LIKE', '%' . $searchQuery . '%')
                      ->orWhere('users.phone', 'LIKE', '%' . $searchQuery . '%')
                      ->orWhereRaw("CONCAT(users.first_name, ' ', users.last_name) LIKE ?", ['%' . $searchQuery . '%']);
            })
            ->select('users.*', 'friendships.responded_at', 'friendships.requested_at', 'friendships.created_at as friendship_created_at');

            // Apply sorting
            if ($sortBy === 'name') {
                $query->orderByRaw("CONCAT(users.first_name, ' ', users.last_name) {$sortOrder}");
            } elseif ($sortBy === 'date_accepted') {
                $query->orderBy('friendships.responded_at', $sortOrder);
            }

            // Get friends with pagination
            $friends = $query->paginate($perPage);

            if($friends->isEmpty()) {
                return response()->json([
                    'status_code' => 404,
                    'success' => false,
                    'message' => 'No friends found matching your search'
                ], 404);
            }

            // Map friends data with online status and mutual friends
            $mappedFriends = $friends->getCollection()->map(function ($friend) use ($user) {
                $userDetails = $this->authController->mapUserDetails($friend);
                $userDetails = $this->addOnlineStatusToUserDetails($userDetails, $friend->id);
                $userDetails = $this->addMutualFriendsToUserDetails($userDetails, $user->id, $friend->id);
                
                // Add friendship metadata - Convert string dates to Carbon instances
                $respondedAt = $friend->responded_at ? \Carbon\Carbon::parse($friend->responded_at) : null;
                $requestedAt = $friend->requested_at ? \Carbon\Carbon::parse($friend->requested_at) : null;
                $friendshipCreatedAt = $friend->friendship_created_at ? \Carbon\Carbon::parse($friend->friendship_created_at) : null;
                
                $userDetails['friendship_info'] = [
                    'responded_at' => $respondedAt,
                    'responded_at_human' => $respondedAt ? $respondedAt->diffForHumans() : null,
                    'requested_at' => $requestedAt,
                    'requested_at_human' => $requestedAt ? $requestedAt->diffForHumans() : null,
                    'friendship_created_at' => $friendshipCreatedAt
                ];
                
                return $userDetails;
            });

            return response()->json([
                'status_code' => 200,
                'success' => true,
                'message' => 'Friends search results retrieved successfully',
                'data' => [
                    'friends' => $mappedFriends,
                    'total_friends' => $friends->total(),
                    'search_query' => $searchQuery,
                    'sorting' => [
                        'sort_by' => $sortBy,
                        'sort_order' => $sortOrder
                    ],
                    'pagination' => [
                        'current_page' => $friends->currentPage(),
                        'last_page' => $friends->lastPage(),
                        'per_page' => $friends->perPage(),
                        'total' => $friends->total(),
                        'from' => $friends->firstItem(),
                        'to' => $friends->lastItem(),
                        'has_more_pages' => $friends->hasMorePages()
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
                'message' => 'Failed to search friends',
                'error' => $e->getMessage()
            ], 500);
        }
     }

     public function deleteFriendship(Request $request)
     {
         try {
             $validatedData = $request->validate([
                 'friend_id' => 'required|integer|exists:users,id'
             ]);
 
             $user = Auth::guard('user')->user();
             $friendId = $validatedData['friend_id'];
 
             // Check if trying to delete friendship with themselves
             if ($user->id == $friendId) {
                 return response()->json([
                     'status_code' => 400,
                     'success' => false,
                     'message' => 'Invalid operation'
                 ], 400);
             }
 
             // Find the friendship
             $friendship = Friendship::where(function ($query) use ($user, $friendId) {
                 $query->where('user_id', $user->id)->where('friend_id', $friendId);
             })->orWhere(function ($query) use ($user, $friendId) {
                 $query->where('user_id', $friendId)->where('friend_id', $user->id);
             })->first();
 
             if (!$friendship) {
                 return response()->json([
                     'status_code' => 404,
                     'success' => false,
                     'message' => 'Friendship not found'
                 ], 404);
             }
 
             // Check if friendship is in a state that can be deleted
             if (!in_array($friendship->status, ['accepted', 'pending', 'declined', 'cancelled'])) {
                 return response()->json([
                     'status_code' => 400,
                     'success' => false,
                     'message' => 'Cannot delete this friendship'
                 ], 400);
             }
 
             // Delete the friendship
             $friendship->delete();
 
             return response()->json([
                 'status_code' => 200,
                 'success' => true,
                 'message' => 'Friendship deleted successfully'
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
                 'message' => 'Failed to delete friendship',
                 'error' => $e->getMessage()
             ], 500);
         }
     }
 
     public function blockFriend(Request $request)
     {
         try {
             $validatedData = $request->validate([
                 'friend_id' => 'required|integer|exists:users,id'
             ]);
 
             $user = Auth::guard('user')->user();
             $friendId = $validatedData['friend_id'];
 
             // Check if trying to block themselves
             if ($user->id == $friendId) {
                 return response()->json([
                     'status_code' => 400,
                     'success' => false,
                     'message' => 'You cannot block yourself'
                 ], 400);
             }
 
             // Find existing friendship or create new one
             $friendship = Friendship::where(function ($query) use ($user, $friendId) {
                 $query->where('user_id', $user->id)->where('friend_id', $friendId);
             })->orWhere(function ($query) use ($user, $friendId) {
                 $query->where('user_id', $friendId)->where('friend_id', $user->id);
             })->first();
 
             if ($friendship) {
                 // Update existing friendship to blocked
                 if ($friendship->status === 'blocked') {
                     return response()->json([
                         'status_code' => 400,
                         'success' => false,
                         'message' => 'User is already blocked'
                     ], 400);
                 }
 
                 $friendship->block();
             } else {
                 // Create new friendship with blocked status
                 $friendship = Friendship::create([
                     'user_id' => $user->id,
                     'friend_id' => $friendId,
                     'status' => 'blocked',
                     'requested_at' => now(),
                     'responded_at' => now()
                 ]);
             }
 
             return response()->json([
                 'status_code' => 200,
                 'success' => true,
                 'message' => 'User blocked successfully'
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
                 'message' => 'Failed to block user',
                 'error' => $e->getMessage()
             ], 500);
         }
     }
 
     public function getSentRequests(Request $request)
     {
         try {
             $validatedData = $request->validate([
                 'page' => 'nullable|integer|min:1',
                 'per_page' => 'nullable|integer|min:1|max:100',
                 'sort_by' => 'nullable|string|in:name,date_sent,status',
                 'sort_order' => 'nullable|string|in:asc,desc',
                 'status' => 'nullable|string|in:pending,declined,cancelled,all'
             ]);

             $user = Auth::guard('user')->user();
             $perPage = $validatedData['per_page'] ?? 20;
             $sortBy = $validatedData['sort_by'] ?? 'date_sent';
             $sortOrder = $validatedData['sort_order'] ?? 'desc';
             $statusFilter = $validatedData['status'] ?? 'all';
             
             if (!$user) {
                 return response()->json([
                     'status_code' => 404,
                     'success' => false,
                     'message' => 'User not found'
                 ], 404);
             }

             // Build query to get sent friend requests
             $query = User::join('friendships', function($join) use ($user) {
                 $join->on('friendships.friend_id', '=', 'users.id')
                      ->where('friendships.user_id', $user->id);
             })
             ->select('users.*', 'friendships.id as friendship_id', 'friendships.status', 'friendships.requested_at', 'friendships.responded_at', 'friendships.created_at as friendship_created_at');

             // Apply status filter
             if ($statusFilter === 'pending') {
                 $query->where('friendships.status', 'pending');
             } elseif ($statusFilter === 'declined') {
                 $query->where('friendships.status', 'declined');
             } elseif ($statusFilter === 'cancelled') {
                 $query->where('friendships.status', 'cancelled');
             } else {
                 // Show pending, declined, and cancelled requests
                 $query->whereIn('friendships.status', ['pending', 'declined', 'cancelled']);
             }

             // Apply sorting
             if ($sortBy === 'name') {
                 $query->orderByRaw("CONCAT(users.first_name, ' ', users.last_name) {$sortOrder}");
             } elseif ($sortBy === 'date_sent') {
                 $query->orderBy('friendships.requested_at', $sortOrder);
             } elseif ($sortBy === 'status') {
                 $query->orderBy('friendships.status', $sortOrder);
             }

             // Get requests with pagination
             $sentRequests = $query->paginate($perPage);

             if($sentRequests->isEmpty()) {
                 return response()->json([
                     'status_code' => 404,
                     'success' => false,
                     'message' => 'No sent friend requests found'
                 ], 404);
             }

             // Map requests data
             $mappedRequests = $sentRequests->getCollection()->map(function ($friend) use ($user) {
                 $userDetails = $this->authController->mapUserDetails($friend);
                 $userDetails = $this->addOnlineStatusToUserDetails($userDetails, $friend->id);
                 $userDetails = $this->addMutualFriendsToUserDetails($userDetails, $user->id, $friend->id);
                 
                 // Add friendship metadata - Convert string dates to Carbon instances
                 $respondedAt = $friend->responded_at ? \Carbon\Carbon::parse($friend->responded_at) : null;
                 $requestedAt = $friend->requested_at ? \Carbon\Carbon::parse($friend->requested_at) : null;
                 $friendshipCreatedAt = $friend->friendship_created_at ? \Carbon\Carbon::parse($friend->friendship_created_at) : null;
                 
                 $userDetails['friendship_info'] = [
                     'friendship_id' => $friend->friendship_id,
                     'status' => $friend->status,
                     'requested_at' => $requestedAt,
                     'requested_at_human' => $requestedAt ? $requestedAt->diffForHumans() : null,
                     'responded_at' => $respondedAt,
                     'responded_at_human' => $respondedAt ? $respondedAt->diffForHumans() : null,
                     'friendship_created_at' => $friendshipCreatedAt
                 ];
                 
                 return $userDetails;
             });

             // Count requests by status
             $pendingCount = Friendship::where('user_id', $user->id)->where('status', 'pending')->count();
             $declinedCount = Friendship::where('user_id', $user->id)->where('status', 'declined')->count();
             $cancelledCount = Friendship::where('user_id', $user->id)->where('status', 'cancelled')->count();

             return response()->json([
                 'status_code' => 200,
                 'success' => true,
                 'message' => 'Sent friend requests retrieved successfully',
                 'data' => [
                     'requests' => $mappedRequests,
                     'total_requests' => $sentRequests->total(),
                     'counts' => [
                         'pending' => $pendingCount,
                         'declined' => $declinedCount,
                         'cancelled' => $cancelledCount,
                         'total' => $pendingCount + $declinedCount + $cancelledCount
                     ],
                     'current_filter' => $statusFilter,
                     'sorting' => [
                         'sort_by' => $sortBy,
                         'sort_order' => $sortOrder
                     ],
                     'pagination' => [
                         'current_page' => $sentRequests->currentPage(),
                         'last_page' => $sentRequests->lastPage(),
                         'per_page' => $sentRequests->perPage(),
                         'total' => $sentRequests->total(),
                         'from' => $sentRequests->firstItem(),
                         'to' => $sentRequests->lastItem(),
                         'has_more_pages' => $sentRequests->hasMorePages()
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
                 'message' => 'Failed to retrieve sent requests',
                 'error' => $e->getMessage()
             ], 500);
         }
     }

     public function searchSentRequests(Request $request)
     {
         try {
             $validatedData = $request->validate([
                 'search' => 'required|string|min:1|max:255',
                 'page' => 'nullable|integer|min:1',
                 'per_page' => 'nullable|integer|min:1|max:100',
                 'sort_by' => 'nullable|string|in:name,date_sent,status',
                 'sort_order' => 'nullable|string|in:asc,desc',
                 'status' => 'nullable|string|in:pending,declined,cancelled,all'
             ]);

             $user = Auth::guard('user')->user();
             $searchQuery = $validatedData['search'];
             $perPage = $validatedData['per_page'] ?? 20;
             $sortBy = $validatedData['sort_by'] ?? 'date_sent';
             $sortOrder = $validatedData['sort_order'] ?? 'desc';
             $statusFilter = $validatedData['status'] ?? 'all';
             
             if (!$user) {
                 return response()->json([
                     'status_code' => 404,
                     'success' => false,
                     'message' => 'User not found'
                 ], 404);
             }

             // Build query to search sent friend requests
             $query = User::join('friendships', function($join) use ($user) {
                 $join->on('friendships.friend_id', '=', 'users.id')
                      ->where('friendships.user_id', $user->id);
             })
             ->where(function($query) use ($searchQuery) {
                 $query->where('users.first_name', 'LIKE', '%' . $searchQuery . '%')
                       ->orWhere('users.last_name', 'LIKE', '%' . $searchQuery . '%')
                       ->orWhere('users.email', 'LIKE', '%' . $searchQuery . '%')
                       ->orWhere('users.phone', 'LIKE', '%' . $searchQuery . '%')
                       ->orWhereRaw("CONCAT(users.first_name, ' ', users.last_name) LIKE ?", ['%' . $searchQuery . '%']);
             })
             ->select('users.*', 'friendships.id as friendship_id', 'friendships.status', 'friendships.requested_at', 'friendships.responded_at', 'friendships.created_at as friendship_created_at');

             // Apply status filter
             if ($statusFilter === 'pending') {
                 $query->where('friendships.status', 'pending');
             } elseif ($statusFilter === 'declined') {
                 $query->where('friendships.status', 'declined');
             } elseif ($statusFilter === 'cancelled') {
                 $query->where('friendships.status', 'cancelled');
             } else {
                 // Show pending, declined, and cancelled requests
                 $query->whereIn('friendships.status', ['pending', 'declined', 'cancelled']);
             }

             // Apply sorting
             if ($sortBy === 'name') {
                 $query->orderByRaw("CONCAT(users.first_name, ' ', users.last_name) {$sortOrder}");
             } elseif ($sortBy === 'date_sent') {
                 $query->orderBy('friendships.requested_at', $sortOrder);
             } elseif ($sortBy === 'status') {
                 $query->orderBy('friendships.status', $sortOrder);
             }

             // Get requests with pagination
             $sentRequests = $query->paginate($perPage);

             if($sentRequests->isEmpty()) {
                 return response()->json([
                     'status_code' => 404,
                     'success' => false,
                     'message' => 'No sent friend requests found matching your search'
                 ], 404);
             }

             // Map requests data
             $mappedRequests = $sentRequests->getCollection()->map(function ($friend) use ($user) {
                 $userDetails = $this->authController->mapUserDetails($friend);
                 $userDetails = $this->addOnlineStatusToUserDetails($userDetails, $friend->id);
                 $userDetails = $this->addMutualFriendsToUserDetails($userDetails, $user->id, $friend->id);
                 
                 // Add friendship metadata - Convert string dates to Carbon instances
                 $respondedAt = $friend->responded_at ? \Carbon\Carbon::parse($friend->responded_at) : null;
                 $requestedAt = $friend->requested_at ? \Carbon\Carbon::parse($friend->requested_at) : null;
                 $friendshipCreatedAt = $friend->friendship_created_at ? \Carbon\Carbon::parse($friend->friendship_created_at) : null;
                 
                 $userDetails['friendship_info'] = [
                     'friendship_id' => $friend->friendship_id,
                     'status' => $friend->status,
                     'requested_at' => $requestedAt,
                     'requested_at_human' => $requestedAt ? $requestedAt->diffForHumans() : null,
                     'responded_at' => $respondedAt,
                     'responded_at_human' => $respondedAt ? $respondedAt->diffForHumans() : null,
                     'friendship_created_at' => $friendshipCreatedAt
                 ];
                 
                 return $userDetails;
             });

             // Count requests by status (for the search query)
             $totalSearchQuery = User::join('friendships', function($join) use ($user) {
                 $join->on('friendships.friend_id', '=', 'users.id')
                      ->where('friendships.user_id', $user->id);
             })
             ->where(function($query) use ($searchQuery) {
                 $query->where('users.first_name', 'LIKE', '%' . $searchQuery . '%')
                       ->orWhere('users.last_name', 'LIKE', '%' . $searchQuery . '%')
                       ->orWhere('users.email', 'LIKE', '%' . $searchQuery . '%')
                       ->orWhere('users.phone', 'LIKE', '%' . $searchQuery . '%')
                       ->orWhereRaw("CONCAT(users.first_name, ' ', users.last_name) LIKE ?", ['%' . $searchQuery . '%']);
             });

             $pendingCount = (clone $totalSearchQuery)->where('friendships.status', 'pending')->count();
             $declinedCount = (clone $totalSearchQuery)->where('friendships.status', 'declined')->count();
             $cancelledCount = (clone $totalSearchQuery)->where('friendships.status', 'cancelled')->count();

             return response()->json([
                 'status_code' => 200,
                 'success' => true,
                 'message' => 'Sent friend requests search results retrieved successfully',
                 'data' => [
                     'requests' => $mappedRequests,
                     'total_requests' => $sentRequests->total(),
                     'search_query' => $searchQuery,
                     'counts' => [
                         'pending' => $pendingCount,
                         'declined' => $declinedCount,
                         'cancelled' => $cancelledCount,
                         'total' => $pendingCount + $declinedCount + $cancelledCount
                     ],
                     'current_filter' => $statusFilter,
                     'sorting' => [
                         'sort_by' => $sortBy,
                         'sort_order' => $sortOrder
                     ],
                     'pagination' => [
                         'current_page' => $sentRequests->currentPage(),
                         'last_page' => $sentRequests->lastPage(),
                         'per_page' => $sentRequests->perPage(),
                         'total' => $sentRequests->total(),
                         'from' => $sentRequests->firstItem(),
                         'to' => $sentRequests->lastItem(),
                         'has_more_pages' => $sentRequests->hasMorePages()
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
                 'message' => 'Failed to search sent requests',
                 'error' => $e->getMessage()
             ], 500);
         }
     }
   
     public function cancelFriendRequest(Request $request)
     {
         try {
             $validatedData = $request->validate([
                 'friend_id' => 'required|integer|exists:users,id'
             ]);

             $user = Auth::guard('user')->user();
             $friendId = $validatedData['friend_id'];

             // Check if trying to cancel request to themselves
             if ($user->id == $friendId) {
                 return response()->json([
                     'status_code' => 400,
                     'success' => false,
                     'message' => 'Invalid operation'
                 ], 400);
             }

             // Find the pending friend request sent by current user
             $friendship = Friendship::where('user_id', $user->id)
                 ->where('friend_id', $friendId)
                 ->where('status', 'pending')
                 ->first();

             if (!$friendship) {
                 return response()->json([
                     'status_code' => 404,
                     'success' => false,
                     'message' => 'Pending friend request not found'
                 ], 404);
             }

             // Cancel the friend request
             $friendship->cancel();

             return response()->json([
                 'status_code' => 200,
                 'success' => true,
                 'message' => 'Friend request cancelled successfully'
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
                 'message' => 'Failed to cancel friend request',
                 'error' => $e->getMessage()
             ], 500);
         }
     }

     public function getReceivedRequests(Request $request)
     {
         try {
             $validatedData = $request->validate([
                 'page' => 'nullable|integer|min:1',
                 'per_page' => 'nullable|integer|min:1|max:100',
                 'sort_by' => 'nullable|string|in:name,date_sent',
                 'sort_order' => 'nullable|string|in:asc,desc'
             ]);

             $user = Auth::guard('user')->user();
             $perPage = $validatedData['per_page'] ?? 20;
             $sortBy = $validatedData['sort_by'] ?? 'date_sent';
             $sortOrder = $validatedData['sort_order'] ?? 'desc';
             
             if (!$user) {
                 return response()->json([
                     'status_code' => 404,
                     'success' => false,
                     'message' => 'User not found'
                 ], 404);
             }

             // Build query to get pending friend requests received by current user
             $query = User::join('friendships', function($join) use ($user) {
                 $join->on('friendships.user_id', '=', 'users.id')
                      ->where('friendships.friend_id', $user->id);
             })
             ->where('friendships.status', 'pending')
             ->select('users.*', 'friendships.id as friendship_id', 'friendships.status', 'friendships.requested_at', 'friendships.responded_at', 'friendships.created_at as friendship_created_at');

             // Apply sorting
             if ($sortBy === 'name') {
                 $query->orderByRaw("CONCAT(users.first_name, ' ', users.last_name) {$sortOrder}");
             } elseif ($sortBy === 'date_sent') {
                 $query->orderBy('friendships.requested_at', $sortOrder);
             }

             // Get requests with pagination
             $pendingRequests = $query->paginate($perPage);

             if($pendingRequests->isEmpty()) {
                 return response()->json([
                     'status_code' => 404,
                     'success' => false,
                     'message' => 'No pending friend requests found'
                 ], 404);
             }

             // Map requests data
             $mappedRequests = $pendingRequests->getCollection()->map(function ($requester) use ($user) {
                 $userDetails = $this->authController->mapUserDetails($requester);
                 $userDetails = $this->addOnlineStatusToUserDetails($userDetails, $requester->id);
                 $userDetails = $this->addMutualFriendsToUserDetails($userDetails, $user->id, $requester->id);
                 
                 // Add friendship metadata - Convert string dates to Carbon instances
                 $requestedAt = $requester->requested_at ? \Carbon\Carbon::parse($requester->requested_at) : null;
                 $respondedAt = $requester->responded_at ? \Carbon\Carbon::parse($requester->responded_at) : null;
                 $friendshipCreatedAt = $requester->friendship_created_at ? \Carbon\Carbon::parse($requester->friendship_created_at) : null;
                 
                 $userDetails['friendship_info'] = [
                     'friendship_id' => $requester->friendship_id,
                     'status' => $requester->status,
                     'requested_at' => $requestedAt,
                     'requested_at_human' => $requestedAt ? $requestedAt->diffForHumans() : null,
                     'responded_at' => $respondedAt,
                     'responded_at_human' => $respondedAt ? $respondedAt->diffForHumans() : null,
                     'friendship_created_at' => $friendshipCreatedAt
                 ];
                 
                 return $userDetails;
             });

             return response()->json([
                 'status_code' => 200,
                 'success' => true,
                 'message' => 'Pending friend requests retrieved successfully',
                 'data' => [
                     'requests' => $mappedRequests,
                     'total_requests' => $pendingRequests->total(),
                     'sorting' => [
                         'sort_by' => $sortBy,
                         'sort_order' => $sortOrder
                     ],
                     'pagination' => [
                         'current_page' => $pendingRequests->currentPage(),
                         'last_page' => $pendingRequests->lastPage(),
                         'per_page' => $pendingRequests->perPage(),
                         'total' => $pendingRequests->total(),
                         'from' => $pendingRequests->firstItem(),
                         'to' => $pendingRequests->lastItem(),
                         'has_more_pages' => $pendingRequests->hasMorePages()
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
                 'message' => 'Failed to retrieve pending requests',
                 'error' => $e->getMessage()
             ], 500);
         }
     }

     public function searchReceivedRequests(Request $request)
     {
         try {
             $validatedData = $request->validate([
                 'search' => 'required|string|min:1|max:255',
                 'page' => 'nullable|integer|min:1',
                 'per_page' => 'nullable|integer|min:1|max:100',
                 'sort_by' => 'nullable|string|in:name,date_sent',
                 'sort_order' => 'nullable|string|in:asc,desc'
             ]);

             $user = Auth::guard('user')->user();
             $searchQuery = $validatedData['search'];
             $perPage = $validatedData['per_page'] ?? 20;
             $sortBy = $validatedData['sort_by'] ?? 'date_sent';
             $sortOrder = $validatedData['sort_order'] ?? 'desc';
             
             if (!$user) {
                 return response()->json([
                     'status_code' => 404,
                     'success' => false,
                     'message' => 'User not found'
                 ], 404);
             }

             // Build query to search pending friend requests received by current user
             $query = User::join('friendships', function($join) use ($user) {
                 $join->on('friendships.user_id', '=', 'users.id')
                      ->where('friendships.friend_id', $user->id);
             })
             ->where('friendships.status', 'pending')
             ->where(function($query) use ($searchQuery) {
                 $query->where('users.first_name', 'LIKE', '%' . $searchQuery . '%')
                       ->orWhere('users.last_name', 'LIKE', '%' . $searchQuery . '%')
                       ->orWhere('users.email', 'LIKE', '%' . $searchQuery . '%')
                       ->orWhere('users.phone', 'LIKE', '%' . $searchQuery . '%')
                       ->orWhereRaw("CONCAT(users.first_name, ' ', users.last_name) LIKE ?", ['%' . $searchQuery . '%']);
             })
             ->select('users.*', 'friendships.id as friendship_id', 'friendships.status', 'friendships.requested_at', 'friendships.responded_at', 'friendships.created_at as friendship_created_at');

             // Apply sorting
             if ($sortBy === 'name') {
                 $query->orderByRaw("CONCAT(users.first_name, ' ', users.last_name) {$sortOrder}");
             } elseif ($sortBy === 'date_sent') {
                 $query->orderBy('friendships.requested_at', $sortOrder);
             }

             // Get requests with pagination
             $pendingRequests = $query->paginate($perPage);

             if($pendingRequests->isEmpty()) {
                 return response()->json([
                     'status_code' => 404,
                     'success' => false,
                     'message' => 'No pending friend requests found matching your search'
                 ], 404);
             }

             // Map requests data
             $mappedRequests = $pendingRequests->getCollection()->map(function ($requester) use ($user) {
                 $userDetails = $this->authController->mapUserDetails($requester);
                 $userDetails = $this->addOnlineStatusToUserDetails($userDetails, $requester->id);
                 $userDetails = $this->addMutualFriendsToUserDetails($userDetails, $user->id, $requester->id);
                 
                 // Add friendship metadata - Convert string dates to Carbon instances
                 $requestedAt = $requester->requested_at ? \Carbon\Carbon::parse($requester->requested_at) : null;
                 $respondedAt = $requester->responded_at ? \Carbon\Carbon::parse($requester->responded_at) : null;
                 $friendshipCreatedAt = $requester->friendship_created_at ? \Carbon\Carbon::parse($requester->friendship_created_at) : null;
                 
                 $userDetails['friendship_info'] = [
                     'friendship_id' => $requester->friendship_id,
                     'status' => $requester->status,
                     'requested_at' => $requestedAt,
                     'requested_at_human' => $requestedAt ? $requestedAt->diffForHumans() : null,
                     'responded_at' => $respondedAt,
                     'responded_at_human' => $respondedAt ? $respondedAt->diffForHumans() : null,
                     'friendship_created_at' => $friendshipCreatedAt
                 ];
                 
                 return $userDetails;
             });

             return response()->json([
                 'status_code' => 200,
                 'success' => true,
                 'message' => 'Pending friend requests search results retrieved successfully',
                 'data' => [
                     'requests' => $mappedRequests,
                     'total_requests' => $pendingRequests->total(),
                     'search_query' => $searchQuery,
                     'sorting' => [
                         'sort_by' => $sortBy,
                         'sort_order' => $sortOrder
                     ],
                     'pagination' => [
                         'current_page' => $pendingRequests->currentPage(),
                         'last_page' => $pendingRequests->lastPage(),
                         'per_page' => $pendingRequests->perPage(),
                         'total' => $pendingRequests->total(),
                         'from' => $pendingRequests->firstItem(),
                         'to' => $pendingRequests->lastItem(),
                         'has_more_pages' => $pendingRequests->hasMorePages()
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
                 'message' => 'Failed to search pending requests',
                 'error' => $e->getMessage()
             ], 500);
         }
     }

     public function acceptFriendRequest(Request $request)
     {
         try {
             // Validate request data
             $validatedData = $request->validate([
                 'friendship_id' => 'required|integer|min:1|exists:friendships,id'
             ]);

             $friendshipId = $validatedData['friendship_id'];

             $user = Auth::guard('user')->user();
             
             if (!$user) {
                 return response()->json([
                     'status_code' => 401,
                     'success' => false,
                     'message' => 'Unauthenticated'
                 ], 401);
             }

             // Use database transaction for data integrity
             DB::beginTransaction();

             try {
                 // Find the friendship with eager loading for requester details
                 $friendship = Friendship::with(['user:id,first_name,last_name,img,provider'])
                     ->where('id', $friendshipId)
                     ->where('friend_id', $user->id)
                     ->where('status', 'pending')
                     ->first();

                 if (!$friendship) {
                     DB::rollBack();
                     return response()->json([
                         'status_code' => 404,
                         'success' => false,
                         'message' => 'Pending friend request not found'
                     ], 404);
                 }

                 // Accept the friendship and set accepted_at timestamp
                 $friendship->update([
                     'status' => 'accepted',
                     'responded_at' => now(),
                     'accepted_at' => now()
                 ]);

                 DB::commit();

                 // Prepare response data with requester details
                 $friendData = [
                     'friendship_id' => $friendship->id,
                     'friend' => [
                         'id' => $friendship->user->id,
                         'name' => $friendship->user->first_name . ' ' . $friendship->user->last_name,
                         'first_name' => $friendship->user->first_name,
                         'last_name' => $friendship->user->last_name,
                         'profile_image' => $this->getProfileImageUrl($friendship->user),
                     ],
                     'status' => $friendship->status,
                     'requested_at' => $friendship->requested_at,
                     'accepted_at' => $friendship->accepted_at,
                     'accepted_at_human' => $friendship->accepted_at->diffForHumans()
                 ];

                 return response()->json([
                     'status_code' => 200,
                     'success' => true,
                     'message' => 'Friend request accepted successfully',
                     'data' => $friendData
                 ], 200);

             } catch (Exception $e) {
                 DB::rollBack();
                 throw $e;
             }

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
                 'message' => 'Failed to accept friend request',
                 'error' => $e->getMessage()
             ], 500);
         }
     }

     public function declineFriendRequest(Request $request)
     {
         try {
             // Validate request data
             $validatedData = $request->validate([
                 'friendship_id' => 'required|integer|min:1|exists:friendships,id'
             ]);

             $friendshipId = $validatedData['friendship_id'];

             $user = Auth::guard('user')->user();
             
             if (!$user) {
                 return response()->json([
                     'status_code' => 401,
                     'success' => false,
                     'message' => 'Unauthenticated'
                 ], 401);
             }

             // Use database transaction for data integrity
             DB::beginTransaction();

             try {
                 // Find the friendship with eager loading for requester details
                 $friendship = Friendship::with(['user:id,first_name,last_name,img,provider'])
                     ->where('id', $friendshipId)
                     ->where('friend_id', $user->id)
                     ->where('status', 'pending')
                     ->first();

                 if (!$friendship) {
                     DB::rollBack();
                     return response()->json([
                         'status_code' => 404,
                         'success' => false,
                         'message' => 'Pending friend request not found'
                     ], 404);
                 }

                 // Decline the friendship and set responded_at timestamp
                 $friendship->update([
                     'status' => 'declined',
                     'responded_at' => now()
                 ]);

                 DB::commit();

                 // Prepare response data with requester details
                 $friendData = [
                     'friendship_id' => $friendship->id,
                     'declined_user' => [
                         'id' => $friendship->user->id,
                         'name' => $friendship->user->first_name . ' ' . $friendship->user->last_name,
                         'first_name' => $friendship->user->first_name,
                         'last_name' => $friendship->user->last_name,
                         'profile_image' => $this->getProfileImageUrl($friendship->user),
                     ],
                     'status' => $friendship->status,
                     'requested_at' => $friendship->requested_at,
                     'declined_at' => $friendship->responded_at,
                     'declined_at_human' => $friendship->responded_at->diffForHumans()
                 ];

                 return response()->json([
                     'status_code' => 200,
                     'success' => true,
                     'message' => 'Friend request declined successfully',
                     'data' => $friendData
                 ], 200);

             } catch (Exception $e) {
                 DB::rollBack();
                 throw $e;
             }

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
                 'message' => 'Failed to decline friend request',
                 'error' => $e->getMessage()
             ], 500);
         }
     }

     public function sendFriendRequest(Request $request)
     {
         try {
             // Validate request data
             $validatedData = $request->validate([
                 'friend_id' => 'required|integer|min:1|exists:users,id'
             ]);

             $user = Auth::guard('user')->user();
             $friendId = $validatedData['friend_id'];
             
             if (!$user) {
                 return response()->json([
                     'status_code' => 401,
                     'success' => false,
                     'message' => 'Unauthenticated'
                 ], 401);
             }

             // Check if trying to send request to themselves
             if ($user->id == $friendId) {
                 return response()->json([
                     'status_code' => 400,
                     'success' => false,
                     'message' => 'You cannot send a friend request to yourself'
                 ], 400);
             }

             // Use database transaction for data integrity
             DB::beginTransaction();

             try {
                 // Check if friendship already exists (optimized query)
                 $existingFriendship = Friendship::where(function ($query) use ($user, $friendId) {
                     $query->where(function ($subQuery) use ($user, $friendId) {
                         $subQuery->where('user_id', $user->id)
                                  ->where('friend_id', $friendId);
                     })->orWhere(function ($subQuery) use ($user, $friendId) {
                         $subQuery->where('user_id', $friendId)
                                  ->where('friend_id', $user->id);
                     });
                 })->first();

                 if ($existingFriendship) {
                     // Handle existing friendship based on status
                     if ($existingFriendship->status === 'cancelled' && $existingFriendship->user_id === $user->id) {
                         // Delete the cancelled request and allow new one
                         $existingFriendship->delete();
                     } else {
                         DB::rollBack();
                         
                         $statusMessages = [
                             'accepted' => 'You are already friends with this user',
                             'pending' => $existingFriendship->user_id === $user->id 
                                        ? 'Friend request already sent' 
                                        : 'This user has already sent you a friend request',
                             'blocked' => 'Unable to send friend request',
                             'declined' => 'Friend request was previously declined',
                             'cancelled' => 'Friend request was previously cancelled'
                         ];

                         $message = $statusMessages[$existingFriendship->status] ?? 'Unable to send friend request';

                         return response()->json([
                             'status_code' => 400,
                             'success' => false,
                             'message' => $message,
                             'existing_status' => $existingFriendship->status
                         ], 400);
                     }
                 }

                 // Get friend details for response
                 $friend = User::select('id', 'first_name', 'last_name', 'img', 'provider')
                     ->find($friendId);

                 if (!$friend) {
                     DB::rollBack();
                     return response()->json([
                         'status_code' => 404,
                         'success' => false,
                         'message' => 'User not found'
                     ], 404);
                 }

                 // Create friend request
                 $friendship = Friendship::create([
                     'user_id' => $user->id,
                     'friend_id' => $friendId,
                     'status' => 'pending',
                     'requested_at' => now()
                 ]);

                 DB::commit();

                 // Prepare response data with friend details
                 $friendData = [
                     'friendship_id' => $friendship->id,
                     'friend' => [
                         'id' => $friend->id,
                         'name' => $friend->first_name . ' ' . $friend->last_name,
                         'first_name' => $friend->first_name,
                         'last_name' => $friend->last_name,
                         'profile_image' => $this->getProfileImageUrl($friend),
                     ],
                     'status' => $friendship->status,
                     'requested_at' => $friendship->requested_at,
                     'requested_at_human' => $friendship->requested_at->diffForHumans()
                 ];

                 return response()->json([
                     'status_code' => 201,
                     'success' => true,
                     'message' => 'Friend request sent successfully',
                     'data' => $friendData
                 ], 201);

             } catch (Exception $e) {
                 DB::rollBack();
                 throw $e;
             }

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
                 'message' => 'Failed to send friend request',
                 'error' => $e->getMessage()
             ], 500);
         }
     }


} 