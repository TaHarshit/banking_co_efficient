<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminAuth
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if user is authenticated with the default guard (admin)
        if (!auth()->check()) {
            return redirect()->route('login')->with('error', 'Please login to access admin panel');
        }

        // Prevent business users from accessing admin panel
        if (auth()->guard('business')->check()) {
            auth()->guard('business')->logout();
            return redirect()->route('business.login')->with('error', 'Please use business login');
        }

        return $next($request);
    }
}
