<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PostReaction extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'post_id',
        'reaction_id'
    ];

    /**
     * Get the user that owns the reaction
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the post that was reacted to
     */
    public function post()
    {
        return $this->belongsTo(Post::class);
    }

    /**
     * Get the reaction details
     */
    public function reaction()
    {
        return $this->belongsTo(Reaction::class);
    }

    /**
     * Scope a query to only include reactions for a specific post
     */
    public function scopeForPost($query, $postId)
    {
        return $query->where('post_id', $postId);
    }

    /**
     * Scope a query to only include reactions by a specific user
     */
    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope a query to only include reactions of a specific type
     */
    public function scopeOfType($query, $reactionId)
    {
        return $query->where('reaction_id', $reactionId);
    }
}
