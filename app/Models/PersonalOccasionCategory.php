<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\PersonalOccasionSetting;

class PersonalOccasionCategory extends Model
{
    //

    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'web_icon',
        'mobile_icon',
        'status'
    ];

    public function personalOccasionSettings()
    {
        return $this->hasMany(PersonalOccasionSetting::class);
    }

}
