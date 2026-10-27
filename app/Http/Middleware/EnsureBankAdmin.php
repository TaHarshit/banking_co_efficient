<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;

class EnsureBankAdmin
{
    /**
     * Handle an incoming request.
     *
     * Ensures only Bank Administrators (and not Bank Managers) can access the route.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (session('business_role') === 'manager') {
            Session::flash('message', __('messages.access_restricted_admin_only') ?? 'Access restricted to Bank Administrators.');
            Session::flash('icon', 'warning');

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => __('messages.access_restricted_admin_only') ?? 'Access restricted to Bank Administrators.'
                ], 403);
            }

            return redirect()->route('business.skill-assessment.exam-results');
        }

        return $next($request);
    }
}
