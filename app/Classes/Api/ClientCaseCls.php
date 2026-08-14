<?php

namespace App\Classes\Api;

use App\General\General;
use App\General\Validate;
use App\Jobs\AnalyzeCaseJob;
use App\Jobs\GeneratePlanJob;
use App\Models\AiJob;
use App\Repositories\Api\CaseStudyQuestionRepository;
use App\Repositories\Api\ClientCaseRepository;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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
            $caseId = $postData['id'] ?? $postData['case_id'] ?? null;

            if ($caseId) {
                // Edit existing case
                $case = $this->clientCaseRepository->GetCaseDetails($caseId, Auth::id());
                if (! $case) {
                    return General::setResponse('VALIDATION_ERROR', 'Case not found.');
                }

                if (array_key_exists('client_alias', $postData)) {
                    $validator = Validate::required($postData, ['client_alias']);
                    if ($validator->fails()) {
                        return General::setResponse('VALIDATION_ERROR', $validator->errors()->first());
                    }
                }

                $data = [
                    'client_id'        => $postData['client_id'] ?? $case->client_id,
                    'case_reference'   => $postData['case_reference'] ?? $case->case_reference,
                    'client_alias'     => $postData['client_alias'] ?? $case->client_alias,
                    'context_overview' => $postData['context_overview'] ?? $case->context_overview,
                    'case_details'     => $postData['case_details'] ?? $case->case_details,
                ];

                DB::beginTransaction();
                $case->update($data);
                DB::commit();

                $response         = General::setResponse('SUCCESS', 'Case updated successfully.');
                $response['data'] = $case->fresh();

                return $response;
            } else {
                // Create new case
                // Validation
                $validator = Validate::required($postData, ['client_alias']);
                if ($validator->fails()) {
                    return General::setResponse('VALIDATION_ERROR', $validator->errors()->first());
                }

                // Structure data for storage
                $data = [
                    'user_id'          => Auth::id(),
                    'client_id'        => $postData['client_id'] ?? null,
                    'case_reference'   => $postData['case_reference'] ?? null,
                    'client_alias'     => $postData['client_alias'],
                    'context_overview' => $postData['context_overview'] ?? null,
                    'case_details'     => $postData['case_details'] ?? [],
                ];

                DB::beginTransaction();
                $case = $this->clientCaseRepository->Store($data);
                DB::commit();

                if ($case) {
                    $response         = General::setResponse('SUCCESS', 'Case created successfully.');
                    $response['data'] = $case;

                    return $response;
                } else {
                    return General::setResponse('VALIDATION_ERROR', 'Failed to create case.');
                }
            }
        } catch (Exception $e) {
            DB::rollBack();

            return General::setResponse('OTHER_ERROR', $e->getMessage());
        }
    }

    public function GetCases($search = null, $rating = null, $clientId = null)
    {
        try {
            $cases            = $this->clientCaseRepository->GetUserCases(Auth::id(), $search, $rating, $clientId);
            $response         = General::setResponse('SUCCESS', 'Cases retrieved successfully.');
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

            if (! $case) {
                return General::setResponse('VALIDATION_ERROR', 'Case not found.');
            }

            $response         = General::setResponse('SUCCESS', 'Case details retrieved successfully.');
            $response['data'] = $case;

            return $response;
        } catch (Exception $e) {
            return General::setResponse('OTHER_ERROR', $e->getMessage());
        }
    }

    public function DeleteCase($id)
    {
        try {
            $case = $this->clientCaseRepository->GetCaseDetails($id, Auth::id());

            if (! $case) {
                return General::setResponse('VALIDATION_ERROR', 'Case not found or you do not have permission to delete it.');
            }

            DB::beginTransaction();
            $case->delete();
            DB::commit();

            return General::setResponse('SUCCESS', 'Case deleted successfully.');
        } catch (Exception $e) {
            DB::rollBack();
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
                    'locale'       => $locale,
                    'questions'    => $sectionQuestions->map(function ($question) {
                        return [
                            'id'            => $question->id,
                            'question_text' => $question->question,
                            'options'       => $question->options->map(function ($option) {
                                return [
                                    'id'          => $option->id,
                                    'option_text' => $option->option,
                                    'is_correct'  => $option->is_correct,
                                ];
                            })->values(),
                        ];
                    })->values(),
                ];
            })->values();

            $response         = General::setResponse('SUCCESS', 'Case study sections retrieved successfully.');
            $response['data'] = $grouped;

            return $response;
        } catch (Exception $e) {
            return General::setResponse('OTHER_ERROR', $e->getMessage());
        }
    }

    /**
     * Dispatch async case analysis job and return immediately with job_id.
     */
    public function AnalyzeCase($postData)
    {
        try {
            $validator = Validate::required($postData, ['case_id']);
            if ($validator->fails()) {
                return General::setResponse('VALIDATION_ERROR', $validator->errors()->first());
            }

            $user       = Auth::user();
            $clientCase = $this->clientCaseRepository->GetCaseDetails($postData['case_id'], $user->id);
            if (! $clientCase) {
                return General::setResponse('VALIDATION_ERROR', 'Case not found.');
            }

            // Create a tracking record in ai_jobs
            $aiJob = AiJob::create([
                'user_id'  => $user->id,
                'case_id'  => $clientCase->id,
                'job_type' => 'analyze_case',
                'status'   => 'pending',
                'attempts' => 0,
            ]);

            $locale = request()->input('lang', request()->header('Accept-Language', 'en'));
            if (str_starts_with(strtolower($locale), 'fr')) {
                $locale = 'fr';
            } else {
                $locale = 'en';
            }
            // Dispatch job to queue
            AnalyzeCaseJob::dispatch($aiJob->id, $clientCase->id, $user->id, $locale);

            // Auto-trigger background queue worker (no separate worker process needed)
            $this->spawnQueueWorker();

            $response           = General::setResponse('SUCCESS', 'Analysis queued. You will be notified when complete.');
            $response['job_id'] = $aiJob->id;
            $response['case_id'] = $clientCase->id;
            $response['status'] = 'pending';

            return $response;
        } catch (Exception $e) {
            Log::error('AnalyzeCase dispatch error', ['error' => $e->getMessage()]);

            return General::setResponse('OTHER_ERROR', $e->getMessage());
        }
    }

    /**
     * Dispatch async plan generation job and return immediately with job_id.
     */
    public function GeneratePlan($postData)
    {
        try {
            $validator = Validate::required($postData, ['case_id']);
            if ($validator->fails()) {
                return General::setResponse('VALIDATION_ERROR', $validator->errors()->first());
            }

            $user       = Auth::user();
            $clientCase = $this->clientCaseRepository->GetCaseDetails($postData['case_id'], $user->id);
            if (! $clientCase) {
                return General::setResponse('VALIDATION_ERROR', 'Case not found.');
            }

            $caseData     = $postData['case_data'] ?? $clientCase->case_details;
            $analysisData = $postData['analysis_data'] ?? $clientCase->ai_analysis;

            if (! $caseData || ! $analysisData) {
                return General::setResponse('VALIDATION_ERROR', 'Missing case data or analysis data. Please run AI analysis first.');
            }

            // Create a tracking record in ai_jobs
            $aiJob = AiJob::create([
                'user_id'  => $user->id,
                'case_id'  => $clientCase->id,
                'job_type' => 'generate_plan',
                'status'   => 'pending',
                'attempts' => 0,
            ]);

            $locale = request()->input('lang', request()->header('Accept-Language', 'en'));
            if (str_starts_with(strtolower($locale), 'fr')) {
                $locale = 'fr';
            } else {
                $locale = 'en';
            }
            // Dispatch job to queue
            GeneratePlanJob::dispatch($aiJob->id, $clientCase->id, $user->id, $caseData, $analysisData, $locale);

            // Auto-trigger background queue worker (no separate worker process needed)
            $this->spawnQueueWorker();

            $response            = General::setResponse('SUCCESS', 'Plan generation queued. You will be notified when complete.');
            $response['job_id']  = $aiJob->id;
            $response['case_id'] = $clientCase->id;
            $response['status']  = 'pending';

            return $response;
        } catch (Exception $e) {
            Log::error('GeneratePlan dispatch error', ['error' => $e->getMessage()]);

            return General::setResponse('OTHER_ERROR', $e->getMessage());
        }
    }

    /**
     * Poll the status of an AI job.
     */
    public function GetAiJobStatus($jobId)
    {
        try {
            $aiJob = AiJob::where('id', $jobId)
                ->where('user_id', Auth::id())
                ->first();

            if (! $aiJob) {
                return General::setResponse('VALIDATION_ERROR', 'Job not found.');
            }

            $response           = General::setResponse('SUCCESS', 'Job status retrieved.');
            $response['job_id'] = $aiJob->id;
            $response['status'] = $aiJob->status;
            $response['job_type'] = $aiJob->job_type;
            $response['case_id']  = $aiJob->case_id;
            $response['attempts'] = $aiJob->attempts;

            if ($aiJob->isCompleted()) {
                $response['data'] = $aiJob->result;
            }

            if ($aiJob->isFailed()) {
                $response['error'] = $aiJob->error_message;
            }

            return $response;
        } catch (Exception $e) {
            return General::setResponse('OTHER_ERROR', $e->getMessage());
        }
    }

    /**
     * Spawn a background queue worker process so jobs run without a separate
     * persistent worker. Uses `queue:work --once` to process one job and exit.
     * Works on both Windows (XAMPP) and Linux.
     */
    private function spawnQueueWorker(): void
    {
        try {
            $artisan = base_path('artisan');
            $phpBin  = PHP_BINARY;

            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                // Windows: start /B runs process detached in background
                pclose(popen("start /B \"{$phpBin}\" \"{$artisan}\" queue:work --once --timeout=960 --tries=1 2>NUL", 'r'));
            } else {
                // Linux/macOS
                exec("\"{$phpBin}\" \"{$artisan}\" queue:work --once --timeout=960 --tries=1 > /dev/null 2>&1 &");
            }

            Log::info('[ClientCaseCls] Queue worker spawned successfully.');
        } catch (Exception $e) {
            Log::warning('[ClientCaseCls] Could not spawn queue worker (jobs will run on next manual queue:work)', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get the case plan for PDF export.
     */
    public function GetCasePlanForExport($id)
    {
        try {
            $case = $this->clientCaseRepository->GetCaseDetails($id, Auth::id());

            if (! $case) {
                return General::setResponse('VALIDATION_ERROR', 'Case not found.');
            }

            if (empty($case->action_plan)) {
                return General::setResponse('VALIDATION_ERROR', 'No action plan generated for this case yet.');
            }

            $response         = General::setResponse('SUCCESS', 'Case plan retrieved successfully.');
            $response['data'] = $case;

            return $response;
        } catch (Exception $e) {
            return General::setResponse('OTHER_ERROR', $e->getMessage());
        }
    }

    /**
     * Rate the AI generated action plan.
     */
    public function RatePlan($postData)
    {
        try {
            $validator = Validate::required($postData, ['case_id', 'rating']);
            if ($validator->fails()) {
                return General::setResponse('VALIDATION_ERROR', $validator->errors()->first());
            }

            $rating = (int)$postData['rating'];
            if ($rating < 1 || $rating > 5) {
                return General::setResponse('VALIDATION_ERROR', 'Rating must be between 1 and 5.');
            }

            $user = Auth::user();
            $clientCase = $this->clientCaseRepository->GetCaseDetails($postData['case_id'], $user->id);
            
            if (! $clientCase) {
                return General::setResponse('VALIDATION_ERROR', 'Case not found.');
            }

            if (! $clientCase->action_plan) {
                return General::setResponse('VALIDATION_ERROR', 'No action plan generated for this case yet.');
            }

            $clientCase->update(['plan_rating' => $rating]);

            $response = General::setResponse('SUCCESS', 'Action plan rated successfully.');

            return $response;
        } catch (Exception $e) {
            return General::setResponse('OTHER_ERROR', $e->getMessage());
        }
    }

    /**
     * Get distinct clients dropdown list for the authenticated user.
     */
    public function GetClientsDropdown($search = null)
    {
        try {
            $clients = $this->clientCaseRepository->getDistinctClients(Auth::id(), $search);
            $response = General::setResponse('SUCCESS', 'Clients retrieved successfully.');
            $response['data'] = $clients;

            return $response;
        } catch (Exception $e) {
            return General::setResponse('OTHER_ERROR', $e->getMessage());
        }
    }

    /**
     * Check if a client_id already exists and get its status.
     */
    public function CheckClientId($clientId)
    {
        try {
            $clientId = trim((string) $clientId);

            if ($clientId === '') {
                return General::setResponse('VALIDATION_ERROR', 'Client ID is required.');
            }

            $userId = Auth::id();
            $existingCase = $this->clientCaseRepository->checkClientIdExists($userId, $clientId);

            if ($existingCase) {
                $totalCases = $this->clientCaseRepository->countClientCases($userId, $clientId);
                $response = General::setResponse('SUCCESS', 'Client ID already in use.');
                $response['data'] = [
                    'client_id'      => $clientId,
                    'is_used'        => true,
                    'exists'         => true,
                    'client_alias'   => $existingCase->client_alias,
                    'total_cases'    => $totalCases,
                    'last_case_date' => $existingCase->created_at?->format('Y-m-d H:i:s'),
                    'message'        => "Client ID '{$clientId}' is already associated with '{$existingCase->client_alias}' ({$totalCases} existing case" . ($totalCases > 1 ? 's' : '') . "). Creating this case will link it to this client's history.",
                ];

                return $response;
            }

            $response = General::setResponse('SUCCESS', 'Client ID is available.');
            $response['data'] = [
                'client_id'      => $clientId,
                'is_used'        => false,
                'exists'         => false,
                'client_alias'   => null,
                'total_cases'    => 0,
                'last_case_date' => null,
                'message'        => "Client ID '{$clientId}' is available.",
            ];

            return $response;
        } catch (Exception $e) {
            return General::setResponse('OTHER_ERROR', $e->getMessage());
        }
    }
}
