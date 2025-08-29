<?php

namespace App\Http\Controllers\Api\V1\User\Profile;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Api\V1\User\Auth\AuthController;
use App\Models\Post;
use App\Models\Friendship;

class ProfileController extends Controller
{
    //

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
}
