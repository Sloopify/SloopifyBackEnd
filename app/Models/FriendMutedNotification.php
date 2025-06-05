<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class FriendMutedNotification extends Model
{
    use HasFactory;

    protected $table = 'friend_muted_notifications';

    protected $fillable = [
        'user_id',
        'friend_id',
        'is_muted',
        'muted_at',
        'reason',
        'description',
        'is_active',
        'duration',
        'expires_at'
    ];

    protected $casts = [
        'is_muted' => 'boolean',
        'is_active' => 'boolean',
        'muted_at' => 'datetime',
        'expires_at' => 'datetime'
    ];

    protected $dates = [
        'muted_at',
        'expires_at',
        'created_at',
        'updated_at'
    ];

    /**
     * Get the user who muted notifications
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the friend whose notifications are muted
     */
    public function friend()
    {
        return $this->belongsTo(User::class, 'friend_id');
    }

    /**
     * Scope for active muted notifications
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
                    ->where('is_muted', true);
    }

    /**
     * Scope for non-expired muted notifications
     */
    public function scopeNotExpired($query)
    {
        return $query->where(function($q) {
            $q->whereNull('expires_at')
              ->orWhere('expires_at', '>', now());
        });
    }

    /**
     * Check if the mute is still active
     */
    public function isActiveMute()
    {
        if (!$this->is_active || !$this->is_muted) {
            return false;
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        return true;
    }

    /**
     * Check if a user has muted notifications from a friend
     */
    public static function isMuted($userId, $friendId)
    {
        return self::where('user_id', $userId)
                  ->where('friend_id', $friendId)
                  ->active()
                  ->notExpired()
                  ->exists();
    }

    /**
     * Get all users who have muted notifications from a specific friend
     */
    public static function getUsersWhoMuted($friendId)
    {
        return self::where('friend_id', $friendId)
                  ->active()
                  ->notExpired()
                  ->pluck('user_id')
                  ->toArray();
    }

    /**
     * Mute notifications from a friend
     */
    public static function muteNotifications($userId, $friendId, $duration = 'always', $reason = null, $description = null)
    {
        $expiresAt = null;
        if ($duration === '1_month') {
            $expiresAt = now()->addMonth();
        }

        return self::updateOrCreate(
            [
                'user_id' => $userId,
                'friend_id' => $friendId
            ],
            [
                'is_muted' => true,
                'muted_at' => now(),
                'reason' => $reason,
                'description' => $description,
                'is_active' => true,
                'duration' => $duration,
                'expires_at' => $expiresAt
            ]
        );
    }

    /**
     * Unmute notifications from a friend
     */
    public static function unmuteNotifications($userId, $friendId)
    {
        return self::where('user_id', $userId)
                  ->where('friend_id', $friendId)
                  ->update([
                      'is_muted' => false,
                      'is_active' => false
                  ]);
    }

    /**
     * Clean up expired muted notifications
     */
    public static function cleanupExpired()
    {
        return self::where('expires_at', '<', now())
                  ->where('is_active', true)
                  ->update([
                      'is_active' => false,
                      'is_muted' => false
                  ]);
    }
}
