<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Classes\Api\PersonalizedExperienceCls;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PersonalizedExperienceController extends Controller
{
    protected $ExperienceCls;

    public function __construct(PersonalizedExperienceCls $ExperienceCls)
    {
        $this->ExperienceCls = $ExperienceCls;
    }

    /**
     * Get all sections with questions
     * GET /api/experience/sections
     * 
     * For authenticated users with a business_id, returns business-specific sections.
     * Also supports 'business_code' header to specify business without authentication.
     * Falls back to admin sections if business has no custom sections.
     */
    public function GetSections(Request $request)
    {
        $locale = $request->header('Accept-Language', 'en');

        // Normalize locale
        if (str_starts_with($locale, 'fr')) {
            $locale = 'fr';
        } else {
            $locale = 'en';
        }

        // Get business_id from multiple sources (priority order)
        $businessId = null;

        // 1. Check if business_code header is provided
        $businessCode = $request->header('business_code') ?? $request->header('business-code');
        if ($businessCode) {
            $business = \App\Models\Business::where('business_code', $businessCode)->first();
            if ($business) {
                $businessId = $business->id;
            }
        }

        // 2. If no business_code, check if user is authenticated and has business_id
        if (!$businessId && Auth::check()) {
            $user = Auth::user();
            $businessId = $user->business_id ?? null;
        }

        $result = $this->ExperienceCls->GetExperienceData($locale, $businessId);

        if ($result['success']) {
            return response()->json([
                'status' => true,
                'message' => 'Sections retrieved successfully',
                'data' => $result['data'],
                'total_sections' => $result['total_sections'],
            ], 200);
        }

        return response()->json([
            'status' => false,
            'message' => $result['message'] ?? 'Failed to retrieve sections',
        ], 500);
    }

    /**
     * Submit user responses
     * POST /api/experience/submit
     */
    public function SubmitResponses(Request $request)
    {
        $request->validate([
            'responses' => 'required|array',
            'responses.*.question_id' => 'required|exists:questions,id',
        ]);

        $userId = Auth::id();
        $responses = $request->input('responses');

        $result = $this->ExperienceCls->SubmitResponses($userId, $responses);

        if ($result['success']) {
            return response()->json([
                'status' => true,
                'message' => $result['message'],
            ], 200);
        }

        return response()->json([
            'status' => false,
            'message' => $result['message'] ?? 'Failed to submit responses',
        ], 500);
    }

    /**
     * Get user's responses
     * GET /api/experience/responses
     */
    public function GetResponses(Request $request)
    {
        $userId = Auth::id();

        $result = $this->ExperienceCls->GetUserResponses($userId);

        if ($result['success']) {
            return response()->json([
                'status' => true,
                'message' => 'Responses retrieved successfully',
                'data' => $result['data'],
            ], 200);
        }

        return response()->json([
            'status' => false,
            'message' => $result['message'] ?? 'Failed to retrieve responses',
        ], 500);
    }

    /**
     * Get completion status
     * GET /api/experience/status
     */
    public function GetStatus(Request $request)
    {
        $userId = Auth::id();

        $result = $this->ExperienceCls->GetCompletionStatus($userId);

        if ($result['success']) {
            return response()->json([
                'status' => true,
                'message' => 'Status retrieved successfully',
                'data' => [
                    'total_questions' => $result['total_questions'],
                    'completion_percentage' => $result['completion_percentage'],
                ],
            ], 200);
        }

        return response()->json([
            'status' => false,
            'message' => $result['message'] ?? 'Failed to retrieve status',
        ], 500);
    }
}
