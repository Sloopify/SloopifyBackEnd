<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class UserJob extends Model
{
    protected $table = 'user_jobs';
    
    protected $fillable = [
        'user_id',
        'job_title',
        'company_name',
        'location',
        'employment_type',
        'start_date',
        'end_date',
        'industry',
        'job_description',
        'responsibilities',
        'skills_used',
        'is_current_job',
        'is_previous_job',
        'sort_order'
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'skills_used' => 'array',
        'is_current_job' => 'boolean',
        'is_previous_job' => 'boolean',
        'sort_order' => 'integer'
    ];

    // Employment type constants
    const TYPE_FULL_TIME = 'full_time';
    const TYPE_PART_TIME = 'part_time';
    const TYPE_INTERNSHIP = 'internship';
    const TYPE_FREELANCE = 'freelance';
    const TYPE_CONTRACT = 'contract';
    const TYPE_SELF_EMPLOYED = 'self_employed';

    /**
     * Get the user that owns the job record.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope to get current jobs
     */
    public function scopeCurrent($query)
    {
        return $query->where('is_current_job', true);
    }

    /**
     * Scope to get previous jobs
     */
    public function scopePrevious($query)
    {
        return $query->where('is_previous_job', true);
    }

    /**
     * Scope to get jobs by employment type
     */
    public function scopeByEmploymentType($query, $type)
    {
        return $query->where('employment_type', $type);
    }

    /**
     * Get employment type display name
     */
    public function getEmploymentTypeDisplayAttribute()
    {
        $types = [
            self::TYPE_FULL_TIME => 'Full-time',
            self::TYPE_PART_TIME => 'Part-time',
            self::TYPE_INTERNSHIP => 'Internship',
            self::TYPE_FREELANCE => 'Freelance',
            self::TYPE_CONTRACT => 'Contract',
            self::TYPE_SELF_EMPLOYED => 'Self-employed'
        ];

        return $types[$this->employment_type] ?? $this->employment_type;
    }

    /**
     * Get duration string
     */
    public function getDurationAttribute()
    {
        $startDate = $this->start_date ? Carbon::parse($this->start_date)->format('M Y') : null;
        $endDate = $this->end_date ? Carbon::parse($this->end_date)->format('M Y') : null;
        
        if ($startDate && $endDate) {
            return $startDate . ' - ' . $endDate;
        } elseif ($startDate) {
            return $startDate . ' - Present';
        }
        return null;
    }

    /**
     * Get duration in months
     */
    public function getDurationInMonthsAttribute()
    {
        if (!$this->start_date) {
            return 0;
        }

        $endDate = $this->end_date ?? now();
        return Carbon::parse($this->start_date)->diffInMonths($endDate);
    }

    /**
     * Get duration in years and months
     */
    public function getDurationInYearsMonthsAttribute()
    {
        if (!$this->start_date) {
            return '0 years 0 months';
        }

        $endDate = $this->end_date ?? now();
        $years = Carbon::parse($this->start_date)->diffInYears($endDate);
        $months = Carbon::parse($this->start_date)->addYears($years)->diffInMonths($endDate);
        
        return $years . ' years ' . $months . ' months';
    }

    /**
     * Check if job is currently active
     */
    public function getIsCurrentlyWorkingAttribute()
    {
        return $this->is_current_job || is_null($this->end_date);
    }
}
