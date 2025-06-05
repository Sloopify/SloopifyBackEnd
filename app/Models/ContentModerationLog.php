<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContentModerationLog extends Model
{
    //
    use HasFactory;

    protected $fillable = [
        'post_id', 'detected_issues', 'toxicity_score', 'spam_score',
        'flagged_words', 'action_taken', 'ai_reasoning'
    ];

    protected $casts = [
        'detected_issues' => 'array',
        'flagged_words' => 'array',
    ];

    public function post()
    {
        return $this->belongsTo(Post::class);
    }
}
