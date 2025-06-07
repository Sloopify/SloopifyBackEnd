<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Interest extends Model
{
    //

    protected $fillable = [
        'name', 
        'web_icon', 
        'mobile_icon', 
        'status',
        'category'
        ];

    public function userInterests()
    {
        return $this->hasMany(UserInterest::class);
    }
}
