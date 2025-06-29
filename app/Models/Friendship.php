<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Friendship extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'friend_id',
        'status',
        'requested_at',
        'responded_at',
        'accepted_at'
    ];

    protected $casts = [
        'requested_at' => 'datetime',
        'responded_at' => 'datetime',
        'accepted_at' => 'datetime',
    ];

    /**
     * Get the user who initiated the friendship
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the friend user
     */
    public function friend()
    {
        return $this->belongsTo(User::class, 'friend_id');
    }

    /**
     * Scope to get accepted friendships
     */
    public function scopeAccepted($query)
    {
        return $query->where('status', 'accepted');
    }

    /**
     * Scope to get pending friendships
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Check if friendship is accepted
     */
    public function isAccepted()
    {
        return $this->status === 'accepted';
    }

    /**
     * Check if friendship is pending
     */
    public function isPending()
    {
        return $this->status === 'pending';
    }

    /**
     * Accept the friendship request
     */
    public function accept()
    {
        $this->update([
            'status' => 'accepted',
            'responded_at' => now(),
            'accepted_at' => now()
        ]);
    }

    /**
     * Decline the friendship request
     */
    public function decline()
    {
        $this->update([
            'status' => 'declined',
            'responded_at' => now()
        ]);
    }

    /**
     * Block the friendship
     */
    public function block()
    {
        $this->update([
            'status' => 'blocked',
            'responded_at' => now()
        ]);
    }

    /**
     * Cancel the friendship request
     */
    public function cancel()
    {
        $this->update([
            'status' => 'cancelled',
            'responded_at' => now()
        ]);
    }
} 