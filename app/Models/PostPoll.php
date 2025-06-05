<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PostPoll extends Model
{
    use HasFactory;

    protected $fillable = [
        'post_id', 'question', 'options', 'multiple_choice',
        'ends_at', 'show_results_after_vote', 'show_results_after_end'
    ];

    protected $casts = [
        'options' => 'array',
        'multiple_choice' => 'boolean',
        'show_results_after_vote' => 'boolean',
        'show_results_after_end' => 'boolean',
        'ends_at' => 'datetime',
    ];

    public function post()
    {
        return $this->belongsTo(Post::class);
    }

    public function votes()
    {
        return $this->hasMany(PollVote::class, 'poll_id');
    }

    public function isExpired()
    {
        return $this->ends_at && $this->ends_at->isPast();
    }

    public function getResultsAttribute()
    {
        $votes = $this->votes()->get();
        $totalVotes = $votes->count();
        $results = [];

        foreach ($this->options as $index => $option) {
            $optionVotes = $votes->filter(function ($vote) use ($index) {
                return in_array($index, $vote->selected_options);
            })->count();

            $results[] = [
                'option' => $option,
                'votes' => $optionVotes,
                'percentage' => $totalVotes > 0 ? round(($optionVotes / $totalVotes) * 100, 1) : 0
            ];
        }

        return [
            'total_votes' => $totalVotes,
            'options' => $results
        ];
    }
    
}
