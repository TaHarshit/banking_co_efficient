<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ChatHistory;
use App\Models\ChatSession;
use App\General\General;
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
     * @return \Illuminate\Http\Response
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

            // Retrieve client language preference
            $locale = $request->header('Accept-Language', 'en');

            // Call the Python microservice
            $response = Http::timeout(60)->withHeaders([
                'Accept-Language' => $locale
            ])->post($pythonServiceUrl, [
                'question' => $question,
                'history' => $history,
                'lang' => $locale
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
                
                $apiResponse = General::setResponse('SUCCESS', 'Answer retrieved successfully');
                $apiResponse['data'] = [
                    'answer' => $answer,
                    'suggestions' => !empty($suggestions) ? $suggestions : 'No Suggestion',
                    'images' => $images,
                    'reference_pages' => $reference_pages,
                    'chat_session_id' => $sessionId,
                    'chat_session_title' => $session ? $session->title : null,
                ];

                return get_response($request, $apiResponse);
            } else {
                // Python service returned an error
                Log::error('PDF Service Error', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);

                $apiResponse = General::setResponse('OTHER_ERROR', 'Failed to get answer from PDF service');

                return get_response($request, $apiResponse);
            }
        } catch (\Illuminate\Validation\ValidationException $e) {
            $apiResponse = General::setResponse('VALIDATION_ERROR', 'Validation failed');
            $apiResponse['errors'] = $e->errors();

            return get_response($request, $apiResponse);
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('PDF Service Connection Error', [
                'url' => $pythonServiceUrl,
                'error' => $e->getMessage()
            ]);

            $apiResponse = General::setResponse('OTHER_ERROR', 'Cannot connect to PDF service (' . $pythonServiceUrl . '). Error: ' . $e->getMessage());

            return get_response($request, $apiResponse);
        } catch (\Exception $e) {
            Log::error('PDF Question API Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            $apiResponse = General::setResponse('OTHER_ERROR', 'An error occurred while processing your question');

            return get_response($request, $apiResponse);
        }
    }

    /**
     * Get chat history for the authenticated user
     * 
     * @param Request $request
     * @return \Illuminate\Http\Response
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

            $apiResponse = General::setResponse('SUCCESS', 'Chat history retrieved successfully');
            $apiResponse['data'] = $history;

            return get_response($request, $apiResponse);
        } catch (\Exception $e) {
            Log::error('Failed to retrieve chat history', ['error' => $e->getMessage()]);

            $apiResponse = General::setResponse('OTHER_ERROR', 'An error occurred while retrieving chat history');

            return get_response($request, $apiResponse);
        }
    }

    /**
     * List chat sessions for the authenticated user
     * 
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function getSessions(Request $request)
    {
        try {
            $sessions = ChatSession::where('user_id', Auth::id())
                ->withCount('messages')
                ->orderBy('updated_at', 'desc')
                ->get();

            $apiResponse = General::setResponse('SUCCESS', 'Chat sessions retrieved successfully');
            $apiResponse['data'] = $sessions;

            return get_response($request, $apiResponse);
        } catch (\Exception $e) {
            Log::error('Failed to retrieve chat sessions', ['error' => $e->getMessage()]);

            $apiResponse = General::setResponse('OTHER_ERROR', 'Failed to retrieve chat sessions');

            return get_response($request, $apiResponse);
        }
    }

    /**
     * Create a new chat session
     * 
     * @param Request $request
     * @return \Illuminate\Http\Response
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

            $apiResponse = General::setResponse('SUCCESS', 'Chat session created successfully');
            $apiResponse['code'] = 201;
            $apiResponse['data'] = $session;

            return get_response($request, $apiResponse);
        } catch (\Illuminate\Validation\ValidationException $e) {
            $apiResponse = General::setResponse('VALIDATION_ERROR', 'Validation failed');
            $apiResponse['errors'] = $e->errors();

            return get_response($request, $apiResponse);
        } catch (\Exception $e) {
            Log::error('Failed to create chat session', ['error' => $e->getMessage()]);

            $apiResponse = General::setResponse('OTHER_ERROR', 'Failed to create chat session');

            return get_response($request, $apiResponse);
        }
    }

    /**
     * Rename an existing chat session
     * 
     * @param Request $request
     * @return \Illuminate\Http\Response
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

            $apiResponse = General::setResponse('SUCCESS', 'Chat session renamed successfully');
            $apiResponse['data'] = $session;

            return get_response($request, $apiResponse);
        } catch (\Illuminate\Validation\ValidationException $e) {
            $apiResponse = General::setResponse('VALIDATION_ERROR', 'Validation failed');
            $apiResponse['errors'] = $e->errors();

            return get_response($request, $apiResponse);
        } catch (\Exception $e) {
            Log::error('Failed to rename chat session', ['error' => $e->getMessage()]);

            $apiResponse = General::setResponse('OTHER_ERROR', 'Failed to rename chat session');

            return get_response($request, $apiResponse);
        }
    }

    /**
     * Delete a chat session and its history
     * 
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function deleteSession(Request $request)
    {
        try {
            $validated = $request->validate([
                'chat_session_id' => 'required|exists:chat_sessions,id,user_id,' . Auth::id()
            ]);

            $session = ChatSession::findOrFail($validated['chat_session_id']);
            $session->delete();

            $apiResponse = General::setResponse('SUCCESS', 'Chat session deleted successfully');
            $apiResponse['data'] = (object)[];

            return get_response($request, $apiResponse);
        } catch (\Illuminate\Validation\ValidationException $e) {
            $apiResponse = General::setResponse('VALIDATION_ERROR', 'Validation failed');
            $apiResponse['errors'] = $e->errors();

            return get_response($request, $apiResponse);
        } catch (\Exception $e) {
            Log::error('Failed to delete chat session', ['error' => $e->getMessage()]);

            $apiResponse = General::setResponse('OTHER_ERROR', 'Failed to delete chat session');

            return get_response($request, $apiResponse);
        }
    }

    /**
     * Check if PDF service is running
     * 
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function status(Request $request)
    {
        try {
            $pythonServiceUrl = config('services.pdf_service.url');

            // Remove /ask from the URL if present
            $baseUrl = str_replace('/ask', '', $pythonServiceUrl);

            $response = Http::timeout(5)->get($baseUrl . '/docs');

            if ($response->successful()) {
                $apiResponse = General::setResponse('SUCCESS', 'PDF service is running');
                $apiResponse['data'] = [
                    'service_url' => $baseUrl
                ];

                return get_response($request, $apiResponse);
            } else {
                $apiResponse = General::setResponse('OTHER_ERROR', 'PDF service is not responding');
                $apiResponse['data'] = [
                    'service_url' => $baseUrl
                ];

                return get_response($request, $apiResponse);
            }
        } catch (\Exception $e) {
            $apiResponse = General::setResponse('OTHER_ERROR', 'PDF service is not running');

            return get_response($request, $apiResponse);
        }
    }
}
