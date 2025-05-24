<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Otp extends Model
{
    //
    protected $fillable = [
        'otp',
        'type',
        'phone',
        'email',
        'expires_at',
        'email_verified',
        'phone_verified',
        'temp_password',
        'is_used',
    ];
}
