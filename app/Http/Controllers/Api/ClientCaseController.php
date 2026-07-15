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
        $search = $request->query('search');
        $rating = $request->query('rating');
        $data = $this->clientCaseCls->GetCases($search, $rating);
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

    /**
     * Dispatch async AI case analysis.
     * Returns immediately with job_id. Frontend should poll ai/job-status/{job_id}.
     */
    public function analyzeCase(Request $request)
    {
        $postData = General::stripRequest($request->all());
        $data = $this->clientCaseCls->AnalyzeCase($postData);
        return get_response($request, $data);
    }

    /**
     * Dispatch async AI plan generation.
     * Returns immediately with job_id. Frontend should poll ai/job-status/{job_id}.
     */
    public function generatePlan(Request $request)
    {
        $postData = General::stripRequest($request->all());
        $data = $this->clientCaseCls->GeneratePlan($postData);
        return get_response($request, $data);
    }

    /**
     * Poll the status of an async AI job.
     * GET ai/job-status/{job_id}
     *
     * Response statuses:
     *   pending    -> job is queued, not started yet
     *   processing -> job is currently running
     *   completed  -> result is available in `data`
     *   failed     -> error message in `error`
     */
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

        $caseDetails = $case->case_details;
        if (is_string($caseDetails)) {
            $decodedCaseDetails = json_decode($caseDetails, true);
            $caseDetails = is_array($decodedCaseDetails) ? $decodedCaseDetails : [];
        }

        if (!is_array($caseDetails)) {
            $caseDetails = [];
        }

        $caseImages = $this->extractCaseImages($caseDetails);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.action-plan', [
            'case' => $case,
            'plan' => $plan,
            'caseImages' => $caseImages,
        ])->setOption('isRemoteEnabled', true);

        // Generate the PDF content in memory
        $pdfContent = $pdf->output();

        // Encode the PDF to a base64 string so it can be sent via JSON without saving to disk
        $base64Pdf = base64_encode($pdfContent);

        // Return a standard JSON API response with the base64 string
        $apiResponse = \App\General\General::setResponse('SUCCESS', 'PDF generated successfully.');
        $apiResponse['data'] = [
            'pdf_base64' => $base64Pdf,
            'file_name' => 'Action_Plan_' . ($case->client_alias ?? 'Case') . '.pdf'
        ];

        return get_response($request, $apiResponse);
    }

    /**
     * Recursively extract image URLs from the case details payload.
     */
    private function extractCaseImages(mixed $value, string $fieldName = '', bool $forceImageGroup = false): array
    {
        $images = [];
        $seen = [];

        $walk = function (mixed $node, string $label, bool $imageGroup) use (&$walk, &$images, &$seen): void {
            if (is_string($node)) {
                if (! $this->isImageSource($node, $imageGroup)) {
                    return;
                }

                $src = $this->normalizeImageSource($node);
                if (! $src || isset($seen[$src])) {
                    return;
                }

                $seen[$src] = true;
                $images[] = [
                    'label' => $label !== '' ? $label : 'Image',
                    'src' => $src,
                ];

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

        if (preg_match('/^https?:\/\//i', $value)) {
            return $value;
        }

        if (str_starts_with($value, '/')) {
            return url($value);
        }

        return url($value);
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

    /**
     * Rate the generated AI plan.
     */
    public function ratePlan(Request $request)
    {
        $postData = General::stripRequest($request->all());
        $data = $this->clientCaseCls->RatePlan($postData);
        return get_response($request, $data);
    }
}