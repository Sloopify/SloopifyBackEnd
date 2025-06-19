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
        'display_order',
        'x_position',
        'y_position',
        'metadata'
    ];

    protected $casts = [
        'metadata' => 'array',
        'x_position' => 'decimal:2',
        'y_position' => 'decimal:2'
    ];

    public function story()
    {
        return $this->belongsTo(Story::class);
    }

    public function getFullUrlAttribute()
    {
        return config('app.url') . $this->url;
    }
} 