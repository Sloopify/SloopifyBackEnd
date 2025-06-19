<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class StoryHideSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'story_owner_id',
        'specific_story_id',
        'hide_type',
        'expires_at'
    ];

    protected $casts = [
        'expires_at' => 'datetime'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function storyOwner()
    {
        return $this->belongsTo(User::class, 'story_owner_id');
    }

    public function specificStory()
    {
        return $this->belongsTo(Story::class, 'specific_story_id');
    }

    // Scope for active hide settings (not expired)
    public function scopeActive($query)
    {
        return $query->where(function($q) {
            $q->whereNull('expires_at')
              ->orWhere('expires_at', '>', now());
        });
    }

    // Check if hide setting is expired
    public function getIsExpiredAttribute()
    {
        return $this->expires_at && $this->expires_at->isPast();
    }
} 