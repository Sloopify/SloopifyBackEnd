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
use App\Http\Controllers\Api\V1\User\Auth\AuthController;
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
                ->visibleTo($user->id)
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
                'data' => [
                    'posts' => $posts->items(),
                    'pagination' => [
                        'current_page' => $posts->currentPage(),
                        'last_page' => $posts->lastPage(),
                        'per_page' => $posts->perPage(),
                        'total' => $posts->total(),
                        'from' => $posts->firstItem(),
                        'to' => $posts->lastItem(),
                        'has_more_pages' => $posts->hasMorePages()
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
                'message' => 'Failed to retrieve user posts',
                'error' => $e->getMessage()
            ], 500);
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
