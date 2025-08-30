<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserEducation extends Model
{
    protected $table = 'user_educations';
    
    protected $fillable = [
        'user_id',
        'education_level',
        'institution_name',
        'field_of_study',
        'description',
        'status',
        'start_year',
        'end_year',
        'is_current',
        'sort_order'
    ];

    protected $casts = [
        'start_year' => 'integer',
        'end_year' => 'integer',
        'is_current' => 'boolean',
        'sort_order' => 'integer'
    ];

    // Education level constants
    const LEVEL_HIGH_SCHOOL = 'high_school';
    const LEVEL_BACHELORS = 'bachelors_degree';
    const LEVEL_MASTERS = 'masters_degree';
    const LEVEL_PHD = 'phd_doctorate';
    const LEVEL_VOCATIONAL = 'vocational_training';
    const LEVEL_OTHER = 'other_education';

    // Status constants
    const STATUS_CURRENTLY_STUDYING = 'currently_studying';
    const STATUS_CURRENTLY_ENROLLED = 'currently_enrolled';
    const STATUS_GRADUATED = 'graduated';
    const STATUS_COMPLETED = 'completed';
    const STATUS_DID_NOT_GRADUATE = 'did_not_graduate';
    const STATUS_DROPPED_OUT = 'dropped_out';

    /**
     * Get the user that owns the education record.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope to get current education
     */
    public function scopeCurrent($query)
    {
        return $query->where('is_current', true);
    }

    /**
     * Scope to get completed education
     */
    public function scopeCompleted($query)
    {
        return $query->whereIn('status', [self::STATUS_GRADUATED, self::STATUS_COMPLETED]);
    }

    /**
     * Scope to get ongoing education
     */
    public function scopeOngoing($query)
    {
        return $query->whereIn('status', [self::STATUS_CURRENTLY_STUDYING, self::STATUS_CURRENTLY_ENROLLED]);
    }

    /**
     * Get education level display name
     */
    public function getEducationLevelDisplayAttribute()
    {
        $levels = [
            self::LEVEL_HIGH_SCHOOL => 'High School',
            self::LEVEL_BACHELORS => 'Bachelor\'s Degree',
            self::LEVEL_MASTERS => 'Master\'s Degree',
            self::LEVEL_PHD => 'Ph.D. / Doctorate',
            self::LEVEL_VOCATIONAL => 'Vocational / Training Program',
            self::LEVEL_OTHER => 'Other Education'
        ];

        return $levels[$this->education_level] ?? $this->education_level;
    }

    /**
     * Get status display name
     */
    public function getStatusDisplayAttribute()
    {
        $statuses = [
            self::STATUS_CURRENTLY_STUDYING => 'Currently Studying',
            self::STATUS_CURRENTLY_ENROLLED => 'Currently Enrolled',
            self::STATUS_GRADUATED => 'Graduated',
            self::STATUS_COMPLETED => 'Completed',
            self::STATUS_DID_NOT_GRADUATE => 'Did Not Graduate',
            self::STATUS_DROPPED_OUT => 'Dropped Out'
        ];

        return $statuses[$this->status] ?? $this->status;
    }

    /**
     * Get duration string
     */
    public function getDurationAttribute()
    {
        if ($this->start_year && $this->end_year) {
            return $this->start_year . ' - ' . $this->end_year;
        } elseif ($this->start_year) {
            return $this->start_year . ' - Present';
        }
        return null;
    }
}
