<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PostActivity extends Model
{
    protected $fillable = 
    [
        'name', 
        'mobile_icon', 
        'web_icon', 
        'status',
        'category',
        'created_by',
        'updated_by'
    ];

    public function posts()
    {
     return $this->belongsToMany(Post::class, 'post_activities', 'post_id', 'activity_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(Admin::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(Admin::class, 'updated_by');
    }
}
