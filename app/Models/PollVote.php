<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PollVote extends Model
{
    //
    use HasFactory;

    protected $fillable = ['poll_id', 'user_id', 'selected_options'];

    protected $casts = [
        'selected_options' => 'array',
    ];

    public function poll()
    {
        return $this->belongsTo(PostPoll::class, 'poll_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
