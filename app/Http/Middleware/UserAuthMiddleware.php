<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class UserAuthMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if(!Auth::guard('user')->check()){
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        if(Auth::guard('user')->user()->status === 'inactive'){
            Auth::guard('user')->logout();
            return response()->json(['message' => 'Your account is inactive.'], 401);
        }

        if(Auth::guard('user')->user()->is_blocked){
            Auth::guard('user')->logout();
            return response()->json(['message' => 'Your account is blocked.'], 401);
        }
        
        return $next($request);
    }
}
