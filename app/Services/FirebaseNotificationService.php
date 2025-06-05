<?php

namespace App\Services;

use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Kreait\Firebase\Messaging\AndroidConfig;
use Kreait\Firebase\Messaging\ApnsConfig;
use Kreait\Firebase\Messaging\WebPushConfig;
use Kreait\Firebase\Exception\FirebaseException;
use App\Models\UserSession;
use Illuminate\Support\Facades\Log;

class FirebaseNotificationService
{
    protected $messaging;

    public function __construct()
    {
        try {
            $factory = (new Factory)
                ->withServiceAccount(config('firebase.credentials'))
                ->withProjectId(config('firebase.project_id'));
            
            $this->messaging = $factory->createMessaging();
        } catch (\Exception $e) {
            Log::error('Firebase initialization failed: ' . $e->getMessage());
            $this->messaging = null;
        }
    }

    /**
     * Send notification to a single user across all their devices
     */
    public function sendToUser($userId, $title, $body, $data = [], $type = 'general')
    {
        if (!$this->messaging) {
            Log::error('Firebase messaging not initialized');
            return false;
        }

        try {
            // Get all active sessions with push tokens for this user
            $sessions = UserSession::where('user_id', $userId)
                ->active()
                ->whereNotNull('push_token')
                ->get();

            if ($sessions->isEmpty()) {
                Log::info("No active sessions with push tokens found for user {$userId}");
                return false;
            }

            $results = [];
            foreach ($sessions as $session) {
                $result = $this->sendToDevice(
                    $session->push_token, 
                    $title, 
                    $body, 
                    $data, 
                    $type, 
                    $session->device_type
                );
                $results[] = $result;
            }

            return $results;
        } catch (\Exception $e) {
            Log::error('Failed to send notification to user: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Send notification to multiple users
     */
    public function sendToUsers($userIds, $title, $body, $data = [], $type = 'general')
    {
        if (!$this->messaging) {
            Log::error('Firebase messaging not initialized');
            return false;
        }

        try {
            $tokens = UserSession::whereIn('user_id', $userIds)
                ->active()
                ->whereNotNull('push_token')
                ->pluck('push_token')
                ->unique()
                ->values()
                ->toArray();

            if (empty($tokens)) {
                Log::info('No push tokens found for users: ' . implode(', ', $userIds));
                return false;
            }

            return $this->sendToTokens($tokens, $title, $body, $data, $type);
        } catch (\Exception $e) {
            Log::error('Failed to send notification to users: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Send notification to a specific device token
     */
    public function sendToDevice($token, $title, $body, $data = [], $type = 'general', $deviceType = 'mobile')
    {
        if (!$this->messaging) {
            Log::error('Firebase messaging not initialized');
            return false;
        }

        try {
            $notification = Notification::create($title, $body);
            
            $message = CloudMessage::withTarget('token', $token)
                ->withNotification($notification)
                ->withData(array_merge($data, [
                    'type' => $type,
                    'timestamp' => now()->toISOString()
                ]));

            // Add platform-specific configurations
            $this->addPlatformConfig($message, $deviceType, $type);

            $result = $this->messaging->send($message);
            
            Log::info('Notification sent successfully', [
                'token' => substr($token, 0, 20) . '...',
                'title' => $title,
                'type' => $type
            ]);

            return $result;
        } catch (FirebaseException $e) {
            Log::error('Firebase notification failed: ' . $e->getMessage(), [
                'token' => substr($token, 0, 20) . '...',
                'title' => $title
            ]);
            return false;
        } catch (\Exception $e) {
            Log::error('Notification send failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Send notification to multiple tokens
     */
    public function sendToTokens($tokens, $title, $body, $data = [], $type = 'general')
    {
        if (!$this->messaging) {
            Log::error('Firebase messaging not initialized');
            return false;
        }

        try {
            $notification = Notification::create($title, $body);
            
            $message = CloudMessage::new()
                ->withNotification($notification)
                ->withData(array_merge($data, [
                    'type' => $type,
                    'timestamp' => now()->toISOString()
                ]));

            // Add platform-specific configurations
            $this->addPlatformConfig($message, 'mobile', $type);

            $result = $this->messaging->sendMulticast($message, $tokens);
            
            Log::info('Multicast notification sent', [
                'success_count' => $result->successes()->count(),
                'failure_count' => $result->failures()->count(),
                'title' => $title,
                'type' => $type
            ]);

            return $result;
        } catch (FirebaseException $e) {
            Log::error('Firebase multicast notification failed: ' . $e->getMessage());
            return false;
        } catch (\Exception $e) {
            Log::error('Multicast notification send failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Add platform-specific configurations
     */
    protected function addPlatformConfig($message, $deviceType, $type)
    {
        // Android configuration
        $androidConfig = AndroidConfig::fromArray([
            'ttl' => '3600s',
            'priority' => 'high',
            'notification' => [
                'icon' => 'stock_ticker_update',
                'color' => '#f45342',
                'sound' => 'default',
                'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                'channel_id' => $this->getChannelId($type)
            ]
        ]);

        // iOS configuration
        $apnsConfig = ApnsConfig::fromArray([
            'payload' => [
                'aps' => [
                    'alert' => [
                        'title' => $message->notification()->title(),
                        'body' => $message->notification()->body()
                    ],
                    'badge' => 1,
                    'sound' => 'default',
                    'content-available' => 1,
                    'mutable-content' => 1
                ]
            ]
        ]);

        // Web Push configuration
        $webPushConfig = WebPushConfig::fromArray([
            'notification' => [
                'title' => $message->notification()->title(),
                'body' => $message->notification()->body(),
                'icon' => '/icon-192x192.png',
                'badge' => '/badge-72x72.png',
                'actions' => [
                    [
                        'action' => 'open',
                        'title' => 'Open'
                    ]
                ]
            ]
        ]);

        $message = $message->withAndroidConfig($androidConfig)
                          ->withApnsConfig($apnsConfig)
                          ->withWebPushConfig($webPushConfig);

        return $message;
    }

    /**
     * Get notification channel ID based on type
     */
    protected function getChannelId($type)
    {
        $channels = [
            'post_mention' => 'mentions',
            'post_notification' => 'posts',
            'friend_request' => 'friends',
            'general' => 'general'
        ];

        return $channels[$type] ?? 'general';
    }

    /**
     * Send post mention notifications
     */
    public function sendPostMentionNotification($mentionedUserIds, $postAuthor, $postContent = null)
    {
        $title = "You were mentioned in a post";
        $body = "{$postAuthor->username} mentioned you in their post";
        
        if ($postContent && strlen($postContent) > 0) {
            $preview = strlen($postContent) > 50 ? substr($postContent, 0, 47) . '...' : $postContent;
            $body = "{$postAuthor->username} mentioned you: \"{$preview}\"";
        }

        $data = [
            'post_author_id' => (string)$postAuthor->id,
            'post_author_username' => $postAuthor->username,
            'action' => 'view_post'
        ];

        return $this->sendToUsers($mentionedUserIds, $title, $body, $data, 'post_mention');
    }

    /**
     * Send post notification to friends
     */
    public function sendPostNotificationToFriends($friendIds, $postAuthor, $postContent = null, $postType = 'regular')
    {
        $typeText = $postType === 'poll' ? 'poll' : ($postType === 'personal_occasion' ? 'celebration' : 'post');
        $title = "New {$typeText} from {$postAuthor->username}";
        
        $body = "{$postAuthor->username} shared a new {$typeText}";
        if ($postContent && strlen($postContent) > 0 && $postType !== 'poll') {
            $preview = strlen($postContent) > 50 ? substr($postContent, 0, 47) . '...' : $postContent;
            $body = "{$postAuthor->username}: \"{$preview}\"";
        }

        $data = [
            'post_author_id' => (string)$postAuthor->id,
            'post_author_username' => $postAuthor->username,
            'post_type' => $postType,
            'action' => 'view_post'
        ];

        return $this->sendToUsers($friendIds, $title, $body, $data, 'post_notification');
    }
} 