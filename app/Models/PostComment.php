<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PostComment extends Model
{
    use HasFactory;

    protected $table = 'post_comments';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'post_id',
        'user_id',
        'parent_comment_id',
        'comment_text',
        'mentions',
        'media',
        'is_deleted'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'mentions' => 'array',
        'media' => 'array',
        'is_deleted' => 'boolean'
    ];

    /**
     * Get the post that owns the comment
     */
    public function post()
    {
        return $this->belongsTo(Post::class);
    }

    /**
     * Get the user that owns the comment
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the parent comment
     */
    public function parentComment()
    {
        return $this->belongsTo(PostComment::class, 'parent_comment_id');
    }

    /**
     * Get the replies to this comment
     */
    public function replies()
    {
        return $this->hasMany(PostComment::class, 'parent_comment_id');
    }

    /**
     * Get the reactions for this comment
     */
    public function reactions()
    {
        return $this->hasMany(CommentReaction::class, 'comment_id');
    }

    /**
     * Scope a query to only include non-deleted comments
     */
    public function scopeNotDeleted($query)
    {
        return $query->where('is_deleted', false);
    }

    /**
     * Scope a query to only include top-level comments
     */
    public function scopeTopLevel($query)
    {
        return $query->whereNull('parent_comment_id');
    }
}
