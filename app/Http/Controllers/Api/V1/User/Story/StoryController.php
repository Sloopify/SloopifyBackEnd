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
use App\Http\Controllers\Api\V1\User\Auth\AuthController;
use App\Models\StoryNotificationSetting;

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

    public function generateShareUrl($storyId)
    {
        // Generate a unique share URL using the story ID and a random string
        $baseUrl = config('app.url');
        $uniqueId = base64_encode($storyId . '_' . time() . '_' . rand(1000, 9999));
        $uniqueId = str_replace(['+', '/', '='], ['-', '_', ''], $uniqueId); // URL-safe base64
        
        return $baseUrl . '/story/' . $uniqueId;
    }

    public function isMobileAppRequest(Request $request)
    {
        $userAgent = $request->header('User-Agent', '');
        $acceptHeader = $request->header('Accept', '');
        
        // Check for custom headers that mobile app might send
        $appVersion = $request->header('X-App-Version');
        $appPlatform = $request->header('X-App-Platform');
        
        // Check if it's a mobile app request
        if ($appVersion || $appPlatform) {
            return true;
        }
        
        // Check for JSON accept header (mobile apps typically request JSON)
        if (strpos($acceptHeader, 'application/json') !== false && 
            strpos($acceptHeader, 'text/html') === false) {
            return true;
        }
        
        // Check for Flutter/Dart user agent
        if (strpos($userAgent, 'Dart') !== false || strpos($userAgent, 'Flutter') !== false) {
            return true;
        }
        
        return false;
    }

    public function isWebBrowserRequest(Request $request)
    {
        $userAgent = $request->header('User-Agent', '');
        $acceptHeader = $request->header('Accept', '');
        
        // Check for common web browser user agents
        $browserPatterns = [
            'Chrome', 'Firefox', 'Safari', 'Edge', 'Opera', 'MSIE', 'Trident'
        ];
        
        foreach ($browserPatterns as $pattern) {
            if (strpos($userAgent, $pattern) !== false) {
                return true;
            }
        }
        
        // Check for HTML accept header
        if (strpos($acceptHeader, 'text/html') !== false) {
            return true;
        }
        
        return false;
    }

    public function isSocialMediaRequest(Request $request)
    {
        $userAgent = $request->header('User-Agent', '');
        
        // Check for social media crawlers
        $socialMediaPatterns = [
            'facebookexternalhit', 'WhatsApp', 'Twitterbot', 'LinkedInBot',
            'TelegramBot', 'Instagram', 'Snapchat', 'Pinterest'
        ];
        
        foreach ($socialMediaPatterns as $pattern) {
            if (stripos($userAgent, $pattern) !== false) {
                return true;
            }
        }
        
        return false;
    }

    private function mapStoryForMobile($story)
    {
        // Simplified story data for mobile app
        return [
            'id' => $story->id,
            'title' => $this->generateStoryTitle($story),
            'description' => $this->generateStoryDescription($story),
            'image_url' => $this->getStoryMainImage($story),
            'user' => [
                'id' => $story->user->id,
                'name' => $story->user->first_name . ' ' . $story->user->last_name,
                'image' => $story->user->image
            ],
            'created_at' => $story->created_at,
            'expires_at' => $story->expires_at,
            'is_expired' => $story->is_expired,
            'share_url' => $story->share_url,
            'deep_link' => 'myapp://story/' . $story->id
        ];
    }

    private function generateStoryTitle($story)
    {
        // Generate a title based on story content
        if ($story->text_elements) {
            $textElements = is_string($story->text_elements) 
                ? json_decode($story->text_elements, true) 
                : $story->text_elements;
            
            if (is_array($textElements) && !empty($textElements)) {
                $firstText = $textElements[0]['text'] ?? '';
                return mb_substr($firstText, 0, 50) . (mb_strlen($firstText) > 50 ? '...' : '');
            }
        }
        
        return $story->user->first_name . "'s Story";
    }

    private function generateStoryDescription($story)
    {
        // Generate a description based on story content
        $description = '';
        
        if ($story->text_elements) {
            $textElements = is_string($story->text_elements) 
                ? json_decode($story->text_elements, true) 
                : $story->text_elements;
            
            if (is_array($textElements)) {
                foreach ($textElements as $element) {
                    if (isset($element['text'])) {
                        $description .= $element['text'] . ' ';
                    }
                }
            }
        }
        
        if (empty($description)) {
            $description = 'Check out this story from ' . $story->user->first_name . ' ' . $story->user->last_name;
        }
        
        return trim($description);
    }

    private function getStoryMainImage($story)
    {
        // Get the first image from story media
        if ($story->media && $story->media->count() > 0) {
            foreach ($story->media as $media) {
                if ($media->type === 'image') {
                    return $media->full_url;
                }
            }
        }
        
        // Return default image if no media
        return $story->user->image ?? config('app.url') . '/images/default-story.jpg';
    }

    private function renderStoryWebView($story)
    {
        // Prepare data for the Blade view
        $viewData = [
            'story' => $story,
            'title' => $this->generateStoryTitle($story),
            'description' => $this->generateStoryDescription($story),
            'image_url' => $this->getStoryMainImage($story),
            'user_name' => $story->user->first_name . ' ' . $story->user->last_name,
            'user_image' => $story->user->image,
            'created_at' => $story->created_at->format('M d, Y'),
            'deep_link' => 'myapp://story/' . $story->id,
            'app_store_url' => 'https://apps.apple.com/app/your-app-id', // Replace with actual app store URL
            'play_store_url' => 'https://play.google.com/store/apps/details?id=your.app.id', // Replace with actual play store URL
            'share_url' => $story->share_url
        ];
        
        return view('story.shared', $viewData);
    }

    private function mapStory($story, $currentUser = null)
     {
         $currentUser = $currentUser ?: Auth::guard('user')->user();
         
         // Helper function to decode JSON and convert numeric values
         $decodeAndConvert = function($data) {
             // Handle null values
             if (is_null($data)) {
                 return null;
             }
             
             // If it's a string, decode it first
             if (is_string($data)) {
                 $decoded = json_decode($data, true);
                 // If decoding failed, return null
                 if (json_last_error() !== JSON_ERROR_NONE) {
                     return null;
                 }
                 return $this->convertNumericValuesToDouble($decoded);
             }
             
             // If it's already an array, process it
             if (is_array($data)) {
                 return $this->convertNumericValuesToDouble($data);
             }
             
             // For any other type, return as is
             return $data;
         };
         
         return [
             'id' => $story->id,
             'user' => app(AuthController::class)->mapUserDetails($story->user),
             'privacy' => $story->privacy,
             'specific_friends' => $decodeAndConvert($story->specific_friends),
             'friend_except' => $decodeAndConvert($story->friend_except),
             'text_elements' => $decodeAndConvert($story->text_elements),
             'background_color' => $decodeAndConvert($story->background_color),
             'mentions_elements' => $decodeAndConvert($story->mentions_elements),
             'clock_element' => $decodeAndConvert($story->clock_element),
             'feeling_element' => $decodeAndConvert($story->feeling_element),
             'temperature_element' => $decodeAndConvert($story->temperature_element),
             'audio_element' => $decodeAndConvert($story->audio_element),
             'poll_element' => $decodeAndConvert($story->poll_element),
             'location_element' => $decodeAndConvert($story->location_element),
             'drawing_elements' => $decodeAndConvert($story->drawing_elements),
             'gif_element' => $decodeAndConvert($story->gif_element),
             'is_video_muted' => $story->is_video_muted,
             'is_story_muted_notification' => $story->is_story_muted_notification,
             'media' => $story->media->map(function ($media) {
                 return [
                     'id' => $media->id,
                     'type' => $media->type,
                     'url' =>  $media->full_url,
                     'order' => (float) $media->order,
                     'rotate_angle' => (float) $media->rotate_angle,
                     'scale' => (float) $media->scale,
                     'dx' => (float) $media->dx,
                     'dy' => (float) $media->dy,
                     // Ensure metadata numeric values (e.g., width, height) are floats
                     'metadata' => is_string($media->metadata)
                         ? $this->convertNumericValuesToDouble(json_decode($media->metadata, true))
                         : $this->convertNumericValuesToDouble($media->metadata)
                 ];
             }),
             'views_count' => $story->views_count,
             'replies_count' => $story->replies_count,
            // Owner should always be considered as having viewed their own story
            'has_viewed' => ($currentUser && $story->user_id === $currentUser->id)
                ? true
                : ($currentUser ? $story->hasBeenViewedBy($currentUser->id) : false),
             'has_voted' => $currentUser ? $story->hasVotedBy($currentUser->id) : false,
             'poll_results' => $story->poll_results,
             'share_url' => $story->share_url,
             'expires_at' => $story->expires_at,
             'is_expired' => $story->is_expired,
             'created_at' => $story->created_at
         ];
     }

    private function mapStoryAudio($audio, $user = null)
    {
        return [
            'id' => $audio->id,
            'name' => $audio->name,
            'filename' => $audio->filename,
            'file_url' => $audio->path ? config('app.url') . asset('storage/' . $audio->path) : null,
            'duration' => $audio->duration,
            'duration_formatted' => $this->formatDuration($audio->duration),
            'file_size' => $audio->file_size,
            'file_size_formatted' => $this->formatFileSize($audio->file_size),
            'mime_type' => $audio->mime_type,
            'image' => $audio->image ? config('app.url') . asset('storage/' . $audio->image) : null,
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

    private function convertNumericValuesToDouble($data)
    {
        if (is_null($data)) {
            return null;
        }

        if (is_array($data)) {
            // Convert array to JSON with preserved zero fractions, then decode back
            $jsonString = json_encode($data, JSON_PRESERVE_ZERO_FRACTION);
            $decoded = json_decode($jsonString, true);
            
            // Recursively process the decoded data to ensure all numeric values are floats
            return $this->forceFloatValues($decoded);
        }

        return $data;
    }

    private function forceFloatValues($data)
    {
        if (is_null($data)) {
            return null;
        }

        if (is_array($data)) {
            $converted = [];
            foreach ($data as $key => $value) {
                if (is_array($value)) {
                    $converted[$key] = $this->forceFloatValues($value);
                } elseif ($this->shouldKeepAsBoolean($key)) {
                    // Explicitly cast to boolean when key requires boolean type
                    $converted[$key] = (bool) $value;
                } elseif (is_bool($value)) {
                    $converted[$key] = $value;
                } elseif (is_numeric($value)) {
                    // Skip converting IDs and other fields that should remain integers
                    if ($this->shouldKeepAsInteger($key)) {
                        $converted[$key] = (int) $value;
                    } else {
                        // Force to float and ensure it stays as float
                        $floatValue = (float) $value;
                        // Add a tiny amount to ensure it's treated as float in JSON
                        $converted[$key] = $floatValue + 0.0;
                    }
                } else {
                    $converted[$key] = $value;
                }
            }
            return $converted;
        }

        return $data;
    }

    private function shouldKeepAsInteger($key)
    {
        // Fields that should remain as integers
        $integerFields = [
            'id', 'user_id', 'story_id', 'feeling_id', 'audio_id', 'poll_id', 'location_id',
            'option_id', 'viewer_id', 'reply_id', 'vote_id', 'place_id', 'friend_id',
            'weather_code', 'votes', 'total_votes', 'percentage', 'views_count', 'replies_count',
            'code'
        ];

        return in_array($key, $integerFields);
    }

    private function shouldKeepAsBoolean($key)
    {
        // Fields that should be booleans
        $booleanFields = [
            'isDay',
        ];

        return in_array($key, $booleanFields);
    }

    private function mapStoryPollVote($vote, $user = null)
    {
        return [
            'id' => $vote->id,
            'user' => app(AuthController::class)->mapUserDetails($vote->user),
            'selected_options' => $vote->selected_options,
            'created_at' => $vote->created_at
        ];
    }

    public function createStory(Request $request)
    {
        try {
            $validatedData = $request->validate([
                // Privacy settings
                'privacy' => 'required|in:public,friends,specific_friends,friend_except',
                'specific_friends' => 'nullable|array',
                'specific_friends.*' => 'exists:users,id',
                'friend_except' => 'nullable|array',
                'friend_except.*' => 'exists:users,id',
                
                // Text elements (array for stories without media, object for stories with media)
                'text_elements' => 'nullable|array|max:20',
                'text_elements.*.text_properties' => 'nullable|array',
                'text_elements.*.text_properties.color' => 'nullable|string|max:7',
                'text_elements.*.text_properties.font_type' => 'nullable|string|max:50',
                'text_elements.*.text_properties.font_size' => 'nullable|numeric|min:8|max:72',
                'text_elements.*.text_properties.bold' => 'nullable|boolean',
                'text_elements.*.text_properties.italic' => 'nullable|boolean',
                'text_elements.*.text_properties.underline' => 'nullable|boolean',
                'text_elements.*.text_properties.alignment' => 'nullable|in:left,center,right',
                'text_elements.*.text' => 'required|string|max:5000',
                'text_elements.*.x' => 'required|numeric',
                'text_elements.*.y' => 'required|numeric',
                'text_elements.*.size_x' => 'nullable|numeric',
                'text_elements.*.size_h' => 'nullable|numeric',
                'text_elements.*.rotation' => 'nullable|numeric',
                'text_elements.*.scale' => 'nullable|numeric',
                
                // Single text element for stories with media
                'text_element' => 'nullable|array',
                'text_element.text_properties' => 'nullable|array',
                'text_element.text_properties.color' => 'nullable|string|max:7',
                'text_element.text_properties.font_type' => 'nullable|string|max:50',
                'text_element.text_properties.font_size' => 'nullable|numeric|min:8|max:72',
                'text_element.text_properties.bold' => 'nullable|boolean',
                'text_element.text_properties.italic' => 'nullable|boolean',
                'text_element.text_properties.underline' => 'nullable|boolean',
                'text_element.text_properties.alignment' => 'nullable|in:left,center,right',
                'text_element.text' => 'required_with:text_element|string|max:5000',
                'text_element.x' => 'required_with:text_element|numeric',
                'text_element.y' => 'required_with:text_element|numeric',
                'text_element.size_x' => 'nullable|numeric',
                'text_element.size_h' => 'nullable|numeric',
                'text_element.rotation' => 'nullable|numeric',
                'text_element.scale' => 'nullable|numeric',
                
                // Background colors
                'background_color' => 'nullable|array|max:10',
                'background_color.*' => 'string|regex:/^#[0-9A-Fa-f]{6}$/',
                
                // Media with new structure
                'media' => 'nullable|array|max:10',
                'media.*.file' => 'required|file|mimes:jpeg,png,gif,mp4,avi|max:51200',
                'media.*.order' => 'nullable|integer|min:1',
                'media.*.rotate_angle' => 'nullable|numeric',
                'media.*.scale' => 'nullable|numeric',
                'media.*.dx' => 'nullable|numeric',
                'media.*.dy' => 'nullable|numeric',
                
                // Enhanced positioning elements
                'mentions_elements' => 'nullable|array|max:10',
                'mentions_elements.*.friend_id' => 'required|exists:users,id',
                'mentions_elements.*.friend_name' => 'nullable|string|max:255',
                'mentions_elements.*.x' => 'required|numeric',
                'mentions_elements.*.y' => 'required|numeric',
                'mentions_elements.*.theme' => 'nullable|in:theme_1,theme_2,theme_3,theme_4',
                'mentions_elements.*.size_x' => 'nullable|numeric',
                'mentions_elements.*.size_h' => 'nullable|numeric',
                'mentions_elements.*.rotation' => 'nullable|numeric',
                'mentions_elements.*.scale' => 'nullable|numeric',
                
                'clock_element' => 'nullable|array',
                'clock_element.clock' => 'required_with:clock_element|string|max:20',
                'clock_element.x' => 'required_with:clock_element|numeric',
                'clock_element.y' => 'required_with:clock_element|numeric',
                'clock_element.theme' => 'nullable|in:theme_1,theme_2,theme_3,theme_4',
                'clock_element.size_x' => 'nullable|numeric',
                'clock_element.size_h' => 'nullable|numeric',
                'clock_element.rotation' => 'nullable|numeric',
                'clock_element.scale' => 'nullable|numeric',
                
                'feeling_element' => 'nullable|array',
                'feeling_element.feeling_id' => 'required_with:feeling_element|exists:post_feelings,id',
                'feeling_element.feeling_name' => 'nullable|string|max:255',
                'feeling_element.x' => 'required_with:feeling_element|numeric',
                'feeling_element.y' => 'required_with:feeling_element|numeric',
                'feeling_element.theme' => 'nullable|in:theme_1,theme_2,theme_3,theme_4',
                'feeling_element.size_x' => 'nullable|numeric',
                'feeling_element.size_h' => 'nullable|numeric',
                'feeling_element.rotation' => 'nullable|numeric',
                'feeling_element.scale' => 'nullable|numeric',
                
                'temperature_element' => 'nullable|array',
                'temperature_element.value' => 'nullable|numeric|min:-50|max:60',
                'temperature_element.weather_code' => 'nullable|string|max:10',
                'temperature_element.code' => 'nullable|numeric',
                'temperature_element.isDay' => 'nullable|boolean',
                'temperature_element.x' => 'required_with:temperature_element|numeric',
                'temperature_element.y' => 'required_with:temperature_element|numeric',
                'temperature_element.theme' => 'nullable|in:theme_1,theme_2,theme_3,theme_4',
                'temperature_element.size_x' => 'nullable|numeric',
                'temperature_element.size_h' => 'nullable|numeric',
                'temperature_element.rotation' => 'nullable|numeric',
                'temperature_element.scale' => 'nullable|numeric',
                
                'audio_element' => 'nullable|array',
                'audio_element.audio_id' => 'nullable|exists:story_audio,id',
                'audio_element.audio_name' => 'nullable|string|max:255',
                'audio_element.audio_image' => 'nullable|url|max:2048',
                'audio_element.audio_url' => 'nullable|url|max:2048',
                'audio_element.x' => 'required_with:audio_element|numeric',
                'audio_element.y' => 'required_with:audio_element|numeric',
                'audio_element.theme' => 'nullable|in:theme_1,theme_2,theme_3,theme_4',
                'audio_element.size_x' => 'nullable|numeric',
                'audio_element.size_h' => 'nullable|numeric',
                'audio_element.rotation' => 'nullable|numeric',
                'audio_element.scale' => 'nullable|numeric',
                
                'poll_element' => 'nullable|array',
                'poll_element.question' => 'required_with:poll_element|string|max:500',
                'poll_element.poll_options' => 'required_with:poll_element|array|min:2|max:5',
                'poll_element.poll_options.*.option_id' => 'required|integer',
                'poll_element.poll_options.*.option_name' => 'required|string|max:255',
                'poll_element.poll_options.*.votes' => 'nullable|integer|min:0',
                'poll_element.x' => 'required_with:poll_element|numeric',
                'poll_element.y' => 'required_with:poll_element|numeric',
                'poll_element.theme' => 'nullable|in:theme_1,theme_2,theme_3,theme_4',
                'poll_element.size_x' => 'nullable|numeric',
                'poll_element.size_h' => 'nullable|numeric',
                'poll_element.rotation' => 'nullable|numeric',
                'poll_element.scale' => 'nullable|numeric',
                
                'location_element' => 'nullable|array',
                'location_element.id' => 'nullable|exists:user_places,id',
                'location_element.country_name' => 'nullable|string|max:255',
                'location_element.city_name' => 'nullable|string|max:255',
                'location_element.x' => 'required_with:location_element|numeric',
                'location_element.y' => 'required_with:location_element|numeric',
                'location_element.theme' => 'nullable|in:theme_1,theme_2,theme_3,theme_4',
                'location_element.size_x' => 'nullable|numeric',
                'location_element.size_h' => 'nullable|numeric',
                'location_element.rotation' => 'nullable|numeric',
                'location_element.scale' => 'nullable|numeric',
                
                // New elements
                'drawing_elements' => 'nullable|array|max:20',
                'drawing_elements.*.points' => 'required|array|min:2',
                'drawing_elements.*.points.*.x' => 'required|numeric',
                'drawing_elements.*.points.*.y' => 'required|numeric',
                'drawing_elements.*.stroke_width' => 'required|numeric|min:1|max:20',
                'drawing_elements.*.stroke_color' => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
                
                'gif_element' => 'nullable|array',
                'gif_element.gif_url' => 'required_with:gif_element|url|max:2048',
                'gif_element.x' => 'required_with:gif_element|numeric',
                'gif_element.y' => 'required_with:gif_element|numeric',
                'gif_element.size_x' => 'nullable|numeric',
                'gif_element.size_h' => 'nullable|numeric',
                'gif_element.rotation' => 'nullable|numeric',
                'gif_element.scale' => 'nullable|numeric',
                
                // Other options
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

            // Handle text elements (convert single text_element to array for consistency)
            $textElements = null;
            if (!empty($validatedData['text_elements'])) {
                $textElements = $this->convertNumericValuesToDouble($validatedData['text_elements']);
            } elseif (!empty($validatedData['text_element'])) {
                $textElements = [$this->convertNumericValuesToDouble($validatedData['text_element'])];
            }

            // Create story with manual JSON encoding to preserve doubles
            $story = new Story();
            $story->user_id = $user->id;
            $story->privacy = $validatedData['privacy'];
            $story->specific_friends = $validatedData['privacy'] === 'specific_friends' ? ($validatedData['specific_friends'] ?? null) : null;
            $story->friend_except = $validatedData['privacy'] === 'friend_except' ? ($validatedData['friend_except'] ?? null) : null;
            $story->text_elements = json_encode($textElements, JSON_PRESERVE_ZERO_FRACTION);
            $story->background_color = isset($validatedData['background_color']) ? json_encode($validatedData['background_color'], JSON_PRESERVE_ZERO_FRACTION) : null;
            $story->mentions_elements = isset($validatedData['mentions_elements']) ? json_encode($this->convertNumericValuesToDouble($validatedData['mentions_elements']), JSON_PRESERVE_ZERO_FRACTION) : null;
            $story->clock_element = isset($validatedData['clock_element']) ? json_encode($this->convertNumericValuesToDouble($validatedData['clock_element']), JSON_PRESERVE_ZERO_FRACTION) : null;
            $story->feeling_element = isset($validatedData['feeling_element']) ? json_encode($this->convertNumericValuesToDouble($validatedData['feeling_element']), JSON_PRESERVE_ZERO_FRACTION) : null;
            $story->temperature_element = isset($validatedData['temperature_element']) ? json_encode($this->convertNumericValuesToDouble($validatedData['temperature_element']), JSON_PRESERVE_ZERO_FRACTION) : null;
            $story->audio_element = isset($validatedData['audio_element']) ? json_encode($this->convertNumericValuesToDouble($validatedData['audio_element']), JSON_PRESERVE_ZERO_FRACTION) : null;
            $story->poll_element = isset($validatedData['poll_element']) ? json_encode($this->convertNumericValuesToDouble($validatedData['poll_element']), JSON_PRESERVE_ZERO_FRACTION) : null;
            $story->location_element = isset($validatedData['location_element']) ? json_encode($this->convertNumericValuesToDouble($validatedData['location_element']), JSON_PRESERVE_ZERO_FRACTION) : null;
            $story->drawing_elements = isset($validatedData['drawing_elements']) ? json_encode($this->convertNumericValuesToDouble($validatedData['drawing_elements']), JSON_PRESERVE_ZERO_FRACTION) : null;
            $story->gif_element = isset($validatedData['gif_element']) ? json_encode($this->convertNumericValuesToDouble($validatedData['gif_element']), JSON_PRESERVE_ZERO_FRACTION) : null;
            $story->is_video_muted = $validatedData['is_video_muted'] ?? false;
            $story->expires_at = Carbon::now()->addHours(24);
            $story->status = 'active';
            $story->save();

            // Generate and save share URL after the story is created
            $story->share_url = $this->generateShareUrl($story->id);
            $story->save();

            // Handle media uploads
            if (!empty($validatedData['media'])) {
                $this->handleStoryMediaUploads($story, $validatedData['media']);
            }

            DB::commit();

            // Load relationships for response
            $story->load(['user', 'media']);

            $responseData = [
                'status_code' => 200,
                'success' => true,
                'message' => 'Story created successfully',
                'data' => $this->mapStory($story)
            ];

            return response()->json($responseData, 200, [], JSON_PRESERVE_ZERO_FRACTION);

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

                // Normalize URL to begin with /public/storage for consistency
                $storageUrl = Storage::url($path); // typically "/storage/..."
                if (strpos($storageUrl, '/storage/') === 0) {
                    $storageUrl = '/public' . $storageUrl; // "/public/storage/..."
                }

            StoryMedia::create([
                'story_id' => $story->id,
                'type' => strpos($file->getMimeType(), 'image') !== false ? 'image' : 'video',
                'filename' => $filename,
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize(),
                'path' => $path,
                    'url' => $storageUrl,
                'order' => $mediaItem['order'] ?? ($index + 1),
                'rotate_angle' => $mediaItem['rotate_angle'] ?? 0.0,
                'scale' => $mediaItem['scale'] ?? 1.0,
                'dx' => $mediaItem['dx'] ?? 0.0,
                'dy' => $mediaItem['dy'] ?? 0.0,
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
                'data' =>
                   [ 
                'audio' => $mappedAudio,
                'pagination' => [
                    'current_page' => $audioFiles->currentPage(),
                    'last_page' => $audioFiles->lastPage(),
                    'per_page' => $audioFiles->perPage(),
                    'total' => $audioFiles->total(),
                    'from' => $audioFiles->firstItem(),
                    'to' => $audioFiles->lastItem(),
                    'has_more_pages' => $audioFiles->hasMorePages()
                ]
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
                'data' => [
                'audio' => $mappedAudio,
                'pagination' => [
                    'current_page' => $audioFiles->currentPage(),
                    'last_page' => $audioFiles->lastPage(),
                    'per_page' => $audioFiles->perPage(),
                    'total' => $audioFiles->total(),
                    'from' => $audioFiles->firstItem(),
                    'to' => $audioFiles->lastItem(),
                    'has_more_pages' => $audioFiles->hasMorePages()
                    ]
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
                    'user' => app(AuthController::class)->mapUserDetails($view->viewer),
                    'viewed_at' => $view->viewed_at,
                    'replies' => $mappedReplies
                ];
            });

            return response()->json([
                'status_code' => 200,
                'success' => true,
                'message' => 'Story viewers retrieved successfully',
                'data' => [
                    'viewers' => $mappedViewers,
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
                    'user' => app(AuthController::class)->mapUserDetails($view->viewer),
                    'viewed_at' => $view->viewed_at,
                    'replies' => $mappedReplies
                ];
            });

            return response()->json([
                'status_code' => 200,
                'success' => true,
                'message' => 'Story viewers retrieved successfully',
                'data' => [
                    'viewers' => $mappedViewers,
                'pagination' => [
                    'current_page' => $viewers->currentPage(),
                    'last_page' => $viewers->lastPage(),
                    'per_page' => $viewers->perPage(),
                    'total' => $viewers->total(),
                    'from' => $viewers->firstItem(),
                    'to' => $viewers->lastItem(),
                    'has_more_pages' => $viewers->hasMorePages()
                    ]
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

     public function changeStoryMutedNotification(Request $request)
     {
         try {
             $validatedData = $request->validate([
                 'story_id' => 'required|integer|exists:stories,id',
                 'is_story_muted_notification' => 'required|boolean'
             ]);
             $user = Auth::guard('user')->user();
             $story = Story::where('user_id', $user->id)->findOrFail($validatedData['story_id']);
             
             // Check if the story is already in the requested muted state
             if ($story->is_story_muted_notification == $validatedData['is_story_muted_notification']) {
                 $message = $validatedData['is_story_muted_notification'] 
                     ? 'Story notifications are already muted' 
                     : 'Story notifications are already unmuted';
                 
                 return response()->json([
                     'status_code' => 400,
                     'success' => false,
                     'message' => $message
                 ], 400);
             }
             
             $story->update(['is_story_muted_notification' => $validatedData['is_story_muted_notification']]);
             
             $message = $validatedData['is_story_muted_notification']
                 ? 'Story notifications muted successfully'
                 : 'Story notifications unmuted successfully';
             
             return response()->json([
                 'status_code' => 200,
                 'success' => true,
                 'message' => $message
             ], 200);
         }
         catch (Exception $e) {
             return response()->json([
                 'status_code' => 500,
                 'success' => false,
                 'message' => 'Failed to change story muted notification',
                 'error' => $e->getMessage()
             ], 500);
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
                'data' => [
                'poll_votes' => $mappedPollVotes,
                'pagination' => [
                    'current_page' => $pollVotes->currentPage(),
                    'last_page' => $pollVotes->lastPage(),
                    'per_page' => $pollVotes->perPage(),
                    'total' => $pollVotes->total(),
                    'from' => $pollVotes->firstItem(),
                    'to' => $pollVotes->lastItem(),
                    'has_more_pages' => $pollVotes->hasMorePages()
                    ]
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

    public function searchStoryPollResults(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'story_id' => 'required|integer|exists:stories,id',
                'search' => 'nullable|string|max:255',
                'page' => 'nullable|integer|min:1',
                'per_page' => 'nullable|integer|min:1|max:100'
            ]);

            $user = Auth::guard('user')->user();
            $story = Story::where('user_id', $user->id)->findOrFail($validatedData['story_id']);
            $perPage = $validatedData['per_page'] ?? 20;
            $search = $validatedData['search'] ?? null;

            $pollVotes = StoryPollVote::with('user')
                ->where('story_id', $story->id)
                ->whereHas('user', function ($query) use ($search) {
                    if ($search) {
                        $query->where(function ($q) use ($search) {
                            $q->where('first_name', 'like', '%' . $search . '%')
                              ->orWhere('last_name', 'like', '%' . $search . '%')
                              ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ['%' . $search . '%']);
                        });
                    }
                })
                ->orderBy('created_at', 'desc')
                ->paginate($perPage);

            $mappedPollVotes = $pollVotes->map(function ($vote) use ($user) {
                return $this->mapStoryPollVote($vote, $user);
            });

            return response()->json([
                'status_code' => 200,
                'success' => true,
                'message' => 'Poll results retrieved successfully',
                'data' => [
                    'poll_votes' => $mappedPollVotes,
                'pagination' => [
                    'current_page' => $pollVotes->currentPage(),
                    'last_page' => $pollVotes->lastPage(),
                    'per_page' => $pollVotes->perPage(),
                    'total' => $pollVotes->total(),
                    'from' => $pollVotes->firstItem(),
                    'to' => $pollVotes->lastItem(),
                    'has_more_pages' => $pollVotes->hasMorePages()
                    ]
                ]
            ], 200);
        }
        catch (Exception $e) {
            return response()->json([
                'status_code' => 500,
                'success' => false,
                'message' => 'Failed to search poll results',
                'error' => $e->getMessage()
            ], 500);
        }
    }

     public function getStoryById(Request $request)
     {
         try {
             $validatedData = $request->validate([
                 'story_id' => 'required|integer|exists:stories,id',
             ]);

             $user = Auth::guard('user')->user();

             $story = Story::with(['user', 'media', 'views', 'replies', 'pollVotes'])
                 ->active()
                 ->visibleTo($user->id)
                 ->findOrFail($validatedData['story_id']);

                         $responseData = [
                 'status_code' => 200,
                 'success' => true,
                 'message' => 'Story retrieved successfully',
                 'data' => $this->mapStory($story, $user)
            ];

            return response()->json($responseData, 200, [], JSON_PRESERVE_ZERO_FRACTION);

         } catch (ValidationException $e) {
             return response()->json([
                 'status_code' => 422,
                 'success' => false,
                 'message' => 'Validation failed',
                 'errors' => $e->errors()
             ], 422);
         } catch (Exception $e) {
             return response()->json([
                 'status_code' => 404,
                 'success' => false,
                 'message' => 'Story not found or not accessible',
                 'error' => $e->getMessage()
             ], 404);
         }
     }
    
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
 
             // Group stories by user ID
             $groupedStories = $stories->getCollection()->groupBy('user_id')->map(function ($userStories, $userId) use ($user) {
                 $firstStory = $userStories->first();
                 $userDetails = app(AuthController::class)->mapUserDetails($firstStory->user);
                 
                 return [
                     'user' => $userDetails,
                     'stories' => $userStories->map(function ($story) use ($user) {
                         $storyData = $this->mapStory($story, $user);
                         $storyData['is_my_story'] = $story->user_id == $user->id;
                         return $storyData;
                     })->values(),
                     'total_stories' => $userStories->count(),
                     'latest_story_time' => $userStories->max('created_at')
                 ];
             })->values();
 
                         $responseData = [
                 'status_code' => 200,
                 'success' => true,
                 'message' => 'Stories retrieved successfully',
                'data' => [
                    'stories_by_user' => $groupedStories,
                 'pagination' => [
                     'current_page' => $stories->currentPage(),
                     'last_page' => $stories->lastPage(),
                     'per_page' => $stories->perPage(),
                     'total' => $stories->total(),
                        'from' => $stories->firstItem(),
                        'to' => $stories->lastItem(),
                     'has_more_pages' => $stories->hasMorePages()
                 ]
                ]
            ];

            return response()->json($responseData, 200, [], JSON_PRESERVE_ZERO_FRACTION);
 
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
                 'message' => 'Failed to retrieve stories',
                 'error' => $e->getMessage()
             ], 500);
         }
     }

     public function getStoriesByUserId(Request $request)
     {
         try {
            $validatedData = $request->validate([
                 'user_id' => 'required|integer|exists:users,id',
                 'page' => 'nullable|integer|min:1',
                 'per_page' => 'nullable|integer|min:1|max:100'
             ]);

             $currentUser = Auth::guard('user')->user();
             $targetUserId = $validatedData['user_id'];
             $perPage = $validatedData['per_page'] ?? 20;

             // Check if user is trying to get their own stories
             if ($currentUser->id == $targetUserId) {
                return response()->json([
                    'status_code' => 400,
                    'success' => false,
                     'message' => 'Use viewMyStoryById to get your own stories'
                ], 400);
            }

             // Check if users are friends (for privacy)
             if (!$currentUser->isFriendsWith($targetUserId)) {
                 return response()->json([
                     'status_code' => 403,
                     'success' => false,
                     'message' => 'You can only view stories from your friends'
                 ], 403);
             }

             $stories = Story::with(['user', 'media', 'views'])
                 ->where('user_id', $targetUserId)
                 ->active()
                 ->visibleTo($currentUser->id)
                 ->orderBy('created_at', 'asc') // Oldest to newest
                 ->paginate($perPage);

             $mappedStories = $stories->getCollection()->map(function ($story) use ($currentUser) {
                 $storyData = $this->mapStory($story, $currentUser);
                 $storyData['is_my_story'] = false; // These are not the current user's stories
                 return $storyData;
             });

                         $responseData = [
                'status_code' => 200,
                'success' => true,
                'message' => 'User stories retrieved successfully',
                'data' => [
                    'stories' => $mappedStories,
                    'user' => app(AuthController::class)->mapUserDetails(User::find($targetUserId)),
                    'total_stories' => $stories->total()
                ],
                'pagination' => [
                    'current_page' => $stories->currentPage(),
                    'last_page' => $stories->lastPage(),
                    'per_page' => $stories->perPage(),
                    'total' => $stories->total(),
                    'from' => $stories->firstItem(),
                    'to' => $stories->lastItem(),
                    'has_more_pages' => $stories->hasMorePages()
                ]
            ];

            return response()->json($responseData, 200, [], JSON_PRESERVE_ZERO_FRACTION);

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
                 'message' => 'Failed to retrieve user stories',
                 'error' => $e->getMessage()
             ], 500);
         }
     }

     public function getMyStories(Request $request)
     {
         try {
             $validatedData = $request->validate([
                 'page' => 'nullable|integer|min:1',
                 'per_page' => 'nullable|integer|min:1|max:100',
                 'status' => 'nullable|in:active,expired,all'
             ]);

             $user = Auth::guard('user')->user();
             $perPage = $validatedData['per_page'] ?? 20;
             $status = $validatedData['status'] ?? 'active';

             $query = Story::with(['user', 'media', 'views', 'replies', 'pollVotes'])
                 ->where('user_id', $user->id);

             // Filter by status
             if ($status === 'active') {
                 $query->active();
             } elseif ($status === 'expired') {
                 $query->where('expires_at', '<', now());
             }
             // If status is 'all', no additional filtering needed

             $stories = $query->orderBy('created_at', 'desc')
                 ->paginate($perPage);

             $mappedStories = $stories->getCollection()->map(function ($story) use ($user) {
                 $storyData = $this->mapStory($story, $user);
                 $storyData['is_my_story'] = true; // These are the current user's stories
                 return $storyData;
             });

                         $responseData = [
                'status_code' => 200,
                'success' => true,
                'message' => 'My stories retrieved successfully',
                'data' => [
                    'stories' => $mappedStories,
                    'user' => app(AuthController::class)->mapUserDetails($user),
                    'total_stories' => $stories->total(),
                    'status_filter' => $status
                ],
                'pagination' => [
                    'current_page' => $stories->currentPage(),
                    'last_page' => $stories->lastPage(),
                    'per_page' => $stories->perPage(),
                    'total' => $stories->total(),
                    'from' => $stories->firstItem(),
                    'to' => $stories->lastItem(),
                    'has_more_pages' => $stories->hasMorePages()
                ]
            ];

            return response()->json($responseData, 200, [], JSON_PRESERVE_ZERO_FRACTION);

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
                 'message' => 'Failed to retrieve my stories',
                 'error' => $e->getMessage()
             ], 500);
         }
     }

     public function viewStory(Request $request)
     {
         try {
             $validatedData = $request->validate([
                 'story_id' => 'required|integer|exists:stories,id',
             ]);

             $user = Auth::guard('user')->user();
             
             $story = Story::with(['user', 'media', 'views', 'replies'])
                 ->active()
                 ->visibleTo($user->id)
                 ->findOrFail($validatedData['story_id']);
 
             // Check if user is trying to view their own story
             if ($story->user_id == $user->id) {
                 return response()->json([
                     'status_code' => 400,
                     'success' => false,
                     'message' => 'You cannot view your own story'
                 ], 400);
             }

             DB::beginTransaction();
 
             // Record view if not already viewed
             if (!$story->hasBeenViewedBy($user->id)) {
                 StoryView::create([
                     'story_id' => $story->id,
                     'viewer_id' => $user->id,
                     'viewed_at' => now()
                 ]);
             }
 
             DB::commit();

                         $responseData = [
                 'status_code' => 200,
                 'success' => true,
                'message' => 'Story viewed successfully',
                 'data' => $this->mapStory($story, $user)
            ];

            return response()->json($responseData, 200, [], JSON_PRESERVE_ZERO_FRACTION);

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
                 'status_code' => 404,
                 'success' => false,
                 'message' => 'Story not found or not accessible',
                 'error' => $e->getMessage()
             ], 404);
         }
     }

    public function viewMyStoryById(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'story_id' => 'required|integer|exists:stories,id',
            ]);

            $user = Auth::guard('user')->user();
            
            $story = Story::with(['user', 'media', 'views', 'replies', 'pollVotes'])
                ->where('user_id', $user->id)
                ->active()
                ->findOrFail($validatedData['story_id']);

            $responseData = [
                'status_code' => 200,
                'success' => true,
                'message' => 'My story retrieved successfully',
                'data' => $this->mapStory($story, $user)
            ];

            return response()->json($responseData, 200, [], JSON_PRESERVE_ZERO_FRACTION);

        } catch (ValidationException $e) {
            return response()->json([
                'status_code' => 422,
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'status_code' => 404,
                'success' => false,
                'message' => 'Story not found, expired, or you do not have permission to view it',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    public function muteStoryNotifications(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'story_id' => 'nullable|integer|exists:stories,id',
                'muted_user_id' => 'nullable|integer|exists:users,id',
                'mute_replies' => 'nullable|boolean',
                'mute_poll_votes' => 'nullable|boolean',
                'mute_all' => 'nullable|boolean',
                'mute_story_notifications' => 'nullable|boolean',
                // New: global mute for all my stories
                'mute_all_for_me' => 'nullable|boolean'
            ]);

            $user = Auth::guard('user')->user();

            // Check if at least one of story_id or muted_user_id or mute_all_for_me is provided
            if (!isset($validatedData['story_id']) && !isset($validatedData['muted_user_id']) && !array_key_exists('mute_all_for_me', $validatedData)) {
                return response()->json([
                    'status_code' => 422,
                    'success' => false,
                    'message' => 'Either story_id, muted_user_id, or mute_all_for_me must be provided'
                ], 422);
            }

            DB::beginTransaction();

            // Option 0: Global mute/unmute for all my stories (no story_id or muted_user_id expected)
            if (array_key_exists('mute_all_for_me', $validatedData)) {
                // Ensure no conflicting identifiers are provided
                if (isset($validatedData['story_id']) || isset($validatedData['muted_user_id'])) {
                    return response()->json([
                        'status_code' => 422,
                        'success' => false,
                        'message' => 'Do not provide story_id or muted_user_id when using mute_all_for_me'
                    ], 422);
                }

                StoryNotificationSetting::updateOrCreate(
                    [
                        'story_owner_id' => $user->id,
                        'story_id' => null,
                        'muted_user_id' => null
                    ],
                    [
                        // When muting globally, set all relevant flags together
                        'mute_story_notifications' => (bool) ($validatedData['mute_all_for_me']),
                        'mute_replies' => (bool) ($validatedData['mute_all_for_me']),
                        'mute_poll_votes' => (bool) ($validatedData['mute_all_for_me']),
                        'mute_all' => (bool) ($validatedData['mute_all_for_me'])
                    ]
                );
            }

            // Option 1: Mute notifications from specific friend for all your stories
            if (isset($validatedData['muted_user_id']) && !isset($validatedData['story_id'])) {
                // Check if users are friends
                if (!$user->isFriendsWith($validatedData['muted_user_id'])) {
                    return response()->json([
                        'status_code' => 422,
                        'success' => false,
                        'message' => 'You can only mute notifications from friends'
                    ], 422);
                }

                // Check if user is trying to mute themselves
                if ($user->id == $validatedData['muted_user_id']) {
                    return response()->json([
                        'status_code' => 422,
                        'success' => false,
                        'message' => 'You cannot mute notifications from yourself'
                    ], 422);
                }

                StoryNotificationSetting::updateOrCreate(
                    [
                        'story_owner_id' => $user->id,
                        'muted_user_id' => $validatedData['muted_user_id'],
                        'story_id' => null
                    ],
                    [
                        'mute_replies' => $validatedData['mute_replies'] ?? false,
                        'mute_poll_votes' => $validatedData['mute_poll_votes'] ?? false,
                        'mute_all' => $validatedData['mute_all'] ?? false,
                        'mute_story_notifications' => false
                    ]
                );
            }

            // Option 2: Mute all notifications for a specific story
            if (isset($validatedData['story_id']) && !isset($validatedData['muted_user_id'])) {
                // Verify the story belongs to the user
                $story = Story::where('user_id', $user->id)
                    ->findOrFail($validatedData['story_id']);

                StoryNotificationSetting::updateOrCreate(
                    [
                        'story_owner_id' => $user->id,
                        'story_id' => $validatedData['story_id'],
                        'muted_user_id' => null
                    ],
                    [
                        'mute_story_notifications' => $validatedData['mute_story_notifications'] ?? true,
                        'mute_replies' => false,
                        'mute_poll_votes' => false,
                        'mute_all' => false
                    ]
                );
            }

            // Option 3: Mute notifications from specific friend for specific story
            if (isset($validatedData['story_id']) && isset($validatedData['muted_user_id'])) {
                // Check if users are friends
                if (!$user->isFriendsWith($validatedData['muted_user_id'])) {
                    return response()->json([
                        'status_code' => 422,
                        'success' => false,
                        'message' => 'You can only mute notifications from friends'
                    ], 422);
                }

                // Verify the story belongs to the user
                $story = Story::where('user_id', $user->id)
                    ->findOrFail($validatedData['story_id']);

                StoryNotificationSetting::updateOrCreate(
                    [
                        'story_owner_id' => $user->id,
                        'story_id' => $validatedData['story_id'],
                        'muted_user_id' => $validatedData['muted_user_id']
                    ],
                    [
                        'mute_replies' => $validatedData['mute_replies'] ?? false,
                        'mute_poll_votes' => $validatedData['mute_poll_votes'] ?? false,
                        'mute_all' => $validatedData['mute_all'] ?? false,
                        'mute_story_notifications' => $validatedData['mute_story_notifications'] ?? false
                    ]
                );
            }

            DB::commit();

            return response()->json([
                'status_code' => 200,
                'success' => true,
                'message' => 'Story notification settings updated successfully'
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
                'message' => 'Failed to update notification settings',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function voteStoryPoll(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'story_id' => 'required|integer|exists:stories,id',
                'selected_option' => 'required|integer|min:0'
            ]);

            $user = Auth::guard('user')->user();
            $story = Story::active()->visibleTo($user->id)->findOrFail($validatedData['story_id']);

            if (!$story->poll_element) {
                return response()->json([
                    'status_code' => 400,
                    'success' => false,
                    'message' => 'This story does not have a poll'
                ], 400);
            }

            // Validate selected option
            $pollElement = is_string($story->poll_element) ? json_decode($story->poll_element, true) : $story->poll_element;
            $pollOptions = $pollElement['poll_options'] ?? [];
            $validOptionIds = array_column($pollOptions, 'option_id');
            
            if (!in_array($validatedData['selected_option'], $validOptionIds)) {
                    return response()->json([
                    'status_code' => 422,
                        'success' => false,
                    'message' => 'Invalid poll option selected',
                    'errors' => ['selected_option' => ['The selected option is invalid']]
                ], 422);
            }

            DB::beginTransaction();

            // Create or update vote (single option)
            StoryPollVote::updateOrCreate(
                [
                    'story_id' => $story->id,
                    'user_id' => $user->id
                ],
                [
                    'selected_options' => [$validatedData['selected_option']]
                ]
            );

            DB::commit();

            // Refresh the story model to get updated poll results
            $story->refresh();

            return response()->json([
                'status_code' => 200,
                'success' => true,
                'message' => 'Vote recorded successfully',
                'data' => [
                    'results' => $story->poll_results,
                    'selected_option' => $validatedData['selected_option']
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
                'message' => 'Failed to record vote',
                'error' => $e->getMessage()
            ], 500);
        }
    }
  
    public function replyToStory(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'story_id' => 'required|integer|exists:stories,id',
                'reply_text' => 'nullable|string|max:1000',
                'reply_media' => 'nullable|file|mimes:jpeg,png,gif,mp4|max:25600',
                'reply_type' => 'required|in:text,media,emoji',
                'emoji' => 'nullable|string|max:10'
            ]);

            $user = Auth::guard('user')->user();
            $story = Story::active()->visibleTo($user->id)->findOrFail($validatedData['story_id']);

            // Validate reply type requirements
            if ($validatedData['reply_type'] === 'text' && empty($validatedData['reply_text'])) {
                return response()->json([
                    'status_code' => 422,
                    'success' => false,
                    'message' => 'Reply text is required for text type replies',
                    'errors' => ['reply_text' => ['Reply text is required for text type replies']]
                ], 422);
            }

            if ($validatedData['reply_type'] === 'media' && !$request->hasFile('reply_media')) {
                return response()->json([
                    'status_code' => 422,
                    'success' => false,
                    'message' => 'Reply media is required for media type replies',
                    'errors' => ['reply_media' => ['Reply media is required for media type replies']]
                ], 422);
            }

            if ($validatedData['reply_type'] === 'emoji' && empty($validatedData['emoji'])) {
                return response()->json([
                    'status_code' => 422,
                    'success' => false,
                    'message' => 'Emoji is required for emoji type replies',
                    'errors' => ['emoji' => ['Emoji is required for emoji type replies']]
                ], 422);
            }

            DB::beginTransaction();

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

            DB::commit();

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
                    'user' => app(AuthController::class)->mapUserDetails($reply->user),
                    'created_at' => $reply->created_at
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
                'message' => 'Failed to send reply',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function hideStory(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'story_owner_id' => 'nullable|integer|exists:users,id',
                'specific_story_id' => 'nullable|integer|exists:stories,id',
                'hide_type' => 'required|in:permanent,30_days,specific_story'
            ]);

            $user = Auth::guard('user')->user();

            // Validate required parameters based on hide type
            if ($validatedData['hide_type'] === 'specific_story' && empty($validatedData['specific_story_id'])) {
                return response()->json([
                    'status_code' => 422,
                    'success' => false,
                    'message' => 'Specific story ID is required for specific story hide type',
                    'errors' => ['specific_story_id' => ['Specific story ID is required for specific story hide type']]
                ], 422);
            }

            if ($validatedData['hide_type'] !== 'specific_story' && empty($validatedData['story_owner_id'])) {
                return response()->json([
                    'status_code' => 422,
                    'success' => false,
                    'message' => 'Story owner ID is required for permanent or 30 days hide types',
                    'errors' => ['story_owner_id' => ['Story owner ID is required for permanent or 30 days hide types']]
                ], 422);
            }

            // Validate story ownership for specific story hide
            if ($validatedData['hide_type'] === 'specific_story' && !empty($validatedData['specific_story_id'])) {
                $story = Story::find($validatedData['specific_story_id']);
                if (!$story) {
                    return response()->json([
                        'status_code' => 404,
                        'success' => false,
                        'message' => 'Story not found'
                    ], 404);
                }
            }

            // Validate user cannot hide their own stories
            if (!empty($validatedData['story_owner_id']) && $validatedData['story_owner_id'] == $user->id) {
                return response()->json([
                    'status_code' => 422,
                    'success' => false,
                    'message' => 'You cannot hide your own stories',
                    'errors' => ['story_owner_id' => ['You cannot hide your own stories']]
                ], 422);
            }

            $expiresAt = null;
            if ($validatedData['hide_type'] === '30_days') {
                $expiresAt = Carbon::now()->addDays(30);
            }

            DB::beginTransaction();

            // Check if hide setting already exists
            $existingHide = DB::table('story_hide_settings')
                ->where('user_id', $user->id)
                ->where('hide_type', $validatedData['hide_type']);

            if ($validatedData['hide_type'] === 'specific_story') {
                $existingHide->where('specific_story_id', $validatedData['specific_story_id']);
            } else {
                $existingHide->where('story_owner_id', $validatedData['story_owner_id']);
            }

            if ($existingHide->exists()) {
                return response()->json([
                    'status_code' => 400,
                    'success' => false,
                    'message' => 'Story is already hidden with this hide type'
                ], 400);
            }

            DB::table('story_hide_settings')->insert([
                'user_id' => $user->id,
                'story_owner_id' => $validatedData['hide_type'] === 'specific_story' ? null : $validatedData['story_owner_id'],
                'specific_story_id' => $validatedData['specific_story_id'] ?? null,
                'hide_type' => $validatedData['hide_type'],
                'expires_at' => $expiresAt,
                'created_at' => now(),
                'updated_at' => now()
            ]);

            DB::commit();

            return response()->json([
                'status_code' => 200,
                'success' => true,
                'message' => 'Story hidden successfully'
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
                'message' => 'Failed to hide story',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function unhideStory(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'story_owner_id' => 'nullable|integer|exists:users,id',
                'specific_story_id' => 'nullable|integer|exists:stories,id',
                'hide_type' => 'required|in:permanent,30_days,specific_story'
            ]);

            $user = Auth::guard('user')->user();

            // Validate required parameters based on hide type
            if ($validatedData['hide_type'] === 'specific_story' && empty($validatedData['specific_story_id'])) {
                return response()->json([
                    'status_code' => 422,
                    'success' => false,
                    'message' => 'Specific story ID is required for specific story hide type',
                    'errors' => ['specific_story_id' => ['Specific story ID is required for specific story hide type']]
                ], 422);
            }

            if ($validatedData['hide_type'] !== 'specific_story' && empty($validatedData['story_owner_id'])) {
                return response()->json([
                    'status_code' => 422,
                    'success' => false,
                    'message' => 'Story owner ID is required for permanent or 30 days hide types',
                    'errors' => ['story_owner_id' => ['Story owner ID is required for permanent or 30 days hide types']]
                ], 422);
            }

            // Validate story exists for specific story hide
            if ($validatedData['hide_type'] === 'specific_story' && !empty($validatedData['specific_story_id'])) {
                $story = Story::find($validatedData['specific_story_id']);
                if (!$story) {
                    return response()->json([
                        'status_code' => 404,
                        'success' => false,
                        'message' => 'Story not found'
                    ], 404);
                }
            }

            DB::beginTransaction();

            $query = DB::table('story_hide_settings')
                ->where('user_id', $user->id)
                ->where('hide_type', $validatedData['hide_type']);

            if ($validatedData['hide_type'] === 'specific_story') {
                $query->where('specific_story_id', $validatedData['specific_story_id']);
            } else {
                $query->where('story_owner_id', $validatedData['story_owner_id']);
            }

            // Check if hide setting exists before deleting
            if (!$query->exists()) {
                return response()->json([
                    'status_code' => 404,
                    'success' => false,
                    'message' => 'No hide setting found to remove'
                ], 404);
            }

            $deletedCount = $query->delete();

            if ($deletedCount === 0) {
                return response()->json([
                    'status_code' => 404,
                    'success' => false,
                    'message' => 'No hide setting found to remove'
                ], 404);
            }

            DB::commit();

            return response()->json([
                'status_code' => 200,
                'success' => true,
                'message' => 'Story unhidden successfully'
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
                'message' => 'Failed to unhide story',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function viewSharedStory(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'share_url' => 'required|string'
            ]);

            // Find story by share URL - search for the complete URL
            $baseUrl = config('app.url');
            $fullShareUrl = $baseUrl . '/story/' . $validatedData['share_url'];
            
            $story = Story::with(['user', 'media', 'views', 'replies', 'pollVotes'])
                ->where('share_url', $fullShareUrl)
                ->where('status', 'active') // Only check status, not expiration for shared stories
                ->first();

            if (!$story) {
                return response()->json([
                    'status_code' => 404,
                    'success' => false,
                    'message' => 'Story not found'
                ], 404);
            }

            // Check if story is public or accessible
            if ($story->privacy !== 'public') {
                return response()->json([
                    'status_code' => 403,
                    'success' => false,
                    'message' => 'This story is not publicly accessible'
                ], 403);
            }

            // Detect request source
            $isMobileApp = $this->isMobileAppRequest($request);
            $isWebBrowser = $this->isWebBrowserRequest($request);
            $isSocialMedia = $this->isSocialMediaRequest($request);

            // Return appropriate response based on request source
            if ($isMobileApp) {
                // Return JSON for mobile app
                return response()->json([
                    'status_code' => 200,
                    'success' => true,
                    'message' => 'Story retrieved successfully',
                    'data' => $this->mapStoryForMobile($story)
                ], 200, [], JSON_PRESERVE_ZERO_FRACTION);
            } else {
                // Return HTML view for web browsers and social media
                return $this->renderStoryWebView($story);
            }

        } catch (Exception $e) {
            return response()->json([
                'status_code' => 500,
                'success' => false,
                'message' => 'Failed to retrieve shared story',
                'error' => $e->getMessage()
            ], 500);
        }
    }

} 