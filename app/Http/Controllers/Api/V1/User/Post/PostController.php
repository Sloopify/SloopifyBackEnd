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
use App\Services\PostNotificationService;
use Illuminate\Validation\ValidationException;
use Exception;
use App\Models\PostFeeling;
use App\Models\PostActivity;
use App\Utils\PhoneNumberHelper;
use App\Models\Friendship;
use App\Models\User;
use App\Models\PersonalOccasionSetting;
use App\Models\UserPlace;
use App\Models\PersonalOccasionCategory;
use App\Models\PostReaction;
use App\Models\CommentReaction;
use App\Models\Reaction;
use App\Models\PostComment;
use App\Http\Controllers\Api\V1\User\Auth\AuthController;
use App\Http\Controllers\Api\V1\User\Home\HomeController;
use Carbon\Carbon;

class PostController extends Controller
{
    //

    protected $moderationService;
    protected $notificationService;

    public function __construct(
        ContentModerationService $moderationService,
        PostNotificationService $notificationService
    ) {
        $this->moderationService = $moderationService;
        $this->notificationService = $notificationService;
    }

    public function mapFeelings($feelings)
    {
        // Convert array to collection if needed
        if (is_array($feelings)) {
            $feelings = collect($feelings);
        }
        
        return $feelings->map(function ($feeling) {
            return [
                'id' => $feeling->id,
                'name' => $feeling->name,
                'mobile_icon' => $feeling->mobile_icon ? config('app.url') . asset('storage/' . $feeling->mobile_icon) : null,
                'web_icon' => $feeling->web_icon ? config('app.url') . asset('storage/' . $feeling->web_icon) : null,
                'status' => $feeling->status,
                'created_at' => $feeling->created_at,
                'updated_at' => $feeling->updated_at,
            ];
        })->values();
    }

    public function mapActivities($activities)
    {
        return $activities->map(function ($activity) {
            return [
                'id' => $activity->id,
                'name' => $activity->name,
                'mobile_icon' => $activity->mobile_icon ? config('app.url') . asset('storage/' . $activity->mobile_icon) : null,
                'web_icon' => $activity->web_icon ? config('app.url') . asset('storage/' . $activity->web_icon) : null,
                'status' => $activity->status,
                'category' => $activity->category,
                'created_at' => $activity->created_at,
                'updated_at' => $activity->updated_at,
            ];
        })->values();
    }

    public function mapPersonalOccasionSettings($occasions)
    {
        // Convert array to collection if needed
        if (is_array($occasions)) {
            $occasions = collect($occasions);
        }
        
        return $occasions->map(function ($occasion) {
            return [
                'id' => $occasion->id,
                'name' => $occasion->name,
                'title' => $occasion->title,
                'description' => $occasion->description,
                'mobile_icon' => $occasion->mobile_icon ? config('app.url') . asset('storage/personal_occasions/mobile/' . $occasion->mobile_icon) : null,
                'web_icon' => $occasion->web_icon ? config('app.url') . asset('storage/personal_occasions/web/' . $occasion->web_icon) : null,
                'status' => $occasion->status,
                'created_at' => $occasion->created_at,
                'updated_at' => $occasion->updated_at,
            ];
        })->values();
    }

    public function mapPersonalOccasionCategories($categories)
    {
        // Handle single model instance
        if ($categories instanceof \App\Models\PersonalOccasionCategory) {
            return [
                'id' => $categories->id,
                'name' => $categories->name,
                'description' => $categories->description,
                'web_icon' => $categories->web_icon ? config('app.url') . asset('storage/personal_occasions_categories/web/' . $categories->web_icon) : null,
                'mobile_icon' => $categories->mobile_icon ? config('app.url') . asset('storage/personal_occasions_categories/mobile/' . $categories->mobile_icon) : null,
                'status' => $categories->status,
                'occasions' => $categories->personalOccasionSettings ? $this->mapPersonalOccasionSettings($categories->personalOccasionSettings) : [],
                'created_at' => $categories->created_at,
                'updated_at' => $categories->updated_at,
            ];
        }
        
        // Handle arrays and collections
        if (is_array($categories)) {
            $categories = collect($categories);
        }
        
        return $categories->map(function ($category) {
            return [
                'id' => $category->id,
                'name' => $category->name,
                'description' => $category->description,
                'web_icon' => $category->web_icon ? config('app.url') . asset('storage/personal_occasions_categories/web/' . $category->web_icon) : null,
                'mobile_icon' => $category->mobile_icon ? config('app.url') . asset('storage/personal_occasions_categories/mobile/' . $category->mobile_icon) : null,
                'status' => $category->status,
                'occasions' => $category->personalOccasionSettings ? $this->mapPersonalOccasionSettings($category->personalOccasionSettings) : [],
                'created_at' => $category->created_at,
                'updated_at' => $category->updated_at,
            ];
        })->values();
    }

    public function mapUsersDetails($users)
    {
        return $users->map(function ($user) {
            return app(AuthController::class)->mapUserDetails($user);
        })->values();
    }

    public function mapUserPlaces($userPlaces)
    {
        // Handle single model instance
        if ($userPlaces instanceof \App\Models\UserPlace) {
            return [
                'id' => $userPlaces->id,
                'name' => $userPlaces->name,
                'city' => $userPlaces->city,
                'country' => $userPlaces->country,
                'latitude' => $userPlaces->latitude,
                'longitude' => $userPlaces->longitude,
                'status' => $userPlaces->status,
                'created_at' => $userPlaces->created_at,
                'updated_at' => $userPlaces->updated_at,
            ];
        }
        
        // Handle arrays and collections
        if (is_array($userPlaces)) {
            $userPlaces = collect($userPlaces);
        }
        
        return $userPlaces->map(function ($userPlace) {
            return [
                'id' => $userPlace->id,
                'name' => $userPlace->name,
                'city' => $userPlace->city,
                'country' => $userPlace->country,
                'latitude' => $userPlace->latitude,
                'longitude' => $userPlace->longitude,
                'status' => $userPlace->status,
                'created_at' => $userPlace->created_at,
                'updated_at' => $userPlace->updated_at,
            ];
        })->values();
    }

    private function formatReactionUrl($url)
    {
        if (empty($url)) {
            return null;
        }
        
        // If it's already a full URL, return as is
        if (filter_var($url, FILTER_VALIDATE_URL)) {
            return $url;
        }
        
        // If it starts with storage/, remove it to avoid double paths
        if (str_starts_with($url, 'storage/')) {
            $url = substr($url, 8); // Remove 'storage/' prefix
        }
        
        // If it's a relative path, add storage prefix
        if (!str_starts_with($url, 'http')) {
            return asset('storage/' . $url);
        }
        
        return $url;
    }

  
    private function buildCompletePostData($post, $user)
    {
        // Get friend IDs for this user
        $friendships = Friendship::where(function($q) use ($user) {
            $q->where('user_id', $user->id)
              ->orWhere('friend_id', $user->id);
        })
        ->where('status', 'accepted')
        ->get();

        $friendIds = $friendships->map(function($f) use ($user) {
            return $f->user_id == $user->id ? $f->friend_id : $f->user_id;
        })->unique()->values();

        // Get comment count
        $commentCount = \DB::table('post_comments')
            ->where('post_id', $post->id)
            ->whereNull('parent_comment_id')
            ->where('is_deleted', false)
            ->count();

        // Get post reactions data
        $postReactions = PostReaction::where('post_id', $post->id)
            ->with(['reaction', 'user'])
            ->get();

        $totalPostReactions = $postReactions->count();

        // Get user's reaction for this post
        $userReaction = PostReaction::where('user_id', $user->id)
            ->where('post_id', $post->id)
            ->with('reaction')
            ->first();

        // Check if post is saved
        $isSaved = \DB::table('saved_posts')
            ->where('user_id', $user->id)
            ->where('post_id', $post->id)
            ->exists();

        // Build post data
        $data = $post->toArray();
        $data['comments_count'] = $commentCount;
        $data['reactions_count'] = $totalPostReactions;
        $data['is_saved'] = $isSaved;
        $data['is_user_friend'] = $friendIds->contains($post->user_id);

        // Map user data
        $data['user'] = $this->mapUsersDetails(collect([$post->user]))->first();

        // Map mentions friends to full user data
        if (isset($data['mentions']['friends']) && !empty($data['mentions']['friends'])) {
            $mentionedUserIds = $data['mentions']['friends'];
            $mentionedUsers = User::whereIn('id', $mentionedUserIds)->get();
            $data['mentions']['friends'] = $this->mapUsersDetails($mentionedUsers);
        }

        // Map mentions place to full place data
        if (isset($data['mentions']['place']) && !empty($data['mentions']['place'])) {
            $userPlace = UserPlace::find($data['mentions']['place']);
            if ($userPlace) {
                $data['mentions']['place'] = $this->mapUserPlaces($userPlace);
            }
        }

        // Check if mentions object is empty and set to null
        if (isset($data['mentions']) && empty(array_filter($data['mentions']))) {
            $data['mentions'] = null;
        }

        // Group reactions by type and count them
        $reactionCounts = $postReactions->groupBy('reaction_id')
            ->map(function ($reactions) {
                return [
                    'reaction' => $reactions->first()->reaction,
                    'count' => $reactions->count(),
                    'users' => $reactions->pluck('user')
                ];
            });

        // Add post reactions data
        $data['post_reactions'] = [
            'user_reaction' => $userReaction ? [
                'id' => $userReaction->reaction->id,
                'name' => $userReaction->reaction->name,
                'content' => $userReaction->reaction->content,
                'image' => $this->formatReactionUrl($userReaction->reaction->image_url),
                'video' => $this->formatReactionUrl($userReaction->reaction->video_url)
            ] : null,
            'reactions' => $reactionCounts->map(function ($item) {
                return [
                    'id' => $item['reaction']->id,
                    'name' => $item['reaction']->name,
                    'content' => $item['reaction']->content,
                    'image' => $this->formatReactionUrl($item['reaction']->image_url),
                    'video' => $this->formatReactionUrl($item['reaction']->video_url),
                    'count' => $item['count'],
                    'users' => $this->mapUsersDetails($item['users'])
                ];
            })->values(),
            'total_reactions' => $totalPostReactions
        ];

        return $data;
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
                'background_color' => 'nullable|array',
                'background_color.*' => 'string|regex:/^#[0-9A-Fa-f]{6}$/',
                'privacy' => 'required|in:public,friends,specific_friends,friend_except,only_me',
                'specific_friends' => 'nullable|array',
                'specific_friends.*' => 'exists:users,id',
                'friend_except' => 'nullable|array',
                'friend_except.*' => 'exists:users,id',
                'disappears_24h' => 'nullable|boolean',
                'mentions.friends' => 'nullable|array',
                'mentions.friends.*' => 'exists:users,id',
                'mentions.place' => 'nullable|integer|exists:user_places,id',
                'mentions.feeling' => 'nullable|string|max:100',
                'mentions.activity' => 'nullable|string|max:100',
                'media' => 'nullable|array',
                'media.*.file' => 'required|file|mimes:jpeg,png,gif,mp4,avi|max:51200',
                'media.*.order' => 'nullable|integer|min:1',
                'media.*.auto_play' => 'nullable|boolean',
                'media.*.apply_to_download' => 'nullable|boolean',
                'media.*.is_rotate' => 'nullable|boolean',
                'media.*.rotate_angle' => 'nullable|integer|min:0|max:360',
                'media.*.is_flip_horizontal' => 'nullable|boolean',
                'media.*.is_flip_vertical' => 'nullable|boolean',
                'media.*.filter_name' => 'nullable|string|max:255',
                'gif_url' => 'nullable|url|max:2048',
                
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
                            'background_color' => ['Background colors can only be used with regular posts.']]
                    ], 422);
                }

                if (!empty($validatedData['media']) || !empty($validatedData['gif_url'])) {
                    return response()->json([
                        'status_code' => 422,
                        'success' => false,
                        'message' => 'Validation failed',
                        'errors' => [
                            'background_color' => ['Background colors cannot be used when uploading media files or using GIF URL.']]
                    ], 422);
                }

                // Additional validation for background_color array
                if (count($validatedData['background_color']) > 10) {
                    return response()->json([
                        'status_code' => 422,
                        'success' => false,
                        'message' => 'Validation failed',
                        'errors' => [
                            'background_color' => ['You can specify a maximum of 10 background colors.']]
                    ], 422);
                }
            }

            // Custom validation for gif_url and media conflict
            if (!empty($validatedData['gif_url']) && !empty($validatedData['media'])) {
                return response()->json([
                    'status_code' => 422,
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => [
                        'gif_url' => ['You cannot use both GIF URL and upload media files at the same time.']]
                ], 422);
            }

            // Custom validation for gif_url with post types
            if (!empty($validatedData['gif_url'])) {
                if ($validatedData['type'] === 'poll') {
                    return response()->json([
                        'status_code' => 422,
                        'success' => false,
                        'message' => 'Validation failed',
                        'errors' => [
                            'gif_url' => ['GIF URL cannot be used with poll posts.']]
                    ], 422);
                }
            }

            // Custom validation for media order uniqueness
            if (!empty($validatedData['media'])) {
                $orders = [];
                foreach ($validatedData['media'] as $index => $mediaItem) {
                    if (isset($mediaItem['order'])) {
                        $order = $mediaItem['order'];
                        if (in_array($order, $orders)) {
                            return response()->json([
                                'status_code' => 422,
                                'success' => false,
                                'message' => 'Validation failed',
                                'errors' => [
                                    "media.{$index}.order" => ['Display order values must be unique for each media file.']]
                            ], 422);
                        }
                        $orders[] = $order;
                    }
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

            // Validate user place ownership
            if ($hasPlace && !empty($mentions['place'])) {
                $userPlace = UserPlace::where('id', $mentions['place'])
                    ->where('user_id', $user->id)
                    ->where('status', 'active')
                    ->first();

                if (!$userPlace) {
                    return response()->json([
                        'status_code' => 422,
                        'success' => false,
                        'message' => 'Validation failed',
                        'errors' => [
                            'mentions.place' => [
                                'The selected place does not belong to you or is not active.'
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
                'gif_url' => $validatedData['gif_url'] ?? null,
                'status' => 'pending' // Will be updated by moderation service
            ]);

            // Handle media uploads
            if (!empty($validatedData['media'])) {
                $this->handleMediaUploads($post, $validatedData['media']);
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

            // Send notifications if post is approved
            if ($moderationResult['action'] === 'approved') {
                try {
                    $this->notificationService->sendPostNotifications($post);
                } catch (\Exception $e) {
                    // Log notification errors but don't fail the post creation
                    \Log::error('Failed to send post notifications: ' . $e->getMessage(), [
                        'post_id' => $post->id
                    ]);
                }
            }

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

    private function handleMediaUploads($post, $mediaItems)
    {
        foreach ($mediaItems as $index => $mediaItem) {
            $file = $mediaItem['file'];
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
                'display_order' => $mediaItem['order'] ?? ($index + 1),
                'auto_play' => $mediaItem['auto_play'] ?? false,
                'apply_to_download' => $mediaItem['apply_to_download'] ?? false,
                'is_rotate' => $mediaItem['is_rotate'] ?? false,
                'rotate_angle' => $mediaItem['rotate_angle'] ?? 0,
                'is_flip_horizontal' => $mediaItem['is_flip_horizontal'] ?? false,
                'is_flip_vertical' => $mediaItem['is_flip_vertical'] ?? false,
                'filter_name' => $mediaItem['filter_name'] ?? null,
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
            // First check if FFmpeg/ffprobe is available
            if ($this->isFFmpegAvailable()) {
                // Use ffprobe (part of FFmpeg) to get video information
                $command = "ffprobe -v quiet -print_format json -show-format -show-streams " . escapeshellarg($filePath);
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
                        if (!isset($metadata['size']) && isset($format['size'])) {
                            $metadata['size'] = (int)$format['size'];
                        }
                    }
                    
                    \Log::info('Video metadata extracted successfully using FFmpeg', [
                        'file' => basename($filePath),
                        'metadata' => $metadata
                    ]);
                } else {
                    \Log::warning('FFmpeg command executed but returned empty output', [
                        'file' => basename($filePath),
                        'command' => $command
                    ]);
                    $this->extractBasicVideoMetadata($filePath, $metadata);
                }
            } else {
                \Log::warning('FFmpeg/ffprobe not available, using basic metadata extraction', [
                    'file' => basename($filePath)
                ]);
                $this->extractBasicVideoMetadata($filePath, $metadata);
            }
        } catch (Exception $e) {
            // Log error but don't fail the upload
            \Log::error('Failed to extract video metadata: ' . $e->getMessage(), [
                'file' => basename($filePath),
                'error' => $e->getTraceAsString()
            ]);
            $this->extractBasicVideoMetadata($filePath, $metadata);
        }
    }

    private function isFFmpegAvailable()
    {
        try {
            // Check if shell_exec is enabled
            if (!function_exists('shell_exec')) {
                \Log::warning('shell_exec function is not available');
                return false;
            }
            
            // Check if ffprobe command is available
            $output = shell_exec('ffprobe -version 2>&1');
            return !empty($output) && strpos($output, 'ffprobe') !== false;
        } catch (Exception $e) {
            \Log::warning('Error checking FFmpeg availability: ' . $e->getMessage());
            return false;
        }
    }

    private function extractBasicVideoMetadata($filePath, &$metadata)
    {
        try {
            // Get basic file information
            if (file_exists($filePath)) {
                $fileSize = filesize($filePath);
                if ($fileSize !== false) {
                    $metadata['size'] = $fileSize;
                }
                
                // Try to get MIME type
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                if ($finfo) {
                    $mimeType = finfo_file($finfo, $filePath);
                    if ($mimeType) {
                        $metadata['mime_type'] = $mimeType;
                    }
                    finfo_close($finfo);
                }
                
                \Log::info('Basic video metadata extracted', [
                    'file' => basename($filePath),
                    'metadata' => $metadata
                ]);
            }
        } catch (Exception $e) {
            \Log::error('Failed to extract basic video metadata: ' . $e->getMessage(), [
                'file' => basename($filePath)
            ]);
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

    private function getCommentNestingLevel($commentId)
    {
        $level = 0;
        $currentCommentId = $commentId;
        
        while ($currentCommentId) {
            $comment = DB::table('post_comments')
                ->where('id', $currentCommentId)
                ->where('is_deleted', false)
                ->select('parent_comment_id')
                ->first();
                
            if (!$comment || !$comment->parent_comment_id) {
                break;
            }
            
            $level++;
            $currentCommentId = $comment->parent_comment_id;
            
            // Safety check to prevent infinite loops
            if ($level > 10) {
                break;
            }
        }
        
        return $level;
    }

    private function buildCommentTree($comments, $users, $parentId = null, $maxDepth = 5, $currentDepth = 0, $commentReactions = null, $userCommentReactions = null)
    {
        if ($currentDepth >= $maxDepth) {
            return [];
        }
        
        $tree = [];
        foreach ($comments as $comment) {
            if ($comment->parent_comment_id == $parentId) {
                $user = $users->get($comment->user_id);
                
                // Get comment reactions data for this comment
                $commentReactionData = $commentReactions ? $commentReactions->get($comment->id, collect()) : collect();
                $totalCommentReactions = $commentReactionData->count();
                
                // Get user's reaction for this comment
                $userCommentReactionData = $userCommentReactions ? $userCommentReactions->get($comment->id) : null;

                // Group reactions by type and count them
                $reactionCounts = $commentReactionData->groupBy('reaction_id')
                    ->map(function ($reactions) {
                        return [
                            'reaction' => $reactions->first()->reaction,
                            'count' => $reactions->count(),
                            'users' => $reactions->pluck('user')
                        ];
                    });

                $commentData = [
                    'id' => $comment->id,
                    'parent_comment_id' => $comment->parent_comment_id,
                    'comment_text' => $comment->comment_text,
                    'mentions' => $comment->mentions ? json_decode($comment->mentions, true) : [],
                    'media' => $comment->media ? json_decode($comment->media, true) : null,
                    'created_at' => $comment->created_at,
                    'user' => $user ? app(AuthController::class)->mapUserDetails($user) : null,
                    'nesting_level' => $currentDepth,
                    'replies_count' => 0,
                    'replies' => [],
                    'comment_reactions' => [
                        'user_reaction' => $userCommentReactionData ? [
                            'id' => $userCommentReactionData->reaction->id,
                            'name' => $userCommentReactionData->reaction->name,
                            'content' => $userCommentReactionData->reaction->content,
                            'image' => $this->formatReactionUrl($userCommentReactionData->reaction->image_url),
                            'video' => $this->formatReactionUrl($userCommentReactionData->reaction->video_url)
                        ] : null,
                        'reactions' => $reactionCounts->map(function ($item) {
                            return [
                                'id' => $item['reaction']->id,
                                'name' => $item['reaction']->name,
                                'content' => $item['reaction']->content,
                                'image' => $this->formatReactionUrl($item['reaction']->image_url),
                                'video' => $this->formatReactionUrl($item['reaction']->video_url),
                                'count' => $item['count'],
                                'users' => $this->mapUsersDetails($item['users'])
                            ];
                        })->values(),
                        'total_reactions' => $totalCommentReactions
                    ]
                ];
                
                // Get direct replies count
                $commentData['replies_count'] = $comments->where('parent_comment_id', $comment->id)->count();
                
                // Recursively build replies (limited depth for performance)
                if ($currentDepth < $maxDepth - 1) {
                    $commentData['replies'] = $this->buildCommentTree($comments, $users, $comment->id, $maxDepth, $currentDepth + 1, $commentReactions, $userCommentReactions);
                }
                
                $tree[] = $commentData;
            }
        }
        
        return $tree;
    }

    private function deleteCommentAndReplies($commentId)
    {
        // Get all replies for this comment
        $replies = DB::table('post_comments')
            ->where('parent_comment_id', $commentId)
            ->where('is_deleted', false)
            ->get();

        // Recursively delete all replies first
        foreach ($replies as $reply) {
            $this->deleteCommentAndReplies($reply->id);
        }

        // Delete media files for this comment
        $comment = DB::table('post_comments')
            ->where('id', $commentId)
            ->where('is_deleted', false)
            ->first();

        if ($comment && $comment->media) {
            $mediaData = json_decode($comment->media, true);
            if ($mediaData && isset($mediaData['path'])) {
                try {
                    Storage::disk('public')->delete($mediaData['path']);
                } catch (Exception $e) {
                    \Log::warning('Failed to delete comment media file: ' . $e->getMessage(), [
                        'comment_id' => $commentId,
                        'media_path' => $mediaData['path']
                    ]);
                }
            }
        }

        // Soft delete this comment
        DB::table('post_comments')
            ->where('id', $commentId)
            ->update([
                'is_deleted' => true,
                'updated_at' => now()
            ]);
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

    public function getFeeling(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'page' => 'nullable|integer|min:1',
                'per_page' => 'nullable|integer|min:1|max:100'
            ]);

            $perPage = $validatedData['per_page'] ?? 20;

            $feelings = PostFeeling::where('status', 'active')->paginate($perPage);

            $mappedFeelings = $feelings->getCollection()->map(function ($feeling) {
                return $this->mapFeelings(collect([$feeling]))->first();
            });
        
            return response()->json([
                'status_code' => 200,
                'success' => true,
                'message' => 'Feelings retrieved successfully',
                'data' =>[
                'feelings' => $mappedFeelings,
                'pagination' => [
                    'current_page' => $feelings->currentPage(),
                    'last_page' => $feelings->lastPage(),
                    'per_page' => $feelings->perPage(),
                    'total' => $feelings->total(),
                    'from' => $feelings->firstItem(),
                    'to' => $feelings->lastItem(),
                    'has_more_pages' => $feelings->hasMorePages()
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
                'message' => 'Failed to retrieve feelings',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getFeelingById(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'feeling_id' => 'required|integer|exists:post_feelings,id'
            ]);

            $feeling = PostFeeling::where('id', $validatedData['feeling_id'])
                ->where('status', 'active')
                ->first();

            if (!$feeling) {
                return response()->json([
                    'status_code' => 404,
                    'success' => false,
                    'message' => 'Feeling not found or inactive'
                ], 404);
            }

            $mappedFeeling = $this->mapFeelings(collect([$feeling]))->first();

            return response()->json([
                'status_code' => 200,
                'success' => true,
                'message' => 'Feeling retrieved successfully',
                'data' => [
                    'feeling' => $mappedFeeling
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
                'message' => 'Failed to retrieve feeling',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getActivityCategory(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'page' => 'nullable|integer|min:1',
                'per_page' => 'nullable|integer|min:1|max:100'
            ]);

            $perPage = $validatedData['per_page'] ?? 20;
            $page = $validatedData['page'] ?? 1;

            // Get all distinct categories first
            $allCategories = PostActivity::where('status', 'active')
                ->select('category')
                ->distinct()
                ->whereNotNull('category')
                ->where('category', '!=', '')
                ->orderBy('category')
                ->pluck('category')
                ->values();

            // Manual pagination
            $total = $allCategories->count();
            $lastPage = $total > 0 ? ceil($total / $perPage) : 1;
            $offset = ($page - 1) * $perPage;
            
            // Get categories for current page
            $categories = $allCategories->slice($offset, $perPage)->values();
            
            // Calculate pagination info
            $from = $categories->isEmpty() ? null : $offset + 1;
            $to = $categories->isEmpty() ? null : $offset + $categories->count();
            
            return response()->json([
                'status_code' => 200,
                'success' => true,
                'message' => 'Activity categories retrieved successfully',
                'data' => [
                    'categories' => $categories,
                    'pagination' => [
                        'current_page' => $page,
                        'last_page' => $lastPage,
                        'per_page' => $perPage,
                        'total' => $total,
                        'from' => $from,
                        'to' => $to,
                        'has_more_pages' => $page < $lastPage
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
                'message' => 'Failed to retrieve activity categories',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getActivityByCategoryName(Request $request)
    {
       try {
        $validatedData = $request->validate([
            'category' => 'required|string|max:255',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100'
        ]);

        $perPage = $validatedData['per_page'] ?? 20;

        $activities = PostActivity::where('category', $validatedData['category'])
            ->where('status', 'active')
            ->paginate($perPage);

        $mappedActivities = $activities->getCollection()->map(function ($activity) {
            return $this->mapActivities(collect([$activity]))->first();
        });

        return response()->json([
            'status_code' => 200,
            'success' => true,
            'message' => 'Activities retrieved successfully by category name',
            'data' => [
                'activities' => $mappedActivities,
                'pagination' => [
                    'current_page' => $activities->currentPage(),
                    'last_page' => $activities->lastPage(),
                    'per_page' => $activities->perPage(),
                    'total' => $activities->total(),
                    'from' => $activities->firstItem(),
                    'to' => $activities->lastItem(),
                    'has_more_pages' => $activities->hasMorePages()
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
                'message' => 'Failed to retrieve activities by category name',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function searchFeeling(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'search' => 'required|string|max:255',
                'page' => 'nullable|integer|min:1',
                'per_page' => 'nullable|integer|min:1|max:100'
            ]);

            $perPage = $validatedData['per_page'] ?? 20;

            $feelings = PostFeeling::where('name', 'like', '%' . $validatedData['search'] . '%')
                ->where('status', 'active')
                ->paginate($perPage);

            $mappedFeelings = $feelings->getCollection()->map(function ($feeling) {
                return $this->mapFeelings(collect([$feeling]))->first();
            });

            return response()->json([
                'status_code' => 200,
                'success' => true,
                'message' => 'Feelings retrieved successfully',
                'data' => [
                    'feelings' => $mappedFeelings,
                    'pagination' => [
                        'current_page' => $feelings->currentPage(),
                        'last_page' => $feelings->lastPage(),
                        'per_page' => $feelings->perPage(),
                        'total' => $feelings->total(),
                        'from' => $feelings->firstItem(),
                        'to' => $feelings->lastItem(),
                        'has_more_pages' => $feelings->hasMorePages()
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
                'message' => 'Failed to search feelings',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function searchActivityByCategory(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'search' => 'required|string|max:255',
                'page' => 'nullable|integer|min:1',
                'per_page' => 'nullable|integer|min:1|max:100'
            ]);

            $perPage = $validatedData['per_page'] ?? 20;
            $page = $validatedData['page'] ?? 1;

            // Get all distinct categories that match search first
            $allCategories = PostActivity::where('status', 'active')
                ->where('category', 'like', '%' . $validatedData['search'] . '%')
                ->select('category')
                ->distinct()
                ->whereNotNull('category')
                ->where('category', '!=', '')
                ->orderBy('category')
                ->pluck('category')
                ->values();

            // Manual pagination
            $total = $allCategories->count();
            $lastPage = $total > 0 ? ceil($total / $perPage) : 1;
            $offset = ($page - 1) * $perPage;
            
            // Get categories for current page
            $categories = $allCategories->slice($offset, $perPage)->values();
            
            // Calculate pagination info
            $from = $categories->isEmpty() ? null : $offset + 1;
            $to = $categories->isEmpty() ? null : $offset + $categories->count();

            return response()->json([
                'status_code' => 200,
                'success' => true,
                'message' => 'Categories retrieved successfully',
                'data' => [
                    'categories' => $categories,
                    'pagination' => [
                        'current_page' => $page,
                        'last_page' => $lastPage,
                        'per_page' => $perPage,
                        'total' => $total,
                        'from' => $from,
                        'to' => $to,
                        'has_more_pages' => $page < $lastPage
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
                'message' => 'Failed to search categories',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function searchActivity(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'search' => 'required|string|max:255',
                'page' => 'nullable|integer|min:1',
                'per_page' => 'nullable|integer|min:1|max:100'
            ]);

            $perPage = $validatedData['per_page'] ?? 20;

            $activities = PostActivity::where('name', 'like', '%' . $validatedData['search'] . '%')
                ->where('status', 'active')
                ->paginate($perPage);

            $mappedActivities = $activities->getCollection()->map(function ($activity) {
                return $this->mapActivities(collect([$activity]))->first();
            });

            return response()->json([
                'status_code' => 200,
                'success' => true,
                'message' => 'Activities retrieved successfully',
                'data' => [
                    'activities' => $mappedActivities,
                    'pagination' => [
                        'current_page' => $activities->currentPage(),
                        'last_page' => $activities->lastPage(),
                        'per_page' => $activities->perPage(),
                        'total' => $activities->total(),
                        'from' => $activities->firstItem(),
                        'to' => $activities->lastItem(),
                        'has_more_pages' => $activities->hasMorePages()
                    ]
                ]
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'status_code' => 500,
                'success' => false,
                'message' => 'Failed to search activities',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getFriends(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'page' => 'nullable|integer|min:1',
                'per_page' => 'nullable|integer|min:1|max:100'
            ]);

            $user = Auth::guard('user')->user();
            $perPage = $validatedData['per_page'] ?? 20;
            
            if (!$user) {
                return response()->json([
                    'status_code' => 404,
                    'success' => false,
                    'message' => 'User not found'
                ], 404);
            }

            // Get all accepted friends
            $friendships = Friendship::where(function($query) use ($user) {
                $query->where('user_id', $user->id)
                      ->orWhere('friend_id', $user->id);
            })
            ->where('status', 'accepted')
            ->get();

            // Extract friend IDs
            $friendIds = $friendships->map(function($friendship) use ($user) {
                return $friendship->user_id == $user->id ? $friendship->friend_id : $friendship->user_id;
            });

            // Get friends with pagination
            $friends = User::whereIn('id', $friendIds)->paginate($perPage);

            // Get specific friends IDs
            $specificFriendIds = \App\Models\SpecificFriends::where('user_id', $user->id)
                ->pluck('friend_id')
                ->toArray();

            // Get friend except IDs
            $friendExceptIds = \App\Models\FriendExcept::where('user_id', $user->id)
                ->pluck('friend_id')
                ->toArray();

            // Map friends with specific/except flags
            $mappedFriends = $friends->getCollection()->map(function ($friend) use ($specificFriendIds, $friendExceptIds) {
                $friendData = app(AuthController::class)->mapUserDetails($friend);
                $friendData['is_specific_friend'] = in_array($friend->id, $specificFriendIds);
                $friendData['is_friend_except'] = in_array($friend->id, $friendExceptIds);
                return $friendData;
            });

            return response()->json([
                'status_code' => 200,
                'success' => true,
                'message' => 'Friends retrieved successfully',
                'data' => [
                    'friends' => $mappedFriends,
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
                'search' => 'required|string|max:255',
                'page' => 'nullable|integer|min:1',
                'per_page' => 'nullable|integer|min:1|max:100'
            ]);

            $user = Auth::guard('user')->user();
            $perPage = $validatedData['per_page'] ?? 20;

            if (!$user) {
                return response()->json([
                    'status_code' => 404,
                    'success' => false,
                    'message' => 'User not found'
                ], 404);
            }

            // Get all accepted friends
            $friendships = Friendship::where(function($query) use ($user) {
                $query->where('user_id', $user->id)
                      ->orWhere('friend_id', $user->id);
            })
            ->where('status', 'accepted')
            ->get();

            // Extract friend IDs
            $friendIds = $friendships->map(function($friendship) use ($user) {
                return $friendship->user_id == $user->id ? $friendship->friend_id : $friendship->user_id;
            });

            // Search among friends by first_name, last_name, or email with pagination
            $friends = User::whereIn('id', $friendIds)
                ->where(function($query) use ($validatedData) {
                    $query->where('first_name', 'like', '%' . $validatedData['search'] . '%')
                          ->orWhere('last_name', 'like', '%' . $validatedData['search'] . '%')
                          ->orWhere('email', 'like', '%' . $validatedData['search'] . '%')
                          ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ['%' . $validatedData['search'] . '%']);
                })
                ->paginate($perPage);

            // Get specific friends IDs
            $specificFriendIds = \App\Models\SpecificFriends::where('user_id', $user->id)
                ->pluck('friend_id')
                ->toArray();

            // Get friend except IDs
            $friendExceptIds = \App\Models\FriendExcept::where('user_id', $user->id)
                ->pluck('friend_id')
                ->toArray();

            // Map friends with specific/except flags
            $mappedFriends = $friends->getCollection()->map(function ($friend) use ($specificFriendIds, $friendExceptIds) {
                $friendData = app(AuthController::class)->mapUserDetails($friend);
                $friendData['is_specific_friend'] = in_array($friend->id, $specificFriendIds);
                $friendData['is_friend_except'] = in_array($friend->id, $friendExceptIds);
                return $friendData;
            });

            return response()->json([
                'status_code' => 200,
                'success' => true,
                'message' => 'Friends found successfully',
                'data' => [
                    'friends' => $mappedFriends,
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

    public function getPersonalOccasionCategories(Request $request)
    {
        try{
            $validatedData = $request->validate([
                'page' => 'nullable|integer|min:1',
                'per_page' => 'nullable|integer|min:1|max:100'
            ]);

            $perPage = $validatedData['per_page'] ?? 20;

            $personalOccasionCategories = PersonalOccasionCategory::where('status', 'active')
                ->paginate($perPage);

            $mappedCategories = $personalOccasionCategories->getCollection()->map(function ($category) {
                return $this->mapPersonalOccasionCategories(collect([$category]))->first();
            });

            return response()->json([
                'status_code' => 200,
                'success' => true,
                'message' => 'Personal occasion categories retrieved successfully',
                'data' => [
                    'personal_occasion_categories' => $mappedCategories,
                    'pagination' => [
                        'current_page' => $personalOccasionCategories->currentPage(),
                        'last_page' => $personalOccasionCategories->lastPage(),
                        'per_page' => $personalOccasionCategories->perPage(),
                        'total' => $personalOccasionCategories->total(),
                        'from' => $personalOccasionCategories->firstItem(),
                        'to' => $personalOccasionCategories->lastItem(),
                        'has_more_pages' => $personalOccasionCategories->hasMorePages()
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
                'message' => 'Failed to retrieve personal occasion categories',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getPersonalOccasionCategoriesWithOccasions(Request $request)
    {
        try{
            $validatedData = $request->validate([
                'page' => 'nullable|integer|min:1',
                'per_page' => 'nullable|integer|min:1|max:100'
            ]);

            $perPage = $validatedData['per_page'] ?? 20;

            $personalOccasionCategories = PersonalOccasionCategory::with(['personalOccasionSettings' => function($query) {
                $query->where('status', 'active');
            }])->where('status', 'active')
            ->paginate($perPage);

            $mappedCategories = $personalOccasionCategories->getCollection()->map(function ($category) {
                return $this->mapPersonalOccasionCategories(collect([$category]))->first();
            });

            return response()->json([
                'status_code' => 200,
                'success' => true,
                'message' => 'Personal occasion categories with occasions retrieved successfully',
                'data' => [
                    'personal_occasion_categories' => $mappedCategories,
                    'pagination' => [
                        'current_page' => $personalOccasionCategories->currentPage(),
                        'last_page' => $personalOccasionCategories->lastPage(),
                        'per_page' => $personalOccasionCategories->perPage(),
                        'total' => $personalOccasionCategories->total(),
                        'from' => $personalOccasionCategories->firstItem(),
                        'to' => $personalOccasionCategories->lastItem(),
                        'has_more_pages' => $personalOccasionCategories->hasMorePages()
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
                'message' => 'Failed to retrieve personal occasion categories with occasions',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getPersonalOccasionSettingsByCategory(Request $request)
    {
        try{
            $validatedData = $request->validate([
                'category_id' => 'required|exists:personal_occasion_categories,id',
                'page' => 'nullable|integer|min:1',
                'per_page' => 'nullable|integer|min:1|max:100'
            ]);

            $perPage = $validatedData['per_page'] ?? 20;

            $personalOccasionSettings = PersonalOccasionSetting::where('status', 'active')
                ->where('personal_occasion_category_id', $validatedData['category_id'])
                ->paginate($perPage);

            $mappedSettings = $personalOccasionSettings->getCollection()->map(function ($setting) {
                return $this->mapPersonalOccasionSettings(collect([$setting]))->first();
            });

            return response()->json([
                'status_code' => 200,
                'success' => true,
                'message' => 'Personal occasion settings retrieved successfully',
                'data' => [
                    'personal_occasion_settings' => $mappedSettings,
                    'pagination' => [
                        'current_page' => $personalOccasionSettings->currentPage(),
                        'last_page' => $personalOccasionSettings->lastPage(),
                        'per_page' => $personalOccasionSettings->perPage(),
                        'total' => $personalOccasionSettings->total(),
                        'from' => $personalOccasionSettings->firstItem(),
                        'to' => $personalOccasionSettings->lastItem(),
                        'has_more_pages' => $personalOccasionSettings->hasMorePages()
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
                'message' => 'Failed to retrieve personal occasion settings',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getUserPlaces(Request $request)
    {
        try{
            $validatedData = $request->validate([
                'page' => 'nullable|integer|min:1',
                'per_page' => 'nullable|integer|min:1|max:100'
            ]);

            $perPage = $validatedData['per_page'] ?? 20;

            $userPlaces = UserPlace::where('user_id', Auth::guard('user')->user()->id)
                ->where('status', 'active')
                ->paginate($perPage);

            $mappedUserPlaces = $userPlaces->getCollection()->map(function ($userPlace) {
                return $this->mapUserPlaces($userPlace);
            });

            return response()->json([
                'status_code' => 200,
                'success' => true,
                'message' => 'User places retrieved successfully',
                'data' => [
                    'user_places' => $mappedUserPlaces,
                    'pagination' => [
                        'current_page' => $userPlaces->currentPage(),
                        'last_page' => $userPlaces->lastPage(),
                        'per_page' => $userPlaces->perPage(),
                        'total' => $userPlaces->total(),
                        'from' => $userPlaces->firstItem(),
                        'to' => $userPlaces->lastItem(),
                        'has_more_pages' => $userPlaces->hasMorePages()
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
                'message' => 'Failed to retrieve user places',
                'error' => $e->getMessage()
            ], 500);    
        }
    }

    public function getUserPlaceById(Request $request)
    {
        try{
            $validatedData = $request->validate([
                'place_id' => 'required|exists:user_places,id',
            ]);
            $userPlace = UserPlace::where('user_id', Auth::guard('user')->user()->id)->find($validatedData['place_id']);

            return response()->json([
                'status_code' => 200,
                'success' => true,
                'message' => 'User place retrieved successfully',
                'data' => $userPlace ? $this->mapUserPlaces($userPlace) : null
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
                'message' => 'Failed to retrieve user place',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    public function createUserPlace(Request $request)
    {
        try{
            $validatedData = $request->validate([
                'name' => 'required|string|max:255',
                'city' => 'nullable|string|max:255',
                'country' => 'nullable|string|max:255',
                'latitude' => 'nullable|string|max:255',
                'longitude' => 'nullable|string|max:255',
                'status' => 'nullable|in:active,inactive',
            ]);
            $validatedData['user_id'] = Auth::guard('user')->user()->id;
            $userPlace = UserPlace::create([
                'user_id' => Auth::guard('user')->user()->id,
                'name' => $validatedData['name'],
                'city' => $validatedData['city'],
                'country' => $validatedData['country'],
                'latitude' => $validatedData['latitude'],
                'longitude' => $validatedData['longitude'],
                'status' => $validatedData['status'],
            ]);

            return response()->json([
                'status_code' => 200,
                'success' => true,
                'message' => 'User place created successfully',
                'data' => $this->mapUserPlaces($userPlace)
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
                'message' => 'Failed to create user place',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function updateUserPlace(Request $request)
    {
        try{
            $validatedData = $request->validate([
                'place_id' => 'required|exists:user_places,id',
                'name' => 'nullable|string|max:255',
                'city' => 'nullable|string|max:255',
                'country' => 'nullable|string|max:255',
                'latitude' => 'nullable|string|max:255',
                'longitude' => 'nullable|string|max:255',
                'status' => 'nullable|in:active,inactive',
            ]);
            $userPlace = UserPlace::where('user_id', Auth::guard('user')->user()->id)->findOrFail($validatedData['place_id']);
            $userPlace->update([
                'name' => $validatedData['name'],
                'city' => $validatedData['city'],
                'country' => $validatedData['country'],
                'latitude' => $validatedData['latitude'],
                'longitude' => $validatedData['longitude'],
                'status' => $validatedData['status'],
            ]);

            return response()->json([
                'status_code' => 200,
                'success' => true,
                'message' => 'User place updated successfully',
                'data' => $this->mapUserPlaces($userPlace)
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
                'message' => 'Failed to update user place',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function searchUserPlaces(Request $request)
    {
        try{
            $validatedData = $request->validate([
                'search' => 'required|string|max:255',
                'page' => 'nullable|integer|min:1',
                'per_page' => 'nullable|integer|min:1|max:100'
            ]);

            $perPage = $validatedData['per_page'] ?? 20;

            $userPlaces = UserPlace::where('user_id', Auth::guard('user')->user()->id)
                ->where('status', 'active')
                ->where('name', 'like', '%' . $validatedData['search'] . '%')
                ->paginate($perPage);

            $mappedUserPlaces = $userPlaces->getCollection()->map(function ($userPlace) {
                return $this->mapUserPlaces($userPlace);
            });

            return response()->json([
                'status_code' => 200,
                'success' => true,
                'message' => 'User places retrieved successfully',
                'data' => [
                    'user_places' => $mappedUserPlaces,
                    'pagination' => [
                        'current_page' => $userPlaces->currentPage(),
                        'last_page' => $userPlaces->lastPage(),
                        'per_page' => $userPlaces->perPage(),
                        'total' => $userPlaces->total(),
                        'from' => $userPlaces->firstItem(),
                        'to' => $userPlaces->lastItem(),
                        'has_more_pages' => $userPlaces->hasMorePages()
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
        }
    }

    public function pinPost(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'post_id' => 'required|integer|exists:posts,id',
                'is_pinned' => 'required|boolean'
            ]);

            $user = Auth::guard('user')->user();
            
            // Find the post and ensure it belongs to the user
            $post = Post::where('user_id', $user->id)
                ->findOrFail($validatedData['post_id']);

            DB::beginTransaction();

            // If pinning this post, unpin any previously pinned posts
            if ($validatedData['is_pinned']) {
                Post::where('user_id', $user->id)
                    ->where('is_pinned', true)
                    ->update(['is_pinned' => false]);
            }

            // Update the post's pinned status
            $post->update(['is_pinned' => $validatedData['is_pinned']]);

            DB::commit();

            return response()->json([
                'status_code' => 200,
                'success' => true,
                'message' => $validatedData['is_pinned'] ? 'Post pinned successfully' : 'Post unpinned successfully',
                'data' => [
                    'post_id' => $post->id,
                    'is_pinned' => $post->is_pinned,
                    'pinned_at' => $post->is_pinned ? $post->updated_at : null
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
                'message' => 'Failed to update post pin status',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function updatePost(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'post_id' => 'required|integer|exists:posts,id',
                'content' => 'nullable|string|max:10000',
                'text_properties' => 'nullable|array',
                'text_properties.color' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
                'text_properties.bold' => 'nullable|boolean',
                'text_properties.italic' => 'nullable|boolean',
                'text_properties.underline' => 'nullable|boolean',
                'background_color' => 'nullable|array',
                'background_color.*' => 'string|regex:/^#[0-9A-Fa-f]{6}$/',
                'privacy' => 'nullable|in:public,friends,specific_friends,friend_except,only_me',
                'specific_friends' => 'nullable|array',
                'specific_friends.*' => 'exists:users,id',
                'friend_except' => 'nullable|array',
                'friend_except.*' => 'exists:users,id',
                'disappears_24h' => 'nullable|boolean',
                'mentions.friends' => 'nullable|array',
                'mentions.friends.*' => 'exists:users,id',
                'mentions.place' => 'nullable|integer|exists:user_places,id',
                'mentions.feeling' => 'nullable|string|max:100',
                'mentions.activity' => 'nullable|string|max:100',
                'media' => 'nullable|array',
                'media.*.file' => 'required|file|mimes:jpeg,png,gif,mp4,avi|max:51200',
                'media.*.order' => 'nullable|integer|min:1',
                'media.*.auto_play' => 'nullable|boolean',
                'media.*.apply_to_download' => 'nullable|boolean',
                'media.*.is_rotate' => 'nullable|boolean',
                'media.*.rotate_angle' => 'nullable|integer|min:0|max:360',
                'media.*.is_flip_horizontal' => 'nullable|boolean',
                'media.*.is_flip_vertical' => 'nullable|boolean',
                'media.*.filter_name' => 'nullable|string|max:255',
                'gif_url' => 'nullable|url|max:2048',
                'is_pinned' => 'nullable|boolean'
            ]);

            $user = Auth::guard('user')->user();
            
            // Find the post and ensure it belongs to the user
            $post = Post::where('user_id', $user->id)
                ->findOrFail($validatedData['post_id']);

            // Custom validation for background_color
            if (!empty($validatedData['background_color'])) {
                if ($post->type !== 'regular') {
                    return response()->json([
                        'status_code' => 422,
                        'success' => false,
                        'message' => 'Validation failed',
                        'errors' => [
                            'background_color' => ['Background colors can only be used with regular posts.']]
                    ], 422);
                }

                if (!empty($validatedData['media']) || !empty($validatedData['gif_url'])) {
                    return response()->json([
                        'status_code' => 422,
                        'success' => false,
                        'message' => 'Validation failed',
                        'errors' => [
                            'background_color' => ['Background colors cannot be used when uploading media files or using GIF URL.']]
                    ], 422);
                }

                // Additional validation for background_color array
                if (count($validatedData['background_color']) > 10) {
                    return response()->json([
                        'status_code' => 422,
                        'success' => false,
                        'message' => 'Validation failed',
                        'errors' => [
                            'background_color' => ['You can specify a maximum of 10 background colors.']]
                    ], 422);
                }
            }

            // Custom validation for gif_url and media conflict
            if (!empty($validatedData['gif_url']) && !empty($validatedData['media'])) {
                return response()->json([
                    'status_code' => 422,
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => [
                        'gif_url' => ['You cannot use both GIF URL and upload media files at the same time.']]
                ], 422);
            }

            // Custom validation for gif_url with post types
            if (!empty($validatedData['gif_url'])) {
                if ($post->type === 'poll') {
                    return response()->json([
                        'status_code' => 422,
                        'success' => false,
                        'message' => 'Validation failed',
                        'errors' => [
                            'gif_url' => ['GIF URL cannot be used with poll posts.']]
                    ], 422);
                }
            }

            // Custom validation for media order uniqueness
            if (!empty($validatedData['media'])) {
                $orders = [];
                foreach ($validatedData['media'] as $index => $mediaItem) {
                    if (isset($mediaItem['order'])) {
                        $order = $mediaItem['order'];
                        if (in_array($order, $orders)) {
                            return response()->json([
                                'status_code' => 422,
                                'success' => false,
                                'message' => 'Validation failed',
                                'errors' => [
                                    "media.{$index}.order" => ['Display order values must be unique for each media file.']]
                            ], 422);
                        }
                        $orders[] = $order;
                    }
                }
            }

            // Custom validation for mentions
            $mentions = $request->input('mentions', []);
            $hasFeeling = !empty($mentions['feeling']);
            $hasActivity = !empty($mentions['activity']);
            $hasPlace = !empty($mentions['place']);
            $hasFriends = !empty($mentions['friends']);
            
            // Check if poll type has any mentions
            if ($post->type === 'poll') {
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
            if ($post->type !== 'poll' && $hasFeeling && $hasActivity) {
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

            // Validate user place ownership
            if ($hasPlace && !empty($mentions['place'])) {
                $userPlace = UserPlace::where('id', $mentions['place'])
                    ->where('user_id', $user->id)
                    ->where('status', 'active')
                    ->first();

                if (!$userPlace) {
                    return response()->json([
                        'status_code' => 422,
                        'success' => false,
                        'message' => 'Validation failed',
                        'errors' => [
                            'mentions.place' => [
                                'The selected place does not belong to you or is not active.'
                            ]
                        ]
                    ], 422);
                }
            }

            // Validate friendship for specific_friends privacy
            if (isset($validatedData['privacy']) && $validatedData['privacy'] === 'specific_friends' && !empty($validatedData['specific_friends'])) {
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
            if (isset($validatedData['privacy']) && $validatedData['privacy'] === 'friend_except' && !empty($validatedData['friend_except'])) {
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
                if (isset($validatedData['privacy']) && $validatedData['privacy'] === 'specific_friends' && !empty($validatedData['specific_friends'])) {
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
                if (isset($validatedData['privacy']) && $validatedData['privacy'] === 'friend_except' && !empty($validatedData['friend_except'])) {
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

            // Prepare update data
            $updateData = [];
            
            if (isset($validatedData['content'])) {
                $updateData['content'] = $validatedData['content'];
            }
            if (isset($validatedData['text_properties'])) {
                $updateData['text_properties'] = $validatedData['text_properties'];
            }
            if (isset($validatedData['background_color'])) {
                $updateData['background_color'] = $validatedData['background_color'];
            }
            if (isset($validatedData['privacy'])) {
                $updateData['privacy'] = $validatedData['privacy'];
                $updateData['specific_friends'] = $validatedData['privacy'] === 'specific_friends' ? ($validatedData['specific_friends'] ?? null) : null;
                $updateData['friend_except'] = $validatedData['privacy'] === 'friend_except' ? ($validatedData['friend_except'] ?? null) : null;
            }
            if (isset($validatedData['disappears_24h'])) {
                $updateData['disappears_24h'] = $validatedData['disappears_24h'];
            }
            if (isset($validatedData['gif_url'])) {
                $updateData['gif_url'] = $validatedData['gif_url'];
            }
            if (isset($validatedData['is_pinned'])) {
                // If pinning this post, unpin any previously pinned posts
                if ($validatedData['is_pinned']) {
                    Post::where('user_id', $user->id)
                        ->where('id', '!=', $post->id)
                        ->where('is_pinned', true)
                        ->update(['is_pinned' => false]);
                }
                $updateData['is_pinned'] = $validatedData['is_pinned'];
            }

            // Update mentions only for non-poll posts
            if ($post->type !== 'poll') {
                $updateData['mentions'] = $mentions;
            }

            // Update the post
            $post->update($updateData);

            // Handle media uploads if provided
            if (!empty($validatedData['media'])) {
                // Delete existing media files
                foreach ($post->media as $media) {
                    Storage::disk('public')->delete($media->path);
                }
                $post->media()->delete();
                
                // Upload new media files
                $this->handleMediaUploads($post, $validatedData['media']);
            }

            DB::commit();

            // Load relationships for response
            $post->load(['user', 'media', 'poll', 'personalOccasion']);

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

    public function mutePostNotifications(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'post_id' => 'required|integer|exists:posts,id',
                'mute_type' => 'required|in:24_hours,7_days,30_days,permanent,unmute',
                'mute_reactions' => 'nullable|boolean',
                'mute_comments' => 'nullable|boolean',
                'mute_shares' => 'nullable|boolean',
                'mute_all' => 'nullable|boolean'
            ]);

            $user = Auth::guard('user')->user();
            
            // Find the post and ensure it belongs to the user
            $post = Post::where('user_id', $user->id)
                ->findOrFail($validatedData['post_id']);

            DB::beginTransaction();

            // Calculate expiration time based on mute type
            $expiresAt = null;
            if ($validatedData['mute_type'] !== 'permanent' && $validatedData['mute_type'] !== 'unmute') {
                switch ($validatedData['mute_type']) {
                    case '24_hours':
                        $expiresAt = Carbon::now()->addHours(24);
                        break;
                    case '7_days':
                        $expiresAt = Carbon::now()->addDays(7);
                        break;
                    case '30_days':
                        $expiresAt = Carbon::now()->addDays(30);
                        break;
                }
            }

            // If unmuting, delete the mute setting
            if ($validatedData['mute_type'] === 'unmute') {
                DB::table('post_notification_settings')
                    ->where('post_id', $post->id)
                    ->where('post_owner_id', $user->id)
                    ->delete();

                DB::commit();

                return response()->json([
                    'status_code' => 200,
                    'success' => true,
                    'message' => 'Post notifications unmuted successfully',
                    'data' => [
                        'post_id' => $post->id,
                        'mute_type' => 'unmuted',
                        'expires_at' => null
                    ]
                ], 200);
            }

            // Check if mute setting already exists
            $existingMute = DB::table('post_notification_settings')
                ->where('post_id', $post->id)
                ->where('post_owner_id', $user->id)
                ->first();

            $muteData = [
                'post_id' => $post->id,
                'post_owner_id' => $user->id,
                'mute_type' => $validatedData['mute_type'],
                'mute_reactions' => $validatedData['mute_reactions'] ?? false,
                'mute_comments' => $validatedData['mute_comments'] ?? false,
                'mute_shares' => $validatedData['mute_shares'] ?? false,
                'mute_all' => $validatedData['mute_all'] ?? false,
                'expires_at' => $expiresAt,
                'updated_at' => now()
            ];

            if ($existingMute) {
                // Update existing mute setting
                DB::table('post_notification_settings')
                    ->where('post_id', $post->id)
                    ->where('post_owner_id', $user->id)
                    ->update($muteData);
            } else {
                // Create new mute setting
                $muteData['created_at'] = now();
                DB::table('post_notification_settings')->insert($muteData);
            }

            DB::commit();

            return response()->json([
                'status_code' => 200,
                'success' => true,
                'message' => 'Post notification settings updated successfully',
                'data' => [
                    'post_id' => $post->id,
                    'mute_type' => $validatedData['mute_type'],
                    'mute_reactions' => $validatedData['mute_reactions'] ?? false,
                    'mute_comments' => $validatedData['mute_comments'] ?? false,
                    'mute_shares' => $validatedData['mute_shares'] ?? false,
                    'mute_all' => $validatedData['mute_all'] ?? false,
                    'expires_at' => $expiresAt,
                    'created_at' => $existingMute ? $existingMute->created_at : now(),
                    'updated_at' => now()
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
                'message' => 'Failed to update post notification settings',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getPostNotificationSettings(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'post_id' => 'required|integer|exists:posts,id'
            ]);

            $user = Auth::guard('user')->user();
            
            // Find the post and ensure it belongs to the user
            $post = Post::where('user_id', $user->id)
                ->findOrFail($validatedData['post_id']);

            // Get current notification settings
            $notificationSettings = DB::table('post_notification_settings')
                ->where('post_id', $post->id)
                ->where('post_owner_id', $user->id)
                ->first();

            if (!$notificationSettings) {
                return response()->json([
                    'status_code' => 200,
                    'success' => true,
                    'message' => 'No notification settings found for this post',
                    'data' => [
                        'post_id' => $post->id,
                        'mute_type' => 'none',
                        'mute_reactions' => false,
                        'mute_comments' => false,
                        'mute_shares' => false,
                        'mute_all' => false,
                        'expires_at' => null,
                        'is_expired' => false
                    ]
                ], 200);
            }

            // Check if the mute setting has expired
            $isExpired = false;
            if ($notificationSettings->expires_at && Carbon::parse($notificationSettings->expires_at)->isPast()) {
                $isExpired = true;
            }

            return response()->json([
                'status_code' => 200,
                'success' => true,
                'message' => 'Post notification settings retrieved successfully',
                'data' => [
                    'post_id' => $post->id,
                    'mute_type' => $notificationSettings->mute_type,
                    'mute_reactions' => (bool) $notificationSettings->mute_reactions,
                    'mute_comments' => (bool) $notificationSettings->mute_comments,
                    'mute_shares' => (bool) $notificationSettings->mute_shares,
                    'mute_all' => (bool) $notificationSettings->mute_all,
                    'expires_at' => $notificationSettings->expires_at,
                    'is_expired' => $isExpired,
                    'created_at' => $notificationSettings->created_at,
                    'updated_at' => $notificationSettings->updated_at
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
                'message' => 'Failed to retrieve post notification settings',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function savePost(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'post_id' => 'required|integer|exists:posts,id',
                'is_saved' => 'required|boolean'
            ]);

            $user = Auth::guard('user')->user();
            
            // Find the post and ensure it's visible to the user
            $post = Post::approved()
                ->visibleTo($user)
                ->findOrFail($validatedData['post_id']);

            DB::beginTransaction();

            if ($validatedData['is_saved']) {
                // Check if post is already saved
                $existingSave = DB::table('saved_posts')
                    ->where('user_id', $user->id)
                    ->where('post_id', $post->id)
                    ->first();

                if (!$existingSave) {
                    // Save the post
                    DB::table('saved_posts')->insert([
                        'user_id' => $user->id,
                        'post_id' => $post->id,
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                }

                $message = 'Post saved successfully';
            } else {
                // Unsave the post
                DB::table('saved_posts')
                    ->where('user_id', $user->id)
                    ->where('post_id', $post->id)
                    ->delete();

                $message = 'Post removed from saved posts';
            }

            DB::commit();

            return response()->json([
                'status_code' => 200,
                'success' => true,
                'message' => $message,
                'data' => [
                    'post_id' => $post->id,
                    'is_saved' => $validatedData['is_saved'],
                    'saved_at' => $validatedData['is_saved'] ? now() : null
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
                'message' => 'Failed to update post save status',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getSavedPosts(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'page' => 'nullable|integer|min:1',
                'per_page' => 'nullable|integer|min:1|max:100'
            ]);

            $user = Auth::guard('user')->user();
            $perPage = $validatedData['per_page'] ?? 20;

            // Get saved posts with pagination
            $savedPosts = DB::table('saved_posts')
                ->join('posts', 'saved_posts.post_id', '=', 'posts.id')
                ->where('saved_posts.user_id', $user->id)
                ->where('posts.status', 'approved')
                ->select('posts.*', 'saved_posts.created_at as saved_at')
                ->orderBy('saved_posts.created_at', 'desc')
                ->paginate($perPage);

            // Load relationships for each post
            $posts = Post::with(['user', 'media', 'poll', 'personalOccasion'])
                ->whereIn('id', $savedPosts->pluck('id'))
                ->get()
                ->keyBy('id');

            // Map posts with save information
            $mappedPosts = $savedPosts->getCollection()->map(function ($savedPost) use ($posts) {
                $post = $posts->get($savedPost->id);
                if (!$post) return null;

                $postData = $post->toArray();
                $postData['saved_at'] = $savedPost->saved_at;
                $postData['is_saved'] = true;

                return $postData;
            })->filter();

            return response()->json([
                'status_code' => 200,
                'success' => true,
                'message' => 'Saved posts retrieved successfully',
                'data' => [
                    'posts' => $mappedPosts->values(),
                    'pagination' => [
                        'current_page' => $savedPosts->currentPage(),
                        'last_page' => $savedPosts->lastPage(),
                        'per_page' => $savedPosts->perPage(),
                        'total' => $savedPosts->total(),
                        'from' => $savedPosts->firstItem(),
                        'to' => $savedPosts->lastItem(),
                        'has_more_pages' => $savedPosts->hasMorePages()
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
                'message' => 'Failed to retrieve saved posts',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function checkPostSavedStatus(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'post_id' => 'required|integer|exists:posts,id'
            ]);

            $user = Auth::guard('user')->user();
            
            // Check if post is saved
            $savedPost = DB::table('saved_posts')
                ->where('user_id', $user->id)
                ->where('post_id', $validatedData['post_id'])
                ->first();

            return response()->json([
                'status_code' => 200,
                'success' => true,
                'message' => 'Post save status retrieved successfully',
                'data' => [
                    'post_id' => $validatedData['post_id'],
                    'is_saved' => !is_null($savedPost),
                    'saved_at' => $savedPost ? $savedPost->created_at : null
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
                'message' => 'Failed to check post save status',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function togglePostComments(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'post_id' => 'required|integer|exists:posts,id',
                'comments_enabled' => 'required|boolean'
            ]);

            $user = Auth::guard('user')->user();
            
            // Find the post and ensure it belongs to the user
            $post = Post::where('user_id', $user->id)
                ->findOrFail($validatedData['post_id']);

            DB::beginTransaction();

            // Update the post's comments status
            $post->update(['comments_enabled' => $validatedData['comments_enabled']]);

            DB::commit();

            return response()->json([
                'status_code' => 200,
                'success' => true,
                'message' => $validatedData['comments_enabled'] ? 'Comments enabled successfully' : 'Comments disabled successfully',
                'data' => [
                    'post_id' => $post->id,
                    'comments_enabled' => $validatedData['comments_enabled'],
                    'updated_at' => $post->updated_at
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
                'message' => 'Failed to update post comments status',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getPostCommentsStatus(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'post_id' => 'required|integer|exists:posts,id'
            ]);

            $user = Auth::guard('user')->user();
            
            // Find the post and ensure it belongs to the user
            $post = Post::where('user_id', $user->id)
                ->findOrFail($validatedData['post_id']);

            return response()->json([
                'status_code' => 200,
                'success' => true,
                'message' => 'Post comments status retrieved successfully',
                'data' => [
                    'post_id' => $post->id,
                    'comments_enabled' => (bool) $post->comments_enabled,
                    'updated_at' => $post->updated_at
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
                'message' => 'Failed to retrieve post comments status',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function deletePost(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'post_id' => 'required|integer|exists:posts,id'
            ]);

            $user = Auth::guard('user')->user();
            
            // Find the post and ensure it belongs to the user
            $post = Post::where('user_id', $user->id)
                ->findOrFail($validatedData['post_id']);

            DB::beginTransaction();

            // Delete media files from storage
            foreach ($post->media as $media) {
                try {
                Storage::disk('public')->delete($media->path);
                } catch (Exception $e) {
                    // Log the error but don't fail the deletion
                    \Log::warning('Failed to delete media file: ' . $e->getMessage(), [
                        'post_id' => $post->id,
                        'media_id' => $media->id,
                        'path' => $media->path
                    ]);
                }
            }

            // Delete related data (cascade will handle most, but we'll be explicit)
            $post->media()->delete();
            $post->poll()->delete();
            $post->personalOccasion()->delete();

            // Delete from saved posts
            DB::table('saved_posts')
                ->where('post_id', $post->id)
                ->delete();

            // Delete notification settings
            DB::table('post_notification_settings')
                ->where('post_id', $post->id)
                ->delete();

            // Delete the post
            $post->delete();

            DB::commit();

            return response()->json([
                'status_code' => 200,
                'success' => true,
                'message' => 'Post deleted successfully',
                'data' => [
                    'post_id' => $validatedData['post_id'],
                    'deleted_at' => now()
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
                'message' => 'Failed to delete post',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function postInterest(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'post_id' => 'required|integer|exists:posts,id',
                'interest_type' => 'required|in:interested,not_interested'
            ]);

            $user = Auth::guard('user')->user();
            
            // Find the post and ensure it's from a friend and visible to the user
            $post = Post::approved()
                ->visibleTo($user)
                ->findOrFail($validatedData['post_id']);

            // Ensure the post is from a friend (not the user's own post)
            if ($post->user_id === $user->id) {
                return response()->json([
                    'status_code' => 400,
                    'success' => false,
                    'message' => 'You cannot mark your own posts as interested/not interested'
                ], 400);
            }

            // Check if user is friends with the post owner
            if (!$user->isFriendsWith($post->user_id)) {
                return response()->json([
                    'status_code' => 400,
                    'success' => false,
                    'message' => 'You can only mark posts from your friends as interested/not interested'
                ], 400);
            }

            DB::beginTransaction();

            // Check if interest feedback already exists
            $existingInterest = DB::table('post_interest_feedback')
                ->where('user_id', $user->id)
                ->where('post_id', $post->id)
                ->first();

            if ($existingInterest) {
                // Update existing feedback
                DB::table('post_interest_feedback')
                    ->where('user_id', $user->id)
                    ->where('post_id', $post->id)
                    ->update([
                        'interest_type' => $validatedData['interest_type'],
                        'updated_at' => now()
                    ]);

                $message = $validatedData['interest_type'] === 'interested' 
                    ? 'Post marked as interested successfully' 
                    : 'Post marked as not interested successfully';
            } else {
                // Create new feedback
                DB::table('post_interest_feedback')->insert([
                    'user_id' => $user->id,
                    'post_id' => $post->id,
                    'post_owner_id' => $post->user_id,
                    'interest_type' => $validatedData['interest_type'],
                    'created_at' => now(),
                    'updated_at' => now()
                ]);

                $message = $validatedData['interest_type'] === 'interested' 
                    ? 'Post marked as interested successfully' 
                    : 'Post marked as not interested successfully';
            }

            DB::commit();

            return response()->json([
                'status_code' => 200,
                'success' => true,
                'message' => $message,
                'data' => [
                    'post_id' => $post->id,
                    'post_owner_id' => $post->user_id,
                    'interest_type' => $validatedData['interest_type'],
                    'feedback_at' => now()
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
                'message' => 'Failed to update post interest feedback',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function togglePostNotifications(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'post_id' => 'required|integer|exists:posts,id',
                'notifications_enabled' => 'required|boolean'
            ]);

            $user = Auth::guard('user')->user();
            
            // Find the post and ensure it belongs to the user
            $post = Post::where('user_id', $user->id)
                ->findOrFail($validatedData['post_id']);

            DB::beginTransaction();

            // Update the post's notifications status
            $post->update(['notifications_enabled' => $validatedData['notifications_enabled']]);

            DB::commit();

            return response()->json([
                'status_code' => 200,
                'success' => true,
                'message' => $validatedData['notifications_enabled'] ? 'Post notifications enabled successfully' : 'Post notifications disabled successfully',
                'data' => [
                    'post_id' => $post->id,
                    'notifications_enabled' => $validatedData['notifications_enabled'],
                    'updated_at' => $post->updated_at
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
                'message' => 'Failed to update post notifications status',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getPostNotificationsStatus(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'post_id' => 'required|integer|exists:posts,id'
            ]);

            $user = Auth::guard('user')->user();
            
            // Find the post and ensure it belongs to the user
            $post = Post::where('user_id', $user->id)
                ->findOrFail($validatedData['post_id']);

            return response()->json([
                'status_code' => 200,
                'success' => true,
                'message' => 'Post notifications status retrieved successfully',
                'data' => [
                    'post_id' => $post->id,
                    'notifications_enabled' => (bool) $post->notifications_enabled,
                    'updated_at' => $post->updated_at
                ]
            ], 200);

        } catch (ValidationException $e) {
            return response()->json([
                'status_code' => 422,
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'status_code' => 404,
                'success' => false,
                'message' => 'Post not found or does not belong to you',
                'error' => $e->getMessage(),
                'details' => [
                    'post_id' => $request->post_id,
                    'user_id' => Auth::guard('user')->id()
                ]
            ], 404);
        } catch (Exception $e) {
            return response()->json([
                'status_code' => 500,
                'success' => false,
                'message' => 'Failed to retrieve post notifications status',
                'error' => $e->getMessage(),
                'details' => [
                    'post_id' => $request->post_id ?? null,
                    'user_id' => Auth::guard('user')->id() ?? null
                ]
            ], 500);
        }
    }

    public function hideSpecificFriendPost(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'post_id' => 'required|integer|exists:posts,id',
                'hide_type' => 'required|in:permanent,30_days,unhide'
            ]);

            $user = Auth::guard('user')->user();
            
            // Find the post and ensure it's from a friend
            $post = Post::approved()
                ->visibleTo($user)
                ->findOrFail($validatedData['post_id']);

            // Ensure the post is from a friend (not the user's own post)
            if ($post->user_id === $user->id) {
                return response()->json([
                    'status_code' => 400,
                    'success' => false,
                    'message' => 'You cannot hide your own posts'
                ], 400);
            }

            // Check if user is friends with the post owner
            if (!$user->isFriendsWith($post->user_id)) {
                return response()->json([
                    'status_code' => 400,
                    'success' => false,
                    'message' => 'You can only hide posts from your friends'
                ], 400);
            }

            DB::beginTransaction();

            // Handle unhide (remove hide setting)
            if ($validatedData['hide_type'] === 'unhide') {
                $deletedCount = DB::table('hidden_friend_posts')
                    ->where('user_id', $user->id)
                    ->where('friend_id', $post->user_id)
                    ->where('post_id', $post->id)
                    ->delete();

                if ($deletedCount === 0) {
                    return response()->json([
                        'status_code' => 404,
                        'success' => false,
                        'message' => 'No hide setting found for this friend post'
                    ], 404);
                }

                DB::commit();

                return response()->json([
                    'status_code' => 200,
                    'success' => true,
                    'message' => 'Friend post unhidden successfully',
                    'data' => [
                        'post_id' => $post->id,
                        'friend_id' => $post->user_id,
                        'hide_type' => 'unhidden',
                        'unhidden_at' => now()
                    ]
                ], 200);
            }

            // Calculate expiration date for 30 days hide
            $expiresAt = null;
            if ($validatedData['hide_type'] === '30_days') {
                $expiresAt = Carbon::now()->addDays(30);
            }

            // Check if hide setting already exists for this specific post
            $existingHide = DB::table('hidden_friend_posts')
                ->where('user_id', $user->id)
                ->where('friend_id', $post->user_id)
                ->where('post_id', $post->id)
                ->first();

            if ($existingHide) {
                return response()->json([
                    'status_code' => 400,
                    'success' => false,
                    'message' => 'This post is already hidden'
                ], 400);
            }

            // Create new hide setting for specific post
            DB::table('hidden_friend_posts')->insert([
                'user_id' => $user->id,
                'friend_id' => $post->user_id,
                'post_id' => $post->id,
                'hide_type' => $validatedData['hide_type'],
                'expires_at' => $expiresAt,
                'created_at' => now(),
                'updated_at' => now()
            ]);

            DB::commit();

            $message = $validatedData['hide_type'] === 'permanent' 
                ? 'Post hidden permanently' 
                : 'Post hidden for 30 days';

            return response()->json([
                'status_code' => 200,
                'success' => true,
                'message' => $message,
                'data' => [
                    'post_id' => $post->id,
                    'friend_id' => $post->user_id,
                    'hide_type' => $validatedData['hide_type'],
                    'expires_at' => $expiresAt,
                    'hidden_at' => now()
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
                'message' => 'Failed to hide specific friend post',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getHiddenPosts(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'page' => 'nullable|integer|min:1',
                'per_page' => 'nullable|integer|min:1|max:100',
                'hide_type' => 'nullable|in:permanent,30_days,all'
            ]);

            $user = Auth::guard('user')->user();
            $perPage = $validatedData['per_page'] ?? 20;
            $hideType = $validatedData['hide_type'] ?? 'all';

            // Get hidden posts with pagination
            $hiddenPosts = DB::table('hidden_friend_posts')
                ->join('posts', 'hidden_friend_posts.post_id', '=', 'posts.id')
                ->join('users', 'posts.user_id', '=', 'users.id')
                ->where('hidden_friend_posts.user_id', $user->id)
                ->whereNotNull('hidden_friend_posts.post_id') // Only specific posts, not all friend posts
                ->where(function($query) {
                    $query->where('hidden_friend_posts.hide_type', 'permanent')
                          ->orWhere(function($q) {
                              $q->where('hidden_friend_posts.hide_type', '30_days')
                                ->where('hidden_friend_posts.expires_at', '>', now());
                          });
                });

            // Filter by hide type if specified
            if ($hideType !== 'all') {
                $hiddenPosts->where('hidden_friend_posts.hide_type', $hideType);
            }

            $hiddenPosts = $hiddenPosts->select(
                    'hidden_friend_posts.*',
                    'posts.content',
                    'posts.type',
                    'posts.privacy',
                    'posts.created_at as post_created_at',
                    'users.first_name',
                    'users.last_name',
                    'users.email'
                )
                ->orderBy('hidden_friend_posts.created_at', 'desc')
                ->paginate($perPage);

            // Map the results
            $mappedHiddenPosts = $hiddenPosts->getCollection()->map(function ($hiddenPost) {
                return [
                    'post_id' => $hiddenPost->post_id,
                    'friend_id' => $hiddenPost->friend_id,
                    'friend_name' => $hiddenPost->first_name . ' ' . $hiddenPost->last_name,
                    'friend_email' => $hiddenPost->email,
                    'post_content' => $hiddenPost->content,
                    'post_type' => $hiddenPost->type,
                    'post_privacy' => $hiddenPost->privacy,
                    'post_created_at' => $hiddenPost->post_created_at,
                    'hide_type' => $hiddenPost->hide_type,
                    'expires_at' => $hiddenPost->expires_at,
                    'is_expired' => $hiddenPost->expires_at ? Carbon::parse($hiddenPost->expires_at)->isPast() : false,
                    'hidden_at' => $hiddenPost->created_at,
                    'updated_at' => $hiddenPost->updated_at
                ];
            });

            return response()->json([
                'status_code' => 200,
                'success' => true,
                'message' => 'Hidden posts retrieved successfully',
                'data' => [
                    'hidden_posts' => $mappedHiddenPosts,
                    'hide_type_filter' => $hideType,
                    'pagination' => [
                        'current_page' => $hiddenPosts->currentPage(),
                        'last_page' => $hiddenPosts->lastPage(),
                        'per_page' => $hiddenPosts->perPage(),
                        'total' => $hiddenPosts->total(),
                        'from' => $hiddenPosts->firstItem(),
                        'to' => $hiddenPosts->lastItem(),
                        'has_more_pages' => $hiddenPosts->hasMorePages()
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
                'message' => 'Failed to retrieve hidden posts',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function hideFriendPosts(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'friend_id' => 'required|integer|exists:users,id',
                'hide_type' => 'required|in:permanent,30_days'
            ]);

            $user = Auth::guard('user')->user();
            
            // Check if user is friends with the specified friend
            if (!$user->isFriendsWith($validatedData['friend_id'])) {
                return response()->json([
                    'status_code' => 400,
                    'success' => false,
                    'message' => 'You can only hide posts from users who are your friends'
                ], 400);
            }

            // Prevent hiding own posts
            if ($validatedData['friend_id'] === $user->id) {
                return response()->json([
                    'status_code' => 400,
                    'success' => false,
                    'message' => 'You cannot hide your own posts'
                ], 400);
            }

            DB::beginTransaction();

            // Calculate expiration date for 30 days hide
            $expiresAt = null;
            if ($validatedData['hide_type'] === '30_days') {
                $expiresAt = Carbon::now()->addDays(30);
            }

            // Check if hide setting already exists
            $existingHide = DB::table('hidden_friend_posts')
                ->where('user_id', $user->id)
                ->where('friend_id', $validatedData['friend_id'])
                ->first();

            if ($existingHide) {
                // Update existing hide setting
                DB::table('hidden_friend_posts')
                    ->where('user_id', $user->id)
                    ->where('friend_id', $validatedData['friend_id'])
                    ->update([
                        'hide_type' => $validatedData['hide_type'],
                        'expires_at' => $expiresAt,
                        'updated_at' => now()
                    ]);

                $message = $validatedData['hide_type'] === 'permanent' 
                    ? 'Posts from this friend hidden permanently' 
                    : 'Posts from this friend hidden for 30 days';
            } else {
                // Create new hide setting
                DB::table('hidden_friend_posts')->insert([
                    'user_id' => $user->id,
                    'friend_id' => $validatedData['friend_id'],
                    'hide_type' => $validatedData['hide_type'],
                    'expires_at' => $expiresAt,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);

                $message = $validatedData['hide_type'] === 'permanent' 
                    ? 'Posts from this friend hidden permanently' 
                    : 'Posts from this friend hidden for 30 days';
            }

            DB::commit();

            return response()->json([
                'status_code' => 200,
                'success' => true,
                'message' => $message,
                'data' => [
                    'friend_id' => $validatedData['friend_id'],
                    'hide_type' => $validatedData['hide_type'],
                    'expires_at' => $expiresAt,
                    'hidden_at' => now()
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
                'message' => 'Failed to hide friend posts',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function unhideFriendPosts(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'friend_id' => 'required|integer|exists:users,id'
            ]);

            $user = Auth::guard('user')->user();
            
            DB::beginTransaction();

            // Delete the hide setting
            $deletedCount = DB::table('hidden_friend_posts')
                ->where('user_id', $user->id)
                ->where('friend_id', $validatedData['friend_id'])
                ->delete();

            if ($deletedCount === 0) {
                return response()->json([
                    'status_code' => 404,
                    'success' => false,
                    'message' => 'No hidden posts found for this friend'
                ], 404);
            }

            DB::commit();

            return response()->json([
                'status_code' => 200,
                'success' => true,
                'message' => 'Posts from this friend are now visible',
                'data' => [
                    'friend_id' => $validatedData['friend_id'],
                    'unhidden_at' => now()
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
                'message' => 'Failed to unhide friend posts',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getHiddenFriendPosts(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'page' => 'nullable|integer|min:1',
                'per_page' => 'nullable|integer|min:1|max:100'
            ]);

            $user = Auth::guard('user')->user();
            $perPage = $validatedData['per_page'] ?? 20;

            // Get hidden friend posts with pagination
            $hiddenPosts = DB::table('hidden_friend_posts')
                ->join('users', 'hidden_friend_posts.friend_id', '=', 'users.id')
                ->where('hidden_friend_posts.user_id', $user->id)
                ->where(function($query) {
                    $query->where('hidden_friend_posts.hide_type', 'permanent')
                          ->orWhere(function($q) {
                              $q->where('hidden_friend_posts.hide_type', '30_days')
                                ->where('hidden_friend_posts.expires_at', '>', now());
                          });
                })
                ->select(
                    'hidden_friend_posts.*',
                    'users.first_name',
                    'users.last_name',
                    'users.email'
                )
                ->orderBy('hidden_friend_posts.created_at', 'desc')
                ->paginate($perPage);

            // Map the results
            $mappedHiddenPosts = $hiddenPosts->getCollection()->map(function ($hiddenPost) {
                return [
                    'friend_id' => $hiddenPost->friend_id,
                    'friend_name' => $hiddenPost->first_name . ' ' . $hiddenPost->last_name,
                    'friend_email' => $hiddenPost->email,
                    'friend_profile_picture' => null, // Profile picture not available in users table
                    'hide_type' => $hiddenPost->hide_type,
                    'expires_at' => $hiddenPost->expires_at,
                    'is_expired' => $hiddenPost->expires_at ? Carbon::parse($hiddenPost->expires_at)->isPast() : false,
                    'hidden_at' => $hiddenPost->created_at,
                    'updated_at' => $hiddenPost->updated_at
                ];
            });

            return response()->json([
                'status_code' => 200,
                'success' => true,
                'message' => 'Hidden friend posts retrieved successfully',
                'data' => [
                    'hidden_posts' => $mappedHiddenPosts,
                    'pagination' => [
                        'current_page' => $hiddenPosts->currentPage(),
                        'last_page' => $hiddenPosts->lastPage(),
                        'per_page' => $hiddenPosts->perPage(),
                        'total' => $hiddenPosts->total(),
                        'from' => $hiddenPosts->firstItem(),
                        'to' => $hiddenPosts->lastItem(),
                        'has_more_pages' => $hiddenPosts->hasMorePages()
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
                'message' => 'Failed to retrieve hidden friend posts',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function restrictFriendNotifications(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'friend_id' => 'required|integer|exists:users,id',
                'restriction_type' => 'required|in:30_days,permanent,unrestrict',
                'mute_reactions' => 'nullable|boolean',
                'mute_comments' => 'nullable|boolean',
                'mute_shares' => 'nullable|boolean',
                'mute_all' => 'nullable|boolean'
            ]);

            $user = Auth::guard('user')->user();

            // Ensure the friend_id is not the user's own ID
            if ($validatedData['friend_id'] === $user->id) {
                return response()->json([
                    'status_code' => 400,
                    'success' => false,
                    'message' => 'You cannot restrict notifications from yourself'
                ], 400);
            }

            // Check if user is friends with the specified friend
            if (!$user->isFriendsWith($validatedData['friend_id'])) {
                return response()->json([
                    'status_code' => 400,
                    'success' => false,
                    'message' => 'You can only restrict notifications from your friends'
                ], 400);
            }

            DB::beginTransaction();

            // Handle unrestrict (remove restriction)
            if ($validatedData['restriction_type'] === 'unrestrict') {
                $deletedCount = DB::table('friend_notification_restrictions')
                    ->where('user_id', $user->id)
                    ->where('friend_id', $validatedData['friend_id'])
                    ->delete();

                if ($deletedCount === 0) {
                    return response()->json([
                        'status_code' => 404,
                        'success' => false,
                        'message' => 'No notification restriction found for this friend'
                    ], 404);
                }

                DB::commit();

                return response()->json([
                    'status_code' => 200,
                    'success' => true,
                    'message' => 'Notification restriction removed successfully',
                    'data' => [
                        'friend_id' => $validatedData['friend_id'],
                        'restriction_type' => 'unrestricted',
                        'unrestricted_at' => now()
                    ]
                ], 200);
            }

            // Calculate expiration date for 30 days restriction
            $expiresAt = null;
            if ($validatedData['restriction_type'] === '30_days') {
                $expiresAt = Carbon::now()->addDays(30);
            }

            // Prepare restriction data
            $restrictionData = [
                'user_id' => $user->id,
                'friend_id' => $validatedData['friend_id'],
                'restriction_type' => $validatedData['restriction_type'],
                'mute_reactions' => $validatedData['mute_reactions'] ?? false,
                'mute_comments' => $validatedData['mute_comments'] ?? false,
                'mute_shares' => $validatedData['mute_shares'] ?? false,
                'mute_all' => $validatedData['mute_all'] ?? false,
                'expires_at' => $expiresAt,
                'updated_at' => now()
            ];

            // Check if restriction already exists
            $existingRestriction = DB::table('friend_notification_restrictions')
                ->where('user_id', $user->id)
                ->where('friend_id', $validatedData['friend_id'])
                ->first();

            if ($existingRestriction) {
                // Update existing restriction
                DB::table('friend_notification_restrictions')
                    ->where('user_id', $user->id)
                    ->where('friend_id', $validatedData['friend_id'])
                    ->update($restrictionData);
            } else {
                // Create new restriction
                $restrictionData['created_at'] = now();
                DB::table('friend_notification_restrictions')->insert($restrictionData);
            }

            DB::commit();

            $message = $validatedData['restriction_type'] === 'permanent' 
                ? 'Notifications from this friend restricted permanently' 
                : 'Notifications from this friend restricted for 30 days';

            return response()->json([
                'status_code' => 200,
                'success' => true,
                'message' => $message,
                'data' => [
                    'friend_id' => $validatedData['friend_id'],
                    'restriction_type' => $validatedData['restriction_type'],
                    'mute_reactions' => $restrictionData['mute_reactions'],
                    'mute_comments' => $restrictionData['mute_comments'],
                    'mute_shares' => $restrictionData['mute_shares'],
                    'mute_all' => $restrictionData['mute_all'],
                    'expires_at' => $expiresAt,
                    'restricted_at' => now()
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
                'message' => 'Failed to restrict friend notifications',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getFriendNotificationRestrictions(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'page' => 'nullable|integer|min:1',
                'per_page' => 'nullable|integer|min:1|max:100',
                'restriction_type' => 'nullable|in:30_days,permanent,all'
            ]);

            $user = Auth::guard('user')->user();
            $perPage = $validatedData['per_page'] ?? 20;
            $restrictionType = $validatedData['restriction_type'] ?? 'all';

            // Get notification restrictions with pagination
            $restrictions = DB::table('friend_notification_restrictions')
                ->join('users', 'friend_notification_restrictions.friend_id', '=', 'users.id')
                ->where('friend_notification_restrictions.user_id', $user->id)
                ->where(function($query) {
                    $query->where('friend_notification_restrictions.restriction_type', 'permanent')
                          ->orWhere(function($q) {
                              $q->where('friend_notification_restrictions.restriction_type', '30_days')
                                ->where('friend_notification_restrictions.expires_at', '>', now());
                          });
                });

            // Filter by restriction type if specified
            if ($restrictionType !== 'all') {
                $restrictions->where('friend_notification_restrictions.restriction_type', $restrictionType);
            }

            $restrictions = $restrictions->select(
                    'friend_notification_restrictions.*',
                    'users.first_name',
                    'users.last_name',
                    'users.email'
                )
                ->orderBy('friend_notification_restrictions.created_at', 'desc')
                ->paginate($perPage);

            // Map the results
            $mappedRestrictions = $restrictions->getCollection()->map(function ($restriction) {
                return [
                    'friend_id' => $restriction->friend_id,
                    'friend_name' => $restriction->first_name . ' ' . $restriction->last_name,
                    'friend_email' => $restriction->email,
                    'restriction_type' => $restriction->restriction_type,
                    'mute_reactions' => (bool) $restriction->mute_reactions,
                    'mute_comments' => (bool) $restriction->mute_comments,
                    'mute_shares' => (bool) $restriction->mute_shares,
                    'mute_all' => (bool) $restriction->mute_all,
                    'expires_at' => $restriction->expires_at,
                    'is_expired' => $restriction->expires_at ? Carbon::parse($restriction->expires_at)->isPast() : false,
                    'restricted_at' => $restriction->created_at,
                    'updated_at' => $restriction->updated_at
                ];
            });

            return response()->json([
                'status_code' => 200,
                'success' => true,
                'message' => 'Friend notification restrictions retrieved successfully',
                'data' => [
                    'restrictions' => $mappedRestrictions,
                    'restriction_type_filter' => $restrictionType,
                    'pagination' => [
                        'current_page' => $restrictions->currentPage(),
                        'last_page' => $restrictions->lastPage(),
                        'per_page' => $restrictions->perPage(),
                        'total' => $restrictions->total(),
                        'from' => $restrictions->firstItem(),
                        'to' => $restrictions->lastItem(),
                        'has_more_pages' => $restrictions->hasMorePages()
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
                'message' => 'Failed to retrieve friend notification restrictions',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function suggestedPostInterest(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'post_id' => 'required|integer|exists:posts,id',
                'interest_type' => 'required|in:interested,not_interested'
            ]);

            $user = Auth::guard('user')->user();
            $post = Post::approved()->visibleTo($user)->findOrFail($validatedData['post_id']);

            // Ensure the post is not from the user themselves
            if ($post->user_id === $user->id) {
                return response()->json([
                    'status_code' => 400,
                    'success' => false,
                    'message' => 'You cannot mark your own posts as interested/not interested'
                ], 400);
            }

            // Ensure the post is from a non-friend (suggested post)
            if ($user->isFriendsWith($post->user_id)) {
                return response()->json([
                    'status_code' => 400,
                    'success' => false,
                    'message' => 'This function is for suggested posts only. Use postInterest for friend posts.'
                ], 400);
            }

            DB::beginTransaction();

            $existingInterest = DB::table('suggested_post_interest_feedback')
                ->where('user_id', $user->id)
                ->where('post_id', $post->id)
                ->first();

            if ($existingInterest) {
                DB::table('suggested_post_interest_feedback')
                    ->where('user_id', $user->id)
                    ->where('post_id', $post->id)
                    ->update([
                        'interest_type' => $validatedData['interest_type'],
                        'updated_at' => now()
                    ]);
            } else {
                DB::table('suggested_post_interest_feedback')->insert([
                    'user_id' => $user->id,
                    'post_id' => $post->id,
                    'post_owner_id' => $post->user_id,
                    'interest_type' => $validatedData['interest_type'],
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }

            DB::commit();

            $message = $validatedData['interest_type'] === 'interested' 
                ? 'Suggested post marked as interested successfully' 
                : 'Suggested post marked as not interested successfully';

            return response()->json([
                'status_code' => 200,
                'success' => true,
                'message' => $message,
                'data' => [
                    'post_id' => $post->id,
                    'post_owner_id' => $post->user_id,
                    'interest_type' => $validatedData['interest_type'],
                    'feedback_at' => now()
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
                'message' => 'Failed to mark suggested post interest',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function hideSuggestedPost(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'post_id' => 'required|integer|exists:posts,id',
                'hide_type' => 'required|in:permanent,30_days,unhide'
            ]);

            $user = Auth::guard('user')->user();
            $post = Post::approved()->visibleTo($user)->findOrFail($validatedData['post_id']);

            // Ensure the post is not from the user themselves
            if ($post->user_id === $user->id) {
                return response()->json([
                    'status_code' => 400,
                    'success' => false,
                    'message' => 'You cannot hide your own posts'
                ], 400);
            }

            // Ensure the post is from a non-friend (suggested post)
            if ($user->isFriendsWith($post->user_id)) {
                return response()->json([
                    'status_code' => 400,
                    'success' => false,
                    'message' => 'This function is for suggested posts only. Use hideSpecificFriendPost for friend posts.'
                ], 400);
            }

            DB::beginTransaction();

            // Handle unhide (remove hide setting)
            if ($validatedData['hide_type'] === 'unhide') {
                $deletedCount = DB::table('hidden_suggested_posts')
                    ->where('user_id', $user->id)
                    ->where('post_id', $post->id)
                    ->delete();

                if ($deletedCount === 0) {
                    return response()->json([
                        'status_code' => 404,
                        'success' => false,
                        'message' => 'No hide setting found for this suggested post'
                    ], 404);
                }

                DB::commit();

                return response()->json([
                    'status_code' => 200,
                    'success' => true,
                    'message' => 'Suggested post unhidden successfully',
                    'data' => [
                        'post_id' => $post->id,
                        'post_owner_id' => $post->user_id,
                        'hide_type' => 'unhidden',
                        'unhidden_at' => now()
                    ]
                ], 200);
            }

            $expiresAt = null;
            if ($validatedData['hide_type'] === '30_days') {
                $expiresAt = Carbon::now()->addDays(30);
            }

            // Check if hide setting already exists for this specific post
            $existingHide = DB::table('hidden_suggested_posts')
                ->where('user_id', $user->id)
                ->where('post_id', $post->id)
                ->first();

            if ($existingHide) {
                return response()->json([
                    'status_code' => 400,
                    'success' => false,
                    'message' => 'This suggested post is already hidden'
                ], 400);
            }

            // Create new hide setting for specific suggested post
            DB::table('hidden_suggested_posts')->insert([
                'user_id' => $user->id,
                'post_id' => $post->id,
                'post_owner_id' => $post->user_id,
                'hide_type' => $validatedData['hide_type'],
                'expires_at' => $expiresAt,
                'created_at' => now(),
                'updated_at' => now()
            ]);

            DB::commit();

            $message = $validatedData['hide_type'] === 'permanent' 
                ? 'Suggested post hidden permanently' 
                : 'Suggested post hidden for 30 days';

            return response()->json([
                'status_code' => 200,
                'success' => true,
                'message' => $message,
                'data' => [
                    'post_id' => $post->id,
                    'post_owner_id' => $post->user_id,
                    'hide_type' => $validatedData['hide_type'],
                    'expires_at' => $expiresAt,
                    'hidden_at' => now()
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
                'message' => 'Failed to hide suggested post',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function hideSuggestedUserPosts(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'user_id' => 'required|integer|exists:users,id',
                'hide_type' => 'required|in:permanent,30_days'
            ]);

            $user = Auth::guard('user')->user();

            // Ensure the user_id is not the user's own ID
            if ($validatedData['user_id'] === $user->id) {
                return response()->json([
                    'status_code' => 400,
                    'success' => false,
                    'message' => 'You cannot hide your own posts'
                ], 400);
            }

            // Ensure the target user is not a friend (suggested user)
            if ($user->isFriendsWith($validatedData['user_id'])) {
                return response()->json([
                    'status_code' => 400,
                    'success' => false,
                    'message' => 'This function is for suggested users only. Use hideFriendPosts for friends.'
                ], 400);
            }

            DB::beginTransaction();

            $expiresAt = null;
            if ($validatedData['hide_type'] === '30_days') {
                $expiresAt = Carbon::now()->addDays(30);
            }

            // Check if hide setting already exists for this user
            $existingHide = DB::table('hidden_suggested_users')
                ->where('user_id', $user->id)
                ->where('suggested_user_id', $validatedData['user_id'])
                ->first();

            if ($existingHide) {
                // Update existing hide setting
                DB::table('hidden_suggested_users')
                    ->where('user_id', $user->id)
                    ->where('suggested_user_id', $validatedData['user_id'])
                    ->update([
                        'hide_type' => $validatedData['hide_type'],
                        'expires_at' => $expiresAt,
                        'updated_at' => now()
                    ]);
            } else {
                // Create new hide setting
                DB::table('hidden_suggested_users')->insert([
                    'user_id' => $user->id,
                    'suggested_user_id' => $validatedData['user_id'],
                    'hide_type' => $validatedData['hide_type'],
                    'expires_at' => $expiresAt,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }

            DB::commit();

            $message = $validatedData['hide_type'] === 'permanent' 
                ? 'All posts from this suggested user hidden permanently' 
                : 'All posts from this suggested user hidden for 30 days';

            return response()->json([
                'status_code' => 200,
                'success' => true,
                'message' => $message,
                'data' => [
                    'suggested_user_id' => $validatedData['user_id'],
                    'hide_type' => $validatedData['hide_type'],
                    'expires_at' => $expiresAt,
                    'hidden_at' => now()
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
                'message' => 'Failed to hide suggested user posts',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function unhideSuggestedUserPosts(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'user_id' => 'required|integer|exists:users,id'
            ]);

            $user = Auth::guard('user')->user();

            DB::beginTransaction();

            $deletedCount = DB::table('hidden_suggested_users')
                ->where('user_id', $user->id)
                ->where('suggested_user_id', $validatedData['user_id'])
                ->delete();

            if ($deletedCount === 0) {
                return response()->json([
                    'status_code' => 404,
                    'success' => false,
                    'message' => 'No hide setting found for this suggested user'
                ], 404);
            }

            DB::commit();

            return response()->json([
                'status_code' => 200,
                'success' => true,
                'message' => 'Posts from this suggested user are now visible',
                'data' => [
                    'suggested_user_id' => $validatedData['user_id'],
                    'unhidden_at' => now()
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
                'message' => 'Failed to unhide suggested user posts',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getHiddenSuggestedPosts(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'page' => 'nullable|integer|min:1',
                'per_page' => 'nullable|integer|min:1|max:100',
                'hide_type' => 'nullable|in:permanent,30_days,all'
            ]);

            $user = Auth::guard('user')->user();
            $perPage = $validatedData['per_page'] ?? 20;
            $hideType = $validatedData['hide_type'] ?? 'all';

            // Get hidden suggested posts with pagination
            $hiddenPosts = DB::table('hidden_suggested_posts')
                ->join('posts', 'hidden_suggested_posts.post_id', '=', 'posts.id')
                ->join('users', 'posts.user_id', '=', 'users.id')
                ->where('hidden_suggested_posts.user_id', $user->id)
                ->where(function($query) {
                    $query->where('hidden_suggested_posts.hide_type', 'permanent')
                          ->orWhere(function($q) {
                              $q->where('hidden_suggested_posts.hide_type', '30_days')
                                ->where('hidden_suggested_posts.expires_at', '>', now());
                          });
                });

            // Filter by hide type if specified
            if ($hideType !== 'all') {
                $hiddenPosts->where('hidden_suggested_posts.hide_type', $hideType);
            }

            $hiddenPosts = $hiddenPosts->select(
                    'hidden_suggested_posts.*',
                    'posts.content',
                    'posts.type',
                    'posts.privacy',
                    'posts.created_at as post_created_at',
                    'users.first_name',
                    'users.last_name',
                    'users.email'
                )
                ->orderBy('hidden_suggested_posts.created_at', 'desc')
                ->paginate($perPage);

            // Map the results
            $mappedHiddenPosts = $hiddenPosts->getCollection()->map(function ($hiddenPost) {
                return [
                    'post_id' => $hiddenPost->post_id,
                    'post_owner_id' => $hiddenPost->post_owner_id,
                    'post_owner_name' => $hiddenPost->first_name . ' ' . $hiddenPost->last_name,
                    'post_owner_email' => $hiddenPost->email,
                    'post_content' => $hiddenPost->content,
                    'post_type' => $hiddenPost->type,
                    'post_privacy' => $hiddenPost->privacy,
                    'post_created_at' => $hiddenPost->post_created_at,
                    'hide_type' => $hiddenPost->hide_type,
                    'expires_at' => $hiddenPost->expires_at,
                    'is_expired' => $hiddenPost->expires_at ? Carbon::parse($hiddenPost->expires_at)->isPast() : false,
                    'hidden_at' => $hiddenPost->created_at,
                    'updated_at' => $hiddenPost->updated_at
                ];
            });

            return response()->json([
                'status_code' => 200,
                'success' => true,
                'message' => 'Hidden suggested posts retrieved successfully',
                'data' => [
                    'hidden_posts' => $mappedHiddenPosts,
                    'hide_type_filter' => $hideType,
                    'pagination' => [
                        'current_page' => $hiddenPosts->currentPage(),
                        'last_page' => $hiddenPosts->lastPage(),
                        'per_page' => $hiddenPosts->perPage(),
                        'total' => $hiddenPosts->total(),
                        'from' => $hiddenPosts->firstItem(),
                        'to' => $hiddenPosts->lastItem(),
                        'has_more_pages' => $hiddenPosts->hasMorePages()
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
                'message' => 'Failed to retrieve hidden suggested posts',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    public function createComment(Request $request)
    {
        try {
            $user = Auth::guard('user')->user();
            
            $validatedData = $request->validate([
                'post_id' => 'required|integer|exists:posts,id',
                'comment_text' => 'nullable|string|max:1000',
                'mentions' => 'nullable|array',
                'mentions.*' => 'integer|exists:users,id',
                'media' => 'nullable|file|mimes:jpeg,png,gif,mp4,avi|max:51200'
            ]);

            // Ensure at least one of comment_text or media is provided
            if (empty($validatedData['comment_text']) && !$request->hasFile('media')) {
                return response()->json([
                    'status_code' => 422,
                    'success' => false,
                    'message' => 'Either comment text or media must be provided'
                ], 422);
            }

            DB::beginTransaction();

            // Check if post exists and comments are enabled
            $post = DB::table('posts')
                ->where('id', $validatedData['post_id'])
                ->where('status', 'approved')
                ->first();

            if (!$post) {
                return response()->json([
                    'status_code' => 404,
                    'success' => false,
                    'message' => 'Post not found'
                ]);
            }

            if (!$post->comments_enabled) {
                return response()->json([
                    'status_code' => 403,
                    'success' => false,
                    'message' => 'Comments are disabled for this post'
                ]);
            }

            // Check if user can see the post (privacy checks)
            $postOwner = DB::table('users')->where('id', $post->user_id)->first();
            if (!$postOwner) {
                return response()->json([
                    'status_code' => 404,
                    'success' => false,
                    'message' => 'Post owner not found'
                ]);
            }

            // Privacy check - only friends can comment if post is friends-only
            if ($post->privacy === 'friends' && $post->user_id !== $user->id) {
                $friendship = DB::table('friendships')
                    ->where(function($query) use ($user, $post) {
                        $query->where('user_id', $user->id)
                              ->where('friend_id', $post->user_id);
                    })
                    ->orWhere(function($query) use ($user, $post) {
                        $query->where('user_id', $post->user_id)
                              ->where('friend_id', $user->id);
                    })
                    ->where('status', 'accepted')
                    ->first();

                if (!$friendship) {
                    return response()->json([
                        'status_code' => 403,
                        'success' => false,
                        'message' => 'You can only comment on posts from your friends'
                    ]);
                }
            }

            // Validate mentions if provided
            if (isset($validatedData['mentions']) && !empty($validatedData['mentions'])) {
                foreach ($validatedData['mentions'] as $mentionedUserId) {
                    // Check if mentioned user exists and is a friend
                    $mentionedUser = DB::table('users')->where('id', $mentionedUserId)->first();
                    if (!$mentionedUser) {
                        return response()->json([
                            'status_code' => 422,
                            'success' => false,
                            'message' => 'One or more mentioned users do not exist'
                        ]);
                    }

                    // Check friendship for mentions
                    if ($mentionedUserId !== $user->id) {
                        $mentionFriendship = DB::table('friendships')
                            ->where(function($query) use ($user, $mentionedUserId) {
                                $query->where('user_id', $user->id)
                                      ->where('friend_id', $mentionedUserId);
                            })
                            ->orWhere(function($query) use ($user, $mentionedUserId) {
                                $query->where('user_id', $mentionedUserId)
                                      ->where('friend_id', $user->id);
                            })
                            ->where('status', 'accepted')
                            ->first();

                        if (!$mentionFriendship) {
                            return response()->json([
                                'status_code' => 422,
                                'success' => false,
                                'message' => 'You can only mention your friends'
                            ]);
                        }
                    }
                }
            }

            // Handle media upload if provided
            $mediaData = null;
            if ($request->hasFile('media')) {
                $file = $request->file('media');
                $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                $path = $file->storeAs('comments/' . $validatedData['post_id'], $filename, 'public');
                
                $mediaData = [
                    'type' => strpos($file->getMimeType(), 'image') !== false ? 'image' : 'video',
                    'filename' => $filename,
                    'original_name' => $file->getClientOriginalName(),
                    'mime_type' => $file->getMimeType(),
                    'size' => $file->getSize(),
                    'path' => $path,
                    'url' => config('app.url') . '/public/storage/' . $path,
                    'metadata' => $this->extractMediaMetadata($file)
                ];
            }

            // Create the comment
            $commentId = DB::table('post_comments')->insertGetId([
                'post_id' => $validatedData['post_id'],
                'user_id' => $user->id,
                'comment_text' => $validatedData['comment_text'] ?? null,
                'mentions' => isset($validatedData['mentions']) ? json_encode($validatedData['mentions']) : null,
                'media' => $mediaData ? json_encode($mediaData) : null,
                'created_at' => now(),
                'updated_at' => now()
            ]);

            // Get the created comment with user details
            $comment = DB::table('post_comments')
                ->join('users', 'post_comments.user_id', '=', 'users.id')
                ->where('post_comments.id', $commentId)
                ->select(
                    'post_comments.*',
                    'users.first_name',
                    'users.last_name'
                )
                ->first();

            DB::commit();

            return response()->json([
                'status_code' => 201,
                'success' => true,
                'message' => 'Comment created successfully',
                'data' => [
                    'comment' => [
                        'id' => $comment->id,
                        'post_id' => $comment->post_id,
                        'parent_comment_id' => $comment->parent_comment_id,
                        'comment_text' => $comment->comment_text,
                        'mentions' => $comment->mentions ? json_decode($comment->mentions, true) : [],
                        'media' => $comment->media ? json_decode($comment->media, true) : null,
                        'created_at' => $comment->created_at,
                        'user' => [
                            'id' => $comment->user_id,
                            'first_name' => $comment->first_name,
                            'last_name' => $comment->last_name
                        ],
                        'replies_count' => 0
                    ]
                ]
            ]);

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
                'message' => 'Failed to create comment',
                'error' => $e->getMessage()
            ]);
        }
    }
   
    public function replyToComment(Request $request)
    {
        try {
            $user = Auth::guard('user')->user();
            
            $validatedData = $request->validate([
                'comment_id' => 'required|integer|exists:post_comments,id',
                'comment_text' => 'nullable|string|max:1000',
                'mentions' => 'nullable|array',
                'mentions.*' => 'integer|exists:users,id',
                'media' => 'nullable|file|mimes:jpeg,png,gif,mp4,avi|max:51200'
            ]);

            // Ensure at least one of comment_text or media is provided
            if (empty($validatedData['comment_text']) && !$request->hasFile('media')) {
                return response()->json([
                    'status_code' => 422,
                    'success' => false,
                    'message' => 'Either comment text or media must be provided'
                ], 422);
            }

            DB::beginTransaction();

            // Get the parent comment and post details
            $parentComment = DB::table('post_comments')
                ->join('posts', 'post_comments.post_id', '=', 'posts.id')
                ->where('post_comments.id', $validatedData['comment_id'])
                ->where('post_comments.is_deleted', false)
                ->where('posts.status', 'approved')
                ->select('post_comments.*', 'posts.comments_enabled', 'posts.privacy', 'posts.user_id as post_owner_id')
                ->first();

            if (!$parentComment) {
                return response()->json([
                    'status_code' => 404,
                    'success' => false,
                    'message' => 'Comment not found'
                ]);
            }

            // Check maximum nesting depth (limit to 5 levels to prevent infinite nesting)
            $nestingLevel = $this->getCommentNestingLevel($validatedData['comment_id']);
            if ($nestingLevel >= 5) {
                return response()->json([
                    'status_code' => 422,
                    'success' => false,
                    'message' => 'Maximum nesting depth reached. Cannot reply to this comment.'
                ]);
            }

            if (!$parentComment->comments_enabled) {
                return response()->json([
                    'status_code' => 403,
                    'success' => false,
                    'message' => 'Comments are disabled for this post'
                ]);
            }

            // Privacy check - only friends can reply if post is friends-only
            if ($parentComment->privacy === 'friends' && $parentComment->post_owner_id !== $user->id) {
                $friendship = DB::table('friendships')
                    ->where(function($query) use ($user, $parentComment) {
                        $query->where('user_id', $user->id)
                              ->where('friend_id', $parentComment->post_owner_id);
                    })
                    ->orWhere(function($query) use ($user, $parentComment) {
                        $query->where('user_id', $parentComment->post_owner_id)
                              ->where('friend_id', $user->id);
                    })
                    ->where('status', 'accepted')
                    ->first();

                if (!$friendship) {
                    return response()->json([
                        'status_code' => 403,
                        'success' => false,
                        'message' => 'You can only reply to comments on posts from your friends'
                    ]);
                }
            }

            // Validate mentions if provided
            if (isset($validatedData['mentions']) && !empty($validatedData['mentions'])) {
                foreach ($validatedData['mentions'] as $mentionedUserId) {
                    $mentionedUser = DB::table('users')->where('id', $mentionedUserId)->first();
                    if (!$mentionedUser) {
                        return response()->json([
                            'status_code' => 422,
                            'success' => false,
                            'message' => 'One or more mentioned users do not exist'
                        ]);
                    }

                    if ($mentionedUserId !== $user->id) {
                        $mentionFriendship = DB::table('friendships')
                            ->where(function($query) use ($user, $mentionedUserId) {
                                $query->where('user_id', $user->id)
                                      ->where('friend_id', $mentionedUserId);
                            })
                            ->orWhere(function($query) use ($user, $mentionedUserId) {
                                $query->where('user_id', $mentionedUserId)
                                      ->where('friend_id', $user->id);
                            })
                            ->where('status', 'accepted')
                            ->first();

                        if (!$mentionFriendship) {
                            return response()->json([
                                'status_code' => 422,
                                'success' => false,
                                'message' => 'You can only mention your friends'
                            ]);
                        }
                    }
                }
            }

            // Handle media upload if provided
            $mediaData = null;
            if ($request->hasFile('media')) {
                $file = $request->file('media');
                $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                $path = $file->storeAs('comments/' . $parentComment->post_id, $filename, 'public');
                
                $mediaData = [
                    'type' => strpos($file->getMimeType(), 'image') !== false ? 'image' : 'video',
                    'filename' => $filename,
                    'original_name' => $file->getClientOriginalName(),
                    'mime_type' => $file->getMimeType(),
                    'size' => $file->getSize(),
                    'path' => $path,
                    'url' => config('app.url') . '/public/storage/' . $path,
                    'metadata' => $this->extractMediaMetadata($file)
                ];
            }

            // Create the reply
            $replyId = DB::table('post_comments')->insertGetId([
                'post_id' => $parentComment->post_id,
                'user_id' => $user->id,
                'parent_comment_id' => $validatedData['comment_id'],
                'comment_text' => $validatedData['comment_text'] ?? null,
                'mentions' => isset($validatedData['mentions']) ? json_encode($validatedData['mentions']) : null,
                'media' => $mediaData ? json_encode($mediaData) : null,
                'created_at' => now(),
                'updated_at' => now()
            ]);

            // Get the created reply
            $reply = DB::table('post_comments')
                ->where('post_comments.id', $replyId)
                ->first();

            // Get complete user data
            $user = User::find($reply->user_id);

            DB::commit();

            // Calculate nesting level for the reply
            $nestingLevel = $this->getCommentNestingLevel($reply->id);

            return response()->json([
                'status_code' => 201,
                'success' => true,
                'message' => 'Reply created successfully',
                'data' => [
                    'reply' => [
                        'id' => $reply->id,
                        'post_id' => $reply->post_id,
                        'parent_comment_id' => $reply->parent_comment_id,
                        'comment_text' => $reply->comment_text,
                        'mentions' => $reply->mentions ? json_decode($reply->mentions, true) : [],
                        'media' => $reply->media ? json_decode($reply->media, true) : null,
                        'created_at' => $reply->created_at,
                        'nesting_level' => $nestingLevel,
                        'user' => $user ? app(AuthController::class)->mapUserDetails($user) : null
                    ]
                ]
            ]);

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
                'message' => 'Failed to create reply',
                'error' => $e->getMessage()
            ]);
        }
    }

    public function getPostComments(Request $request)
    {
        try {
            $user = Auth::guard('user')->user();
            
            $validatedData = $request->validate([
                'post_id' => 'required|integer|exists:posts,id',
                'page' => 'nullable|integer|min:1',
                'per_page' => 'nullable|integer|min:1|max:50',
                'max_depth' => 'nullable|integer|min:1|max:10',
                'load_replies' => 'nullable|boolean'
            ]);

            $page = $validatedData['page'] ?? 1;
            $perPage = $validatedData['per_page'] ?? 20;
            $maxDepth = $validatedData['max_depth'] ?? 3; // Default to 3 levels deep
            $loadReplies = $validatedData['load_replies'] ?? true;
            $offset = ($page - 1) * $perPage;

            // Check if post exists and user can see it
            $post = DB::table('posts')
                ->where('id', $validatedData['post_id'])
                ->where('status', 'approved')
                ->first();

            if (!$post) {
                return response()->json([
                    'status_code' => 404,
                    'success' => false,
                    'message' => 'Post not found'
                ]);
            }

            // Privacy check
            if ($post->privacy === 'friends' && $post->user_id !== $user->id) {
                $friendship = DB::table('friendships')
                    ->where(function($query) use ($user, $post) {
                        $query->where('user_id', $user->id)
                              ->where('friend_id', $post->user_id);
                    })
                    ->orWhere(function($query) use ($user, $post) {
                        $query->where('user_id', $post->user_id)
                              ->where('friend_id', $user->id);
                    })
                    ->where('status', 'accepted')
                    ->first();

                if (!$friendship) {
                    return response()->json([
                        'status_code' => 403,
                        'success' => false,
                        'message' => 'You can only view comments on posts from your friends'
                    ]);
                }
            }

            // Get all comments for this post (main comments and replies) excluding hidden ones
            $allComments = DB::table('post_comments')
                ->where('post_comments.post_id', $validatedData['post_id'])
                ->where('post_comments.is_deleted', false)
                ->whereNotExists(function($query) use ($user) {
                    $query->select(DB::raw(1))
                          ->from('hidden_comments')
                          ->whereColumn('hidden_comments.comment_id', 'post_comments.id')
                          ->where('hidden_comments.user_id', $user->id)
                          ->where(function($subQuery) {
                              $subQuery->where('hidden_comments.expires_at', '>', now())
                                      ->orWhere('hidden_comments.hide_type', 'permanent');
                          });
                })
                ->orderBy('post_comments.created_at', 'asc')
                ->get();

            // Get all unique user IDs from comments
            $userIds = $allComments->pluck('user_id')->unique();
            
            // Get complete user data
            $users = User::whereIn('id', $userIds)->get()->keyBy('id');

            // Get all comment IDs for reactions
            $commentIds = $allComments->pluck('id');

            // Get comment reactions data with user details
            $commentReactions = CommentReaction::whereIn('comment_id', $commentIds)
                ->with(['reaction', 'user'])
                ->get()
                ->groupBy('comment_id');

            // Get user's reactions for these comments
            $userCommentReactions = CommentReaction::where('user_id', $user->id)
                ->whereIn('comment_id', $commentIds)
                ->with('reaction')
                ->get()
                ->keyBy('comment_id');

            // Get main comments (top-level comments) for pagination
            $mainComments = $allComments->where('parent_comment_id', null);
            $totalMainComments = $mainComments->count();
            
            // Paginate main comments
            $paginatedMainComments = $mainComments->slice($offset, $perPage);
            $mainCommentIds = $paginatedMainComments->pluck('id');

            // Build comment tree for paginated main comments
            $commentsWithReplies = [];
            foreach ($paginatedMainComments as $mainComment) {
                $user = $users->get($mainComment->user_id);
                
                // Get comment reactions data for this comment
                $commentReactionData = $commentReactions->get($mainComment->id, collect());
                $totalCommentReactions = $commentReactionData->count();
                
                // Get user's reaction for this comment
                $userCommentReactionData = $userCommentReactions->get($mainComment->id);

                // Group reactions by type and count them
                $reactionCounts = $commentReactionData->groupBy('reaction_id')
                    ->map(function ($reactions) {
                        return [
                            'reaction' => $reactions->first()->reaction,
                            'count' => $reactions->count(),
                            'users' => $reactions->pluck('user')
                        ];
                    });

                $commentData = [
                    'id' => $mainComment->id,
                    'parent_comment_id' => $mainComment->parent_comment_id,
                    'comment_text' => $mainComment->comment_text,
                    'mentions' => $mainComment->mentions ? json_decode($mainComment->mentions, true) : [],
                    'media' => $mainComment->media ? json_decode($mainComment->media, true) : null,
                    'created_at' => $mainComment->created_at,
                    'user' => $user ? app(AuthController::class)->mapUserDetails($user) : null,
                    'nesting_level' => 0,
                    'replies_count' => 0,
                    'replies' => [],
                    'comment_reactions' => [
                        'user_reaction' => $userCommentReactionData ? [
                            'id' => $userCommentReactionData->reaction->id,
                            'name' => $userCommentReactionData->reaction->name,
                            'content' => $userCommentReactionData->reaction->content,
                            'image' => $this->formatReactionUrl($userCommentReactionData->reaction->image_url),
                            'video' => $this->formatReactionUrl($userCommentReactionData->reaction->video_url)
                        ] : null,
                        'reactions' => $reactionCounts->map(function ($item) {
                            return [
                                'id' => $item['reaction']->id,
                                'name' => $item['reaction']->name,
                                'content' => $item['reaction']->content,
                                'image' => $this->formatReactionUrl($item['reaction']->image_url),
                                'video' => $this->formatReactionUrl($item['reaction']->video_url),
                                'count' => $item['count'],
                                'users' => $this->mapUsersDetails($item['users'])
                            ];
                        })->values(),
                        'total_reactions' => $totalCommentReactions
                    ]
                ];

                if ($loadReplies) {
                    // Get all replies for this main comment (including nested replies)
                    $repliesForThisComment = $allComments->where('parent_comment_id', $mainComment->id);
                    $commentData['replies_count'] = $repliesForThisComment->count();
                    
                    // Build reply tree recursively (limited depth for performance)
                    $commentData['replies'] = $this->buildCommentTree($allComments, $users, $mainComment->id, $maxDepth, 1, $commentReactions, $userCommentReactions);
                } else {
                    // Just get the count without loading replies
                    $commentData['replies_count'] = $allComments->where('parent_comment_id', $mainComment->id)->count();
                }

                $commentsWithReplies[] = $commentData;
            }

            $totalPages = ceil($totalMainComments / $perPage);

            // Get reactions data from HomeController
            $homeController = app(HomeController::class);
            $reactionsRequest = new Request();
            $reactionsResponse = $homeController->getReactions($reactionsRequest);
            $reactionsData = json_decode($reactionsResponse->getContent(), true);

            return response()->json([
                'status_code' => 200,
                'success' => true,
                'message' => 'Comments retrieved successfully',
                'data' => [
                    'comments' => $commentsWithReplies,
                    'reactions' => $reactionsData['success'] ? $reactionsData['data'] : [],
                    'pagination' => [
                        'current_page' => $page,
                        'per_page' => $perPage,
                        'total' => $totalMainComments,
                        'total_pages' => $totalPages,
                        'from' => $totalMainComments ? $offset + 1 : null,
                        'to' => $totalMainComments ? min($offset + $perPage, $totalMainComments) : null,
                        'has_more_pages' => $page < $totalPages
                    ],
                    'settings' => [
                        'max_depth' => $maxDepth,
                        'load_replies' => $loadReplies
                    ]
                ]
            ]);

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
                'message' => 'Failed to retrieve comments',
                'error' => $e->getMessage()
            ]);
        }
    }

    public function hideComment(Request $request)
    {
        try {
            $user = Auth::guard('user')->user();
            
            $validatedData = $request->validate([
                'comment_id' => 'required|integer|exists:post_comments,id',
                'hide_type' => 'required|in:permanent,30_days,unhide'
            ]);

            DB::beginTransaction();

            // Get the comment
            $comment = DB::table('post_comments')
                ->where('id', $validatedData['comment_id'])
                ->where('is_deleted', false)
                ->first();

            if (!$comment) {
                return response()->json([
                    'status_code' => 404,
                    'success' => false,
                    'message' => 'Comment not found'
                ]);
            }

            // Check if user can hide this comment (not their own comment)
            if ($comment->user_id === $user->id) {
                return response()->json([
                    'status_code' => 403,
                    'success' => false,
                    'message' => 'You cannot hide your own comment'
                ]);
            }

            // Check if hide setting already exists
            $existingHide = DB::table('hidden_comments')
                ->where('user_id', $user->id)
                ->where('comment_id', $validatedData['comment_id'])
                ->first();

            if ($validatedData['hide_type'] === 'unhide') {
                // Remove hide setting
                if ($existingHide) {
                    DB::table('hidden_comments')
                        ->where('user_id', $user->id)
                        ->where('comment_id', $validatedData['comment_id'])
                        ->delete();
                }

                DB::commit();

                return response()->json([
                    'status_code' => 200,
                    'success' => true,
                    'message' => 'Comment unhidden successfully'
                ]);
            }

            // Calculate expiration date
            $expiresAt = null;
            if ($validatedData['hide_type'] === '30_days') {
                $expiresAt = now()->addDays(30);
            }

            // Create or update hide setting
            if ($existingHide) {
                DB::table('hidden_comments')
                    ->where('user_id', $user->id)
                    ->where('comment_id', $validatedData['comment_id'])
                    ->update([
                        'hide_type' => $validatedData['hide_type'],
                        'expires_at' => $expiresAt,
                        'updated_at' => now()
                    ]);
            } else {
                DB::table('hidden_comments')->insert([
                    'user_id' => $user->id,
                    'comment_id' => $validatedData['comment_id'],
                    'comment_owner_id' => $comment->user_id,
                    'post_id' => $comment->post_id,
                    'hide_type' => $validatedData['hide_type'],
                    'expires_at' => $expiresAt,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }

            DB::commit();

            $hideMessage = $validatedData['hide_type'] === 'permanent' 
                ? 'Comment hidden permanently' 
                : 'Comment hidden for 30 days';

            return response()->json([
                'status_code' => 200,
                'success' => true,
                'message' => $hideMessage
            ]);

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
                'message' => 'Failed to hide comment',
                'error' => $e->getMessage()
            ]);
        }
    }

    public function getHiddenComments(Request $request)
    {
        try {
            $user = Auth::guard('user')->user();
            
            $validatedData = $request->validate([
                'page' => 'nullable|integer|min:1',
                'per_page' => 'nullable|integer|min:1|max:50'
            ]);

            $page = $validatedData['page'] ?? 1;
            $perPage = $validatedData['per_page'] ?? 20;
            $offset = ($page - 1) * $perPage;

            // Get hidden comments with comment details
            $hiddenComments = DB::table('hidden_comments')
                ->join('post_comments', 'hidden_comments.comment_id', '=', 'post_comments.id')
                ->join('users', 'post_comments.user_id', '=', 'users.id')
                ->join('posts', 'post_comments.post_id', '=', 'posts.id')
                ->where('hidden_comments.user_id', $user->id)
                ->where(function($query) {
                    $query->where('hidden_comments.expires_at', '>', now())
                          ->orWhere('hidden_comments.hide_type', 'permanent');
                })
                ->select(
                    'hidden_comments.*',
                    'post_comments.comment_text',
                    'post_comments.mentions',
                    'post_comments.media',
                    'post_comments.created_at as comment_created_at',
                    'users.first_name',
                    'users.last_name',
                    'posts.content as post_content'
                )
                ->orderBy('hidden_comments.created_at', 'desc')
                ->offset($offset)
                ->limit($perPage)
                ->get();

            $formattedComments = [];
            foreach ($hiddenComments as $hiddenComment) {
                $formattedComments[] = [
                    'id' => $hiddenComment->comment_id,
                    'comment_text' => $hiddenComment->comment_text,
                    'mentions' => $hiddenComment->mentions ? json_decode($hiddenComment->mentions, true) : [],
                    'media' => $hiddenComment->media ? json_decode($hiddenComment->media, true) : null,
                    'created_at' => $hiddenComment->comment_created_at,
                    'user' => [
                        'id' => $hiddenComment->comment_owner_id,
                        'first_name' => $hiddenComment->first_name,
                        'last_name' => $hiddenComment->last_name
                    ],
                    'post' => [
                        'id' => $hiddenComment->post_id,
                        'content' => $hiddenComment->post_content
                    ],
                    'hide_type' => $hiddenComment->hide_type,
                    'expires_at' => $hiddenComment->expires_at,
                    'hidden_at' => $hiddenComment->created_at
                ];
            }

            // Get total count for pagination
            $totalHidden = DB::table('hidden_comments')
                ->where('user_id', $user->id)
                ->where(function($query) {
                    $query->where('expires_at', '>', now())
                          ->orWhere('hide_type', 'permanent');
                })
                ->count();

            $totalPages = ceil($totalHidden / $perPage);

            return response()->json([
                'status_code' => 200,
                'success' => true,
                'message' => 'Hidden comments retrieved successfully',
                'data' => [
                    'hidden_comments' => $formattedComments,
                    'pagination' => [
                        'current_page' => $page,
                        'per_page' => $perPage,
                        'total' => $totalHidden,
                        'total_pages' => $totalPages,
                        'from' => $offset + 1,
                        'to' => min($offset + $perPage, $totalHidden)
                    ]
                ]
            ]);

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
                'message' => 'Failed to retrieve hidden comments',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function editComment(Request $request)
    {
        try {
            $user = Auth::guard('user')->user();
            
            $validatedData = $request->validate([
                'comment_id' => 'required|integer|exists:post_comments,id',
                'comment_text' => 'nullable|string|max:1000',
                'mentions' => 'nullable|array',
                'mentions.*' => 'integer|exists:users,id',
                'media' => 'nullable|file|mimes:jpeg,png,gif,mp4,avi|max:51200'
            ]);

            // Ensure at least one of comment_text or media is provided
            if (empty($validatedData['comment_text']) && !$request->hasFile('media')) {
                return response()->json([
                    'status_code' => 422,
                    'success' => false,
                    'message' => 'Either comment text or media must be provided'
                ], 422);
            }

            DB::beginTransaction();

            // Get the comment
            $comment = DB::table('post_comments')
                ->where('id', $validatedData['comment_id'])
                ->where('is_deleted', false)
                ->first();

            if (!$comment) {
                return response()->json([
                    'status_code' => 404,
                    'success' => false,
                    'message' => 'Comment not found'
                ]);
            }

            // Check if user owns the comment
            if ($comment->user_id !== $user->id) {
                return response()->json([
                    'status_code' => 403,
                    'success' => false,
                    'message' => 'You can only edit your own comments'
                ]);
            }

            // Check if post still exists and comments are enabled
            $post = DB::table('posts')
                ->where('id', $comment->post_id)
                ->where('status', 'approved')
                ->first();

            if (!$post) {
                return response()->json([
                    'status_code' => 404,
                    'success' => false,
                    'message' => 'Post not found'
                ]);
            }

            if (!$post->comments_enabled) {
                return response()->json([
                    'status_code' => 403,
                    'success' => false,
                    'message' => 'Comments are disabled for this post'
                ]);
            }

            // Validate mentions if provided
            if (isset($validatedData['mentions']) && !empty($validatedData['mentions'])) {
                foreach ($validatedData['mentions'] as $mentionedUserId) {
                    // Check if mentioned user exists and is a friend
                    $mentionedUser = DB::table('users')->where('id', $mentionedUserId)->first();
                    if (!$mentionedUser) {
                        return response()->json([
                            'status_code' => 422,
                            'success' => false,
                            'message' => 'One or more mentioned users do not exist'
                        ]);
                    }

                    // Check friendship for mentions
                    if ($mentionedUserId !== $user->id) {
                        $mentionFriendship = DB::table('friendships')
                            ->where(function($query) use ($user, $mentionedUserId) {
                                $query->where('user_id', $user->id)
                                      ->where('friend_id', $mentionedUserId);
                            })
                            ->orWhere(function($query) use ($user, $mentionedUserId) {
                                $query->where('user_id', $mentionedUserId)
                                      ->where('friend_id', $user->id);
                            })
                            ->where('status', 'accepted')
                            ->first();

                        if (!$mentionFriendship) {
                            return response()->json([
                                'status_code' => 422,
                                'success' => false,
                                'message' => 'You can only mention your friends'
                            ]);
                        }
                    }
                }
            }

            // Handle media upload if provided
            $mediaData = null;
            if ($request->hasFile('media')) {
                $file = $request->file('media');
                $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                $path = $file->storeAs('comments/' . $comment->post_id, $filename, 'public');
                
                $mediaData = [
                    'type' => strpos($file->getMimeType(), 'image') !== false ? 'image' : 'video',
                    'filename' => $filename,
                    'original_name' => $file->getClientOriginalName(),
                    'mime_type' => $file->getMimeType(),
                    'size' => $file->getSize(),
                    'path' => $path,
                    'url' => config('app.url') . '/public/storage/' . $path,
                    'metadata' => $this->extractMediaMetadata($file)
                ];
            }

            // Update the comment
            $updateData = [
                    'mentions' => isset($validatedData['mentions']) ? json_encode($validatedData['mentions']) : null,
                    'updated_at' => now()
            ];

            if (isset($validatedData['comment_text'])) {
                $updateData['comment_text'] = $validatedData['comment_text'];
            }

            if ($mediaData) {
                $updateData['media'] = json_encode($mediaData);
            }

            DB::table('post_comments')
                ->where('id', $validatedData['comment_id'])
                ->update($updateData);

            // Get the updated comment with user details
            $updatedComment = DB::table('post_comments')
                ->join('users', 'post_comments.user_id', '=', 'users.id')
                ->where('post_comments.id', $validatedData['comment_id'])
                ->select(
                    'post_comments.*',
                    'users.first_name',
                    'users.last_name'
                )
                ->first();

            DB::commit();

            return response()->json([
                'status_code' => 200,
                'success' => true,
                'message' => 'Comment updated successfully',
                'data' => [
                    'comment' => [
                        'id' => $updatedComment->id,
                        'post_id' => $updatedComment->post_id,
                        'parent_comment_id' => $updatedComment->parent_comment_id,
                        'comment_text' => $updatedComment->comment_text,
                        'mentions' => $updatedComment->mentions ? json_decode($updatedComment->mentions, true) : [],
                        'media' => $updatedComment->media ? json_decode($updatedComment->media, true) : null,
                        'created_at' => $updatedComment->created_at,
                        'updated_at' => $updatedComment->updated_at,
                        'user' => [
                            'id' => $updatedComment->user_id,
                            'first_name' => $updatedComment->first_name,
                            'last_name' => $updatedComment->last_name
                        ]
                    ]
                ]
            ]);

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
                'message' => 'Failed to update comment',
                'error' => $e->getMessage()
            ], 500);
        }
    }

  
    public function reactToComment(Request $request)
    {
        try {
            $user = Auth::guard('user')->user();
            
            $validatedData = $request->validate([
                'comment_id' => 'required|integer|exists:post_comments,id',
                'reaction_id' => 'required|integer|exists:reactions,id'
            ]);

            DB::beginTransaction();

            // Get the comment
            $comment = PostComment::where('id', $validatedData['comment_id'])
                ->where('is_deleted', false)
                ->first();

            if (!$comment) {
                return response()->json([
                    'status_code' => 404,
                    'success' => false,
                    'message' => 'Comment not found'
                ], 404);
            }

            // Check if reaction exists and is active
            $reaction = Reaction::find($validatedData['reaction_id']);
            if (!$reaction || !$reaction->isActive()) {
                return response()->json([
                    'status_code' => 404,
                    'success' => false,
                    'message' => 'Reaction not found or not available'
                ], 404);
            }

            // Check if user can see the comment (privacy checks)
            $post = Post::where('id', $comment->post_id)
                ->where('status', 'approved')
                ->first();

            if (!$post) {
                return response()->json([
                    'status_code' => 404,
                    'success' => false,
                    'message' => 'Post not found'
                ], 404);
            }

            // Privacy check - only friends can react if post is friends-only
            if ($post->privacy === 'friends' && $post->user_id !== $user->id) {
                $friendship = Friendship::where(function($query) use ($user, $post) {
                        $query->where('user_id', $user->id)
                              ->where('friend_id', $post->user_id);
                    })
                    ->orWhere(function($query) use ($user, $post) {
                        $query->where('user_id', $post->user_id)
                              ->where('friend_id', $user->id);
                    })
                    ->where('status', 'accepted')
                    ->first();

                if (!$friendship) {
                    return response()->json([
                        'status_code' => 403,
                        'success' => false,
                        'message' => 'You can only react to comments on posts from your friends'
                    ], 403);
                }
            }

            // Check if user already has a reaction on this comment
            $existingReaction = CommentReaction::where('user_id', $user->id)
                ->where('comment_id', $validatedData['comment_id'])
                ->first();

            if ($existingReaction) {
                // Update existing reaction
                $existingReaction->update([
                    'reaction_id' => $validatedData['reaction_id'],
                        'updated_at' => now()
                    ]);

                $action = 'updated';
            } else {
                // Create new reaction
                CommentReaction::create([
                    'comment_id' => $validatedData['comment_id'],
                    'user_id' => $user->id,
                    'reaction_id' => $validatedData['reaction_id']
                ]);

                $action = 'added';
            }

            // Get updated reaction counts for this comment
            $reactionCounts = CommentReaction::where('comment_id', $validatedData['comment_id'])
                ->with('reaction')
                ->get()
                ->groupBy('reaction_id')
                ->map(function ($reactions) {
                    return [
                        'reaction' => $reactions->first()->reaction,
                        'count' => $reactions->count()
                    ];
                });

            // Get user's current reaction
            $userReaction = CommentReaction::where('user_id', $user->id)
                ->where('comment_id', $validatedData['comment_id'])
                ->with('reaction')
                ->first();

            DB::commit();

            return response()->json([
                'status_code' => 200,
                'success' => true,
                'message' => "Reaction {$action} successfully",
                'data' => [
                    'comment_id' => $validatedData['comment_id'],
                    'user_reaction' => $userReaction ? [
                        'id' => $userReaction->reaction->id,
                        'name' => $userReaction->reaction->name,
                        'content' => $userReaction->reaction->content,
                        'image' => $this->formatReactionUrl($userReaction->reaction->image_url),
                        'video' => $this->formatReactionUrl($userReaction->reaction->video_url)
                    ] : null,
                    'reaction_counts' => $reactionCounts->map(function ($item) {
                        return [
                            'reaction' => [
                                'id' => $item['reaction']->id,
                                'name' => $item['reaction']->name,
                                'content' => $item['reaction']->content,
                                'image' => $this->formatReactionUrl($item['reaction']->image_url),
                                'video' => $this->formatReactionUrl($item['reaction']->video_url)
                            ],
                            'count' => $item['count']
                        ];
                    })->values(),
                    'total_reactions' => $reactionCounts->sum('count')
                ]
            ]);

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
                'message' => 'Failed to react to comment',
                'error' => $e->getMessage()
            ], 500);
        }
    }
   
    public function removeCommentReaction(Request $request)
    {
        try {
            $user = Auth::guard('user')->user();
            
            $validatedData = $request->validate([
                'comment_id' => 'required|integer|exists:post_comments,id'
            ]);

            DB::beginTransaction();

            // Get the comment
            $comment = PostComment::where('id', $validatedData['comment_id'])
                ->where('is_deleted', false)
                ->first();

            if (!$comment) {
                return response()->json([
                    'status_code' => 404,
                    'success' => false,
                    'message' => 'Comment not found'
                ], 404);
            }

            // Find and delete user's reaction
            $userReaction = CommentReaction::where('user_id', $user->id)
                ->where('comment_id', $validatedData['comment_id'])
                ->first();

            if (!$userReaction) {
                return response()->json([
                    'status_code' => 404,
                    'success' => false,
                    'message' => 'No reaction found to remove'
                ], 404);
            }

            $userReaction->delete();

            // Get updated reaction counts for this comment
            $reactionCounts = CommentReaction::where('comment_id', $validatedData['comment_id'])
                ->with('reaction')
                ->get()
                ->groupBy('reaction_id')
                ->map(function ($reactions) {
                    return [
                        'reaction' => $reactions->first()->reaction,
                        'count' => $reactions->count()
                    ];
                });

            DB::commit();

            return response()->json([
                'status_code' => 200,
                'success' => true,
                'message' => 'Reaction removed successfully',
                'data' => [
                    'comment_id' => $validatedData['comment_id'],
                    'user_reaction' => null,
                    'reaction_counts' => $reactionCounts->map(function ($item) {
                        return [
                            'reaction' => [
                                'id' => $item['reaction']->id,
                                'name' => $item['reaction']->name,
                                'content' => $item['reaction']->content,
                                'image' => $this->formatReactionUrl($item['reaction']->image_url),
                                'video' => $this->formatReactionUrl($item['reaction']->video_url)
                            ],
                            'count' => $item['count']
                        ];
                    })->values(),
                    'total_reactions' => $reactionCounts->sum('count')
                ]
            ]);

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
                'message' => 'Failed to remove reaction',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getCommentReplies(Request $request)
    {
        try {
            $user = Auth::guard('user')->user();
            
            $validatedData = $request->validate([
                'comment_id' => 'required|integer|exists:post_comments,id',
                'page' => 'nullable|integer|min:1',
                'per_page' => 'nullable|integer|min:1|max:50',
                'max_depth' => 'nullable|integer|min:1|max:10'
            ]);

            $page = $validatedData['page'] ?? 1;
            $perPage = $validatedData['per_page'] ?? 20;
            $maxDepth = $validatedData['max_depth'] ?? 3;
            $offset = ($page - 1) * $perPage;

            // Get the parent comment
            $parentComment = DB::table('post_comments')
                ->where('id', $validatedData['comment_id'])
                ->where('is_deleted', false)
                ->first();

            if (!$parentComment) {
                return response()->json([
                    'status_code' => 404,
                    'success' => false,
                    'message' => 'Comment not found'
                ]);
            }

            // Check if user can see the comment (privacy checks)
            $post = DB::table('posts')
                ->where('id', $parentComment->post_id)
                ->where('status', 'approved')
                ->first();

            if (!$post) {
                return response()->json([
                    'status_code' => 404,
                    'success' => false,
                    'message' => 'Post not found'
                ]);
            }

            // Privacy check
            if ($post->privacy === 'friends' && $post->user_id !== $user->id) {
                $friendship = DB::table('friendships')
                    ->where(function($query) use ($user, $post) {
                        $query->where('user_id', $user->id)
                              ->where('friend_id', $post->user_id);
                    })
                    ->orWhere(function($query) use ($user, $post) {
                        $query->where('user_id', $post->user_id)
                              ->where('friend_id', $user->id);
                    })
                    ->where('status', 'accepted')
                    ->first();

                if (!$friendship) {
                    return response()->json([
                        'status_code' => 403,
                        'success' => false,
                        'message' => 'You can only view replies on comments from your friends'
                    ]);
                }
            }

            // Get all replies for this comment (including nested replies)
            $allReplies = DB::table('post_comments')
                ->where('post_comments.post_id', $parentComment->post_id)
                ->where('post_comments.is_deleted', false)
                ->whereNotExists(function($query) use ($user) {
                    $query->select(DB::raw(1))
                          ->from('hidden_comments')
                          ->whereColumn('hidden_comments.comment_id', 'post_comments.id')
                          ->where('hidden_comments.user_id', $user->id)
                          ->where(function($subQuery) {
                              $subQuery->where('hidden_comments.expires_at', '>', now())
                                      ->orWhere('hidden_comments.hide_type', 'permanent');
                          });
                })
                ->orderBy('post_comments.created_at', 'asc')
                ->get();

            // Get all unique user IDs from replies
            $userIds = $allReplies->pluck('user_id')->unique();
            
            // Get complete user data
            $users = User::whereIn('id', $userIds)->get()->keyBy('id');

            // Get all reply IDs for reactions
            $replyIds = $allReplies->pluck('id');

            // Get comment reactions data with user details
            $commentReactions = CommentReaction::whereIn('comment_id', $replyIds)
                ->with(['reaction', 'user'])
                ->get()
                ->groupBy('comment_id');

            // Get user's reactions for these replies
            $userCommentReactions = CommentReaction::where('user_id', $user->id)
                ->whereIn('comment_id', $replyIds)
                ->with('reaction')
                ->get()
                ->keyBy('comment_id');

            // Get direct replies for pagination
            $directReplies = $allReplies->where('parent_comment_id', $validatedData['comment_id']);
            $totalDirectReplies = $directReplies->count();
            
            // Paginate direct replies
            $paginatedDirectReplies = $directReplies->slice($offset, $perPage);

            // Build reply tree for paginated direct replies
            $repliesWithNested = [];
            foreach ($paginatedDirectReplies as $reply) {
                $user = $users->get($reply->user_id);
                
                // Get comment reactions data for this reply
                $commentReactionData = $commentReactions->get($reply->id, collect());
                $totalCommentReactions = $commentReactionData->count();
                
                // Get user's reaction for this reply
                $userCommentReactionData = $userCommentReactions->get($reply->id);

                // Group reactions by type and count them
                $reactionCounts = $commentReactionData->groupBy('reaction_id')
                    ->map(function ($reactions) {
                        return [
                            'reaction' => $reactions->first()->reaction,
                            'count' => $reactions->count(),
                            'users' => $reactions->pluck('user')
                        ];
                    });

                $replyData = [
                    'id' => $reply->id,
                    'parent_comment_id' => $reply->parent_comment_id,
                    'comment_text' => $reply->comment_text,
                    'mentions' => $reply->mentions ? json_decode($reply->mentions, true) : [],
                    'media' => $reply->media ? json_decode($reply->media, true) : null,
                    'created_at' => $reply->created_at,
                    'user' => $user ? app(AuthController::class)->mapUserDetails($user) : null,
                    'nesting_level' => $this->getCommentNestingLevel($reply->id),
                    'replies_count' => 0,
                    'replies' => [],
                    'comment_reactions' => [
                        'user_reaction' => $userCommentReactionData ? [
                            'id' => $userCommentReactionData->reaction->id,
                            'name' => $userCommentReactionData->reaction->name,
                            'content' => $userCommentReactionData->reaction->content,
                            'image' => $this->formatReactionUrl($userCommentReactionData->reaction->image_url),
                            'video' => $this->formatReactionUrl($userCommentReactionData->reaction->video_url)
                        ] : null,
                        'reactions' => $reactionCounts->map(function ($item) {
                            return [
                                'id' => $item['reaction']->id,
                                'name' => $item['reaction']->name,
                                'content' => $item['reaction']->content,
                                'image' => $this->formatReactionUrl($item['reaction']->image_url),
                                'video' => $this->formatReactionUrl($item['reaction']->video_url),
                                'count' => $item['count'],
                                'users' => $this->mapUsersDetails($item['users'])
                            ];
                        })->values(),
                        'total_reactions' => $totalCommentReactions
                    ]
                ];

                // Get nested replies count
                $nestedReplies = $allReplies->where('parent_comment_id', $reply->id);
                $replyData['replies_count'] = $nestedReplies->count();
                
                // Build nested reply tree (limited depth for performance)
                $replyData['replies'] = $this->buildCommentTree($allReplies, $users, $reply->id, $maxDepth - 1, 1, $commentReactions, $userCommentReactions);

                $repliesWithNested[] = $replyData;
            }

            $totalPages = ceil($totalDirectReplies / $perPage);

            // Get reactions data from HomeController
            $homeController = app(HomeController::class);
            $reactionsRequest = new Request();
            $reactionsResponse = $homeController->getReactions($reactionsRequest);
            $reactionsData = json_decode($reactionsResponse->getContent(), true);

            return response()->json([
                'status_code' => 200,
                'success' => true,
                'message' => 'Comment replies retrieved successfully',
                'data' => [
                    'parent_comment_id' => $validatedData['comment_id'],
                    'replies' => $repliesWithNested,
                    'reactions' => $reactionsData['success'] ? $reactionsData['data'] : [],
                    'pagination' => [
                        'current_page' => $page,
                        'per_page' => $perPage,
                        'total' => $totalDirectReplies,
                        'total_pages' => $totalPages,
                        'from' => $totalDirectReplies ? $offset + 1 : null,
                        'to' => $totalDirectReplies ? min($offset + $perPage, $totalDirectReplies) : null,
                        'has_more_pages' => $page < $totalPages
                    ],
                    'settings' => [
                        'max_depth' => $maxDepth
                    ]
                ]
            ]);

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
                'message' => 'Failed to retrieve comment replies',
                'error' => $e->getMessage()
            ], 500);
        }
    }
   
    public function getCommentReactions(Request $request)
    {
        try {
            $user = Auth::guard('user')->user();
            
            $validatedData = $request->validate([
                'comment_id' => 'required|integer|exists:post_comments,id',
                'page' => 'nullable|integer|min:1',
                'per_page' => 'nullable|integer|min:1|max:50'
            ]);

            $page = $validatedData['page'] ?? 1;
            $perPage = $validatedData['per_page'] ?? 20;
            $offset = ($page - 1) * $perPage;

            // Get the comment
            $comment = PostComment::where('id', $validatedData['comment_id'])
                ->where('is_deleted', false)
                ->first();

            if (!$comment) {
                return response()->json([
                    'status_code' => 404,
                    'success' => false,
                    'message' => 'Comment not found'
                ], 404);
            }

            // Check if user can see the comment (privacy checks)
            $post = Post::where('id', $comment->post_id)
                ->where('status', 'approved')
                ->first();

            if (!$post) {
                return response()->json([
                    'status_code' => 404,
                    'success' => false,
                    'message' => 'Post not found'
                ], 404);
            }

            // Privacy check - only friends can see reactions if post is friends-only
            if ($post->privacy === 'friends' && $post->user_id !== $user->id) {
                $friendship = Friendship::where(function($query) use ($user, $post) {
                        $query->where('user_id', $user->id)
                              ->where('friend_id', $post->user_id);
                    })
                    ->orWhere(function($query) use ($user, $post) {
                        $query->where('user_id', $post->user_id)
                              ->where('friend_id', $user->id);
                    })
                    ->where('status', 'accepted')
                    ->first();

                if (!$friendship) {
                    return response()->json([
                        'status_code' => 403,
                        'success' => false,
                        'message' => 'You can only view reactions on comments from your friends'
                    ], 403);
                }
            }

            // Get reaction counts with user details
            $reactionCounts = CommentReaction::where('comment_id', $validatedData['comment_id'])
                ->with(['reaction', 'user'])
                ->get()
                ->groupBy('reaction_id')
                ->map(function ($reactions) {
                    return [
                        'reaction' => $reactions->first()->reaction,
                        'count' => $reactions->count(),
                        'users' => $reactions->pluck('user')
                    ];
                });

            // Get user's reaction
            $userReaction = CommentReaction::where('comment_id', $validatedData['comment_id'])
                ->where('user_id', $user->id)
                ->with('reaction')
                ->first();

            // Get paginated reactions with user details
            $reactions = CommentReaction::where('comment_id', $validatedData['comment_id'])
                ->with(['reaction', 'user'])
                ->orderBy('created_at', 'desc')
                ->offset($offset)
                ->limit($perPage)
                ->get();

            $formattedReactions = $reactions->map(function ($reaction) {
                return [
                    'id' => $reaction->id,
                    'reaction' => [
                        'id' => $reaction->reaction->id,
                        'name' => $reaction->reaction->name,
                        'content' => $reaction->reaction->content,
                        'image' => $this->formatReactionUrl($reaction->reaction->image_url),
                        'video' => $this->formatReactionUrl($reaction->reaction->video_url)
                    ],
                    'created_at' => $reaction->created_at,
                    'user' => $this->mapUsersDetails(collect([$reaction->user]))->first()
                ];
            });

            // Get total count for pagination
            $totalReactions = CommentReaction::where('comment_id', $validatedData['comment_id'])->count();
            $totalPages = ceil($totalReactions / $perPage);

            // Get reactions data from HomeController
            $homeController = app(HomeController::class);
            $reactionsRequest = new Request();
            $reactionsResponse = $homeController->getReactions($reactionsRequest);
            $reactionsData = json_decode($reactionsResponse->getContent(), true);

            return response()->json([
                'status_code' => 200,
                'success' => true,
                'message' => 'Comment reactions retrieved successfully',
                'data' => [
                    'comment_id' => $validatedData['comment_id'],
                    'user_reaction' => $userReaction ? [
                        'id' => $userReaction->reaction->id,
                        'name' => $userReaction->reaction->name,
                        'content' => $userReaction->reaction->content,
                        'image' => $this->formatReactionUrl($userReaction->reaction->image_url),
                        'video' => $this->formatReactionUrl($userReaction->reaction->video_url)
                    ] : null,
                    'reactions' => $reactionCounts->map(function ($item) {
                        return [
                            'reaction' => [
                                'id' => $item['reaction']->id,
                                'name' => $item['reaction']->name,
                                'content' => $item['reaction']->content,
                                'image' => $this->formatReactionUrl($item['reaction']->image_url),
                                'video' => $this->formatReactionUrl($item['reaction']->video_url)
                            ],
                            'count' => $item['count'],
                            'users' => $this->mapUsersDetails($item['users'])
                        ];
                    })->values(),
                    'total_reactions' => $reactionCounts->sum('count'),
                    'reaction_details' => $formattedReactions,
                    'available_reactions' => $reactionsData['success'] ? $reactionsData['data'] : [],
                    'pagination' => [
                        'current_page' => $page,
                        'per_page' => $perPage,
                        'total' => $totalReactions,
                        'total_pages' => $totalPages,
                        'from' => $offset + 1,
                        'to' => min($offset + $perPage, $totalReactions)
                    ]
                ]
            ]);

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
                'message' => 'Failed to retrieve comment reactions',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function deleteComment(Request $request)
    {
        try {
            $user = Auth::guard('user')->user();
            
            $validatedData = $request->validate([
                'comment_id' => 'required|integer|exists:post_comments,id'
            ]);

            DB::beginTransaction();

            // Get the comment
            $comment = DB::table('post_comments')
                ->where('id', $validatedData['comment_id'])
                ->where('is_deleted', false)
                ->first();

            if (!$comment) {
                return response()->json([
                    'status_code' => 404,
                    'success' => false,
                    'message' => 'Comment not found'
                ]);
            }

            // Check if user owns the comment or owns the post
            $post = DB::table('posts')->where('id', $comment->post_id)->first();
            
            if ($comment->user_id !== $user->id && $post->user_id !== $user->id) {
                return response()->json([
                    'status_code' => 403,
                    'success' => false,
                    'message' => 'You can only delete your own comments or comments on your posts'
                ]);
            }

            // Delete media files if they exist
            if ($comment->media) {
                $mediaData = json_decode($comment->media, true);
                if ($mediaData && isset($mediaData['path'])) {
                    try {
                        Storage::disk('public')->delete($mediaData['path']);
                    } catch (Exception $e) {
                        // Log the error but don't fail the deletion
                        \Log::warning('Failed to delete comment media file: ' . $e->getMessage(), [
                            'comment_id' => $comment->id,
                            'media_path' => $mediaData['path']
                        ]);
                    }
                }
            }

            // Soft delete the comment
            DB::table('post_comments')
                ->where('id', $validatedData['comment_id'])
                ->update([
                    'is_deleted' => true,
                    'updated_at' => now()
                ]);

            // Recursively delete all nested replies and their media
            $this->deleteCommentAndReplies($validatedData['comment_id']);

            DB::commit();

            return response()->json([
                'status_code' => 200,
                'success' => true,
                'message' => 'Comment deleted successfully'
            ]);

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
                'message' => 'Failed to delete comment',
                'error' => $e->getMessage()
            ]);
        }
    }

    public function reactToPost(Request $request)
    {
        try {
            $user = Auth::guard('user')->user();
            
            $validatedData = $request->validate([
                'post_id' => 'required|integer|exists:posts,id',
                'reaction_id' => 'required|integer|exists:reactions,id'
            ]);

            DB::beginTransaction();

            // Check if post exists and is approved
            $post = Post::with(['user', 'media', 'poll', 'personalOccasion'])
                ->approved()
                ->notExpired()
                ->visibleTo($user)
                ->find($validatedData['post_id']);

            if (!$post) {
                return response()->json([
                    'status_code' => 404,
                    'success' => false,
                    'message' => 'Post not found or not approved'
                ]);
            }

            // Check if reaction exists and is active
            $reaction = Reaction::find($validatedData['reaction_id']);
            if (!$reaction || !$reaction->isActive()) {
                return response()->json([
                    'status_code' => 404,
                    'success' => false,
                    'message' => 'Reaction not found or not available'
                ]);
            }

            // Check if user already has a reaction on this post
            $existingReaction = PostReaction::where('user_id', $user->id)
                ->where('post_id', $validatedData['post_id'])
                ->first();

            if ($existingReaction) {
                // Update existing reaction
                $existingReaction->update([
                    'reaction_id' => $validatedData['reaction_id'],
                        'updated_at' => now()
                    ]);

                $action = 'updated';
            } else {
                // Create new reaction
                PostReaction::create([
                    'user_id' => $user->id,
                    'post_id' => $validatedData['post_id'],
                    'reaction_id' => $validatedData['reaction_id']
                ]);

                $action = 'added';
            }

            // Get updated reaction counts for this post
            $reactionCounts = PostReaction::where('post_id', $validatedData['post_id'])
                ->with('reaction')
                ->get()
                ->groupBy('reaction_id')
                ->map(function ($reactions) {
                    return [
                        'reaction' => $reactions->first()->reaction,
                        'count' => $reactions->count()
                    ];
                });

            // Get user's current reaction
            $userReaction = PostReaction::where('user_id', $user->id)
                ->where('post_id', $validatedData['post_id'])
                ->with('reaction')
                ->first();

            DB::commit();

            // Build complete post data with same structure as getFeed
            $postData = $this->buildCompletePostData($post, $user);

            return response()->json([
                'status_code' => 200,
                'success' => true,
                'message' => "Reaction {$action} successfully",
                'data' => [
                    'post' => $postData,
                    'user_reaction' => $userReaction ? [
                        'id' => $userReaction->reaction->id,
                        'name' => $userReaction->reaction->name,
                        'content' => $userReaction->reaction->content,
                        'image' => $this->formatReactionUrl($userReaction->reaction->image_url),
                        'video' => $this->formatReactionUrl($userReaction->reaction->video_url)
                    ] : null,
                    'reaction_counts' => $reactionCounts->map(function ($item) {
                        return [
                            'reaction' => [
                                'id' => $item['reaction']->id,
                                'name' => $item['reaction']->name,
                                'content' => $item['reaction']->content,
                                'image' => $this->formatReactionUrl($item['reaction']->image_url),
                                'video' => $this->formatReactionUrl($item['reaction']->video_url)
                            ],
                            'count' => $item['count']
                        ];
                    })->values(),
                    'total_reactions' => $reactionCounts->sum('count')
                ]
            ]);

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
                'message' => 'Failed to react to post',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function removePostReaction(Request $request)
    {
        try {
            $user = Auth::guard('user')->user();
            
            $validatedData = $request->validate([
                'post_id' => 'required|integer|exists:posts,id'
            ]);

            DB::beginTransaction();

            // Check if post exists
            $post = Post::with(['user', 'media', 'poll', 'personalOccasion'])
                ->approved()
                ->notExpired()
                ->visibleTo($user)
                ->find($validatedData['post_id']);

            if (!$post) {
                return response()->json([
                    'status_code' => 404,
                    'success' => false,
                    'message' => 'Post not found or not approved'
                ]);
            }

            // Find and delete user's reaction
            $userReaction = PostReaction::where('user_id', $user->id)
                ->where('post_id', $validatedData['post_id'])
                ->first();

            if (!$userReaction) {
                return response()->json([
                    'status_code' => 404,
                    'success' => false,
                    'message' => 'No reaction found to remove'
                ]);
            }

            $userReaction->delete();

            // Get updated reaction counts for this post
            $reactionCounts = PostReaction::where('post_id', $validatedData['post_id'])
                ->with('reaction')
                ->get()
                ->groupBy('reaction_id')
                ->map(function ($reactions) {
                    return [
                        'reaction' => $reactions->first()->reaction,
                        'count' => $reactions->count()
                    ];
                });

            DB::commit();

            // Build complete post data with same structure as getFeed
            $postData = $this->buildCompletePostData($post, $user);

            return response()->json([
                'status_code' => 200,
                'success' => true,
                'message' => 'Reaction removed successfully',
                'data' => [
                    'post' => $postData,
                    'user_reaction' => null,
                    'reaction_counts' => $reactionCounts->map(function ($item) {
                        return [
                            'reaction' => [
                                'id' => $item['reaction']->id,
                                'name' => $item['reaction']->name,
                                'content' => $item['reaction']->content,
                                'image' => $this->formatReactionUrl($item['reaction']->image_url),
                                'video' => $this->formatReactionUrl($item['reaction']->video_url)
                            ],
                            'count' => $item['count']
                        ];
                    })->values(),
                    'total_reactions' => $reactionCounts->sum('count')
                ]
            ]);

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
                'message' => 'Failed to remove reaction',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getFeed(Request $request)
    {
        try {
            $validated = $request->validate([
                'page' => 'nullable|integer|min:1',
                'per_page' => 'nullable|integer|min:1|max:50'
            ]);

            $user = Auth::guard('user')->user();
            $page = $validated['page'] ?? 1;
            $perPage = $validated['per_page'] ?? 20;

            // Gather friend ids
            $friendships = Friendship::where(function($q) use ($user) {
                    $q->where('user_id', $user->id)
                      ->orWhere('friend_id', $user->id);
                })
                ->where('status', 'accepted')
                ->get();

            $friendIds = $friendships->map(function($f) use ($user) {
                return $f->user_id == $user->id ? $f->friend_id : $f->user_id;
            })->unique()->values();

            // Base query for posts visible to current user
            $visiblePostsQuery = Post::with(['user', 'media', 'poll', 'personalOccasion'])
                ->approved()
                ->notExpired()
                ->visibleTo($user);

            // 1) Direct friends' posts
            $friendsPosts = (clone $visiblePostsQuery)
                ->whereIn('user_id', $friendIds)
                ->inRandomOrder()
                ->take($perPage * 2)
                ->get();

            // 1.a) My own posts
            $myPosts = Post::with(['user', 'media', 'poll', 'personalOccasion'])
                ->approved()
                ->notExpired()
                ->where('user_id', $user->id)
                ->inRandomOrder()
                ->take($perPage * 2)
                ->get();

            // 2) Posts friends interacted with (commented on or interest feedback)
            $friendInteractedPostIds = collect();
            if ($friendIds->isNotEmpty()) {
                $commented = \DB::table('post_comments')
                    ->whereIn('user_id', $friendIds)
                    ->pluck('post_id');
                $interested = \DB::table('post_interest_feedback')
                    ->whereIn('user_id', $friendIds)
                    ->pluck('post_id');
                $friendInteractedPostIds = $commented->merge($interested)->unique()->values();
            }

            $friendInteractedPosts = $friendInteractedPostIds->isEmpty()
                ? collect()
                : (clone $visiblePostsQuery)
                    ->whereIn('id', $friendInteractedPostIds)
                    ->inRandomOrder()
                    ->take($perPage)
                    ->get();

            // 3) Personalized suggested posts (interests + demographics)
            $userInterests = $user->userInterests()->pluck('interests.id');
            $gender = $user->gender;
            $age = $user->age;

            $suggestedQuery = (clone $visiblePostsQuery)
                ->whereNotIn('user_id', $friendIds->push($user->id))
                ->where('privacy', 'public');

            // Boost posts by users with overlapping interests
            if ($userInterests->isNotEmpty()) {
                $suggestedUserIdsByInterests = \DB::table('user_interests')
                    ->whereIn('interest_id', $userInterests)
                    ->where('user_id', '!=', $user->id)
                    ->pluck('user_id');
                $suggestedQuery->whereIn('user_id', $suggestedUserIdsByInterests);
            }

            // Light demographic alignment (optional filters if present)
            $suggestedQuery->when(!empty($gender), function($q) use ($gender) {
                $q->whereHas('user', function($uq) use ($gender) {
                    $uq->where('gender', $gender);
                });
            });
            $suggestedQuery->when(!empty($age), function($q) use ($age) {
                $q->whereHas('user', function($uq) use ($age) {
                    $uq->whereBetween('age', [max(13, $age - 5), $age + 5]);
                });
            });

            $suggestedPosts = $suggestedQuery
                ->inRandomOrder()
                ->take($perPage)
                ->get();

            // 4) Posts similar to ones user marked interested/not interested (use positive ones)
            $likedPostOwnerIds = \DB::table('post_interest_feedback')
                ->where('user_id', $user->id)
                ->where('interest_type', 'interested')
                ->pluck('post_owner_id');

            $similarPosts = $likedPostOwnerIds->isEmpty() ? collect() : (clone $visiblePostsQuery)
                ->whereIn('user_id', $likedPostOwnerIds)
                ->inRandomOrder()
                ->take($perPage)
                ->get();

            // Merge pools and de-duplicate by id
            $pool = $myPosts
                ->merge($friendsPosts)
                ->merge($friendInteractedPosts)
                ->merge($suggestedPosts)
                ->merge($similarPosts)
                ->unique('id')
                ->values();

            // Shuffle to keep it unordered and random per request
            $pool = $pool->shuffle();

            // Paginate manually from the shuffled pool
            $total = $pool->count();
            $offset = ($page - 1) * $perPage;
            $slice = $pool->slice($offset, $perPage)->values();

            // Enrich posts: comment_count, reaction_count (approx via reactions on comments? none for posts), is_saved
            $postIds = $slice->pluck('id');
            $commentCounts = \DB::table('post_comments')
                ->whereIn('post_id', $postIds)
                ->whereNull('parent_comment_id')
                ->where('is_deleted', false)
                ->select('post_id', \DB::raw('count(*) as cnt'))
                ->groupBy('post_id')
                ->pluck('cnt', 'post_id');

            // Use interest feedback on posts as proxy for reactions
            $reactionCountsFriend = \DB::table('post_interest_feedback')
                ->whereIn('post_id', $postIds)
                ->select('post_id', \DB::raw('count(*) as cnt'))
                ->groupBy('post_id')
                ->pluck('cnt', 'post_id');
            $reactionCountsSuggested = \DB::table('suggested_post_interest_feedback')
                ->whereIn('post_id', $postIds)
                ->select('post_id', \DB::raw('count(*) as cnt'))
                ->groupBy('post_id')
                ->pluck('cnt', 'post_id');

            $savedPostIds = \DB::table('saved_posts')
                ->where('user_id', $user->id)
                ->whereIn('post_id', $postIds)
                ->pluck('post_id')
                ->flip();

            // Get post reactions data with user details
            $postReactions = PostReaction::whereIn('post_id', $postIds)
                ->with(['reaction', 'user'])
                ->get()
                ->groupBy('post_id');

            // Get user's reactions for these posts
            $userReactions = PostReaction::where('user_id', $user->id)
                ->whereIn('post_id', $postIds)
                ->with('reaction')
                ->get()
                ->keyBy('post_id');

            $mapped = $slice->map(function($post) use ($commentCounts, $reactionCountsFriend, $reactionCountsSuggested, $savedPostIds, $friendIds, $postReactions, $userReactions) {
                $data = $post->toArray();
                $data['comments_count'] = (int) ($commentCounts[$post->id] ?? 0);
                
                // Get post reactions data for this post
                $postReactionData = $postReactions->get($post->id, collect());
                $totalPostReactions = $postReactionData->count();
                
                // Use new post reactions count instead of old interest feedback
                $data['reactions_count'] = $totalPostReactions;
                
                $data['is_saved'] = $savedPostIds->has($post->id);
                $data['is_user_friend'] = $friendIds->contains($post->user_id);
                // Map user data using mapUsersDetails function
                $data['user'] = $this->mapUsersDetails(collect([$post->user]))->first();
                
                // Map mentions friends to full user data
                if (isset($data['mentions']['friends']) && !empty($data['mentions']['friends'])) {
                    $mentionedUserIds = $data['mentions']['friends'];
                    $mentionedUsers = User::whereIn('id', $mentionedUserIds)->get();
                    $data['mentions']['friends'] = $this->mapUsersDetails($mentionedUsers);
                }
                
                // Map mentions place to full place data
                if (isset($data['mentions']['place']) && !empty($data['mentions']['place'])) {
                    $userPlace = UserPlace::find($data['mentions']['place']);
                    if ($userPlace) {
                        $data['mentions']['place'] = $this->mapUserPlaces($userPlace);
                    }
                }
                
                // Check if mentions object is empty and set to null
                if (isset($data['mentions']) && empty(array_filter($data['mentions']))) {
                    $data['mentions'] = null;
                }

                // Add post reactions data
                $userReactionData = $userReactions->get($post->id);

                // Group reactions by type and count them
                $reactionCounts = $postReactionData->groupBy('reaction_id')
                    ->map(function ($reactions) {
                        return [
                            'reaction' => $reactions->first()->reaction,
                            'count' => $reactions->count(),
                            'users' => $reactions->pluck('user')
                        ];
                    });

                $data['post_reactions'] = [
                    'user_reaction' => $userReactionData ? [
                        'id' => $userReactionData->reaction->id,
                        'name' => $userReactionData->reaction->name,
                        'content' => $userReactionData->reaction->content,
                        'image' => $this->formatReactionUrl($userReactionData->reaction->image_url),
                        'video' => $this->formatReactionUrl($userReactionData->reaction->video_url)
                    ] : null,
                    'reactions' => $reactionCounts->map(function ($item) {
                        return [
                            'id' => $item['reaction']->id,
                            'name' => $item['reaction']->name,
                            'content' => $item['reaction']->content,
                            'image' => $this->formatReactionUrl($item['reaction']->image_url),
                            'video' => $this->formatReactionUrl($item['reaction']->video_url),
                            'count' => $item['count'],
                            'users' => $this->mapUsersDetails($item['users'])
                        ];
                    })->values(),
                    'total_reactions' => $totalPostReactions
                ];
                
                // Date fields already present: created_at, updated_at
                return $data;
            });

            // Get reactions data from HomeController
            $homeController = app(HomeController::class);
            $reactionsRequest = new Request();
            $reactionsResponse = $homeController->getReactions($reactionsRequest);
            $reactionsData = json_decode($reactionsResponse->getContent(), true);

            return response()->json([
                'status_code' => 200,
                'success' => true,
                'message' => 'Feed retrieved successfully',
                'data' => [
                    'posts' => $mapped,
                    'reactions' => $reactionsData['success'] ? $reactionsData['data'] : [],
                    'pagination' => [
                        'current_page' => $page,
                        'per_page' => $perPage,
                        'total' => $total,
                        'last_page' => $total > 0 ? (int)ceil($total / $perPage) : 1,
                        'from' => $total ? $offset + 1 : null,
                        'to' => $total ? min($offset + $perPage, $total) : null,
                        'has_more_pages' => $offset + $perPage < $total
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
        } catch (\Exception $e) {
            return response()->json([
                'status_code' => 500,
                'success' => false,
                'message' => 'Failed to retrieve feed',
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

    public function checkServerCapabilities()
    {
        try {
            $capabilities = [
                'php_version' => PHP_VERSION,
                'shell_exec_enabled' => function_exists('shell_exec'),
                'ffmpeg_available' => $this->isFFmpegAvailable(),
                'file_upload_enabled' => ini_get('file_uploads'),
                'max_upload_size' => ini_get('upload_max_filesize'),
                'max_post_size' => ini_get('post_max_size'),
                'memory_limit' => ini_get('memory_limit'),
                'disabled_functions' => explode(',', ini_get('disable_functions'))
            ];

            // Check FFmpeg version if available
            if ($capabilities['ffmpeg_available']) {
                $ffmpegVersion = shell_exec('ffmpeg -version 2>&1');
                $capabilities['ffmpeg_version'] = $ffmpegVersion ? trim(explode("\n", $ffmpegVersion)[0]) : 'Unknown';
            }

            return response()->json([
                'status_code' => 200,
                'success' => true,
                'message' => 'Server capabilities retrieved successfully',
                'data' => $capabilities
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'status_code' => 500,
                'success' => false,
                'message' => 'Failed to check server capabilities',
                'error' => $e->getMessage()
            ], 500);
        }
    }

}
