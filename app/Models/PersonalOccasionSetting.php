<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\PersonalOccasionCategory;

class PersonalOccasionSetting extends Model
{
    //
    use HasFactory;

    protected $fillable = [
        'personal_occasion_category_id',
        'name',
        'title',
        'description',
        'web_icon',
        'mobile_icon',
        'status'
    ];

    public function personalOccasionCategory()
    {
        return $this->belongsTo(PersonalOccasionCategory::class);
    }

    public function getOccasionTypes()
    {
        return [
            'new_job' => 'Started a new job',
            'job_promotion' => 'Got promoted',
            'graduation' => 'Graduated',
            'started_studies' => 'Started studying',
            'relationship_status' => 'Relationship status changed',
            'moved_city' => 'Moved to a new city',
            'birthday' => 'Birthday',
            'anniversary' => 'Anniversary',
            'achievement' => 'Achievement',
            'travel' => 'Travel',
            'other' => 'Other'
        ];
    }
}
