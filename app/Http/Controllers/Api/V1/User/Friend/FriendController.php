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

class FriendController extends Controller
{
    /**
     * Get user's friends list for post privacy selection
     */
    public function getFriendsForPostPrivacy(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'search' => 'nullable|string|max:255',
                'page' => 'nullable|integer|min:1',
                'per_page' => 'nullable|integer|min:1|max:100'
            ]);

            $user = Auth::guard('user')->user();
            $perPage = $validatedData['per_page'] ?? 20;
            $search = $validatedData['search'] ?? '';

            // Get friends where current user is the initiator
            $sentFriends = $user->sentFriendRequests()
                ->accepted()
                ->with(['friend' => function($query) use ($search) {
                    $query->select('id', 'first_name', 'last_name', 'img', 'provider');
                    if ($search) {
                        $query->where(function($q) use ($search) {
                            $q->where('first_name', 'like', "%{$search}%")
                              ->orWhere('last_name', 'like', "%{$search}%")
                              ->orWhere(DB::raw("CONCAT(first_name, ' ', last_name)"), 'like', "%{$search}%");
                        });
                    }
                }])
                ->get()
                ->pluck('friend')
                ->filter(); // Remove null values from search filtering

            // Get friends where current user is the recipient
            $receivedFriends = $user->receivedFriendRequests()
                ->accepted()
                ->with(['user' => function($query) use ($search) {
                    $query->select('id', 'first_name', 'last_name', 'img', 'provider');
                    if ($search) {
                        $query->where(function($q) use ($search) {
                            $q->where('first_name', 'like', "%{$search}%")
                              ->orWhere('last_name', 'like', "%{$search}%")
                              ->orWhere(DB::raw("CONCAT(first_name, ' ', last_name)"), 'like', "%{$search}%");
                        });
                    }
                }])
                ->get()
                ->pluck('user')
                ->filter(); // Remove null values from search filtering

            // Merge and remove duplicates
            $allFriends = $sentFriends->merge($receivedFriends)->unique('id');

            // Format the friends data
            $friendsData = $allFriends->map(function ($friend) {
                return [
                    'id' => $friend->id,
                    'name' => $friend->first_name . ' ' . $friend->last_name,
                    'first_name' => $friend->first_name,
                    'last_name' => $friend->last_name,
                    'profile_image' => $this->getProfileImageUrl($friend),
                    'is_online' => false, // You can implement online status later
                ];
            })->values();

            // Manual pagination
            $total = $friendsData->count();
            $currentPage = $validatedData['page'] ?? 1;
            $offset = ($currentPage - 1) * $perPage;
            $paginatedFriends = $friendsData->slice($offset, $perPage)->values();

            return response()->json([
                'status_code' => 200,
                'success' => true,
                'message' => 'Friends retrieved successfully',
                'data' => $paginatedFriends,
                'pagination' => [
                    'current_page' => $currentPage,
                    'per_page' => $perPage,
                    'total' => $total,
                    'last_page' => ceil($total / $perPage),
                    'from' => $offset + 1,
                    'to' => min($offset + $perPage, $total),
                    'has_more_pages' => $currentPage < ceil($total / $perPage)
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

    /**
     * Send friend request
     */
    public function sendFriendRequest(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'friend_id' => 'required|integer|exists:users,id'
            ]);

            $user = Auth::guard('user')->user();
            $friendId = $validatedData['friend_id'];

            // Check if trying to add themselves
            if ($user->id == $friendId) {
                return response()->json([
                    'status_code' => 400,
                    'success' => false,
                    'message' => 'You cannot send a friend request to yourself'
                ], 400);
            }

            // Check if friendship already exists
            $existingFriendship = Friendship::where(function ($query) use ($user, $friendId) {
                $query->where('user_id', $user->id)->where('friend_id', $friendId);
            })->orWhere(function ($query) use ($user, $friendId) {
                $query->where('user_id', $friendId)->where('friend_id', $user->id);
            })->first();

            if ($existingFriendship) {
                $message = match($existingFriendship->status) {
                    'accepted' => 'You are already friends with this user',
                    'pending' => 'Friend request already sent',
                    'blocked' => 'Unable to send friend request',
                    'declined' => 'Friend request was previously declined'
                };

                return response()->json([
                    'status_code' => 400,
                    'success' => false,
                    'message' => $message
                ], 400);
            }

            // Create friend request
            $friendship = $user->sendFriendRequest($friendId);

            return response()->json([
                'status_code' => 201,
                'success' => true,
                'message' => 'Friend request sent successfully',
                'data' => $friendship
            ], 201);

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

    /**
     * Get pending friend requests
     */
    public function getPendingRequests(Request $request)
    {
        try {
            $user = Auth::guard('user')->user();
            
            $pendingRequests = $user->receivedFriendRequests()
                ->pending()
                ->with(['user:id,first_name,last_name,img,provider'])
                ->orderBy('created_at', 'desc')
                ->paginate(20);

            $formattedRequests = $pendingRequests->getCollection()->map(function ($friendship) {
                return [
                    'id' => $friendship->id,
                    'user' => [
                        'id' => $friendship->user->id,
                        'name' => $friendship->user->first_name . ' ' . $friendship->user->last_name,
                        'first_name' => $friendship->user->first_name,
                        'last_name' => $friendship->user->last_name,
                        'profile_image' => $this->getProfileImageUrl($friendship->user),
                    ],
                    'requested_at' => $friendship->requested_at,
                    'status' => $friendship->status
                ];
            });

            return response()->json([
                'status_code' => 200,
                'success' => true,
                'message' => 'Pending friend requests retrieved successfully',
                'data' => $formattedRequests,
                'pagination' => [
                    'current_page' => $pendingRequests->currentPage(),
                    'last_page' => $pendingRequests->lastPage(),
                    'per_page' => $pendingRequests->perPage(),
                    'total' => $pendingRequests->total(),
                    'from' => $pendingRequests->firstItem(),
                    'to' => $pendingRequests->lastItem(),
                    'has_more_pages' => $pendingRequests->hasMorePages()
                ]
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'status_code' => 500,
                'success' => false,
                'message' => 'Failed to retrieve pending requests',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Accept friend request
     */
    public function acceptFriendRequest(Request $request, $friendshipId)
    {
        try {
            $user = Auth::guard('user')->user();
            
            $friendship = Friendship::where('id', $friendshipId)
                ->where('friend_id', $user->id)
                ->where('status', 'pending')
                ->first();

            if (!$friendship) {
                return response()->json([
                    'status_code' => 404,
                    'success' => false,
                    'message' => 'Friend request not found'
                ], 404);
            }

            $friendship->accept();

            return response()->json([
                'status_code' => 200,
                'success' => true,
                'message' => 'Friend request accepted successfully'
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'status_code' => 500,
                'success' => false,
                'message' => 'Failed to accept friend request',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Decline friend request
     */
    public function declineFriendRequest(Request $request, $friendshipId)
    {
        try {
            $user = Auth::guard('user')->user();
            
            $friendship = Friendship::where('id', $friendshipId)
                ->where('friend_id', $user->id)
                ->where('status', 'pending')
                ->first();

            if (!$friendship) {
                return response()->json([
                    'status_code' => 404,
                    'success' => false,
                    'message' => 'Friend request not found'
                ], 404);
            }

            $friendship->decline();

            return response()->json([
                'status_code' => 200,
                'success' => true,
                'message' => 'Friend request declined successfully'
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'status_code' => 500,
                'success' => false,
                'message' => 'Failed to decline friend request',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get profile image URL
     */
    private function getProfileImageUrl($user)
    {
        if ($user->provider === 'google' && $user->img) {
            return $user->img;
        }
        
        return $user->img ? config('app.url') . '/storage/' . $user->img : null;
    }
} 