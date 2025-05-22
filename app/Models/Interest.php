<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Interest extends Model
{
    //

    protected $fillable = [
        'name', 
        'image', 
        'status',
        'category'
        ];

    public function userInterests()
    {
        return $this->hasMany(UserInterest::class);
    }
}
