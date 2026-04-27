<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PdfQuestionController extends Controller
{
    /**
     * Ask a question and get answer from PDF
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function ask(Request $request)
    {
        try {
            // Validate the request
            $validated = $request->validate([
                'question' => 'required|string|min:3|max:500',
                'history' => 'nullable|array'
            ]);

            $question = $validated['question'];
            $history = $validated['history'] ?? [];

            // Get Python service URL from environment or use default
            $pythonServiceUrl = env('PDF_SERVICE_URL', 'http://127.0.0.1:8000/ask');

            // Log the request
            Log::info('PDF Question API called', ['question' => $question]);

            // Call the Python microservice
            $response = Http::timeout(60)->post($pythonServiceUrl, [
                'question' => $question,
                'history' => $history
            ]);

            // Check if the request was successful
            if ($response->successful()) {
                $data = $response->json();

                return response()->json([
                    'success' => true,
                    'answer' => $data['answer'] ?? 'No answer found',
                    'suggestions' => $data['suggestions'] ?? 'No Suggestion',
                    'images' => $data['images'] ?? [],
                    'reference_pages' => $data['reference_pages'] ?? [],
                    'message' => 'Answer retrieved successfully'
                ], 200);
            } else {
                // Python service returned an error
                Log::error('PDF Service Error', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Failed to get answer from PDF service',
                    'error' => 'Service unavailable'
                ], 503);
            }
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('PDF Service Connection Error', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Cannot connect to PDF service. Please ensure the Python service is running.',
                'error' => 'Connection failed'
            ], 503);
        } catch (\Exception $e) {
            Log::error('PDF Question API Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while processing your question',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Check if PDF service is running
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function status()
    {
        try {
            $pythonServiceUrl = env('PDF_SERVICE_URL', 'http://127.0.0.1:8000');

            // Remove /ask from the URL if present
            $baseUrl = str_replace('/ask', '', $pythonServiceUrl);

            $response = Http::timeout(5)->get($baseUrl . '/docs');

            if ($response->successful()) {
                return response()->json([
                    'success' => true,
                    'message' => 'PDF service is running',
                    'service_url' => $baseUrl
                ], 200);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'PDF service is not responding',
                    'service_url' => $baseUrl
                ], 503);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'PDF service is not running',
                'error' => $e->getMessage()
            ], 503);
        }
    }
}
