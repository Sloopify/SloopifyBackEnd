<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    protected $fillable = 
    [
        'name',
        'slug',
        'description',
        'is_active',
        'type',
    ];

    public function permissions()
    {
        return $this->belongsToMany(Permission::class , 'role_permissions');
    }

}
