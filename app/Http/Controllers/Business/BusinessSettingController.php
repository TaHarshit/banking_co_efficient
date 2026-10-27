<?php

namespace App\Http\Controllers\Business;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class BusinessSettingController extends Controller
{
    /**
     * Show the business privacy policy edit form.
     */
    public function privacyPolicy()
    {
        $business = Auth::guard('business')->user();

        return view('business.settings.privacy_policy', compact('business'));
    }

    /**
     * Update the business privacy policy.
     */
    public function updatePrivacyPolicy(Request $request)
    {
        $request->validate([
            'privacy_policy' => 'nullable|string',
            'privacy_policy_fr' => 'nullable|string',
        ]);

        $business = Auth::guard('business')->user();

        $business->update([
            'privacy_policy' => $request->input('privacy_policy'),
            'privacy_policy_fr' => $request->input('privacy_policy_fr'),
        ]);

        // Log the business activity
        if (function_exists('logBusinessActivity')) {
            logBusinessActivity(
                'privacy_policy',
                'update',
                $business->id,
                'Updated institution privacy policy',
                [
                    'has_en' => !empty(trim(strip_tags($request->privacy_policy ?? ''))),
                    'has_fr' => !empty(trim(strip_tags($request->privacy_policy_fr ?? ''))),
                ],
                $business->id
            );
        }

        Session::flash('message', __('messages.privacy_policy_updated') ?? 'Privacy policy updated successfully.');
        Session::flash('icon', 'success');

        return redirect()->route('business.privacy_policy');
    }
}
