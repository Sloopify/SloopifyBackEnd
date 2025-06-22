<?php

namespace App\Http\Controllers\Api\V1\User\Story;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use App\Models\Story;
use App\Models\StoryMedia;
use App\Models\StoryView;
use App\Models\StoryReply;
use App\Models\StoryPollVote;
use App\Models\StoryAudio;
use App\Models\User;
use App\Models\UserPlace;
use App\Models\PostFeeling;
use App\Models\Friendship;
use Carbon\Carbon;
use Exception;
use App\Http\Controllers\Api\V1\User\Post\PostController;

class StoryController extends Controller
{

         // Helper methods
    private function getVideoDuration($videoPath)
    {
        try {
            // Using FFmpeg probe to get video duration
            $command = "ffprobe -v quiet -show_entries format=duration -of csv=\"p=0\" " . escapeshellarg($videoPath);
            $duration = shell_exec($command);
            return (float) trim($duration);
        } catch (Exception $e) {
            // Fallback: if ffprobe is not available, allow the video (return 0)
            return 0;
        }
    }

    private function mapStory($story, $currentUser = null)
     {
         $currentUser = $currentUser ?: Auth::guard('user')->user();
         
         return [
             'id' => $story->id,
             'user' => $this->mapUserDetails($story->user),
             'content' => $story->content,
             'text_properties' => $story->text_properties,
             'background_color' => $story->background_color,
             'privacy' => $story->privacy,
             'gif_url' => $story->gif_url,
             'is_video_muted' => $story->is_video_muted,
             'location_element' => $story->location_element,
             'mentions_elements' => $story->mentions_elements,
             'clock_element' => $story->clock_element,
             'feeling_element' => $story->feeling_element,
             'temperature_element' => $story->temperature_element,
             'audio_element' => $story->audio_element,
             'poll_element' => $story->poll_element,
             'media' => $story->media->map(function ($media) {
                 return [
                     'id' => $media->id,
                     'type' => $media->type,
                     'url' => $media->full_url,
                     'x_position' => $media->x_position,
                     'y_position' => $media->y_position,
                     'display_order' => $media->display_order,
                     'metadata' => $media->metadata
                 ];
             }),
             'views_count' => $story->views_count,
             'replies_count' => $story->replies_count,
             'has_viewed' => $story->hasBeenViewedBy($currentUser->id),
             'has_voted' => $story->hasVotedBy($currentUser->id),
             'poll_results' => $story->poll_results,
             'expires_at' => $story->expires_at,
             'is_expired' => $story->is_expired,
             'created_at' => $story->created_at
         ];
    }

 
    private function mapUserDetails($user)
    {
         return [
             'id' => $user->id,
             'first_name' => $user->first_name,
             'last_name' => $user->last_name,
             'email' => $user->email,
             'image' => $user->provider === 'google' ? $user->img : ($user->img ? config('app.url') . asset('storage/' . $user->img) : null)
         ];
    }

    private function mapStoryAudio($audio, $user = null)
    {
        return [
            'id' => $audio->id,
            'name' => $audio->name,
            'filename' => $audio->filename,
            'file_url' => $audio->path ? asset('storage/' . $audio->path) : null,
            'duration' => $audio->duration,
            'duration_formatted' => $this->formatDuration($audio->duration),
            'file_size' => $audio->file_size,
            'file_size_formatted' => $this->formatFileSize($audio->file_size),
            'mime_type' => $audio->mime_type,
            'image' => $audio->image ? asset('storage/' . $audio->image) : null,
            'category' => $audio->category,
            'status' => $audio->status,
            'created_at' => $audio->created_at,
            'updated_at' => $audio->updated_at
        ];
    }

    private function formatDuration($seconds)
    {
        if (!$seconds) return '00:00';
        
        $minutes = floor($seconds / 60);
        $remainingSeconds = $seconds % 60;
        
        return sprintf('%02d:%02d', $minutes, $remainingSeconds);
    }

    private function formatFileSize($bytes)
    {
        if ($bytes == 0) return '0 B';
        
        $units = ['B', 'KB', 'MB', 'GB'];
        $power = floor(log($bytes, 1024));
        $power = min($power, count($units) - 1);
        
        $size = $bytes / pow(1024, $power);
        
        return round($size, 2) . ' ' . $units[$power];
    }

    public function createStory(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'content' => 'nullable|string|max:5000',
                
                // Text styling
                'text_properties' => 'nullable|array',
                'text_properties.color' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
                'text_properties.font_type' => 'nullable|string|max:50',
                'text_properties.bold' => 'nullable|boolean',
                'text_properties.italic' => 'nullable|boolean',
                'text_properties.underline' => 'nullable|boolean',
                'text_properties.alignment' => 'nullable|in:left,center,right',
                
                // Background colors
                'background_color' => 'nullable|array|max:10',
                'background_color.*' => 'string|regex:/^#[0-9A-Fa-f]{6}$/',
                
                // Privacy (without only_me)
                'privacy' => 'required|in:public,friends,specific_friends,friend_except',
                'specific_friends' => 'nullable|array',
                'specific_friends.*' => 'exists:users,id',
                'friend_except' => 'nullable|array',
                'friend_except.*' => 'exists:users,id',
                
                // Media
                'media' => 'nullable|array|max:10',
                'media.*.file' => 'required|file|mimes:jpeg,png,gif,mp4,avi|max:51200',
                'media.*.x_position' => 'nullable|numeric|min:0|max:100',
                'media.*.y_position' => 'nullable|numeric|min:0|max:100',
                'media.*.display_order' => 'nullable|integer|min:1',
                
                                // Story elements with positioning
                'location_element' => 'nullable|array',
                'location_element.id' => 'nullable|exists:user_places,id',
                'location_element.x' => 'required_with:location_element|numeric|min:0|max:100',
                'location_element.y' => 'required_with:location_element|numeric|min:0|max:100',
                'location_element.theme' => 'nullable|in:theme_1,theme_2,theme_3,theme_4',
                'location_element.size' => 'nullable|numeric|min:10|max:200',
                
                'mentions_elements' => 'nullable|array|max:10',
                'mentions_elements.*.friend_id' => 'required|exists:users,id',
                'mentions_elements.*.x' => 'required|numeric|min:0|max:100',
                'mentions_elements.*.y' => 'required|numeric|min:0|max:100',
                'mentions_elements.*.theme' => 'nullable|in:theme_1,theme_2,theme_3,theme_4',
                'mentions_elements.*.size' => 'nullable|numeric|min:10|max:200',
                
                'clock_element' => 'nullable|array',
                'clock_element.x' => 'required_with:clock_element|numeric|min:0|max:100',
                'clock_element.y' => 'required_with:clock_element|numeric|min:0|max:100',
                'clock_element.theme' => 'nullable|in:theme_1,theme_2,theme_3,theme_4',
                'clock_element.size' => 'nullable|numeric|min:10|max:200',
                
                'feeling_element' => 'nullable|array',
                'feeling_element.feeling_id' => 'required_with:feeling_element|exists:post_feelings,id',
                'feeling_element.x' => 'required_with:feeling_element|numeric|min:0|max:100',
                'feeling_element.y' => 'required_with:feeling_element|numeric|min:0|max:100',
                'feeling_element.theme' => 'nullable|in:theme_1,theme_2,theme_3,theme_4',
                'feeling_element.size' => 'nullable|numeric|min:10|max:200',
                
                'temperature_element' => 'nullable|array',
                'temperature_element.x' => 'required_with:temperature_element|numeric|min:0|max:100',
                'temperature_element.y' => 'required_with:temperature_element|numeric|min:0|max:100',
                'temperature_element.value' => 'nullable|numeric|min:-50|max:60',
                'temperature_element.theme' => 'nullable|in:theme_1,theme_2,theme_3,theme_4',
                'temperature_element.size' => 'nullable|numeric|min:10|max:200',
                
                'audio_element' => 'nullable|array',
                'audio_element.audio_id' => 'nullable|exists:story_audio,id',
                'audio_element.x' => 'required_with:audio_element|numeric|min:0|max:100',
                'audio_element.y' => 'required_with:audio_element|numeric|min:0|max:100',
                'audio_element.theme' => 'nullable|in:theme_1,theme_2,theme_3,theme_4',
                'audio_element.size' => 'nullable|numeric|min:10|max:200',
                
                'poll_element' => 'nullable|array',
                'poll_element.x' => 'required_with:poll_element|numeric|min:0|max:100',
                'poll_element.y' => 'required_with:poll_element|numeric|min:0|max:100',
                'poll_element.question' => 'required_with:poll_element|string|max:500',
                'poll_element.options' => 'required_with:poll_element|array|min:2|max:5',
                'poll_element.options.*' => 'string|max:255',
                'poll_element.theme' => 'nullable|in:theme_1,theme_2,theme_3,theme_4',
                'poll_element.size' => 'nullable|numeric|min:10|max:200',
                
                // Other options
                'gif_url' => 'nullable|url|max:2048',
                'is_video_muted' => 'nullable|boolean'
            ]);

            $user = Auth::guard('user')->user();

            // Validate video duration (max 30 seconds)
            if (!empty($validatedData['media'])) {
                foreach ($validatedData['media'] as $index => $mediaItem) {
                    $file = $mediaItem['file'];
                    if (strpos($file->getMimeType(), 'video') !== false) {
                        $videoDuration = $this->getVideoDuration($file->getPathname());
                        if ($videoDuration > 30) {
                            return response()->json([
                                'status_code' => 422,
                                'success' => false,
                                'message' => 'Video duration exceeds 30 seconds limit',
                                'errors' => ['media.' . $index . '.file' => ['Video must be 30 seconds or less']]
                            ], 422);
                        }
                    }
                }
            }

            // Validate friendship for mentioned friends
            if (!empty($validatedData['mentions_elements'])) {
                foreach ($validatedData['mentions_elements'] as $mention) {
                    if (!$user->isFriendsWith($mention['friend_id'])) {
                        return response()->json([
                            'status_code' => 422,
                            'success' => false,
                            'message' => 'You can only mention friends',
                            'errors' => ['mentions_elements' => ['Friend ID ' . $mention['friend_id'] . ' is not your friend']]
                        ], 422);
                    }
                }
            }

            // Validate location ownership
            if (!empty($validatedData['location_element']) && !empty($validatedData['location_element']['id'])) {
                $userPlace = UserPlace::where('id', $validatedData['location_element']['id'])
                    ->where('user_id', $user->id)
                    ->where('status', 'active')
                    ->first();

                if (!$userPlace) {
                    return response()->json([
                        'status_code' => 422,
                        'success' => false,
                        'message' => 'Location does not belong to you',
                        'errors' => ['location_element.id' => ['Invalid location']]
                    ], 422);
                }
            }

            // Validate friendship for privacy settings
            $this->validatePrivacyFriendships($validatedData, $user);

            DB::beginTransaction();

            // Create story
            $story = Story::create([
                'user_id' => $user->id,
                'content' => $validatedData['content'] ?? null,
                'text_properties' => $validatedData['text_properties'] ?? null,
                'background_color' => $validatedData['background_color'] ?? null,
                'privacy' => $validatedData['privacy'],
                'specific_friends' => $validatedData['privacy'] === 'specific_friends' ? ($validatedData['specific_friends'] ?? null) : null,
                'friend_except' => $validatedData['privacy'] === 'friend_except' ? ($validatedData['friend_except'] ?? null) : null,
                'gif_url' => $validatedData['gif_url'] ?? null,
                'is_video_muted' => $validatedData['is_video_muted'] ?? false,
                'location_element' => $validatedData['location_element'] ?? null,
                'mentions_elements' => $validatedData['mentions_elements'] ?? null,
                'clock_element' => $validatedData['clock_element'] ?? null,
                'feeling_element' => $validatedData['feeling_element'] ?? null,
                'temperature_element' => $validatedData['temperature_element'] ?? null,
                'audio_element' => $validatedData['audio_element'] ?? null,
                'poll_element' => $validatedData['poll_element'] ?? null,
                'expires_at' => Carbon::now()->addHours(24),
                'status' => 'active'
            ]);

            // Handle media uploads
            if (!empty($validatedData['media'])) {
                $this->handleStoryMediaUploads($story, $validatedData['media']);
            }

            DB::commit();

            // Load relationships for response
            $story->load(['user', 'media']);

            return response()->json([
                'status_code' => 200,
                'success' => true,
                'message' => 'Story created successfully',
                'data' => $this->mapStory($story)
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
                'message' => 'Failed to create story',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    private function validatePrivacyFriendships($validatedData, $user)
    {
        // Validate friendship for specific_friends privacy
        if ($validatedData['privacy'] === 'specific_friends' && !empty($validatedData['specific_friends'])) {
            foreach ($validatedData['specific_friends'] as $friendId) {
                if (!$user->isFriendsWith($friendId)) {
                    throw ValidationException::withMessages([
                        'specific_friends' => ['Friend ID ' . $friendId . ' is not your friend']
                    ]);
                }
            }
        }

        // Validate friendship for friend_except privacy
        if ($validatedData['privacy'] === 'friend_except' && !empty($validatedData['friend_except'])) {
            foreach ($validatedData['friend_except'] as $friendId) {
                if (!$user->isFriendsWith($friendId)) {
                    throw ValidationException::withMessages([
                        'friend_except' => ['Friend ID ' . $friendId . ' is not your friend']
                    ]);
                }
            }
        }
    }

    private function handleStoryMediaUploads($story, $mediaItems)
    {
        foreach ($mediaItems as $index => $mediaItem) {
            $file = $mediaItem['file'];
            $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('stories/' . $story->id, $filename, 'public');

            StoryMedia::create([
                'story_id' => $story->id,
                'type' => strpos($file->getMimeType(), 'image') !== false ? 'image' : 'video',
                'filename' => $filename,
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize(),
                'path' => $path,
                'url' => Storage::url($path),
                'display_order' => $mediaItem['display_order'] ?? ($index + 1),
                'x_position' => $mediaItem['x_position'] ?? 0,
                'y_position' => $mediaItem['y_position'] ?? 0,
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
        }
        
        return $metadata;
    }

   
    public function getFriends(Request $request)
    {
        try {
            $postController = app(PostController::class);
            return $postController->getFriends($request);
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
            $postController = app(PostController::class);
            return $postController->searchFriends($request);
        } catch (Exception $e) {
            return response()->json([
                'status_code' => 500,
                'success' => false,
                'message' => 'Failed to search friends',
                'error' => $e->getMessage()
            ], 500);
        }
    }
 
    public function getUserPlaces(Request $request)
    {
        try {
            $postController = app(PostController::class);
            return $postController->getUserPlaces($request);
        } catch (Exception $e) {
            return response()->json([
                'status_code' => 500,
                'success' => false,
                'message' => 'Failed to retrieve user places',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function searchUserPlaces(Request $request)
    {
        try {
            $postController = app(PostController::class);
            return $postController->searchUserPlaces($request);
        } catch (Exception $e) {
            return response()->json([
                'status_code' => 500,
                'success' => false,
                'message' => 'Failed to search user places',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getUserPlaceById(Request $request)
    {
        try {
            $postController = app(PostController::class);
            return $postController->getUserPlaceById($request);
        } catch (Exception $e) {
            return response()->json([
                'status_code' => 500,
                'success' => false,
                'message' => 'Failed to retrieve user place by id',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function createUserPlace(Request $request)
    {
        try {
            $postController = app(PostController::class);
            return $postController->createUserPlace($request);
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
        try {
            $postController = app(PostController::class);
            return $postController->updateUserPlace($request);
        } catch (Exception $e) {
            return response()->json([
                'status_code' => 500,
                'success' => false,
                'message' => 'Failed to update user place',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getFeeling(Request $request)
    {
        try {
            $postController = app(PostController::class);
            return $postController->getFeeling($request);
        }
        catch (Exception $e) {
            return response()->json([
                'status_code' => 500,
                'success' => false,
                'message' => 'Failed to retrieve feeling',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function searchFeeling(Request $request)
    {
        try {
            $postController = app(PostController::class);
            return $postController->searchFeeling($request);
        }
        catch (Exception $e) {
            return response()->json([
                'status_code' => 500,
                'success' => false,
                'message' => 'Failed to search feeling',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getStoryAudio(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'page' => 'nullable|integer|min:1',
                'per_page' => 'nullable|integer|min:1|max:100'
            ]);

            $user = Auth::guard('user')->user();
            $perPage = $validatedData['per_page'] ?? 20;
            $audioFiles = StoryAudio::active()->paginate($perPage);

            $mappedAudio = $audioFiles->map(function ($audio) use ($user) {
                return $this->mapStoryAudio($audio, $user);
            });

            return response()->json([
                'status_code' => 200,
                'success' => true,
                'message' => 'Story audio retrieved successfully',
                'data' => $mappedAudio,
                'pagination' => [
                    'current_page' => $audioFiles->currentPage(),
                    'last_page' => $audioFiles->lastPage(),
                    'per_page' => $audioFiles->perPage(),
                    'total' => $audioFiles->total(),
                    'from' => $audioFiles->firstItem(),
                    'to' => $audioFiles->lastItem(),
                    'has_more_pages' => $audioFiles->hasMorePages()
                ]
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'status_code' => 500,
                'success' => false,
                'message' => 'Failed to retrieve story audio',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function searchStoryAudio(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'search' => 'nullable|string|max:255',
                'page' => 'nullable|integer|min:1',
                'per_page' => 'nullable|integer|min:1|max:100'
            ]);

            $user = Auth::guard('user')->user();
            $perPage = $validatedData['per_page'] ?? 20;
            $audioFiles = StoryAudio::active()->where('name', 'like', '%' . $validatedData['search'] . '%')->paginate($perPage);

            $mappedAudio = $audioFiles->map(function ($audio) use ($user) {
                return $this->mapStoryAudio($audio, $user);
            });

            return response()->json([
                'status_code' => 200,
                'success' => true,
                'message' => 'Story audio retrieved successfully',
                'data' => $mappedAudio,
                'pagination' => [
                    'current_page' => $audioFiles->currentPage(),
                    'last_page' => $audioFiles->lastPage(),
                    'per_page' => $audioFiles->perPage(),
                    'total' => $audioFiles->total(),
                    'from' => $audioFiles->firstItem(),
                    'to' => $audioFiles->lastItem(),
                    'has_more_pages' => $audioFiles->hasMorePages()
                ]
            ], 200);
        }
        catch (Exception $e) {
            return response()->json([
                'status_code' => 500,
                'success' => false,
                'message' => 'Failed to search story audio',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getStoryViewers(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'story_id' => 'required|integer|exists:stories,id',
                'page' => 'nullable|integer|min:1',
                'per_page' => 'nullable|integer|min:1|max:100'
            ]);

            $user = Auth::guard('user')->user();
            $perPage = $validatedData['per_page'] ?? 20;
            $story = Story::where('user_id', $user->id)->findOrFail($validatedData['story_id']);

            $viewers = StoryView::with('viewer')
                ->where('story_id', $story->id)
                ->orderBy('viewed_at', 'desc')
                ->paginate($perPage);

            $mappedViewers = $viewers->map(function ($view) use ($story) {
                // Get replies from this viewer for this story
                $replies = StoryReply::with('user')
                    ->where('story_id', $story->id)
                    ->where('user_id', $view->viewer_id)
                    ->orderBy('created_at', 'desc')
                    ->get();

                $mappedReplies = $replies->map(function ($reply) {
                    return [
                        'id' => $reply->id,
                        'reply_text' => $reply->reply_text,
                        'reply_media_url' => $reply->reply_media_url,
                        'reply_type' => $reply->reply_type,
                        'emoji' => $reply->emoji,
                        'created_at' => $reply->created_at
                    ];
                });

                return [
                    'user' => $this->mapUserDetails($view->viewer),
                    'viewed_at' => $view->viewed_at,
                    'replies' => $mappedReplies
                ];
            });

            return response()->json([
                'status_code' => 200,
                'success' => true,
                'message' => 'Story viewers retrieved successfully',
                'data' => $mappedViewers,
                'count' => $viewers->total(),
                'pagination' => [
                    'current_page' => $viewers->currentPage(),
                    'last_page' => $viewers->lastPage(),
                    'per_page' => $viewers->perPage(),
                    'total' => $viewers->total(),
                    'from' => $viewers->firstItem(),
                    'to' => $viewers->lastItem(),
                    'has_more_pages' => $viewers->hasMorePages()
                ]
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'status_code' => 500,
                'success' => false,
                'message' => 'Failed to retrieve story viewers',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function searchStoryViewers(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'story_id' => 'required|integer|exists:stories,id',
                'search' => 'nullable|string|max:255',
                'page' => 'nullable|integer|min:1',
                'per_page' => 'nullable|integer|min:1|max:100'
            ]);

            $user = Auth::guard('user')->user();
            $perPage = $validatedData['per_page'] ?? 20;
            $story = Story::where('user_id', $user->id)->findOrFail($validatedData['story_id']);
            $search = $validatedData['search'] ?? null;

                         $viewers = StoryView::with('viewer')
                 ->where('story_id', $story->id)
                 ->whereHas('viewer', function ($query) use ($search) {
                     if ($search) {
                         $query->where(function ($q) use ($search) {
                             $q->where('first_name', 'like', '%' . $search . '%')
                               ->orWhere('last_name', 'like', '%' . $search . '%')
                               ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ['%' . $search . '%']);
                         });
                     }
                 })
                 ->orderBy('viewed_at', 'desc')
                 ->paginate($perPage);

            $mappedViewers = $viewers->map(function ($view) use ($story) {
                // Get replies from this viewer for this story
                $replies = StoryReply::with('user')
                    ->where('story_id', $story->id)
                    ->where('user_id', $view->viewer_id)
                    ->orderBy('created_at', 'desc')
                    ->get();

                $mappedReplies = $replies->map(function ($reply) {
                    return [
                        'id' => $reply->id,
                        'reply_text' => $reply->reply_text,
                        'reply_media_url' => $reply->reply_media_url,
                        'reply_type' => $reply->reply_type,
                        'emoji' => $reply->emoji,
                        'created_at' => $reply->created_at
                    ];
                });

                return [
                    'user' => $this->mapUserDetails($view->viewer),
                    'viewed_at' => $view->viewed_at,
                    'replies' => $mappedReplies
                ];
            });

            return response()->json([
                'status_code' => 200,
                'success' => true,
                'message' => 'Story viewers retrieved successfully',
                'data' => $mappedViewers,
                'pagination' => [
                    'current_page' => $viewers->currentPage(),
                    'last_page' => $viewers->lastPage(),
                    'per_page' => $viewers->perPage(),
                    'total' => $viewers->total(),
                    'from' => $viewers->firstItem(),
                    'to' => $viewers->lastItem(),
                    'has_more_pages' => $viewers->hasMorePages()
                ]
            ], 200);
        }
        catch (Exception $e) {
            return response()->json([
                'status_code' => 500,
                'success' => false,
                'message' => 'Failed to search story viewers',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function deleteStory(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'story_id' => 'required|integer|exists:stories,id'
            ]);

            $user = Auth::guard('user')->user();
            $story = Story::where('user_id', $user->id)->findOrFail($validatedData['story_id']);

            // Delete media files
            foreach ($story->media as $media) {
                Storage::disk('public')->delete($media->path);
            }

            $story->update(['status' => 'deleted']);

            return response()->json([
                'status_code' => 200,
                'success' => true,
                'message' => 'Story deleted successfully'
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'status_code' => 404,
                'success' => false,
                'message' => 'Story not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    public function getStoryPollResults(Request $request)   
    {
        try {
            $validatedData = $request->validate([
                'story_id' => 'required|integer|exists:stories,id',
                'page' => 'nullable|integer|min:1',
                'per_page' => 'nullable|integer|min:1|max:100'
            ]);
            $user = Auth::guard('user')->user();
            $story = Story::where('user_id', $user->id)->findOrFail($validatedData['story_id']);
            $perPage = $validatedData['per_page'] ?? 20;
            if (!$story->poll_element) {
                return response()->json([
                    'status_code' => 400,
                    'success' => false,
                    'message' => 'This story does not have a poll'
                ], 400);
            }

            $pollVotes = StoryPollVote::with('user')
                ->where('story_id', $story->id)
                ->paginate($perPage);

            $mappedPollVotes = $pollVotes->map(function ($vote) use ($user) {
                return $this->mapStoryPollVote($vote, $user);
            });

            return response()->json([
                'status_code' => 200,
                'success' => true,
                'message' => 'Poll results retrieved successfully',
                'data' => $mappedPollVotes,
                'pagination' => [
                    'current_page' => $pollVotes->currentPage(),
                    'last_page' => $pollVotes->lastPage(),
                    'per_page' => $pollVotes->perPage(),
                    'total' => $pollVotes->total(),
                    'from' => $pollVotes->firstItem(),
                    'to' => $pollVotes->lastItem(),
                    'has_more_pages' => $pollVotes->hasMorePages()
                ]
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'status_code' => 404,
                'success' => false,
                'message' => 'Story not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    // public function getStoryReplies(Request $request)
    // {
    //     try {
    //         $validatedData = $request->validate([
    //             'story_id' => 'required|integer|exists:stories,id'
    //         ]);
    //         $user = Auth::guard('user')->user();
    //         $story = Story::where('user_id', $user->id)->findOrFail($validatedData['story_id']);

    //         $replies = StoryReply::with('user')
    //             ->where('story_id', $story->id)
    //             ->orderBy('created_at', 'desc')
    //             ->get();

    //         $mappedReplies = $replies->map(function ($reply) {
    //             return [
    //                 'id' => $reply->id,
    //                 'reply_text' => $reply->reply_text,
    //                 'reply_media_url' => $reply->reply_media_url,
    //                 'reply_type' => $reply->reply_type,
    //                 'emoji' => $reply->emoji,
    //                 'user' => $this->mapUserDetails($reply->user),
    //                 'created_at' => $reply->created_at
    //             ];
    //         });

    //         return response()->json([
    //             'status_code' => 200,
    //             'success' => true,
    //             'message' => 'Story replies retrieved successfully',
    //             'data' => $mappedReplies
    //         ], 200);

    //     } catch (Exception $e) {
    //         return response()->json([
    //             'status_code' => 404,
    //             'success' => false,
    //             'message' => 'Story not found',
    //             'error' => $e->getMessage()
    //         ], 404);
    //     }
    // }







    public function getStories(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'page' => 'nullable|integer|min:1',
                'per_page' => 'nullable|integer|min:1|max:100'
            ]);

            $user = Auth::guard('user')->user();
            $perPage = $validatedData['per_page'] ?? 20;

            $stories = Story::with(['user', 'media', 'views'])
                ->active()
                ->visibleTo($user->id)
                ->orderBy('created_at', 'desc')
                ->paginate($perPage);

            $mappedStories = $stories->getCollection()->map(function ($story) use ($user) {
                return $this->mapStory($story, $user);
            });

            return response()->json([
                'status_code' => 200,
                'success' => true,
                'message' => 'Stories retrieved successfully',
                'data' => $mappedStories,
                'pagination' => [
                    'current_page' => $stories->currentPage(),
                    'last_page' => $stories->lastPage(),
                    'per_page' => $stories->perPage(),
                    'total' => $stories->total(),
                    'has_more_pages' => $stories->hasMorePages()
                ]
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'status_code' => 500,
                'success' => false,
                'message' => 'Failed to retrieve stories',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function viewStory($storyId)
    {
        try {
            $user = Auth::guard('user')->user();
            $story = Story::with(['user', 'media', 'views', 'replies'])
                ->active()
                ->visibleTo($user->id)
                ->findOrFail($storyId);

            // Record view if not already viewed
            if (!$story->hasBeenViewedBy($user->id)) {
                StoryView::create([
                    'story_id' => $story->id,
                    'viewer_id' => $user->id,
                    'viewed_at' => now()
                ]);
            }

            return response()->json([
                'status_code' => 200,
                'success' => true,
                'message' => 'Story retrieved successfully',
                'data' => $this->mapStory($story, $user)
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'status_code' => 404,
                'success' => false,
                'message' => 'Story not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }


    public function replyToStory(Request $request, $storyId)
    {
        try {
            $validatedData = $request->validate([
                'reply_text' => 'nullable|string|max:1000',
                'reply_media' => 'nullable|file|mimes:jpeg,png,gif,mp4|max:25600',
                'reply_type' => 'required|in:text,media,emoji',
                'emoji' => 'nullable|string|max:10'
            ]);

            $user = Auth::guard('user')->user();
            $story = Story::active()->visibleTo($user->id)->findOrFail($storyId);

            $replyMediaPath = null;
            if ($request->hasFile('reply_media')) {
                $file = $request->file('reply_media');
                $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                $replyMediaPath = $file->storeAs('story_replies', $filename, 'public');
            }

            $reply = StoryReply::create([
                'story_id' => $story->id,
                'user_id' => $user->id,
                'reply_text' => $validatedData['reply_text'] ?? null,
                'reply_media_path' => $replyMediaPath,
                'reply_type' => $validatedData['reply_type'],
                'emoji' => $validatedData['emoji'] ?? null
            ]);

            return response()->json([
                'status_code' => 200,
                'success' => true,
                'message' => 'Reply sent successfully',
                'data' => [
                    'id' => $reply->id,
                    'reply_text' => $reply->reply_text,
                    'reply_media_url' => $reply->reply_media_url,
                    'reply_type' => $reply->reply_type,
                    'emoji' => $reply->emoji,
                    'user' => $this->mapUserDetails($reply->user),
                    'created_at' => $reply->created_at
                ]
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'status_code' => 500,
                'success' => false,
                'message' => 'Failed to send reply',
                'error' => $e->getMessage()
            ], 500);
        }
    }

  

    public function voteStoryPoll(Request $request, $storyId)
    {
        try {
            $validatedData = $request->validate([
                'selected_options' => 'required|array|min:1|max:5',
                'selected_options.*' => 'integer|min:0'
            ]);

            $user = Auth::guard('user')->user();
            $story = Story::active()->visibleTo($user->id)->findOrFail($storyId);

            if (!$story->poll_element) {
                return response()->json([
                    'status_code' => 400,
                    'success' => false,
                    'message' => 'This story does not have a poll'
                ], 400);
            }

            // Validate selected options
            $maxOption = count($story->poll_element['options']) - 1;
            foreach ($validatedData['selected_options'] as $option) {
                if ($option > $maxOption) {
                    return response()->json([
                        'status_code' => 400,
                        'success' => false,
                        'message' => 'Invalid poll option selected'
                    ], 400);
                }
            }

            // Create or update vote
            StoryPollVote::updateOrCreate(
                [
                    'story_id' => $story->id,
                    'user_id' => $user->id
                ],
                [
                    'selected_options' => $validatedData['selected_options']
                ]
            );

            return response()->json([
                'status_code' => 200,
                'success' => true,
                'message' => 'Vote recorded successfully',
                'data' => ['results' => $story->poll_results]
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'status_code' => 500,
                'success' => false,
                'message' => 'Failed to record vote',
                'error' => $e->getMessage()
            ], 500);
        }
    }

  

    public function muteStoryNotifications(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'muted_user_id' => 'required|exists:users,id',
                'mute_replies' => 'nullable|boolean',
                'mute_poll_votes' => 'nullable|boolean',
                'mute_all' => 'nullable|boolean'
            ]);

            $user = Auth::guard('user')->user();

            // Check if users are friends
            if (!$user->isFriendsWith($validatedData['muted_user_id'])) {
                return response()->json([
                    'status_code' => 422,
                    'success' => false,
                    'message' => 'You can only mute notifications from friends'
                ], 422);
            }

            DB::table('story_notification_settings')->updateOrInsert(
                [
                    'story_owner_id' => $user->id,
                    'muted_user_id' => $validatedData['muted_user_id']
                ],
                [
                    'mute_replies' => $validatedData['mute_replies'] ?? false,
                    'mute_poll_votes' => $validatedData['mute_poll_votes'] ?? false,
                    'mute_all' => $validatedData['mute_all'] ?? false,
                    'updated_at' => now()
                ]
            );

            return response()->json([
                'status_code' => 200,
                'success' => true,
                'message' => 'Story notification settings updated successfully'
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'status_code' => 500,
                'success' => false,
                'message' => 'Failed to update notification settings',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function hideStory(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'story_owner_id' => 'nullable|exists:users,id',
                'specific_story_id' => 'nullable|exists:stories,id',
                'hide_type' => 'required|in:permanent,30_days,specific_story'
            ]);

            $user = Auth::guard('user')->user();

            if ($validatedData['hide_type'] === 'specific_story' && empty($validatedData['specific_story_id'])) {
                return response()->json([
                    'status_code' => 422,
                    'success' => false,
                    'message' => 'Specific story ID is required for specific story hide type'
                ], 422);
            }

            if ($validatedData['hide_type'] !== 'specific_story' && empty($validatedData['story_owner_id'])) {
                return response()->json([
                    'status_code' => 422,
                    'success' => false,
                    'message' => 'Story owner ID is required for permanent or 30 days hide types'
                ], 422);
            }

            $expiresAt = null;
            if ($validatedData['hide_type'] === '30_days') {
                $expiresAt = Carbon::now()->addDays(30);
            }

            DB::table('story_hide_settings')->insert([
                'user_id' => $user->id,
                'story_owner_id' => $validatedData['story_owner_id'] ?? null,
                'specific_story_id' => $validatedData['specific_story_id'] ?? null,
                'hide_type' => $validatedData['hide_type'],
                'expires_at' => $expiresAt,
                'created_at' => now(),
                'updated_at' => now()
            ]);

            return response()->json([
                'status_code' => 200,
                'success' => true,
                'message' => 'Story hidden successfully'
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'status_code' => 500,
                'success' => false,
                'message' => 'Failed to hide story',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function unhideStory(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'story_owner_id' => 'nullable|exists:users,id',
                'specific_story_id' => 'nullable|exists:stories,id',
                'hide_type' => 'required|in:permanent,30_days,specific_story'
            ]);

            $user = Auth::guard('user')->user();

            $query = DB::table('story_hide_settings')
                ->where('user_id', $user->id)
                ->where('hide_type', $validatedData['hide_type']);

            if ($validatedData['hide_type'] === 'specific_story') {
                $query->where('specific_story_id', $validatedData['specific_story_id']);
            } else {
                $query->where('story_owner_id', $validatedData['story_owner_id']);
            }

            $query->delete();

            return response()->json([
                'status_code' => 200,
                'success' => true,
                'message' => 'Story unhidden successfully'
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'status_code' => 500,
                'success' => false,
                'message' => 'Failed to unhide story',
                'error' => $e->getMessage()
            ], 500);
        }
    }

   

   
} 