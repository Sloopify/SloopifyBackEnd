<?php

namespace App\Services;

use App\Models\Post;
use App\Models\User;
use App\Models\FriendMutedNotification;
use App\Services\FirebaseNotificationService;
use Illuminate\Support\Facades\Log;

class PostNotificationService
{
    protected $firebaseService;

    public function __construct(FirebaseNotificationService $firebaseService)
    {
        $this->firebaseService = $firebaseService;
    }

    /**
     * Send notifications for a new post
     */
    public function sendPostNotifications(Post $post)
    {
        if (!config('firebase.notifications.enabled', true)) {
            Log::info('Firebase notifications are disabled');
            return;
        }

        try {
            // Get the post author
            $author = $post->user;
            
            // Send mention notifications
            $this->sendMentionNotifications($post, $author);
            
            // Send friend notifications based on privacy settings
            $this->sendFriendNotifications($post, $author);
            
        } catch (\Exception $e) {
            Log::error('Failed to send post notifications: ' . $e->getMessage(), [
                'post_id' => $post->id,
                'user_id' => $post->user_id
            ]);
        }
    }

    /**
     * Send mention notifications
     */
    protected function sendMentionNotifications(Post $post, User $author)
    {
        // Skip if no mentions or poll type
        if ($post->type === 'poll' || empty($post->mentions['friends'])) {
            return;
        }

        $mentionedFriendIds = $post->mentions['friends'];
        
        // Filter out users who have muted notifications from this author
        $mentionedFriendIds = $this->filterMutedUsers($mentionedFriendIds, $author->id);
        
        if (empty($mentionedFriendIds)) {
            Log::info('No valid mentioned friends to notify', ['post_id' => $post->id]);
            return;
        }

        // Send mention notifications
        $result = $this->firebaseService->sendPostMentionNotification(
            $mentionedFriendIds,
            $author,
            $post->content
        );

        Log::info('Mention notifications sent', [
            'post_id' => $post->id,
            'mentioned_count' => count($mentionedFriendIds),
            'success' => $result !== false
        ]);
    }

    /**
     * Send friend notifications based on privacy settings
     */
    protected function sendFriendNotifications(Post $post, User $author)
    {
        $friendIds = $this->getFriendsToNotify($post, $author);
        
        if (empty($friendIds)) {
            Log::info('No friends to notify', ['post_id' => $post->id]);
            return;
        }

        // Filter out users who have muted notifications from this author
        $friendIds = $this->filterMutedUsers($friendIds, $author->id);
        
        if (empty($friendIds)) {
            Log::info('All friends have muted notifications', ['post_id' => $post->id]);
            return;
        }

        // Send friend notifications
        $result = $this->firebaseService->sendPostNotificationToFriends(
            $friendIds,
            $author,
            $post->content,
            $post->type
        );

        Log::info('Friend notifications sent', [
            'post_id' => $post->id,
            'friends_count' => count($friendIds),
            'success' => $result !== false
        ]);
    }

    /**
     * Get friends to notify based on post privacy settings
     */
    protected function getFriendsToNotify(Post $post, User $author)
    {
        switch ($post->privacy) {
            case 'public':
                // For public posts, notify all friends
                return $author->friends()->pluck('id')->toArray();
                
            case 'friends':
                // For friends privacy, notify all friends
                return $author->friends()->pluck('id')->toArray();
                
            case 'specific_friends':
                // For specific friends, notify only those in the list
                return $post->specific_friends ?? [];
                
            case 'friend_except':
                // For friends except, notify all friends except those in the list
                $allFriends = $author->friends()->pluck('id')->toArray();
                $exceptFriends = $post->friend_except ?? [];
                return array_diff($allFriends, $exceptFriends);
                
            case 'only_me':
                // For only me, don't notify anyone
                return [];
                
            default:
                return [];
        }
    }

    /**
     * Filter out users who have muted notifications from the author
     */
    protected function filterMutedUsers($userIds, $authorId)
    {
        if (empty($userIds)) {
            return [];
        }

        // Get users who have muted notifications from this author
        $mutedUsers = FriendMutedNotification::getUsersWhoMuted($authorId);
        
        // Filter out muted users
        return array_diff($userIds, $mutedUsers);
    }

    /**
     * Send notification for a specific post event
     */
    public function sendPostEventNotification($postId, $eventType, $additionalData = [])
    {
        try {
            $post = Post::with('user')->find($postId);
            
            if (!$post) {
                Log::error('Post not found for notification', ['post_id' => $postId]);
                return false;
            }

            $author = $post->user;
            
            switch ($eventType) {
                case 'post_approved':
                    // Notify author that their post was approved
                    return $this->firebaseService->sendToUser(
                        $author->id,
                        'Post Approved',
                        'Your post has been approved and is now visible to others',
                        ['post_id' => (string)$postId, 'action' => 'view_post'],
                        'general'
                    );
                    
                case 'post_rejected':
                    // Notify author that their post was rejected
                    return $this->firebaseService->sendToUser(
                        $author->id,
                        'Post Rejected',
                        'Your post was rejected due to policy violations',
                        ['post_id' => (string)$postId, 'action' => 'view_guidelines'],
                        'general'
                    );
                    
                default:
                    Log::warning('Unknown post event type', ['event_type' => $eventType]);
                    return false;
            }
            
        } catch (\Exception $e) {
            Log::error('Failed to send post event notification: ' . $e->getMessage(), [
                'post_id' => $postId,
                'event_type' => $eventType
            ]);
            return false;
        }
    }

    /**
     * Send poll result notification
     */
    public function sendPollResultNotification($postId, $participantIds = [])
    {
        try {
            $post = Post::with(['user', 'poll'])->find($postId);
            
            if (!$post || $post->type !== 'poll') {
                return false;
            }

            $author = $post->user;
            
            // Filter out muted users
            $participantIds = $this->filterMutedUsers($participantIds, $author->id);
            
            if (empty($participantIds)) {
                return true; // No one to notify
            }

            return $this->firebaseService->sendToUsers(
                $participantIds,
                'Poll Results Available',
                "Results for {$author->username}'s poll are now available",
                [
                    'post_id' => (string)$postId,
                    'poll_id' => (string)$post->poll->id,
                    'action' => 'view_poll_results'
                ],
                'general'
            );
            
        } catch (\Exception $e) {
            Log::error('Failed to send poll result notification: ' . $e->getMessage(), [
                'post_id' => $postId
            ]);
            return false;
        }
    }
} 