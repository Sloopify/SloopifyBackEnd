<?php

namespace App\Http\Controllers\Api\V1\User\Post;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Models\Post;
use App\Models\PostMedia;
use App\Models\PostPoll;
use App\Models\PersonalOccasion;
use App\Models\PollVote;
use App\Services\ContentModerationService;
use Illuminate\Validation\ValidationException;
use Exception;

class PostController extends Controller
{
    //

    protected $moderationService;

    public function __construct(ContentModerationService $moderationService)
    {
        $this->moderationService = $moderationService;
    }


    public function createPost(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'type' => 'required|in:regular,poll,personal_occasion',
                'content' => 'nullable|string|max:10000',
                'text_properties' => 'nullable|array',
                'text_properties.color' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
                'text_properties.bold' => 'nullable|boolean',
                'text_properties.italic' => 'nullable|boolean',
                'text_properties.underline' => 'nullable|boolean',
                'background_color' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
                'privacy' => 'required|in:public,friends,specific_friends,friend_except,only_me',
                'specific_friends' => 'nullable|array',
                'specific_friends.*' => 'exists:users,id',
                'friend_except' => 'nullable|array',
                'friend_except.*' => 'exists:users,id',
                'disappears_24h' => 'nullable|boolean',
                'mentions.friends' => 'nullable|array',
                'mentions.friends.*' => 'exists:users,id',
                'mentions.place' => 'nullable|string|max:255',
                'mentions.feeling' => 'nullable|string|max:100',
                'mentions.activity' => 'nullable|string|max:100',
                'media.*' => 'nullable|file|mimes:jpeg,png,gif,mp4,avi|max:51200', // 50MB
                
                // Poll specific
                'poll.question' => 'required_if:type,poll|string|max:500',
                'poll.options' => 'required_if:type,poll|array|min:2|max:10',
                'poll.options.*' => 'string|max:255',
                'poll.multiple_choice' => 'nullable|boolean',
                'poll.ends_at' => 'nullable|date|after:now',
                'poll.show_results_after_vote' => 'nullable|boolean',
                'poll.show_results_after_end' => 'nullable|boolean',
                
                // Personal occasion specific
                'occasion.type' => 'required_if:type,personal_occasion|in:new_job,job_promotion,graduation,started_studies,relationship_status,moved_city,birthday,anniversary,achievement,travel,other',
                'occasion.title' => 'required_if:type,personal_occasion|string|max:255',
                'occasion.description' => 'nullable|string|max:1000',
                'occasion.details' => 'nullable|array',
                'occasion.date' => 'nullable|date'
            ]);

            $user = Auth::guard('user')->user();

            // Custom validation for background_color
            if (!empty($validatedData['background_color'])) {
                if ($validatedData['type'] !== 'regular') {
                    return response()->json([
                        'status_code' => 422,
                        'success' => false,
                        'message' => 'Validation failed',
                        'errors' => [
                            'background_color' => ['Background color can only be used with regular posts.']]
                    ], 422);
                }

                if ($request->hasFile('media')) {
                    return response()->json([
                        'status_code' => 422,
                        'success' => false,
                        'message' => 'Validation failed',
                        'errors' => [
                            'background_color' => ['Background color cannot be used when uploading media files.']]
                    ], 422);
                }
            }

            // Custom validation for mentions
            $mentions = $request->input('mentions', []);
            $hasFeeling = !empty($mentions['feeling']);
            $hasActivity = !empty($mentions['activity']);
            $hasPlace = !empty($mentions['place']);
            $hasFriends = !empty($mentions['friends']);
            
            // Check if poll type has any mentions
            if ($validatedData['type'] === 'poll') {
                if ($hasFeeling || $hasActivity || $hasPlace || $hasFriends) {
                    return response()->json([
                        'status_code' => 422,
                        'success' => false,
                        'message' => 'Validation failed',
                        'errors' => [
                            'mentions' => ['Poll posts cannot have mentions, feelings, activities, or location.']]
                    ], 422);
                }
            }
            
            // Check feeling and activity conflict for non-poll posts
            if ($validatedData['type'] !== 'poll' && $hasFeeling && $hasActivity) {
                return response()->json([
                    'status_code' => 422,
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => [
                        'mentions' =>
                         ['You cannot specify both feeling and activity at the same time.']]
                ], 422);
            }

            // Validate friendship for mentioned friends
            if ($hasFriends && !empty($mentions['friends'])) {
                $mentionedFriends = $mentions['friends'];
                $invalidFriends = [];

                foreach ($mentionedFriends as $friendId) {
                    if (!$user->isFriendsWith($friendId)) {
                        $invalidFriends[] = $friendId;
                    }
                }

                if (!empty($invalidFriends)) {
                    return response()->json([
                        'status_code' => 422,
                        'success' => false,
                        'message' => 'Validation failed',
                        'errors' => [
                            'mentions.friends' => [
                                'You can only mention users who are your friends. Invalid friend IDs: ' . implode(', ', $invalidFriends)
                            ]
                        ]
                    ], 422);
                }
            }

            // Validate friendship for specific_friends privacy
            if ($validatedData['privacy'] === 'specific_friends' && !empty($validatedData['specific_friends'])) {
                $specificFriends = $validatedData['specific_friends'];
                $invalidSpecificFriends = [];

                foreach ($specificFriends as $friendId) {
                    if (!$user->isFriendsWith($friendId)) {
                        $invalidSpecificFriends[] = $friendId;
                    }
                }

                if (!empty($invalidSpecificFriends)) {
                    return response()->json([
                        'status_code' => 422,
                        'success' => false,
                        'message' => 'Validation failed',
                        'errors' => [
                            'specific_friends' => [
                                'You can only select users who are your friends. Invalid friend IDs: ' . implode(', ', $invalidSpecificFriends)
                            ]
                        ]
                    ], 422);
                }
            }

            // Validate friendship for friend_except privacy
            if ($validatedData['privacy'] === 'friend_except' && !empty($validatedData['friend_except'])) {
                $exceptFriends = $validatedData['friend_except'];
                $invalidExceptFriends = [];

                foreach ($exceptFriends as $friendId) {
                    if (!$user->isFriendsWith($friendId)) {
                        $invalidExceptFriends[] = $friendId;
                    }
                }

                if (!empty($invalidExceptFriends)) {
                    return response()->json([
                        'status_code' => 422,
                        'success' => false,
                        'message' => 'Validation failed',
                        'errors' => [
                            'friend_except' => [
                                'You can only exclude users who are your friends. Invalid friend IDs: ' . implode(', ', $invalidExceptFriends)
                            ]
                        ]
                    ], 422);
                }
            }

            // Validate mentions consistency with privacy settings
            if ($hasFriends && !empty($mentions['friends'])) {
                $mentionedFriends = $mentions['friends'];

                // For specific_friends privacy: mentioned friends must be in the specific_friends list
                if ($validatedData['privacy'] === 'specific_friends' && !empty($validatedData['specific_friends'])) {
                    $specificFriends = $validatedData['specific_friends'];
                    $invalidMentions = [];

                    foreach ($mentionedFriends as $mentionedId) {
                        if (!in_array($mentionedId, $specificFriends)) {
                            $invalidMentions[] = $mentionedId;
                        }
                    }

                    if (!empty($invalidMentions)) {
                        return response()->json([
                            'status_code' => 422,
                            'success' => false,
                            'message' => 'Validation failed',
                            'errors' => [
                                'mentions.friends' => [
                                    'You cannot mention friends who are not in your specific friends list for this post. Invalid mentions: ' . implode(', ', $invalidMentions)
                                ]
                            ]
                        ], 422);
                    }
                }

                // For friend_except privacy: mentioned friends must NOT be in the friend_except list
                if ($validatedData['privacy'] === 'friend_except' && !empty($validatedData['friend_except'])) {
                    $exceptFriends = $validatedData['friend_except'];
                    $invalidMentions = [];

                    foreach ($mentionedFriends as $mentionedId) {
                        if (in_array($mentionedId, $exceptFriends)) {
                            $invalidMentions[] = $mentionedId;
                        }
                    }

                    if (!empty($invalidMentions)) {
                        return response()->json([
                            'status_code' => 422,
                            'success' => false,
                            'message' => 'Validation failed',
                            'errors' => [
                                'mentions.friends' => [
                                    'You cannot mention friends who are excluded from seeing this post. Invalid mentions: ' . implode(', ', $invalidMentions)
                                ]
                            ]
                        ], 422);
                    }
                }
            }

            DB::beginTransaction();

            // Create the post
            $post = Post::create([
                'user_id' => $user->id,
                'type' => $validatedData['type'],
                'content' => $validatedData['content'] ?? null,
                'text_properties' => $validatedData['text_properties'] ?? null,
                'background_color' => $validatedData['background_color'] ?? null,
                'privacy' => $validatedData['privacy'],
                'specific_friends' => $validatedData['privacy'] === 'specific_friends' ? ($validatedData['specific_friends'] ?? null) : null,
                'friend_except' => $validatedData['privacy'] === 'friend_except' ? ($validatedData['friend_except'] ?? null) : null,
                'disappears_24h' => $validatedData['disappears_24h'] ?? false,
                'mentions' => $validatedData['type'] === 'poll' ? null : $mentions, // Set mentions to null for poll posts
                'status' => 'pending' // Will be updated by moderation service
            ]);

            // Handle media uploads
            if ($request->hasFile('media')) {
                $this->handleMediaUploads($post, $request->file('media'));
            }

            // Handle poll creation
            if ($validatedData['type'] === 'poll') {
                $this->createPoll($post, $validatedData['poll']);
            }

            // Handle personal occasion
            if ($validatedData['type'] === 'personal_occasion') {
                $this->createPersonalOccasion($post, $validatedData['occasion']);
            }

            // Run content moderation
            $moderationResult = $this->moderationService->moderatePost($post);

            DB::commit();

            // Load relationships for response
            $post->load(['user', 'media', 'poll', 'personalOccasion']);

            return response()->json([
                'status_code' => 200,
                'success' => true,
                'message' => $this->getModerationMessage($moderationResult['action']),
                'data' => $post,
                'moderation' => $moderationResult
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
                'message' => 'Failed to create post',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    private function handleMediaUploads($post, $files)
    {
        foreach ($files as $file) {
            $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('posts/' . $post->id, $filename, 'public');

            PostMedia::create([
                'post_id' => $post->id,
                'type' => strpos($file->getMimeType(), 'image') !== false ? 'image' : 'video',
                'filename' => $filename,
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize(),
                'path' => $path,
                'url' => Storage::url($path),
                'metadata' => $this->extractMediaMetadata($file)
            ]);
        }
    }

    private function extractMediaMetadata($file)
    {
        $metadata = [];
        
        if (strpos($file->getMimeType(), 'image') !== false) {
            $imageInfo = getimagesize($file->getPathname());
            if ($imageInfo) {
                $metadata['width'] = $imageInfo[0];
                $metadata['height'] = $imageInfo[1];
            }
        } elseif (strpos($file->getMimeType(), 'video') !== false) {
            // Extract video metadata using FFmpeg
            $this->extractVideoMetadata($file->getPathname(), $metadata);
        }
        
        return $metadata;
    }

    private function extractVideoMetadata($filePath, &$metadata)
    {
        try {
            // Use ffprobe (part of FFmpeg) to get video information
            $command = "ffprobe -v quiet -print_format json -show_format -show_streams " . escapeshellarg($filePath);
            $output = shell_exec($command);
            
            if ($output) {
                $data = json_decode($output, true);
                
                if (isset($data['streams'])) {
                    foreach ($data['streams'] as $stream) {
                        if ($stream['codec_type'] === 'video') {
                            $metadata['width'] = $stream['width'] ?? null;
                            $metadata['height'] = $stream['height'] ?? null;
                            $metadata['duration'] = isset($stream['duration']) ? (float)$stream['duration'] : null;
                            $metadata['fps'] = $this->calculateFPS($stream);
                            $metadata['codec'] = $stream['codec_name'] ?? null;
                            $metadata['bitrate'] = isset($stream['bit_rate']) ? (int)$stream['bit_rate'] : null;
                            break;
                        }
                    }
                }
                
                // Get overall format information
                if (isset($data['format'])) {
                    $format = $data['format'];
                    if (!isset($metadata['duration']) && isset($format['duration'])) {
                        $metadata['duration'] = (float)$format['duration'];
                    }
                    if (!isset($metadata['bitrate']) && isset($format['bit_rate'])) {
                        $metadata['bitrate'] = (int)$format['bit_rate'];
                    }
                    $metadata['format'] = $format['format_name'] ?? null;
                    $metadata['size'] = isset($format['size']) ? (int)$format['size'] : null;
                }
            }
        } catch (Exception $e) {
            // Log error but don't fail the upload
            \Log::warning('Failed to extract video metadata: ' . $e->getMessage());
        }
    }

    private function calculateFPS($stream)
    {
        if (isset($stream['r_frame_rate'])) {
            $fps = $stream['r_frame_rate'];
            if (strpos($fps, '/') !== false) {
                $parts = explode('/', $fps);
                if (count($parts) === 2 && $parts[1] != 0) {
                    return round($parts[0] / $parts[1], 2);
                }
            }
        }
        
        if (isset($stream['avg_frame_rate'])) {
            $fps = $stream['avg_frame_rate'];
            if (strpos($fps, '/') !== false) {
                $parts = explode('/', $fps);
                if (count($parts) === 2 && $parts[1] != 0) {
                    return round($parts[0] / $parts[1], 2);
                }
            }
        }
        
        return null;
    }

    private function createPoll($post, $pollData)
    {
        PostPoll::create([
            'post_id' => $post->id,
            'question' => $pollData['question'],
            'options' => $pollData['options'],
            'multiple_choice' => $pollData['multiple_choice'] ?? false,
            'ends_at' => isset($pollData['ends_at']) ? $pollData['ends_at'] : null,
            'show_results_after_vote' => $pollData['show_results_after_vote'] ?? true,
            'show_results_after_end' => $pollData['show_results_after_end'] ?? true
        ]);
    }

    private function createPersonalOccasion($post, $occasionData)
    {
        PersonalOccasion::create([
            'post_id' => $post->id,
            'occasion_type' => $occasionData['type'],
            'title' => $occasionData['title'],
            'description' => $occasionData['description'] ?? null,
            'details' => $occasionData['details'] ?? null,
            'occasion_date' => isset($occasionData['date']) ? $occasionData['date'] : null
        ]);
    }

    private function getModerationMessage($action)
    {
        switch ($action) {
            case 'approved':
                return 'Post created and published successfully';
            case 'rejected':
                return 'Post was rejected due to policy violations';
            case 'flagged_for_review':
                return 'Post is pending review and will be published once approved';
            default:
                return 'Post processed successfully';
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $post = Post::where('user_id', Auth::guard('user')->user()->id)->findOrFail($id);

            $validatedData = $request->validate([
                'content' => 'nullable|string|max:10000',
                'text_properties' => 'nullable|array',
                'privacy' => 'nullable|in:public,friends,specific_friends,friend_except,only_me',
                'specific_friends' => 'nullable|array',
                'specific_friends.*' => 'exists:users,id',
                'friend_except' => 'nullable|array',
                'friend_except.*' => 'exists:users,id',
                'mentions' => 'nullable|array',
                'mentions.friends' => 'nullable|array',
                'mentions.friends.*' => 'exists:users,id',
                'mentions.place' => 'nullable|string|max:255',
                'mentions.feeling' => 'nullable|string|max:100',
                'mentions.activity' => 'nullable|string|max:100',
            ]);

            // Custom validation for mentions
            $mentions = $request->input('mentions', []);
            $hasFeeling = !empty($mentions['feeling']);
            $hasActivity = !empty($mentions['activity']);
            
            if ($hasFeeling && $hasActivity) {
                return response()->json([
                    'status_code' => 422,
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => ['mentions' => ['You cannot specify both feeling and activity at the same time.']]
                ], 422);
            }

            DB::beginTransaction();

            $post->update($validatedData);

            DB::commit();

            return response()->json([
                'status_code' => 200,
                'success' => true,
                'message' => 'Post updated successfully',
                'data' => $post
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
                'message' => 'Failed to update post',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $post = Post::where('user_id', Auth::guard('user')->user()->id)->findOrFail($id);

            // Delete media files
            foreach ($post->media as $media) {
                Storage::disk('public')->delete($media->path);
            }

            $post->delete();

            return response()->json([
                'status_code' => 200,
                'success' => true,
                'message' => 'Post deleted successfully'
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'status_code' => 500,
                'success' => false,
                'message' => 'Failed to delete post',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function votePoll(Request $request, $postId)
    {
        try {
            $validatedData = $request->validate([
                'options' => 'required|array|min:1',
                'options.*' => 'integer|min:0'
            ]);

            $post = Post::with('poll')->approved()->findOrFail($postId);

            if ($post->type !== 'poll' || !$post->poll) {
                return response()->json([
                    'status_code' => 400,
                    'success' => false,
                    'message' => 'This post is not a poll'
                ], 400);
            }

            if ($post->poll->isExpired()) {
                return response()->json([
                    'status_code' => 400,
                    'success' => false,
                    'message' => 'This poll has expired'
                ], 400);
            }

            // Validate selected options
            $maxOption = count($post->poll->options) - 1;
            foreach ($validatedData['options'] as $option) {
                if ($option > $maxOption) {
                    return response()->json([
                        'status_code' => 400,
                        'success' => false,
                        'message' => 'Invalid poll option selected'
                    ], 400);
                }
            }

            // Check if multiple choice is allowed
            if (!$post->poll->multiple_choice && count($validatedData['options']) > 1) {
                return response()->json([
                    'status_code' => 400,
                    'success' => false,
                    'message' => 'Multiple selections not allowed for this poll'
                ], 400);
            }

            // Create or update vote
            PollVote::updateOrCreate(
                [
                    'poll_id' => $post->poll->id,
                    'user_id' => Auth::guard('user')->user()->id
                ],
                [
                    'selected_options' => $validatedData['options']
                ]
            );

            // Get updated results if allowed
            $showResults = $post->poll->show_results_after_vote || 
                          ($post->poll->isExpired() && $post->poll->show_results_after_end);

            $response = [
                'status_code' => 200,
                'success' => true,
                'message' => 'Vote recorded successfully'
            ];

            if ($showResults) {
                $response['data'] = ['results' => $post->poll->results];
            }

            return response()->json($response, 200);

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
                'message' => 'Failed to record vote',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getPollResults($postId)
    {
        try {
            $post = Post::with('poll.votes')->approved()->findOrFail($postId);

            if ($post->type !== 'poll' || !$post->poll) {
                return response()->json([
                    'status_code' => 400,
                    'success' => false,
                    'message' => 'This post is not a poll'
                ], 400);
            }

            $showResults = $post->poll->show_results_after_vote || 
                          ($post->poll->isExpired() && $post->poll->show_results_after_end);

            if (!$showResults) {
                return response()->json([
                    'status_code' => 403,
                    'success' => false,
                    'message' => 'Poll results are not available yet'
                ], 403);
            }

            return response()->json([
                'status_code' => 200,
                'success' => true,
                'message' => 'Poll results retrieved successfully',
                'data' => $post->poll->results
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'status_code' => 404,
                'success' => false,
                'message' => 'Post not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    public function getUserPosts(Request $request, $userId = null)
    {
        try {
            $validatedData = $request->validate([
                'page' => 'nullable|integer|min:1',
                'per_page' => 'nullable|integer|min:1|max:100'
            ]);

            $targetUserId = $userId ?? Auth::guard('user')->user()->id;
            $perPage = $validatedData['per_page'] ?? 20;
            
            $posts = Post::with(['user', 'media', 'poll', 'personalOccasion'])
                ->where('user_id', $targetUserId)
                ->approved()
                ->notExpired()
                ->visibleTo(Auth::guard('user')->user()->id)
                ->orderBy('created_at', 'desc')
                ->paginate($perPage);

            return response()->json([
                'status_code' => 200,
                'success' => true,
                'message' => 'User posts retrieved successfully',
                'data' => $posts->items(),
                'pagination' => [
                    'current_page' => $posts->currentPage(),
                    'last_page' => $posts->lastPage(),
                    'per_page' => $posts->perPage(),
                    'total' => $posts->total(),
                    'from' => $posts->firstItem(),
                    'to' => $posts->lastItem(),
                    'has_more_pages' => $posts->hasMorePages()
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
                'message' => 'Failed to retrieve user posts',
                'error' => $e->getMessage()
            ], 500);
        }
    }
 
    

  
}
