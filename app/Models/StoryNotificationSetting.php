<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StoryNotificationSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'story_owner_id',
        'story_id',
        'muted_user_id',
        'mute_replies',
        'mute_poll_votes',
        'mute_all',
        'mute_story_notifications'
    ];

    protected $casts = [
        'mute_replies' => 'boolean',
        'mute_poll_votes' => 'boolean',
        'mute_all' => 'boolean',
        'mute_story_notifications' => 'boolean'
    ];

    public function storyOwner()
    {
        return $this->belongsTo(User::class, 'story_owner_id');
    }

    public function mutedUser()
    {
        return $this->belongsTo(User::class, 'muted_user_id');
    }

    public function story()
    {
        return $this->belongsTo(Story::class, 'story_id');
    }
} 