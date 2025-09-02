<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class Story extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'stories';
    protected $fillable = [
        'user_id',
        'privacy',
        'specific_friends',
        'friend_except',
        'text_elements',
        'background_color',
        'mentions_elements',
        'clock_element',
        'feeling_element',
        'temperature_element',
        'audio_element',
        'poll_element',
        'location_element',
        'drawing_elements',
        'gif_element',
        'is_video_muted',
        'expires_at',
        'status',
        'is_story_muted_notification',
        'share_url'
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'is_video_muted' => 'boolean',
        'is_story_muted_notification' => 'boolean'
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function media()
    {
        return $this->hasMany(StoryMedia::class)->orderBy('order');
    }

    public function views()
    {
        return $this->hasMany(StoryView::class);
    }

    public function replies()
    {
        return $this->hasMany(StoryReply::class)->orderBy('created_at', 'desc');
    }

    public function pollVotes()
    {
        return $this->hasMany(StoryPollVote::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active')
                    ->where('expires_at', '>', now());
    }

    public function scopeVisibleTo($query, $userId)
    {
        return $query->where(function($q) use ($userId) {
            // User's own stories (always visible)
            $q->where('user_id', $userId)
              // Or public stories
              ->orWhere('privacy', 'public')
              // Or stories where user is a friend
              ->orWhere(function($subQ) use ($userId) {
                  $subQ->where('privacy', 'friends')
                       ->whereHas('user.friendships', function($friendQ) use ($userId) {
                           $friendQ->where(function($fq) use ($userId) {
                               $fq->where('user_id', $userId)->orWhere('friend_id', $userId);
                           })->where('status', 'accepted');
                       });
              })
              // Or specific friends stories where user is included
              ->orWhere(function($subQ) use ($userId) {
                  $subQ->where('privacy', 'specific_friends')
                       ->whereJsonContains('specific_friends', $userId);
              })
              // Or friend except stories where user is not excluded
              ->orWhere(function($subQ) use ($userId) {
                  $subQ->where('privacy', 'friend_except')
                       ->whereJsonDoesntContain('friend_except', $userId)
                       ->whereHas('user.friendships', function($friendQ) use ($userId) {
                           $friendQ->where(function($fq) use ($userId) {
                               $fq->where('user_id', $userId)->orWhere('friend_id', $userId);
                           })->where('status', 'accepted');
                       });
              });
        })
        // Exclude hidden stories
        ->whereNotExists(function($hiddenQuery) use ($userId) {
            $hiddenQuery->select(\DB::raw(1))
                       ->from('story_hide_settings')
                       ->where('user_id', $userId)
                       ->where(function($hideQ) {
                           // Specific story hide
                           $hideQ->where(function($specificQ) {
                               $specificQ->where('hide_type', 'specific_story')
                                        ->whereColumn('specific_story_id', 'stories.id');
                           })
                           // User-wide hide (permanent or 30 days)
                           ->orWhere(function($userQ) {
                               $userQ->whereIn('hide_type', ['permanent', '30_days'])
                                    ->whereColumn('story_owner_id', 'stories.user_id')
                                    ->where(function($expireQ) {
                                        $expireQ->whereNull('expires_at')
                                               ->orWhere('expires_at', '>', now());
                                    });
                           });
                       });
        });
    }

    // Accessors
    public function getIsExpiredAttribute()
    {
        return $this->expires_at->isPast();
    }

    public function getViewsCountAttribute()
    {
        return $this->views()->count();
    }

    public function getRepliesCountAttribute()
    {
        return $this->replies()->count();
    }

    public function getPollResultsAttribute()
    {
        if (!$this->poll_element) {
            return null;
        }

        // Handle both array and JSON string formats
        $pollElement = is_string($this->poll_element) ? json_decode($this->poll_element, true) : $this->poll_element;
        
        if (!$pollElement || !is_array($pollElement)) {
            return null;
        }

        $pollOptions = $pollElement['poll_options'] ?? [];
        if (!is_array($pollOptions)) {
            return null;
        }

        $votes = $this->pollVotes()->get();
        $totalVotes = $votes->count();

        $results = [];
        foreach ($pollOptions as $pollOption) {
            if (!is_array($pollOption) || !isset($pollOption['option_id'])) {
                continue;
            }
            $optionId = $pollOption['option_id'];
            // Count votes where the selected_options array contains this option_id
            $optionVotes = $votes->filter(function($vote) use ($optionId) {
                return is_array($vote->selected_options) && in_array($optionId, $vote->selected_options);
            })->count();
            $results[] = [
                'option_id' => $optionId,
                'option_name' => $pollOption['option_name'] ?? '',
                'votes' => $optionVotes,
                'percentage' => $totalVotes > 0 ? round(($optionVotes / $totalVotes) * 100, 1) : 0
            ];
        }

        return [
            'question' => $pollElement['question'] ?? '',
            'poll_options' => $results,
            'total_votes' => $totalVotes
        ];
    }

    // Check if user has viewed this story
    public function hasBeenViewedBy($userId)
    {
        return $this->views()->where('viewer_id', $userId)->exists();
    }

    // Check if user has voted in poll
    public function hasVotedBy($userId)
    {
        return $this->pollVotes()->where('user_id', $userId)->exists();
    }
} 