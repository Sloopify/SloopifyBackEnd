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
         'metadata'
    ];

    protected $casts = [
        'metadata' => 'array',
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
