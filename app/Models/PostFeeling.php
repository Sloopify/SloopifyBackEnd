<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PostFeeling extends Model
{
    protected $fillable = 
    [
        'name', 
        'mobile_icon', 
        'web_icon', 
        'status',
        'created_by',
        'updated_by'
    ];

    public function posts()
    {
        return $this->belongsToMany(Post::class, 'post_feelings', 'post_id', 'feeling_id');
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
