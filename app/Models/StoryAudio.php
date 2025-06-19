<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StoryAudio extends Model
{
    use HasFactory;

    protected $table = 'story_audio';

    protected $fillable = [
        'name',
        'filename',
        'path',
        'duration',
        'file_size',
        'mime_type',
        'image',
        'status',
        'category'
    ];

    public function story()
    {
        return $this->belongsTo(Story::class, 'audio_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function getFullUrlAttribute()
    {
        return config('app.url') . $this->url;
    }

    public function getDurationFormattedAttribute()
    {
        if (!$this->duration) {
            return null;
        }
        
        $minutes = floor($this->duration / 60);
        $seconds = $this->duration % 60;
        
        return sprintf('%d:%02d', $minutes, $seconds);
    }
} 