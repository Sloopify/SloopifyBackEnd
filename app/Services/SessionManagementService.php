<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserSession;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Jenssegers\Agent\Agent;

class SessionManagementService
{
    protected $agent;

    public function __construct()
    {
        $this->agent = new Agent();
    }

    /**
     * Create a new session for user
     */
    public function createSession(User $user, Request $request, $expiresInDays = 30)
    {
        $sessionToken = UserSession::generateSessionToken();
        
        // Parse user agent and device information
        $this->agent->setUserAgent($request->header('User-Agent', ''));
        
        $deviceInfo = $this->parseDeviceInfo($request);
        $location = $this->getLocationFromIp($request->ip());

        $session = UserSession::create([
            'user_id' => $user->id,
            'session_token' => $sessionToken,
            'device_type' => $deviceInfo['device_type'],
            'device_name' => $deviceInfo['device_name'],
            'device_id' => $request->input('device_id') ?? $request->header('X-Device-ID'),
            'platform' => $deviceInfo['platform'],
            'browser' => $deviceInfo['browser'],
            'ip_address' => $request->ip(),
            'user_agent' => $request->header('User-Agent'),
            'push_token' => $request->input('push_token'),
            'location' => $location,
            'last_activity' => now(),
            'expires_at' => $expiresInDays ? now()->addDays($expiresInDays) : null,
            'is_active' => true
        ]);

        return $session;
    }

    /**
     * Parse device information from request
     */
    protected function parseDeviceInfo(Request $request)
    {
        $deviceType = 'web';
        $deviceName = null;
        $platform = null;
        $browser = null;

        // Check if request comes from mobile app
        if ($request->header('X-App-Type') === 'mobile') {
            $deviceType = 'mobile';
            $deviceName = $request->header('X-Device-Model');
            $platform = $request->header('X-Platform');
        } else {
            // Web browser detection
            if ($this->agent->isMobile()) {
                $deviceType = 'mobile';
            } elseif ($this->agent->isTablet()) {
                $deviceType = 'tablet';
            } else {
                $deviceType = 'web';
            }

            $platform = $this->agent->platform();
            $browser = $this->agent->browser();
            
            // Get device name for mobile/tablet
            if ($deviceType !== 'web') {
                $deviceName = $this->agent->device();
            } else {
                $deviceName = $browser . ' Browser';
            }
        }

        return [
            'device_type' => $deviceType,
            'device_name' => $deviceName,
            'platform' => $platform,
            'browser' => $browser
        ];
    }

    /**
     * Get location information from IP address
     */
    protected function getLocationFromIp($ip)
    {
        // Skip for local IPs
        if ($ip === '127.0.0.1' || $ip === '::1' || strpos($ip, '192.168.') === 0) {
            return ['country' => 'Local', 'city' => 'Local'];
        }

        try {
            // You can use a service like ipapi.co, ipinfo.io, or geoip2
            // For now, return null - implement based on your preferred service
            return null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Validate and get session by token
     */
    public function getSessionByToken($token)
    {
        return UserSession::where('session_token', $token)
            ->active()
            ->with('user')
            ->first();
    }

    /**
     * Update session activity
     */
    public function updateSessionActivity($sessionToken)
    {
        UserSession::where('session_token', $sessionToken)
            ->update(['last_activity' => now()]);
    }

    /**
     * Terminate session by token
     */
    public function terminateSession($sessionToken)
    {
        return UserSession::where('session_token', $sessionToken)
            ->update([
                'is_active' => false,
                'expires_at' => now()
            ]);
    }

    /**
     * Get user's active sessions
     */
    public function getUserActiveSessions($userId)
    {
        return UserSession::where('user_id', $userId)
            ->active()
            ->orderBy('last_activity', 'desc')
            ->get()
            ->map(function ($session) {
                return [
                    'id' => $session->id,
                    'device_display_name' => $session->device_display_name,
                    'location_display' => $session->location_display,
                    'device_type' => $session->device_type,
                    'platform' => $session->platform,
                    'browser' => $session->browser,
                    'ip_address' => $session->ip_address,
                    'last_activity' => $session->last_activity,
                    'is_current' => false, // Will be set by controller
                    'created_at' => $session->created_at
                ];
            });
    }

    /**
     * Clean up expired sessions
     */
    public function cleanupExpiredSessions()
    {
        return UserSession::expired()
            ->update([
                'is_active' => false
            ]);
    }

    /**
     * Limit concurrent sessions per user
     */
    public function limitConcurrentSessions($userId, $maxSessions = 5)
    {
        $activeSessions = UserSession::where('user_id', $userId)
            ->active()
            ->orderBy('last_activity', 'desc')
            ->get();

        if ($activeSessions->count() > $maxSessions) {
            $sessionsToTerminate = $activeSessions->slice($maxSessions);
            
            foreach ($sessionsToTerminate as $session) {
                $session->terminate();
            }
        }
    }

    /**
     * Get session statistics for user
     */
    public function getSessionStats($userId)
    {
        $sessions = UserSession::where('user_id', $userId);
        
        return [
            'total_sessions' => $sessions->count(),
            'active_sessions' => $sessions->active()->count(),
            'devices_used' => $sessions->distinct('device_type')->count('device_type'),
            'last_activity' => $sessions->active()->max('last_activity'),
            'first_session' => $sessions->min('created_at')
        ];
    }
} 