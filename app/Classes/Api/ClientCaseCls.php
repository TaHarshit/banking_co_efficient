<?php

namespace App\Classes\Api;

use App\Repositories\Api\ClientCaseRepository;
use App\Repositories\Api\CaseStudyQuestionRepository;
use App\General\Validate;
use App\General\General;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class ClientCaseCls
{
    protected $clientCaseRepository;
    protected $caseStudyQuestionRepository;

    public function __construct(
        ClientCaseRepository $clientCaseRepository,
        CaseStudyQuestionRepository $caseStudyQuestionRepository
    ) {
        $this->clientCaseRepository = $clientCaseRepository;
        $this->caseStudyQuestionRepository = $caseStudyQuestionRepository;
    }

    public function CreateCase($postData)
    {
        try {
            // Validation
            $validator = Validate::required($postData, ['client_alias']);
            if ($validator->fails()) {
                return General::setResponse('VALIDATION_ERROR', $validator->errors()->first());
            }

            // Structure data for storage
            $data = [
                'user_id' => Auth::id(),
                'case_reference' => $postData['case_reference'] ?? null,
                'client_alias' => $postData['client_alias'],
                'context_overview' => $postData['context_overview'] ?? null,
                'case_details' => $postData['case_details'] ?? [],
            ];

            DB::beginTransaction();
            $case = $this->clientCaseRepository->Store($data);
            DB::commit();

            if ($case) {
                $response = General::setResponse('SUCCESS', 'Case created successfully.');
                $response['data'] = $case;
                return $response;
            } else {
                return General::setResponse('VALIDATION_ERROR', 'Failed to create case.');
            }
        } catch (Exception $e) {
            DB::rollBack();
            return General::setResponse('OTHER_ERROR', $e->getMessage());
        }
    }

    public function GetCases($search = null)
    {
        try {
            $cases = $this->clientCaseRepository->GetUserCases(Auth::id(), $search);
            $response = General::setResponse('SUCCESS', 'Cases retrieved successfully.');
            $response['data'] = $cases;
            return $response;
        } catch (Exception $e) {
            return General::setResponse('OTHER_ERROR', $e->getMessage());
        }
    }

    public function GetCaseDetails($id)
    {
        try {
            $case = $this->clientCaseRepository->GetCaseDetails($id, Auth::id());

            if (!$case) {
                return General::setResponse('VALIDATION_ERROR', 'Case not found.');
            }

            $response = General::setResponse('SUCCESS', 'Case details retrieved successfully.');
            $response['data'] = $case;
            return $response;
        } catch (Exception $e) {
            return General::setResponse('OTHER_ERROR', $e->getMessage());
        }
    }

    public function GetCaseStudySections($locale = 'en')
    {
        try {
            app()->setLocale($locale);

            $questions = $this->caseStudyQuestionRepository->getAllSectionsWithQuestions();

            $grouped = $questions->groupBy('section_name')->map(function ($sectionQuestions, $sectionName) use ($locale) {
                return [
                    'section_name' => $sectionName,
                    'locale' => $locale,
                    'questions' => $sectionQuestions->map(function ($question) {
                        return [
                            'id' => $question->id,
                            'question_text' => $question->question,
                            'options' => $question->options->map(function ($option) {
                                return [
                                    'id' => $option->id,
                                    'option_text' => $option->option,
                                    'is_correct' => $option->is_correct,
                                ];
                            })->values(),
                        ];
                    })->values(),
                ];
            })->values();

            $response = General::setResponse('SUCCESS', 'Case study sections retrieved successfully.');
            $response['data'] = $grouped;
            return $response;
        } catch (Exception $e) {
            return General::setResponse('OTHER_ERROR', $e->getMessage());
        }
    }

    public function AnalyzeCase($postData)
    {
        try {
            $validator = Validate::required($postData, ['client_alias', 'case_details']);
            if ($validator->fails()) {
                return General::setResponse('VALIDATION_ERROR', $validator->errors()->first());
            }

            $caseDetailsArray = is_string($postData['case_details']) ? json_decode($postData['case_details'], true) : $postData['case_details'];
            if (json_last_error() !== JSON_ERROR_NONE) {
                return General::setResponse('VALIDATION_ERROR', 'Invalid JSON format in case_details.');
            }

            $user = Auth::user();
            $userProfile = $user->getAiBehaviorProfile();

            // Find or Create Case Record
            if (isset($postData['case_id'])) {
                $clientCase = $this->clientCaseRepository->GetCaseDetails($postData['case_id'], $user->id);
                if (!$clientCase) return General::setResponse('VALIDATION_ERROR', 'Case not found.');
            } else {
                $clientCase = $this->clientCaseRepository->GetModel();
                $clientCase->user_id = $user->id;
            }

            $clientCase->client_alias = $postData['client_alias'];
            $clientCase->context_overview = $postData['context_overview'] ?? '';
            $clientCase->case_details = $caseDetailsArray;
            $clientCase->save();

            // Call Python AI Service
            $pythonUrl = env('PDF_SERVICE_BASE_URL', 'http://127.0.0.1:8000');
            $endpoint = rtrim($pythonUrl, '/') . '/analyze-case';

            $response = Http::post($endpoint, [
                'client_alias' => $postData['client_alias'],
                'context_overview' => $postData['context_overview'] ?? '',
                'case_details' => $caseDetailsArray,
                'user_profile' => $userProfile,
            ]);

            if ($response->successful()) {
                $analysisData = $response->json();

                $clientCase->ai_analysis = $analysisData;
                $clientCase->save();

                $resp = General::setResponse('SUCCESS', 'Analysis completed and saved.');
                $resp['case_id'] = $clientCase->id;
                $resp['data'] = $analysisData;
                return $resp;
            }

            return General::setResponse('OTHER_ERROR', 'AI Service error: ' . $response->body());
        } catch (Exception $e) {
            Log::error('Case Analysis Error', ['error' => $e->getMessage()]);
            return General::setResponse('OTHER_ERROR', $e->getMessage());
        }
    }

    public function GeneratePlan($postData)
    {
        try {
            $validator = Validate::required($postData, ['case_id']);
            if ($validator->fails()) {
                return General::setResponse('VALIDATION_ERROR', $validator->errors()->first());
            }

            $user = Auth::user();
            $clientCase = $this->clientCaseRepository->GetCaseDetails($postData['case_id'], $user->id);
            if (!$clientCase) return General::setResponse('VALIDATION_ERROR', 'Case not found.');

            $caseData = $postData['case_data'] ?? $clientCase->case_details;
            $analysisData = $postData['analysis_data'] ?? $clientCase->ai_analysis;

            if (!$caseData || !$analysisData) {
                return General::setResponse('VALIDATION_ERROR', 'Missing case data or analysis data.');
            }

            $userProfile = $user->getAiBehaviorProfile();
            $pythonUrl = env('PDF_SERVICE_BASE_URL', 'http://127.0.0.1:8000');
            $endpoint = rtrim($pythonUrl, '/') . '/generate-plan';

            $response = Http::post($endpoint, [
                'case_data' => $caseData,
                'analysis_data' => $analysisData,
                'user_profile' => $userProfile,
            ]);

            if ($response->successful()) {
                $planData = $response->json();

                $clientCase->action_plan = $planData;
                $clientCase->save();

                $resp = General::setResponse('SUCCESS', 'Action plan generated and saved.');
                $resp['case_id'] = $clientCase->id;
                $resp['data'] = $planData;
                return $resp;
            }

            return General::setResponse('OTHER_ERROR', 'AI Service error: ' . $response->body());
        } catch (Exception $e) {
            Log::error('Plan Generation Error', ['error' => $e->getMessage()]);
            return General::setResponse('OTHER_ERROR', $e->getMessage());
        }
    }
}
