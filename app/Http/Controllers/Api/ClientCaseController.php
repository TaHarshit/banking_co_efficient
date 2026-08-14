<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Classes\Api\ClientCaseCls;
use Illuminate\Http\Request;
use App\General\General;
use Illuminate\Support\Facades\Auth;

class ClientCaseController extends Controller
{
    protected $clientCaseCls;

    public function __construct(ClientCaseCls $clientCaseCls)
    {
        $this->clientCaseCls = $clientCaseCls;
    }

    public function store(Request $request)
    {
        $postData = General::stripRequest($request->all());
        $data = $this->clientCaseCls->CreateCase($postData);
        return get_response($request, $data);
    }

    public function index(Request $request)
    {
        $search   = $request->query('search');
        $rating   = $request->query('rating');
        $clientId = $request->query('client_id');
        $data     = $this->clientCaseCls->GetCases($search, $rating, $clientId);
        return get_response($request, $data);
    }

    public function clients(Request $request)
    {
        $search = $request->query('search');
        $data   = $this->clientCaseCls->GetClientsDropdown($search);
        return get_response($request, $data);
    }

    public function checkClientId(Request $request)
    {
        $clientId = $request->input('client_id') ?? $request->query('client_id');
        $data     = $this->clientCaseCls->CheckClientId($clientId);
        return get_response($request, $data);
    }

    public function show($id, Request $request)
    {
        $data = $this->clientCaseCls->GetCaseDetails($id);
        return get_response($request, $data);
    }

    public function destroy($id, Request $request)
    {
        $data = $this->clientCaseCls->DeleteCase($id);
        return get_response($request, $data);
    }

    public function caseStudySections(Request $request)
    {
        $locale = $request->query('lang', $request->header('Accept-Language', 'en'));

        if (str_starts_with($locale, 'fr')) {
            $locale = 'fr';
        } else {
            $locale = 'en';
        }

        $data = $this->clientCaseCls->GetCaseStudySections($locale);
        return get_response($request, $data);
    }

    public function analyzeCase(Request $request)
    {
        $postData = General::stripRequest($request->all());
        $data = $this->clientCaseCls->AnalyzeCase($postData);
        return get_response($request, $data);
    }

    public function generatePlan(Request $request)
    {
        $postData = General::stripRequest($request->all());
        $data = $this->clientCaseCls->GeneratePlan($postData);
        return get_response($request, $data);
    }

    public function getAiJobStatus($jobId, Request $request)
    {
        $data = $this->clientCaseCls->GetAiJobStatus($jobId);
        return get_response($request, $data);
    }

    /**
     * Export the generated AI plan as a PDF document.
     */
    public function exportPlan($id, Request $request)
    {
        $response = $this->clientCaseCls->GetCasePlanForExport($id);

        if (!isset($response['code']) || $response['code'] !== 200 || !isset($response['data'])) {
            return get_response($request, $response);
        }

        $case = $response['data'];
        $plan = $case->action_plan;

        if (is_string($plan)) {
            $plan = json_decode($plan, true);
        }

        if (!is_array($plan)) {
            $plan = [];
        }

        $caseDetails = $case->case_details;
        if (is_string($caseDetails)) {
            $decodedCaseDetails = json_decode($caseDetails, true);
            $caseDetails = is_array($decodedCaseDetails) ? $decodedCaseDetails : [];
        }

        if (!is_array($caseDetails)) {
            $caseDetails = [];
        }

        $caseImages = array_merge(
            $this->extractCaseImages($caseDetails, 'Case Details'),
            $this->extractCaseImages($plan, 'Action Plan')
        );
        $caseImages = $this->dedupeCaseImages($caseImages);

        // Clean up image URLs/references from the text of case details and action plan
        $caseDetails = $this->cleanImageReferences($caseDetails);
        $plan = $this->cleanImageReferences($plan);
        $case->case_details = $caseDetails;
        if ($case->context_overview) {
            $case->context_overview = $this->cleanImageReferences($case->context_overview);
        }

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.action-plan', [
            'case' => $case,
            'plan' => $plan,
            'caseImages' => $caseImages,
        ])->setOption('isRemoteEnabled', true);

        $pdfContent = $pdf->output();
        $base64Pdf = base64_encode($pdfContent);

        $apiResponse = \App\General\General::setResponse('SUCCESS', 'PDF generated successfully.');
        $apiResponse['data'] = [
            'pdf_base64' => $base64Pdf,
            'file_name' => 'Action_Plan_' . ($case->client_alias ?? 'Case') . '.pdf'
        ];

        return get_response($request, $apiResponse);
    }

    /**
     * Recursively extract image URLs from a payload.
     */
    private function extractCaseImages(mixed $value, string $fieldName = '', bool $forceImageGroup = false): array
    {
        $images = [];
        $seen = [];

        $walk = function (mixed $node, string $label, bool $imageGroup) use (&$walk, &$images, &$seen): void {
            if (is_string($node)) {
                // If it is directly an image source
                if ($this->isImageSource($node, $imageGroup)) {
                    $src = $this->normalizeImageSource($node);
                    if ($src && !isset($seen[$src])) {
                        $seen[$src] = true;
                        $images[] = [
                            'label' => $label !== '' ? $label : 'Image',
                            'src' => $src,
                        ];
                    }
                    return;
                }

                // If not, scan for embedded image URLs in the string (e.g. Image: http://... or Images: http://...)
                if (preg_match_all('/(https?:\/\/[^\s\)\],"\';<]+?\.(?:png|jpe?g|gif|webp|bmp|svg)(?:\?.*)?)/i', $node, $matches)) {
                    foreach ($matches[1] as $embeddedUrl) {
                        $src = $this->normalizeImageSource($embeddedUrl);
                        if ($src && !isset($seen[$src])) {
                            $seen[$src] = true;
                            $images[] = [
                                'label' => $label !== '' ? $label : 'Image',
                                'src' => $src,
                            ];
                        }
                    }
                }
                return;
            }

            if (!is_array($node)) {
                return;
            }

            $isList = array_is_list($node);
            foreach ($node as $key => $child) {
                $childLabel = $label;
                if ($isList) {
                    $childLabel = trim($label . ' ' . ((int) $key + 1));
                } else {
                    $prettyKey = $this->humanizeFieldName((string) $key);
                    $childLabel = $label !== '' ? ($label . ' - ' . $prettyKey) : $prettyKey;
                }

                $childImageGroup = $imageGroup || $this->isImageFieldName((string) $key);
                $walk($child, $childLabel, $childImageGroup);
            }
        };

        $initialLabel = $this->humanizeFieldName($fieldName);
        $walk($value, $initialLabel, $forceImageGroup || $this->isImageFieldName($fieldName));

        return $images;
    }

    private function isImageFieldName(string $fieldName): bool
    {
        return (bool) preg_match('/(?:image|images|img|photo|photos|picture|pictures|screenshot|screenshots|attachment|attachments|url|urls)/i', $fieldName);
    }

    private function isImageSource(string $value, bool $forceImageGroup = false): bool
    {
        if (preg_match('/^data:image\//i', $value)) {
            return true;
        }

        if ($forceImageGroup) {
            return true;
        }

        if (! preg_match('/^(https?:\/\/|\/|storage\/|[A-Za-z]:\\\\)/i', $value)) {
            return false;
        }

        return (bool) preg_match('/\.(?:png|jpe?g|gif|webp|bmp|svg)(?:\?.*)?$/i', $value);
    }

    private function normalizeImageSource(string $value): string
    {
        if (preg_match('/^data:image\//i', $value)) {
            return $value;
        }

        // Try to see if it is a local file path or relative URL first.
        // e.g. "storage/..." or "/storage/..." or public path
        $cleanPath = ltrim(parse_url($value, PHP_URL_PATH) ?? $value, '/');
        
        // If it starts with storage/
        if (str_starts_with($cleanPath, 'storage/')) {
            $realPath = storage_path('app/public/' . substr($cleanPath, 8));
            if (file_exists($realPath)) {
                return $this->base64EncodeImage($realPath);
            }
        }

        // Try public path
        $realPath = public_path($cleanPath);
        if (file_exists($realPath)) {
            return $this->base64EncodeImage($realPath);
        }

        // If it's a full HTTP/HTTPS URL
        if (preg_match('/^https?:\/\//i', $value)) {
            // Check if it matches our app URL or localhost, maybe we can resolve it locally
            $appUrl = config('app.url');
            if ($appUrl && str_starts_with($value, $appUrl)) {
                $relativeUrl = substr($value, strlen($appUrl));
                $cleanRelative = ltrim(parse_url($relativeUrl, PHP_URL_PATH) ?? $relativeUrl, '/');
                if (str_starts_with($cleanRelative, 'storage/')) {
                    $realPath = storage_path('app/public/' . substr($cleanRelative, 8));
                    if (file_exists($realPath)) {
                        return $this->base64EncodeImage($realPath);
                    }
                }
                $realPath = public_path($cleanRelative);
                if (file_exists($realPath)) {
                    return $this->base64EncodeImage($realPath);
                }
            }

            // Fetch and base64 encode remote image to bypass DOMPDF remote load issues
            try {
                $ctx = stream_context_create([
                    'ssl' => [
                        'verify_peer' => false,
                        'verify_peer_name' => false,
                    ],
                    'http' => [
                        'timeout' => 5, // 5 seconds timeout
                        'header' => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64)\r\n"
                    ]
                ]);
                $data = @file_get_contents($value, false, $ctx);
                if ($data !== false) {
                    $compressed = $this->compressImage($data);
                    if ($compressed !== null) {
                        return 'data:image/jpeg;base64,' . base64_encode($compressed);
                    }
                    
                    $type = 'png';
                    if (preg_match('/\.(jpe?g|gif|webp|bmp|svg)/i', $value, $matches)) {
                        $type = strtolower($matches[1]);
                        if ($type === 'jpg') $type = 'jpeg';
                    }
                    return 'data:image/' . $type . ';base64,' . base64_encode($data);
                }
            } catch (\Exception $e) {
                // Ignore exception and fallback
            }
        }

        return $value;
    }

    private function base64EncodeImage(string $path): string
    {
        try {
            $data = file_get_contents($path);
            if ($data !== false) {
                $compressed = $this->compressImage($data);
                if ($compressed !== null) {
                    return 'data:image/jpeg;base64,' . base64_encode($compressed);
                }
                
                $type = pathinfo($path, PATHINFO_EXTENSION);
                if (strtolower($type) === 'jpg') {
                    $type = 'jpeg';
                }
                return 'data:image/' . strtolower($type) . ';base64,' . base64_encode($data);
            }
        } catch (\Exception $e) {
            // Fallback
        }
        return url(str_replace(public_path(), '', $path));
    }

    /**
     * Compress and downscale an image to reduce PDF file size and API payload size.
     */
    private function compressImage(string $imageData): ?string
    {
        try {
            if (!extension_loaded('gd') && !function_exists('imagecreatefromstring')) {
                return null;
            }
            
            $image = @imagecreatefromstring($imageData);
            if (!$image) {
                return null;
            }

            $width = imagesx($image);
            $height = imagesy($image);

            // Maximum target width (perfect for the 260px wide display container in the PDF)
            $maxWidth = 500;
            if ($width > $maxWidth) {
                $newWidth = $maxWidth;
                $newHeight = (int)($height * ($maxWidth / $width));
            } else {
                $newWidth = $width;
                $newHeight = $height;
            }

            $resizedImage = imagecreatetruecolor($newWidth, $newHeight);
            
            // Fill background with white (handles transparency conversion to JPEG beautifully)
            $white = imagecolorallocate($resizedImage, 255, 255, 255);
            imagefill($resizedImage, 0, 0, $white);
            
            imagecopyresampled($resizedImage, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
            
            ob_start();
            imagejpeg($resizedImage, null, 70); // Output as JPEG with 70% quality (excellent ratio)
            $compressedData = ob_get_clean();
            
            imagedestroy($resizedImage);
            imagedestroy($image);
            
            return $compressedData;
        } catch (\Exception $e) {
            return null;
        }
    }

    private function humanizeFieldName(string $value): string
    {
        $value = trim($value);

        if ($value === '') {
            return '';
        }

        $value = str_replace(['_', '-'], ' ', $value);
        return ucwords($value);
    }

    private function dedupeCaseImages(array $images): array
    {
        $seen = [];
        $result = [];

        foreach ($images as $image) {
            $src = $image['src'] ?? null;
            if (! $src || isset($seen[$src])) {
                continue;
            }

            $seen[$src] = true;
            $result[] = $image;
        }

        return $result;
    }

    /**
     * Recursively clean image URL references from case details or action plan text.
     */
    private function cleanImageReferences(mixed $value): mixed
    {
        if (is_string($value)) {
            // Remove: (Image: http://...) or (Images: http://...)
            $value = preg_replace('/\s*\(Images?:\s*https?:\/\/[^\s\)]+?\.(?:png|jpe?g|gif|webp|bmp|svg)(?:\?.*)?\)/i', '', $value);
            
            // Remove: [Images: http://...] or [Image: http://...]
            $value = preg_replace('/\s*\[Images?:\s*https?:\/\/[^\s\]]+?\.(?:png|jpe?g|gif|webp|bmp|svg)(?:\?.*)?\]/i', '', $value);

            // Clean up: | Images: http://... inside [Source Page: 1 | Images: http://...]
            $value = preg_replace('/\s*\|\s*Images?:\s*https?:\/\/[^\s\]]+?\.(?:png|jpe?g|gif|webp|bmp|svg)(?:\?.*)?/i', '', $value);

            return $value;
        }

        if (is_array($value)) {
            foreach ($value as $key => $child) {
                $value[$key] = $this->cleanImageReferences($child);
            }
        }

        return $value;
    }

    public function ratePlan(Request $request)
    {
        $postData = General::stripRequest($request->all());
        $data = $this->clientCaseCls->RatePlan($postData);
        return get_response($request, $data);
    }
}