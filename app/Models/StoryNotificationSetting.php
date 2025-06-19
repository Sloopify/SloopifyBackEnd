<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StoryNotificationSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'story_owner_id',
        'muted_user_id',
        'mute_replies',
        'mute_poll_votes',
        'mute_all'
    ];

    protected $casts = [
        'mute_replies' => 'boolean',
        'mute_poll_votes' => 'boolean',
        'mute_all' => 'boolean'
    ];

    public function storyOwner()
    {
        return $this->belongsTo(User::class, 'story_owner_id');
    }

    public function mutedUser()
    {
        return $this->belongsTo(User::class, 'muted_user_id');
    }
} 