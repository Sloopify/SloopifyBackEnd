<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DailyStatus extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'web_icon',
        'mobile_icon',
        'status'
    ];

    protected $casts = [
        'status' => 'boolean'
    ];

    /**
     * Get users who have this daily status
     */
    public function users()
    {
        return $this->hasMany(User::class, 'daily_status_id');
    }

    /**
     * Scope to get only active daily statuses
     */
    public function scopeActive($query)
    {
        return $query->where('status', true);
    }

    /**
     * Scope to search by name
     */
    public function scopeSearch($query, $search)
    {
        return $query->where('name', 'LIKE', '%' . $search . '%');
    }
}
