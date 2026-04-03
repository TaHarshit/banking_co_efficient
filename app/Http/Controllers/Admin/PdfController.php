<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
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
        // Get the current PDF info if possible
        $pdfPath = base_path('pdf_service_deepseek/data/input_pdf.pdf');
        $pdfExists = file_exists($pdfPath);
        $pdfSize = $pdfExists ? round(filesize($pdfPath) / 1024 / 1024, 2) . ' MB' : 'Not found';
        $pdfModified = $pdfExists ? date('Y-m-d H:i:s', filemtime($pdfPath)) : 'N/A';

        return view('admin.pdf.index', compact('pdfExists', 'pdfSize', 'pdfModified'));
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

            $destinationPath = base_path('pdf_service_deepseek/data');
            $fileName = 'input_pdf.pdf';

            // Ensure directory exists
            if (!file_exists($destinationPath)) {
                mkdir($destinationPath, 0755, true);
            }

            $file->move($destinationPath, $fileName);
            $pdfPath = $destinationPath . DIRECTORY_SEPARATOR . $fileName;

            // Run process_pdf.py
            $pythonExe = base_path('pdf_service_deepseek/venv/Scripts/python.exe');
            if (strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN') {
                $pythonExe = base_path('pdf_service_deepseek/venv/bin/python');
            }
            $scriptPath = base_path('pdf_service_deepseek/scripts/process_pdf.py');

            // Set python output to use utf-8 to avoid encoding issues
            putenv('PYTHONUTF8=1');

            $command = escapeshellarg($pythonExe) . " " . escapeshellarg($scriptPath) . " " . escapeshellarg($pdfPath);
            exec($command . ' 2>&1', $output, $returnVar);

            if ($returnVar !== 0) {
                Log::error('PDF Processing Error', ['command' => $command, 'output' => $output]);
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to process PDF',
                    'error' => implode("\n", $output)
                ], 500);
            }

            // Call Python service /reload to reload index
            $pythonServiceUrl = env('PDF_SERVICE_URL', 'http://127.0.0.1:8000');
            $baseUrl = str_replace('/ask', '', $pythonServiceUrl);

            try {
                $response = Http::timeout(10)->post($baseUrl . '/reload');
                $reloadSuccess = $response->successful();
                $reloadResponse = $response->json();
            } catch (\Exception $reqException) {
                // If the python server is not currently running, it's fine, it will load the new PDF next time it starts.
                $reloadSuccess = false;
                $reloadResponse = ['error' => $reqException->getMessage()];
            }

            return response()->json([
                'success' => true,
                'message' => 'PDF uploaded and processed successfully',
                'reloaded' => $reloadSuccess,
                'reload_details' => $reloadResponse,
                'output' => array_slice($output, -10) // Only send the last 10 lines to avoid massive payloads
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
