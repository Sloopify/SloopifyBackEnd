<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PersonalOccasion extends Model
{
    //

    use HasFactory;

    protected $fillable = [
        'post_id', 
        'occasion_type',
        'title', 
        'description',
        'details', 
        'occasion_date'
    ];

    protected $casts = [
        'details' => 'array',
        'occasion_date' => 'date',
    ];

    public function post()
    {
        return $this->belongsTo(Post::class);
    }

    public static function getOccasionTypes()
    {
        return [
            'new_job' => 'Started a new job',
            'job_promotion' => 'Got promoted',
            'graduation' => 'Graduated',
            'started_studies' => 'Started studying',
            'relationship_status' => 'Relationship status changed',
            'moved_city' => 'Moved to a new city',
            'birthday' => 'Birthday celebration',
            'anniversary' => 'Anniversary',
            'achievement' => 'Personal achievement',
            'travel' => 'Travel experience',
            'other' => 'Other occasion'
        ];
    }
    
}
