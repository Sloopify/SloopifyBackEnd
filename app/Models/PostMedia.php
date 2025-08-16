<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PostMedia extends Model
{
    //

    use HasFactory;

    protected $fillable = [
        'post_id', 
        'type',
        'filename', 
        'original_name',
        'mime_type',
        'size', 
        'path', 
        'url',
        'metadata',
        'display_order',
        'apply_to_download',
        'auto_play',
        'is_rotate',
        'rotate_angle',
        'is_flip_horizontal',
        'is_flip_vertical',
        'filter_name',
    ];

    protected $casts = [
        'metadata' => 'array',
        'rotate_angle' => 'float',
    ];

    public function post()
    {
        return $this->belongsTo(Post::class);
    }

    public function getUrlAttribute()
    {
        return asset('storage/' . $this->path);
    }
    
}
