<?php

namespace App\Http\Controllers\Api\V1\User\Post;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ModerationController extends Controller
{
    protected $moderationService;

    public function __construct(ContentModerationService $moderationService)
    {
        $this->moderationService = $moderationService;
    }

    public function getPendingPosts(Request $request)
    {
        $posts = Post::with(['user', 'media', 'poll', 'personalOccasion', 'moderationLogs'])
            ->where('status', 'pending')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $posts,
            'message' => 'Pending posts retrieved successfully'
        ]);
    }

    public function approvePost(Request $request, $postId)
    {
        $validator = Validator::make($request->all(), [
            'reason' => 'nullable|string|max:500'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $post = Post::findOrFail($postId);
            
            $post->update([
                'status' => 'approved',
                'moderation_reason' => $request->reason
            ]);

            // Log the manual approval
            ContentModerationLog::create([
                'post_id' => $post->id,
                'detected_issues' => [],
                'action_taken' => 'approved',
                'ai_reasoning' => 'Manually approved by moderator: ' . ($request->reason ?? 'No reason provided')
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Post approved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to approve post: ' . $e->getMessage()
            ], 500);
        }
    }

    public function rejectPost(Request $request, $postId)
    {
        $validator = Validator::make($request->all(), [
            'reason' => 'required|string|max:500'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $post = Post::findOrFail($postId);
            
            $post->update([
                'status' => 'rejected',
                'moderation_reason' => $request->reason
            ]);

            // Log the manual rejection
            ContentModerationLog::create([
                'post_id' => $post->id,
                'detected_issues' => ['manually_rejected'],
                'action_taken' => 'rejected',
                'ai_reasoning' => 'Manually rejected by moderator: ' . $request->reason
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Post rejected successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to reject post: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getModerationLogs($postId)
    {
        try {
            $post = Post::findOrFail($postId);
            $logs = $post->moderationLogs()->orderBy('created_at', 'desc')->get();

            return response()->json([
                'success' => true,
                'data' => $logs,
                'message' => 'Moderation logs retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve moderation logs: ' . $e->getMessage()
            ], 500);
        }
    }

    public function reprocessPost($postId)
    {
        try {
            $post = Post::with(['media', 'poll', 'personalOccasion'])->findOrFail($postId);
            
            // Reset status to pending
            $post->update(['status' => 'pending']);
            
            // Re-run moderation
            $moderationResult = $this->moderationService->reprocessPost($post);

            return response()->json([
                'success' => true,
                'data' => $moderationResult,
                'message' => 'Post reprocessed successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to reprocess post: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getModerationStats()
    {
        try {
            $stats = [
                'total_posts' => Post::count(),
                'approved_posts' => Post::where('status', 'approved')->count(),
                'rejected_posts' => Post::where('status', 'rejected')->count(),
                'pending_posts' => Post::where('status', 'pending')->count(),
                'today_posts' => Post::whereDate('created_at', today())->count(),
                'today_rejected' => Post::where('status', 'rejected')
                                       ->whereDate('created_at', today())
                                       ->count(),
                'average_toxicity_score' => ContentModerationLog::whereNotNull('toxicity_score')
                                                               ->avg('toxicity_score'),
                'average_spam_score' => ContentModerationLog::whereNotNull('spam_score')
                                                           ->avg('spam_score'),
                'top_flagged_words' => $this->getTopFlaggedWords(),
                'moderation_trends' => $this->getModerationTrends()
            ];

            return response()->json([
                'success' => true,
                'data' => $stats,
                'message' => 'Moderation statistics retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve statistics: ' . $e->getMessage()
            ], 500);
        }
    }

    private function getTopFlaggedWords()
    {
        $logs = ContentModerationLog::whereNotNull('flagged_words')
                                   ->get()
                                   ->pluck('flagged_words')
                                   ->flatten()
                                   ->countBy();

        return $logs->sortDesc()->take(10)->toArray();
    }

    private function getModerationTrends()
    {
        $trends = [];
        
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $trends[] = [
                'date' => $date,
                'total' => Post::whereDate('created_at', $date)->count(),
                'approved' => Post::where('status', 'approved')
                                  ->whereDate('created_at', $date)
                                  ->count(),
                'rejected' => Post::where('status', 'rejected')
                                  ->whereDate('created_at', $date)
                                  ->count(),
                'pending' => Post::where('status', 'pending')
                                 ->whereDate('created_at', $date)
                                 ->count()
            ];
        }

        return $trends;
    }
}
