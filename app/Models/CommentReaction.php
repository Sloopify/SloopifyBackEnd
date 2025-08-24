<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CommentReaction extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'comment_id',
        'user_id',
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
     * Get the comment that was reacted to
     */
    public function comment()
    {
        return $this->belongsTo(PostComment::class, 'comment_id');
    }

    /**
     * Get the reaction details
     */
    public function reaction()
    {
        return $this->belongsTo(Reaction::class);
    }

    /**
     * Scope a query to only include reactions for a specific comment
     */
    public function scopeForComment($query, $commentId)
    {
        return $query->where('comment_id', $commentId);
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
