<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class Post extends Model
{
    //

    use HasFactory, SoftDeletes;

    protected $fillable = [
         'user_id', 
         'type',
         'content', 
         'text_properties', 
         'background_color',
         'privacy',
         'specific_friends',
         'friend_except',
         'disappears_24h',
         'disappears_at', 
         'gif_url',
         'mentions',
         'status',
         'moderation_reason',
         'is_pinned',
         'is_saved',
         'is_notified'
    ];

    protected $casts = [
        'text_properties' => 'array',
        'specific_friends' => 'array',
        'friend_except' => 'array',
        'mentions' => 'array',
        'disappears_24h' => 'boolean',
        'disappears_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function media()
    {
        return $this->hasMany(PostMedia::class);
    }

    public function poll()
    {
        return $this->hasOne(PostPoll::class);
    }

    public function personalOccasion()
    {
        return $this->hasOne(PersonalOccasion::class);
    }

    public function moderationLogs()
    {
        return $this->hasMany(ContentModerationLog::class);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeNotExpired($query)
    {
        return $query->where(function ($q) {
            $q->where('disappears_24h', false)
              ->orWhere('disappears_at', '>', now());
        });
    }

    public function scopeVisibleTo($query, $userId)
    {
        return $query->where(function ($q) use ($userId) {
            $q->where('privacy', 'public')
              ->orWhere(function ($subQ) use ($userId) {
                  $subQ->where('privacy', 'friends')
                       ->whereHas('user', function ($userQ) use ($userId) {
                           $userQ->whereHas('sentFriendRequests', function ($friendQ) use ($userId) {
                               $friendQ->where('friend_id', $userId)
                                      ->where('status', 'accepted');
                           })->orWhereHas('receivedFriendRequests', function ($friendQ) use ($userId) {
                               $friendQ->where('user_id', $userId)
                                      ->where('status', 'accepted');
                           });
                       });
              })
              ->orWhere(function ($subQ) use ($userId) {
                  $subQ->where('privacy', 'specific_friends')
                       ->whereJsonContains('specific_friends', $userId);
              })
              ->orWhere(function ($subQ) use ($userId) {
                  $subQ->where('privacy', 'friend_except')
                       ->whereHas('user', function ($userQ) use ($userId) {
                           $userQ->whereHas('sentFriendRequests', function ($friendQ) use ($userId) {
                               $friendQ->where('friend_id', $userId)
                                      ->where('status', 'accepted');
                           })->orWhereHas('receivedFriendRequests', function ($friendQ) use ($userId) {
                               $friendQ->where('user_id', $userId)
                                      ->where('status', 'accepted');
                           });
                       })
                       ->whereJsonDoesntContain('friend_except', $userId);
              })
              ->orWhere('user_id', $userId);
        });
    }

    public function isExpired()
    {
        return $this->disappears_24h && $this->disappears_at && $this->disappears_at->isPast();
    }

    public function setDisappears24hAttribute($value)
    {
        $this->attributes['disappears_24h'] = $value;
        if ($value) {
            $this->attributes['disappears_at'] = Carbon::now()->addHours(24);
        } else {
            $this->attributes['disappears_at'] = null;
        }
    }

}
