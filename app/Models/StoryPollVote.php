<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StoryPollVote extends Model
{
    use HasFactory;

    protected $fillable = [
        'story_id',
        'user_id',
        'selected_options'
    ];

    protected $casts = [
        'selected_options' => 'array'
    ];

    public function story()
    {
        return $this->belongsTo(Story::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
} 