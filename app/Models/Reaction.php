<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reaction extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'content',
        'image',
        'video',
        'status',
        'is_default'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'status' => 'string',
        'is_default' => 'boolean'
    ];

    /**
     * Get the image URL with full path
     */
    public function getImageUrlAttribute()
    {
        if ($this->image) {
            return config('app.url') . '/public/storage/' . $this->image;
        }
        return null;
    }

    /**
     * Get the video URL with full path
     */
    public function getVideoUrlAttribute()
    {
        if ($this->video) {
            return config('app.url') . '/public/storage/' . $this->video;
        }
        return null;
    }

    /**
     * Scope a query to only include active reactions
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope a query to only include inactive reactions
     */
    public function scopeInactive($query)
    {
        return $query->where('status', 'inactive');
    }

    /**
     * Check if the reaction is active
     */
    public function isActive()
    {
        return $this->status === 'active';
    }

    /**
     * Check if the reaction is inactive
     */
    public function isInactive()
    {
        return $this->status === 'inactive';
    }

    /**
     * Scope a query to only include default reactions
     */
    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    /**
     * Scope a query to only include non-default reactions
     */
    public function scopeNonDefault($query)
    {
        return $query->where('is_default', false);
    }

    /**
     * Check if the reaction is default
     */
    public function isDefault()
    {
        return $this->is_default === true;
    }

    /**
     * Check if the reaction is not default
     */
    public function isNotDefault()
    {
        return $this->is_default === false;
    }
}
