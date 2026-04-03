<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use DB;
use Carbon\Carbon;
use App\General\General;

class VerifyApiMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        try {

            $vtoken      = VerifyApiMiddleware::verifyUser($request);
            $vplatform   = VerifyApiMiddleware::verifyPlatform($request);
            if (!empty($vtoken)) {
                return get_response($request, $vtoken);
            } elseif (!empty($vplatform)) {
                return get_response($request, $vplatform);
            } else {
                return $next($request);
            }
        } catch (\Exception $e) {
            $data = General::setResponse('OTHER_ERROR', $e->getMessage());
            return get_response($request, $data);
        }
    }

    public static function verifyUser($request)
    {
        try {
            $data = array();
            $api_key  = $request->header('api-key');
            if ($api_key != config('enum.general.Apikey')) {
                $data = General::setResponse('VALIDATION_ERROR', 'Access denied, invalid api key');
            }
            return $data;
        } catch (\Exception $e) {
            $data = General::setResponse('OTHER_ERROR', $e->getMessage());
            return get_response($request, $data);
        }
    }

    public static function verifyPlatform($request)
    {
        try {
            $data = array();
            $platform = $request->header('platform');
            $platform_array = explode(',', config('enum.general.Platform'));
            if (!in_array($platform, $platform_array)) {
                $data = General::setResponse('VALIDATION_ERROR', 'Access denied, invalid platform string is used');
            }
            return $data;
        } catch (\Exception $e) {
            $data = General::setResponse('OTHER_ERROR', $e->getMessage());
            return get_response($request, $data);
        }
    }
}
