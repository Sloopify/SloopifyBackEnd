<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;

class User extends Authenticatable
{

    use HasFactory, Notifiable, HasApiTokens;

    protected $guard = 'user';
    
    protected $fillable = [
        'first_name',
        'last_name',
        'user_name',
        'email',
        'email_verified_at',
        'password',
        'gender',
        'status',
        'is_blocked',
        'age',
        'birthday',
        'phone',
        'img',
        'bio',
        'referral_code',
        'referral_link',
        'reffred_by',
        'last_login_at',
        'country',
        'city',
        'google_id',
        'provider',
        'daily_status_id',
        'daily_status_expires_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];


    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'daily_status_expires_at' => 'datetime',
        ];
    }

    public function userInterests()
    {
        return $this->belongsToMany(Interest::class, 'user_interests', 'user_id', 'interest_id');
    }

    /**
     * Get the user's current daily status
     */
    public function dailyStatus()
    {
        return $this->belongsTo(DailyStatus::class, 'daily_status_id');
    }

    /**
     * Get friendships where this user is the initiator
     */
    public function sentFriendRequests()
    {
        return $this->hasMany(Friendship::class, 'user_id');
    }

    /**
     * Get friendships where this user is the recipient
     */
    public function receivedFriendRequests()
    {
        return $this->hasMany(Friendship::class, 'friend_id');
    }

    /**
     * Get all friendships (both directions) - needed for Story visibility scope
     */
    public function friendships()
    {
        return $this->hasMany(Friendship::class, 'user_id')
            ->union(
                $this->hasMany(Friendship::class, 'friend_id')
            );
    }

    /**
     * Get all accepted friends (both directions)
     */
    public function friends()
    {
        return $this->belongsToMany(User::class, 'friendships', 'user_id', 'friend_id')
            ->wherePivot('status', 'accepted')
            ->withPivot('status', 'requested_at', 'responded_at')
            ->union(
                $this->belongsToMany(User::class, 'friendships', 'friend_id', 'user_id')
                    ->wherePivot('status', 'accepted')
                    ->withPivot('status', 'requested_at', 'responded_at')
            );
    }

    /**
     * Get friends list with user details
     */
    public function getFriendsListAttribute()
    {
        $sentFriends = $this->sentFriendRequests()
            ->accepted()
            ->with('friend:id,first_name,last_name,img')
            ->get()
            ->pluck('friend');

        $receivedFriends = $this->receivedFriendRequests()
            ->accepted()
            ->with('user:id,first_name,last_name,img')
            ->get()
            ->pluck('user');

        return $sentFriends->merge($receivedFriends)->unique('id');
    }

    /**
     * Check if user is friends with another user
     */
    public function isFriendsWith($userId)
    {
        return $this->sentFriendRequests()
            ->where('friend_id', $userId)
            ->where('status', 'accepted')
            ->exists() ||
            $this->receivedFriendRequests()
            ->where('user_id', $userId)
            ->where('status', 'accepted')
            ->exists();
    }

    /**
     * Send friend request to another user
     */
    public function sendFriendRequest($userId)
    {
        return $this->sentFriendRequests()->create([
            'friend_id' => $userId,
            'status' => 'pending',
            'requested_at' => now()
        ]);
    }

    /**
     * Get full name attribute
     */
    public function getFullNameAttribute()
    {
        return $this->first_name . ' ' . $this->last_name;
    }

    /**
     * Get profile image URL
     */
    public function getProfileImageAttribute()
    {
        if ($this->provider === 'google' && $this->img) {
            return $this->img;
        }
        
        return $this->img ? config('app.url') . '/storage/' . $this->img : null;
    }

    /**
     * Get all user sessions
     */
    public function sessions()
    {
        return $this->hasMany(UserSession::class);
    }

    /**
     * Get active user sessions
     */
    public function activeSessions()
    {
        return $this->hasMany(UserSession::class)->active();
    }

    /**
     * Get user education records
     */
    public function educations()
    {
        return $this->hasMany(UserEducation::class)->orderBy('sort_order', 'asc');
    }

    /**
     * Get current education
     */
    public function currentEducation()
    {
        return $this->hasMany(UserEducation::class)->current();
    }

    /**
     * Get completed education
     */
    public function completedEducation()
    {
        return $this->hasMany(UserEducation::class)->completed();
    }

    /**
     * Get user job records
     */
    public function jobs()
    {
        return $this->hasMany(UserJob::class)->orderBy('sort_order', 'asc');
    }

    /**
     * Get current jobs
     */
    public function currentJobs()
    {
        return $this->hasMany(UserJob::class)->current();
    }

    /**
     * Get previous jobs
     */
    public function previousJobs()
    {
        return $this->hasMany(UserJob::class)->previous();
    }

    /**
     * Get user links
     */
    public function links()
    {
        return $this->hasMany(UserLink::class)->orderBy('sort_order', 'asc');
    }

    /**
     * Get active user links
     */
    public function activeLinks()
    {
        return $this->hasMany(UserLink::class)->active();
    }

    /**
     * Terminate all sessions except current
     */
    public function terminateOtherSessions($currentSessionToken = null)
    {
        $query = $this->sessions()->active();
        
        if ($currentSessionToken) {
            $query->where('session_token', '!=', $currentSessionToken);
        }
        
        return $query->update([
            'is_active' => false,
            'expires_at' => now()
        ]);
    }

    /**
     * Terminate all sessions
     */
    public function terminateAllSessions()
    {
        return $this->sessions()->active()->update([
            'is_active' => false,
            'expires_at' => now()
        ]);
    }

}
