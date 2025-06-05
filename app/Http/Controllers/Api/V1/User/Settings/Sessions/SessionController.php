<?php

namespace App\Http\Controllers\Api\V1\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\SessionManagementService;
use App\Models\UserSession;
use Illuminate\Validation\ValidationException;
use Exception;

class SessionController extends Controller
{
    protected $sessionService;

    public function __construct(SessionManagementService $sessionService)
    {
        $this->sessionService = $sessionService;
    }

    /**
     * Get all active sessions for the authenticated user
     */
    public function index(Request $request)
    {
        try {
            $user = Auth::guard('user')->user();
            $sessions = $this->sessionService->getUserActiveSessions($user->id);
            
            // Get current session token from request header
            $currentSessionToken = $request->header('X-Session-Token') ?? $request->bearerToken();
            
            // Mark current session
            $sessions = $sessions->map(function ($session) use ($currentSessionToken) {
                // You might need to adjust this logic based on how you store session tokens
                $session['is_current'] = $this->isCurrentSession($session, $currentSessionToken);
                return $session;
            });

            return response()->json([
                'status_code' => 200,
                'success' => true,
                'message' => 'Active sessions retrieved successfully',
                'data' => [
                    'sessions' => $sessions,
                    'total_active_sessions' => $sessions->count()
                ]
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'status_code' => 500,
                'success' => false,
                'message' => 'Failed to retrieve sessions',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get session statistics for the authenticated user
     */
    public function stats()
    {
        try {
            $user = Auth::guard('user')->user();
            $stats = $this->sessionService->getSessionStats($user->id);

            return response()->json([
                'status_code' => 200,
                'success' => true,
                'message' => 'Session statistics retrieved successfully',
                'data' => $stats
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'status_code' => 500,
                'success' => false,
                'message' => 'Failed to retrieve session statistics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Terminate a specific session
     */
    public function terminate(Request $request, $sessionId)
    {
        try {
            $user = Auth::guard('user')->user();
            
            // Find the session belonging to the authenticated user
            $session = UserSession::where('id', $sessionId)
                ->where('user_id', $user->id)
                ->active()
                ->first();

            if (!$session) {
                return response()->json([
                    'status_code' => 404,
                    'success' => false,
                    'message' => 'Session not found or already terminated'
                ], 404);
            }

            // Terminate the session
            $session->terminate();

            return response()->json([
                'status_code' => 200,
                'success' => true,
                'message' => 'Session terminated successfully'
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'status_code' => 500,
                'success' => false,
                'message' => 'Failed to terminate session',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Terminate all other sessions except current
     */
    public function terminateOthers(Request $request)
    {
        try {
            $user = Auth::guard('user')->user();
            $currentSessionToken = $request->header('X-Session-Token') ?? $request->bearerToken();
            
            // Terminate all other sessions
            $terminatedCount = $user->terminateOtherSessions($currentSessionToken);

            return response()->json([
                'status_code' => 200,
                'success' => true,
                'message' => "Successfully terminated {$terminatedCount} other sessions",
                'data' => [
                    'terminated_sessions' => $terminatedCount
                ]
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'status_code' => 500,
                'success' => false,
                'message' => 'Failed to terminate other sessions',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Terminate all sessions (logout from all devices)
     */
    public function terminateAll()
    {
        try {
            $user = Auth::guard('user')->user();
            
            // Terminate all sessions
            $terminatedCount = $user->terminateAllSessions();

            // Revoke all access tokens (if using Passport)
            $user->tokens()->delete();

            return response()->json([
                'status_code' => 200,
                'success' => true,
                'message' => "Successfully logged out from all devices",
                'data' => [
                    'terminated_sessions' => $terminatedCount
                ]
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'status_code' => 500,
                'success' => false,
                'message' => 'Failed to logout from all devices',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get current session details
     */
    public function current(Request $request)
    {
        try {
            $currentSessionToken = $request->header('X-Session-Token') ?? $request->bearerToken();
            
            if (!$currentSessionToken) {
                return response()->json([
                    'status_code' => 400,
                    'success' => false,
                    'message' => 'Session token not provided'
                ], 400);
            }

            $session = $this->sessionService->getSessionByToken($currentSessionToken);
            
            if (!$session) {
                return response()->json([
                    'status_code' => 404,
                    'success' => false,
                    'message' => 'Current session not found'
                ], 404);
            }

            return response()->json([
                'status_code' => 200,
                'success' => true,
                'message' => 'Current session retrieved successfully',
                'data' => [
                    'id' => $session->id,
                    'device_display_name' => $session->device_display_name,
                    'location_display' => $session->location_display,
                    'device_type' => $session->device_type,
                    'platform' => $session->platform,
                    'browser' => $session->browser,
                    'ip_address' => $session->ip_address,
                    'last_activity' => $session->last_activity,
                    'created_at' => $session->created_at,
                    'is_current' => true
                ]
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'status_code' => 500,
                'success' => false,
                'message' => 'Failed to retrieve current session',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update session activity (heartbeat)
     */
    public function heartbeat(Request $request)
    {
        try {
            $currentSessionToken = $request->header('X-Session-Token') ?? $request->bearerToken();
            
            if (!$currentSessionToken) {
                return response()->json([
                    'status_code' => 400,
                    'success' => false,
                    'message' => 'Session token not provided'
                ], 400);
            }

            $this->sessionService->updateSessionActivity($currentSessionToken);

            return response()->json([
                'status_code' => 200,
                'success' => true,
                'message' => 'Session activity updated'
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'status_code' => 500,
                'success' => false,
                'message' => 'Failed to update session activity',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Clean up expired sessions (admin function)
     */
    public function cleanup()
    {
        try {
            $cleanedCount = $this->sessionService->cleanupExpiredSessions();

            return response()->json([
                'status_code' => 200,
                'success' => true,
                'message' => "Cleaned up {$cleanedCount} expired sessions"
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'status_code' => 500,
                'success' => false,
                'message' => 'Failed to cleanup expired sessions',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Check if a session is the current session
     */
    private function isCurrentSession($session, $currentToken)
    {
        // This logic depends on how you implement session token matching
        // You might need to adjust this based on your authentication system
        return false; // Placeholder - implement based on your needs
    }
}
