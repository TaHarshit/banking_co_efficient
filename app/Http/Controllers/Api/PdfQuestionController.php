<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ChatHistory;
use App\Models\ChatSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

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
                'chat_session_id' => 'nullable|exists:chat_sessions,id,user_id,' . Auth::id(),
                'history' => 'nullable|array'
            ]);

            $question = $validated['question'];
            $sessionId = $validated['chat_session_id'] ?? null;
            $history = $validated['history'] ?? [];
            $session = null;

            // Auto-create session if not provided
            if ($sessionId) {
                $session = ChatSession::find($sessionId);
            } else {
                $session = ChatSession::create([
                    'user_id' => Auth::id(),
                    'title' => 'New Chat'
                ]);
                $sessionId = $session->id;
            }

            // Retrieve past messages as history if not manually provided
            if (empty($history)) {
                $pastMessages = ChatHistory::where('chat_session_id', $sessionId)
                    ->orderBy('created_at', 'desc')
                    ->limit(5)
                    ->get()
                    ->reverse();

                foreach ($pastMessages as $msg) {
                    $history[] = ['role' => 'user', 'content' => $msg->question];
                    $history[] = ['role' => 'assistant', 'content' => $msg->answer];
                }
            }

            // Get Python service URL from environment or use default
            $pythonServiceUrl = config('services.pdf_service.url');

            // Log the request
            Log::info('PDF Question API called', [
                'question' => $question,
                'chat_session_id' => $sessionId
            ]);

            // Call the Python microservice
            $response = Http::timeout(60)->post($pythonServiceUrl, [
                'question' => $question,
                'history' => $history
            ]);

            // Check if the request was successful
            if ($response->successful()) {
                $data = $response->json();

                $answer = $data['answer'] ?? 'No answer found';
                $suggestions = $data['suggestions'] ?? [];
                $images = $data['images'] ?? [];
                $reference_pages = $data['reference_pages'] ?? [];

                // Store in history
                try {
                    ChatHistory::create([
                        'user_id' => Auth::id(),
                        'chat_session_id' => $sessionId,
                        'question' => $question,
                        'answer' => $answer,
                        'suggestions' => $suggestions,
                        'images' => $images,
                        'reference_pages' => $reference_pages
                    ]);

                    // Auto-update title if it's default 'New Chat' and touch session to update timestamp
                    if ($session) {
                        if ($session->title === 'New Chat' || empty($session->title)) {
                            $session->title = mb_substr($question, 0, 40) . (mb_strlen($question) > 40 ? '...' : '');
                        }
                        $session->touch();
                    }
                } catch (\Exception $e) {
                    Log::error('Failed to save chat history', ['error' => $e->getMessage()]);
                    // Don't fail the request if history storage fails
                }
                
                return response()->json([
                    'success' => true,
                    'answer' => $answer,
                    'suggestions' => !empty($suggestions) ? $suggestions : 'No Suggestion',
                    'images' => $images,
                    'reference_pages' => $reference_pages,
                    'chat_session_id' => $sessionId,
                    'chat_session_title' => $session ? $session->title : null,
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
     * Get chat history for the authenticated user
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getHistory(Request $request)
    {
        try {
            $query = ChatHistory::where('user_id', Auth::id());

            if ($request->has('chat_session_id')) {
                $query->where('chat_session_id', $request->query('chat_session_id'));
                $order = 'asc';
            } else {
                $order = 'desc';
            }

            $history = $query->orderBy('created_at', $order)
                ->paginate(20);

            return response()->json([
                'success' => true,
                'history' => $history,
                'message' => 'Chat history retrieved successfully'
            ], 200);
        } catch (\Exception $e) {
            Log::error('Failed to retrieve chat history', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving chat history',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * List chat sessions for the authenticated user
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getSessions(Request $request)
    {
        try {
            $sessions = ChatSession::where('user_id', Auth::id())
                ->withCount('messages')
                ->orderBy('updated_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'sessions' => $sessions,
                'message' => 'Chat sessions retrieved successfully'
            ], 200);
        } catch (\Exception $e) {
            Log::error('Failed to retrieve chat sessions', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve chat sessions',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create a new chat session
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function createSession(Request $request)
    {
        try {
            $validated = $request->validate([
                'title' => 'nullable|string|max:100'
            ]);

            $session = ChatSession::create([
                'user_id' => Auth::id(),
                'title' => $validated['title'] ?? 'New Chat'
            ]);

            return response()->json([
                'success' => true,
                'session' => $session,
                'message' => 'Chat session created successfully'
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Failed to create chat session', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to create chat session',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Rename an existing chat session
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function renameSession(Request $request)
    {
        try {
            $validated = $request->validate([
                'chat_session_id' => 'required|exists:chat_sessions,id,user_id,' . Auth::id(),
                'title' => 'required|string|min:1|max:100'
            ]);

            $session = ChatSession::findOrFail($validated['chat_session_id']);
            $session->title = $validated['title'];
            $session->save();

            return response()->json([
                'success' => true,
                'session' => $session,
                'message' => 'Chat session renamed successfully'
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Failed to rename chat session', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to rename chat session',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a chat session and its history
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteSession(Request $request)
    {
        try {
            $validated = $request->validate([
                'chat_session_id' => 'required|exists:chat_sessions,id,user_id,' . Auth::id()
            ]);

            $session = ChatSession::findOrFail($validated['chat_session_id']);
            $session->delete();

            return response()->json([
                'success' => true,
                'message' => 'Chat session deleted successfully'
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Failed to delete chat session', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete chat session',
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
            $pythonServiceUrl = config('services.pdf_service.url');

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
