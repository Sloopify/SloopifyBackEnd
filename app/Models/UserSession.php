<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Carbon\Carbon;

class UserSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'session_token',
        'device_type',
        'device_name',
        'device_id',
        'platform',
        'browser',
        'ip_address',
        'user_agent',
        'push_token',
        'location',
        'last_activity',
        'expires_at',
        'is_active'
    ];

    protected $casts = [
        'location' => 'array',
        'last_activity' => 'datetime',
        'expires_at' => 'datetime',
        'is_active' => 'boolean'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)
                    ->where(function($q) {
                        $q->whereNull('expires_at')
                          ->orWhere('expires_at', '>', now());
                    });
    }

    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<=', now());
    }

    public function isExpired()
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function isActive()
    {
        return $this->is_active && !$this->isExpired();
    }

    public function terminate()
    {
        $this->update([
            'is_active' => false,
            'expires_at' => now()
        ]);
    }

    public function updateActivity()
    {
        $this->update(['last_activity' => now()]);
    }

    public static function generateSessionToken()
    {
        return Str::random(60);
    }

    public function getDeviceDisplayNameAttribute()
    {
        $name = [];
        
        if ($this->device_name) {
            $name[] = $this->device_name;
        } elseif ($this->browser) {
            $name[] = $this->browser;
        }
        
        if ($this->platform) {
            $name[] = $this->platform;
        }
        
        if ($this->device_type) {
            $name[] = ucfirst($this->device_type);
        }
        
        return !empty($name) ? implode(' - ', $name) : 'Unknown Device';
    }

    public function getLocationDisplayAttribute()
    {
        if (!$this->location) {
            return 'Unknown Location';
        }
        
        $location = [];
        if (isset($this->location['city'])) {
            $location[] = $this->location['city'];
        }
        if (isset($this->location['country'])) {
            $location[] = $this->location['country'];
        }
        
        return !empty($location) ? implode(', ', $location) : 'Unknown Location';
    }
}
