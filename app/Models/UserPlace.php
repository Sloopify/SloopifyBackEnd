<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserPlace extends Model
{
    //
    protected $fillable = [
        'user_id',
        'name',
        'city',
        'country',
        'latitude',
        'longitude',
        'status',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
