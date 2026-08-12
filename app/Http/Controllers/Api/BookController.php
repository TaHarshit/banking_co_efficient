<?php

namespace App\Http\Controllers\Api;

use App\General\General;
use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\BookAccessLog;
use App\Models\User;
use App\Services\PdfSecurityService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;

class BookController extends Controller
{
    public function __construct(
        protected PdfSecurityService $pdfSecurityService
    ) {
    }

    /**
     * Request secure access and dynamic watermark metadata for the book PDF
     * Supports language selection via query param (?lang=en/fr) or header
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function getSecureAccess(Request $request): Response
    {
        try {
            $user = Auth::user();
            if (!$user) {
                $data = General::setResponse('UNAUTHORIZED', 'User not authenticated.');
                return get_response($request, $data);
            }

            // Detect language (from param, body, or header)
            $requestedLang = $request->input('lang') 
                ?: $request->header('Accept-Language') 
                ?: $request->header('lang') 
                ?: 'en';

            $bookId = $request->input('book_id');
            $book = null;

            if ($bookId) {
                $book = Book::where('id', $bookId)->where('is_active', true)->first();
            }

            if (!$book) {
                $book = $this->pdfSecurityService->getOrCreateBookForLanguage($requestedLang);
            }

            if (!$book || !File::exists($book->file_path)) {
                $data = General::setResponse('NOT_FOUND', 'Book PDF document not found in private storage.');
                return get_response($request, $data);
            }

            // Read device & client information
            $appVersion = $request->header('app-version') 
                ?: $request->header('X-App-Version') 
                ?: $request->input('app_version', '1.0.0');

            $platform = $request->header('platform') 
                ?: $request->input('platform', 'MOBILE');

            // Generate unique session tracking token
            $trackingToken = $this->pdfSecurityService->generateTrackingToken($user, $book);

            // Expiration window: 15 minutes
            $expiresAt = now()->addMinutes(15);

            // Create access log in database
            $accessLog = BookAccessLog::create([
                'user_id' => $user->id,
                'book_id' => $book->id,
                'document_id' => $book->document_id,
                'access_token' => $trackingToken,
                'lang' => $book->lang ?? 'en',
                'app_version' => $appVersion,
                'platform' => $platform,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'expires_at' => $expiresAt,
            ]);

            // Build temporary signed streaming URL
            $streamUrl = URL::temporarySignedRoute(
                'api.book.stream',
                $expiresAt,
                ['token' => $trackingToken]
            );

            // Watermark payload for app display
            $watermark = $this->pdfSecurityService->buildWatermarkPayload(
                $user,
                $book,
                $trackingToken,
                $appVersion,
                $accessLog->created_at->format('Y-m-d H:i:s')
            );

            // Embedded metadata representation
            $embeddedMetadata = $this->pdfSecurityService->buildEmbeddedMetadata(
                $user,
                $book,
                $trackingToken,
                $appVersion,
                $accessLog->created_at->toIso8601String()
            );

            $data = General::setResponse('SUCCESS', 'Secure book access granted successfully.');
            $data['data'] = [
                'book_id' => $book->id,
                'title' => $book->title,
                'lang' => $book->lang ?? 'en',
                'document_id' => $book->document_id,
                'stream_url' => $streamUrl,
                'expires_at' => $expiresAt->toIso8601String(),
                'expires_in_seconds' => 900,
                'watermark' => $watermark,
                'embedded_metadata' => $embeddedMetadata,
            ];

            return get_response($request, $data);
        } catch (\Exception $e) {
            Log::error('Book Secure Access API Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            $data = General::setResponse('OTHER_ERROR', $e->getMessage());
            return get_response($request, $data);
        }
    }

    /**
     * Stream the PDF binary with embedded digital tracking metadata
     *
     * @param Request $request
     * @param string $token
     * @return \Illuminate\Http\Response|\Illuminate\Http\JsonResponse
     */
    public function stream(Request $request, string $token)
    {
        try {
            // Verify signed route signature
            if (!$request->hasValidSignature()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid or expired signature URL.',
                ], 403);
            }

            // Retrieve access session by token
            $accessLog = BookAccessLog::with(['book', 'user'])
                ->where('access_token', $token)
                ->first();

            if (!$accessLog) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid access token.',
                ], 403);
            }

            if ($accessLog->isExpired()) {
                return response()->json([
                    'success' => false,
                    'message' => 'This access session has expired. Please request a new secure access link.',
                ], 403);
            }

            $book = $accessLog->book;
            if (!$book || !File::exists($book->file_path)) {
                return response()->json([
                    'success' => false,
                    'message' => 'The requested document file does not exist.',
                ], 404);
            }

            // Read raw PDF content from private storage
            $rawPdfContent = File::get($book->file_path);

            // User info (fallback to finding user if relation was null)
            $user = $accessLog->user ?? User::find($accessLog->user_id);
            if (!$user) {
                $user = new User(['id' => $accessLog->user_id, 'name' => 'Licensed User']);
                $user->id = $accessLog->user_id ?: 0;
            }

            // Build metadata and inject directly into PDF binary
            $metadata = $this->pdfSecurityService->buildEmbeddedMetadata(
                $user,
                $book,
                $accessLog->access_token,
                $accessLog->app_version,
                $accessLog->created_at?->toIso8601String()
            );

            $securedPdfContent = $this->pdfSecurityService->injectMetadata($rawPdfContent, $metadata);

            $fileName = 'Book_' . strtoupper($book->lang ?? 'EN') . '_' . ($book->document_id ?: 'Document') . '.pdf';

            return response($securedPdfContent, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Length' => strlen($securedPdfContent),
                'Content-Disposition' => 'inline; filename="' . $fileName . '"',
                'Cache-Control' => 'private, no-store, no-cache, must-revalidate, max-age=0',
                'Pragma' => 'no-cache',
                'Expires' => '0',
                'X-Content-Type-Options' => 'nosniff',
                'X-Pdf-Document-Id' => $book->document_id,
                'X-Pdf-Lang' => $book->lang ?? 'en',
                'X-Pdf-Tracking-Token' => $accessLog->access_token,
                'X-Pdf-App-Version' => $accessLog->app_version ?? '1.0.0',
            ]);
        } catch (\Exception $e) {
            Log::error('Book PDF Streaming Error: ' . $e->getMessage(), [
                'token' => $token,
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while streaming the PDF.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get book information, available languages, and security status
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function details(Request $request): Response
    {
        try {
            $requestedLang = $request->input('lang') 
                ?: $request->header('Accept-Language') 
                ?: $request->header('lang') 
                ?: 'en';

            $currentBook = $this->pdfSecurityService->getOrCreateBookForLanguage($requestedLang);
            $availableBooks = $this->pdfSecurityService->getAvailableBooksList();

            $data = General::setResponse('SUCCESS', 'Book details retrieved successfully.');
            $data['data'] = [
                'current_book' => [
                    'id' => $currentBook->id,
                    'title' => $currentBook->title,
                    'lang' => $currentBook->lang ?? 'en',
                    'document_id' => $currentBook->document_id,
                    'file_size' => $currentBook->file_size,
                    'file_size_formatted' => round(($currentBook->file_size ?: 0) / 1024 / 1024, 2) . ' MB',
                    'is_active' => $currentBook->is_active,
                    'updated_at' => $currentBook->updated_at?->toIso8601String(),
                ],
                'available_books' => $availableBooks,
            ];

            return get_response($request, $data);
        } catch (\Exception $e) {
            $data = General::setResponse('OTHER_ERROR', $e->getMessage());
            return get_response($request, $data);
        }
    }
}
