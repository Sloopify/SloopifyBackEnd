<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StoryReply extends Model
{
    use HasFactory;

    protected $fillable = [
        'story_id',
        'user_id',
        'reply_text',
        'reply_media_path',
        'reply_type',
        'emoji'
    ];

    public function story()
    {
        return $this->belongsTo(Story::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getReplyMediaUrlAttribute()
    {
        return $this->reply_media_path ? config('app.url') . asset('storage/' . $this->reply_media_path) : null;
    }
} 