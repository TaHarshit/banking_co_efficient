<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class CaseStudyController extends Controller
{
    /**
     * Analyze a case and get AI recommendations
     */
    public function analyzeCase(Request $request)
    {
        try {
            $validated = $request->validate([
                'client_alias' => 'required|string|max:255',
                'context_overview' => 'nullable|string',
                'case_details' => 'required|array',
            ]);

            $user = Auth::user();
            $userProfile = $user->getAiBehaviorProfile();

            // Get Python service base URL
            $pythonUrl = env('PDF_SERVICE_BASE_URL', 'http://127.0.0.1:8000');
            $endpoint = rtrim($pythonUrl, '/') . '/analyze-case';

            Log::info('Analyzing Case', ['alias' => $validated['client_alias']]);

            $response = Http::timeout(60)->post($endpoint, [
                'client_alias' => $validated['client_alias'],
                'context_overview' => $validated['context_overview'] ?? '',
                'case_details' => $validated['case_details'],
                'user_profile' => $userProfile,
            ]);

            if ($response->successful()) {
                return response()->json([
                    'success' => true,
                    'data' => $response->json(),
                    'message' => 'Analysis completed successfully'
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'AI Service error',
                'error' => $response->body()
            ], 500);

        } catch (\Exception $e) {
            Log::error('Case Analysis Error', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'An error occurred during analysis',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate a full negotiation action plan
     */
    public function generatePlan(Request $request)
    {
        try {
            $validated = $request->validate([
                'case_data' => 'required|array',
                'analysis_data' => 'required|array',
            ]);

            $user = Auth::user();
            $userProfile = $user->getAiBehaviorProfile();

            $pythonUrl = env('PDF_SERVICE_BASE_URL', 'http://127.0.0.1:8000');
            $endpoint = rtrim($pythonUrl, '/') . '/generate-plan';

            $response = Http::timeout(60)->post($endpoint, [
                'case_data' => $validated['case_data'],
                'analysis_data' => $validated['analysis_data'],
                'user_profile' => $userProfile,
            ]);

            if ($response->successful()) {
                return response()->json([
                    'success' => true,
                    'data' => $response->json(),
                    'message' => 'Action plan generated successfully'
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'AI Service error',
                'error' => $response->body()
            ], 500);

        } catch (\Exception $e) {
            Log::error('Plan Generation Error', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while generating the plan',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
