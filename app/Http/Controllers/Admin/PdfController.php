<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Book;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PdfController extends Controller
{
    /**
     * Show the PDF management page
     * 
     * @return \Illuminate\View\View
     */
    public function index()
    {
        // Check private storage first, fallback to deepseek data path
        $privatePath = storage_path('app/private/books/input_pdf.pdf');
        $deepseekPath = base_path('pdf_service_deepseek/data/input_pdf.pdf');

        $pdfPath = File::exists($privatePath) ? $privatePath : $deepseekPath;
        $pdfExists = File::exists($pdfPath);
        $pdfSize = $pdfExists ? round(File::size($pdfPath) / 1024 / 1024, 2) . ' MB' : 'Not found';
        $pdfModified = $pdfExists ? date('Y-m-d H:i:s', File::lastModified($pdfPath)) : 'N/A';

        $book = Book::where('is_active', true)->latest()->first();
        $documentId = $book?->document_id ?? 'Not Assigned';

        return view('admin.pdf.index', compact('pdfExists', 'pdfSize', 'pdfModified', 'documentId', 'book'));
    }

    /**
     * Upload and process a new PDF
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function upload(Request $request)
    {
        try {
            $request->validate([
                'file' => 'required|mimes:pdf|max:20480' // 20MB max
            ]);

            $file = $request->file('file');
            $fileName = 'input_pdf.pdf';

            // 1. Save to secure private storage
            $privateStorageDir = storage_path('app/private/books');
            if (!File::exists($privateStorageDir)) {
                File::makeDirectory($privateStorageDir, 0755, true);
            }

            $privatePdfPath = $privateStorageDir . DIRECTORY_SEPARATOR . $fileName;
            $file->move($privateStorageDir, $fileName);

            $fileSize = File::size($privatePdfPath);

            // 2. Update or create Book record in database
            $book = Book::where('is_active', true)->latest()->first();
            if (!$book) {
                $book = Book::create([
                    'title' => 'Banking Co-efficient Guide',
                    'document_id' => Book::generateDocumentId(),
                    'file_path' => $privatePdfPath,
                    'file_size' => $fileSize,
                    'is_active' => true,
                ]);
            } else {
                $book->file_path = $privatePdfPath;
                $book->file_size = $fileSize;
                $book->save();
            }

            // 3. Sync to Python AI microservice directory
            $aiDataPath = base_path('pdf_service_deepseek/data');
            if (!File::exists($aiDataPath)) {
                File::makeDirectory($aiDataPath, 0755, true);
            }
            $aiPdfPath = $aiDataPath . DIRECTORY_SEPARATOR . $fileName;
            File::copy($privatePdfPath, $aiPdfPath);

            // 4. Run process_pdf.py for vector embedding index
            $pythonExe = base_path('pdf_service_deepseek/venv/Scripts/python.exe');
            if (strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN') {
                $pythonExe = base_path('pdf_service_deepseek/venv/bin/python');
            }
            $scriptPath = base_path('pdf_service_deepseek/scripts/process_pdf.py');

            putenv('PYTHONUTF8=1');

            $output = [];
            $returnVar = 0;
            if (File::exists($pythonExe) && File::exists($scriptPath)) {
                $command = escapeshellarg($pythonExe) . " " . escapeshellarg($scriptPath) . " " . escapeshellarg($aiPdfPath);
                exec($command . ' 2>&1', $output, $returnVar);

                if ($returnVar !== 0) {
                    Log::error('PDF Processing Error', ['command' => $command, 'output' => $output]);
                }
            }

            // 5. Call Python service /reload
            $pythonServiceUrl = config('services.pdf_service.url');
            $baseUrl = str_replace('/ask', '', $pythonServiceUrl);

            try {
                $response = Http::timeout(10)->post($baseUrl . '/reload');
                $reloadSuccess = $response->successful();
                $reloadResponse = $response->json();
            } catch (\Exception $reqException) {
                $reloadSuccess = false;
                $reloadResponse = ['error' => $reqException->getMessage()];
            }

            return response()->json([
                'success' => true,
                'message' => 'PDF uploaded, secured in private storage, and processed successfully',
                'document_id' => $book->document_id,
                'reloaded' => $reloadSuccess,
                'reload_details' => $reloadResponse,
                'output' => array_slice($output, -10)
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('PDF Upload Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred during upload',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
