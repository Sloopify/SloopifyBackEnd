<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StoryMedia extends Model
{
    use HasFactory;

    protected $fillable = [
        'story_id',
        'type',
        'filename',
        'original_name',
        'mime_type',
        'size',
        'path',
        'url',
        'order',
        'rotate_angle',
        'scale',
        'dx',
        'dy',
        'metadata'
    ];

    protected $casts = [
        'metadata' => 'array',
        'rotate_angle' => 'decimal:2',
        'scale' => 'decimal:2',
        'dx' => 'decimal:2',
        'dy' => 'decimal:2'
    ];

    public function story()
    {
        return $this->belongsTo(Story::class);
    }

    public function getFullUrlAttribute()
    {
        $url = $this->url ?? '';

        // Ensure URLs are standardized to start with "/public/storage" for story media
        // If current url starts with "/storage" or "storage", prefix with "/public"
        if (preg_match('#^/?storage/#', $url)) {
            $url = '/' . ltrim($url, '/');
            $url = '/public' . $url; // now "/public/storage/..."
        }

        // If it already starts with "/public/storage" leave as-is
        // Otherwise, leave existing $url unchanged

        return config('app.url') . $url;
    }
} 