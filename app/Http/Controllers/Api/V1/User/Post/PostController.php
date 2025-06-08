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
                'mobile_icon' => $feeling->mobile_icon ? config('app.url') . asset('storage/feelings/mobile/' . $feeling->mobile_icon) : null,
                'web_icon' => $feeling->web_icon ? config('app.url') . asset('storage/feelings/web/' . $feeling->web_icon) : null,
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
                'mobile_icon' => $activity->mobile_icon ? config('app.url') . asset('storage/activities/mobile/' . $activity->mobile_icon) : null,
                'web_icon' => $activity->web_icon ? config('app.url') . asset('storage/activities/web/' . $activity->web_icon) : null,
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

    public function mapUserDetails($user)
    {
        $phoneDetails = PhoneNumberHelper::parsePhoneNumber($user->phone);

        return [
            'id' => $user->id,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'phone' => [
                'full' => $phoneDetails['formatted'],
                'code' => $phoneDetails['code'],
                'number' => $phoneDetails['number'],
                'valid' => $phoneDetails['valid']
            ],
            'email' => $user->email,
            'email_verified' => !is_null($user->email_verified_at),
            'gender' => $user->gender,
            'status' => $user->status,
            'is_blocked' => (bool)$user->is_blocked,
            'age' => $user->birthday ? now()->diffInYears($user->birthday) : null,
            'birthday' => $user->birthday,
            'bio' => $user->bio,
            'country' => $user->country,
            'city' => $user->city,
            'provider' => $user->provider,
            'image' => $user->provider === 'google' ? $user->img : ($user->img ? config('app.url') . asset('storage/' . $user->img) : null),
            'referral_code' => $user->referral_code,
            'referral_link' => $user->referral_link,
            'reffered_by' => $user->reffered_by,
            'last_login_at' => $user->last_login_at,
            'created_at' => $user->created_at,
            'updated_at' => $user->updated_at,
        ];
    }

    public function mapUsersDetails($users)
    {
        return $users->map(function ($user) {
            return $this->mapUserDetails($user);
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
                'background_color' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
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

    public function getFeeling()
    {
        try {
        $feelings = PostFeeling::where('status', 'active')->get();
        if($feelings->isEmpty()) {
            return response()->json([
                'status_code' => 404,
                'success' => false,
                'message' => 'No feelings found'
            ], 404);
        }
        
        return response()->json([
            'status_code' => 200,
            'success' => true,
            'message' => 'Feelings retrieved successfully',
            'data' => $this->mapFeelings($feelings)
        ], 200);
        } catch (Exception $e) {
            return response()->json([
                'status_code' => 500,
                'success' => false,
                'message' => 'Failed to retrieve feelings',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getActivityCategory()
    {
        try {
            $categories = PostActivity::where('status', 'active')
                ->distinct()
                ->pluck('category')
                ->filter() 
                ->values(); 
            
            if($categories->isEmpty()) {
                return response()->json([
                    'status_code' => 404,
                    'success' => false,
                    'message' => 'No activity categories found'
                ], 404);
            }
            
            return response()->json([
                'status_code' => 200,
                'success' => true,
                'message' => 'Activity categories retrieved successfully',
                'data' => $categories
            ], 200);
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
        'category' => 'required|string|max:255'
        ]);

       $activities = PostActivity::where('category', $validatedData['category'])->get();
        if($activities->isEmpty()) {
            return response()->json([
                'status_code' => 404,
                'success' => false,
                'message' => 'No activities found by category name'
            ], 404);
        }

        return response()->json([
            'status_code' => 200,
            'success' => true,
            'message' => 'Activities retrieved successfully by category name',
            'data' => $this->mapActivities($activities)
        ], 200);
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
            'search' => 'required|string|max:255'
        ]);

        $feelings = PostFeeling::where('name', 'like', '%' . $validatedData['search'] . '%')->get();
        if($feelings->isEmpty()) {
            return response()->json([
                'status_code' => 404,
                'success' => false,
                'message' => 'No feelings found'
            ], 404);
        }   

        return response()->json([
            'status_code' => 200,
            'success' => true,
            'message' => 'Feelings retrieved successfully',
            'data' => $this->mapFeelings($feelings)
        ], 200);
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
                'search' => 'required|string|max:255'
            ]);

            $categories = PostActivity::where('status', 'active')
                ->where('category', 'like', '%' . $validatedData['search'] . '%')
                ->distinct()
                ->pluck('category')
                ->filter()
                ->values();
                
            if($categories->isEmpty()) {
                return response()->json([
                    'status_code' => 404,
                    'success' => false,
                    'message' => 'No categories found matching your search'
                ], 404);
            }

            return response()->json([
                'status_code' => 200,
                'success' => true,
                'message' => 'Categories retrieved successfully',
                'data' => $categories
            ], 200);
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
            'search' => 'required|string|max:255'
        ]);

        $activities = PostActivity::where('name', 'like', '%' . $validatedData['search'] . '%')->get();
        if($activities->isEmpty()) {
            return response()->json([
                'status_code' => 404,
                'success' => false,
                'message' => 'No activities found'
            ], 404);
        }

        return response()->json([
            'status_code' => 200,
            'success' => true,
            'message' => 'Activities retrieved successfully',
            'data' => $this->mapActivities($activities)
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

    public function getFriends()
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

            // Get all friends
            $friends = User::whereIn('id', $friendIds)->get();

            // Get specific friends IDs
            $specificFriendIds = \App\Models\SpecificFriends::where('user_id', $user->id)
                ->pluck('friend_id')
                ->toArray();

            // Get friend except IDs
            $friendExceptIds = \App\Models\FriendExcept::where('user_id', $user->id)
                ->pluck('friend_id')
                ->toArray();

            // Map friends with specific/except flags
            $mappedFriends = $friends->map(function ($friend) use ($specificFriendIds, $friendExceptIds) {
                $friendData = $this->mapUserDetails($friend);
                $friendData['is_specific_friend'] = in_array($friend->id, $specificFriendIds);
                $friendData['is_friend_except'] = in_array($friend->id, $friendExceptIds);
                return $friendData;
            })->values();

            return response()->json([
                'status_code' => 200,
                'success' => true,
                'message' => 'Friends retrieved successfully',
                'data' => $mappedFriends
            ], 200);

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
                'search' => 'required|string|max:255'
            ]);

            $user = Auth::guard('user')->user();

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

            // Search among friends by first_name, last_name, or email
            $friends = User::whereIn('id', $friendIds)
                ->where(function($query) use ($validatedData) {
                    $query->where('first_name', 'like', '%' . $validatedData['search'] . '%')
                          ->orWhere('last_name', 'like', '%' . $validatedData['search'] . '%')
                          ->orWhere('email', 'like', '%' . $validatedData['search'] . '%')
                          ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ['%' . $validatedData['search'] . '%']);
                })
                ->get();

            if ($friends->isEmpty()) {
                return response()->json([
                    'status_code' => 404,
                    'success' => false,
                    'message' => 'No friends found matching your search'
                ], 404);
            }

            // Get specific friends IDs
            $specificFriendIds = \App\Models\SpecificFriends::where('user_id', $user->id)
                ->pluck('friend_id')
                ->toArray();

            // Get friend except IDs
            $friendExceptIds = \App\Models\FriendExcept::where('user_id', $user->id)
                ->pluck('friend_id')
                ->toArray();

            // Map friends with specific/except flags
            $mappedFriends = $friends->map(function ($friend) use ($specificFriendIds, $friendExceptIds) {
                $friendData = $this->mapUserDetails($friend);
                $friendData['is_specific_friend'] = in_array($friend->id, $specificFriendIds);
                $friendData['is_friend_except'] = in_array($friend->id, $friendExceptIds);
                return $friendData;
            })->values();

            return response()->json([
                'status_code' => 200,
                'success' => true,
                'message' => 'Friends found successfully',
                'data' => $mappedFriends
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

    public function getPersonalOccasionSettings()
    {
        try{
            $personalOccasionSettings = PersonalOccasionSetting::where('status', 'active')->get();
            
            if($personalOccasionSettings->isEmpty()) {
                return response()->json([
                    'status_code' => 404,
                    'success' => false,
                    'message' => 'No personal occasion settings found'
                ], 404);
            }

            return response()->json([
                'status_code' => 200,
                'success' => true,
                'message' => 'Personal occasion settings retrieved successfully',
                'data' => $this->mapPersonalOccasionSettings($personalOccasionSettings)
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'status_code' => 500,
                'success' => false,
                'message' => 'Failed to retrieve personal occasion settings',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getUserPlaces()
    {
        try{
            $userPlaces = UserPlace::where('user_id', Auth::guard('user')->user()->id)->where('status', 'active')->get();

            if($userPlaces->isEmpty()) {
                return response()->json([
                    'status_code' => 404,
                    'success' => false,
                    'message' => 'No user places found'
                ], 404);
            }

            return response()->json([
                'status_code' => 200,
                'success' => true,
                'message' => 'User places retrieved successfully',
                'data' => $this->mapUserPlaces($userPlaces)
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'status_code' => 500,
                'success' => false,
                'message' => 'Failed to retrieve user places',
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

    public function getUserPlaceById(Request $request)
    {
        try{
            $validatedData = $request->validate([
                'place_id' => 'required|exists:user_places,id',
            ]);
            $userPlace = UserPlace::where('user_id', Auth::guard('user')->user()->id)->findOrFail($validatedData['place_id']);

            return response()->json([
                'status_code' => 200,
                'success' => true,
                'message' => 'User place retrieved successfully',
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
                'message' => 'Failed to retrieve user place',
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
            ]);
            $userPlaces = UserPlace::where('user_id', Auth::guard('user')->user()->id)->where('status', 'active')->where('name', 'like', '%' . $validatedData['search'] . '%')->get();

            if($userPlaces->isEmpty()) {
                return response()->json([
                    'status_code' => 404,
                    'success' => false,
                    'message' => 'No user places found'
                ], 404);
            }

            return response()->json([
                'status_code' => 200,
                'success' => true,
                'message' => 'User places retrieved successfully',
                'data' => $this->mapUserPlaces($userPlaces)
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
                'mentions.place' => 'nullable|integer|exists:user_places,id',
                'mentions.feeling' => 'nullable|string|max:100',
                'mentions.activity' => 'nullable|string|max:100',
            ]);

            // Custom validation for mentions
            $mentions = $request->input('mentions', []);
            $hasFeeling = !empty($mentions['feeling']);
            $hasActivity = !empty($mentions['activity']);
            $hasPlace = !empty($mentions['place']);
            
            if ($hasFeeling && $hasActivity) {
                return response()->json([
                    'status_code' => 422,
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => ['mentions' => ['You cannot specify both feeling and activity at the same time.']]
                ], 422);
            }

            // Validate user place ownership
            if ($hasPlace && !empty($mentions['place'])) {
                $user = Auth::guard('user')->user();
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
