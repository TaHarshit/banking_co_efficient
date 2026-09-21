<?php

namespace App\Classes\Api;

use App\General\General;
use App\General\Validate;
use App\Jobs\AnalyzeCaseJob;
use App\Jobs\GeneratePlanJob;
use App\Jobs\SummarizeCasesJob;
use App\Models\AiJob;
use App\Repositories\Api\CaseStudyQuestionRepository;
use App\Repositories\Api\ClientCaseRepository;
use App\Repositories\Api\ClientRepository;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ClientCaseCls
{
    protected $clientCaseRepository;

    protected $caseStudyQuestionRepository;

    protected $clientRepository;

    public function __construct(
        ClientCaseRepository $clientCaseRepository,
        CaseStudyQuestionRepository $caseStudyQuestionRepository,
        ClientRepository $clientRepository
    ) {
        $this->clientCaseRepository = $clientCaseRepository;
        $this->caseStudyQuestionRepository = $caseStudyQuestionRepository;
        $this->clientRepository = $clientRepository;
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

                if (! empty($data['client_id'])) {
                    $this->clientRepository->StoreOrUpdate(Auth::id(), [
                        'client_id'    => $data['client_id'],
                        'client_alias' => $data['client_alias'],
                    ]);
                }
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

                if ($case && ! empty($data['client_id'])) {
                    $this->clientRepository->StoreOrUpdate(Auth::id(), [
                        'client_id'    => $data['client_id'],
                        'client_alias' => $data['client_alias'],
                    ]);
                }
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

    public function GetCases($search = null, $rating = null, $clientId = null, array $filters = [])
    {
        try {
            $cases            = $this->clientCaseRepository->GetUserCases(Auth::id(), $search, $rating, $clientId, $filters);
            $response         = General::setResponse('SUCCESS', 'Cases retrieved successfully.');
            $response['data'] = $cases;

            // If filtered by client_id, include the client summary at the top-level of response
            if (! empty($clientId)) {
                $client = $this->clientRepository->FindByClientId(Auth::id(), $clientId);
                $firstCase = $cases->first();
                $response['client_summary'] = $client?->ai_summary 
                    ?? $firstCase?->client_summary 
                    ?? null;
                $response['client_id'] = $clientId;
                $response['client_alias'] = $client?->client_alias ?? $firstCase?->client_alias ?? null;
            }

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

            // Ensure client_summary is loaded (fallback to client table if not on case row)
            if (empty($case->client_summary) && ! empty($case->client_id)) {
                $client = $this->clientRepository->FindByClientId(Auth::id(), $case->client_id);
                if (! empty($client?->ai_summary)) {
                    $case->client_summary = $client->ai_summary;
                }
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

    public function GetCaseStudySections($locale = 'en', $businessId = null)
    {
        try {
            app()->setLocale($locale);

            $policyMessage = null;
            if ($businessId) {
                $business = \App\Models\Business::find($businessId);
                $policyMessage = $business?->business_policies_message;
            }

            $questions = $this->caseStudyQuestionRepository->getAllSectionsWithQuestions($businessId);

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

            $response                  = General::setResponse('SUCCESS', 'Case study sections retrieved successfully.');
            $response['policy_message'] = $policyMessage;
            $response['data']          = $grouped;

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

            // Quota / Entitlement enforcement
            if (! $user->canRunAnalysis()) {
                $response = General::setResponse('VALIDATION_ERROR', 'You have used all 3 free analyses. Please upgrade to Negomaster Pro or purchase a Single Analysis to continue.');
                $response['code'] = 403;
                $response['error_code'] = 'QUOTA_EXCEEDED';
                $response['free_analyses_used'] = (int)$user->free_analyses_used;
                $response['paid_credits_remaining'] = $user->getPaidCredits();
                return $response;
            }

            // Determine quota usage and entitlements
            $canExportPdf = false;
            $isFullProfile = false;

            if ($user->isUnlimited()) {
                $canExportPdf = true;
                $isFullProfile = true;
            } elseif ($user->paid_analyses_credits > 0) {
                $user->decrement('paid_analyses_credits');
                $canExportPdf = true;
                $isFullProfile = true;
            } else {
                $user->increment('free_analyses_used');
                $canExportPdf = false;
                $isFullProfile = false;
            }

            $clientCase->can_export_pdf = $canExportPdf;
            $clientCase->is_full_profile = $isFullProfile;
            $clientCase->save();

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
            // Dispatch job to queue with profiling flag
            AnalyzeCaseJob::dispatch($aiJob->id, $clientCase->id, $user->id, $locale, $isFullProfile);

            // Auto-trigger background queue worker (no separate worker process needed)
            $this->spawnQueueWorker();

            $response           = General::setResponse('SUCCESS', 'Analysis queued. You will be notified when complete.');
            $response['job_id'] = $aiJob->id;
            $response['case_id'] = $clientCase->id;
            $response['status'] = 'pending';
            $response['can_export_pdf'] = $canExportPdf;
            $response['is_full_profile'] = $isFullProfile;
            $response['free_analyses_remaining'] = $user->getRemainingFreeAnalyses();
            $response['paid_credits_remaining'] = $user->getPaidCredits();
            $response['total_analyses_remaining'] = $user->getTotalAvailableAnalyses();

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

            $analysisData = $postData['analysis_data'] ?? $clientCase->ai_analysis;

            if (empty($analysisData)) {
                return General::setResponse('VALIDATION_ERROR', 'Missing AI analysis data. Please run AI analysis first.');
            }

            $caseData = $postData['case_data'] ?? [
                'client_id'        => $clientCase->client_id,
                'client_alias'     => $clientCase->client_alias ?? 'Client',
                'context_overview' => $clientCase->context_overview ?? '',
                'case_details'     => ! empty($clientCase->case_details) ? $clientCase->case_details : (object) [],
            ];

            // Create a tracking record in ai_jobs
            $aiJob = AiJob::create([
                'user_id'  => $user->id,
                'case_id'  => $clientCase->id,
                'job_type' => 'generate_plan',
                'status'   => 'pending',
                'attempts' => 0,
            ]);

            $userQuestion = $postData['user_question']
                ?? $postData['userQuestion']
                ?? $postData['question']
                ?? $postData['custom_question']
                ?? $postData['query']
                ?? $clientCase->user_question
                ?? null;

            if (!empty($userQuestion)) {
                $clientCase->user_question = $userQuestion;
                $clientCase->save();
            }

            $locale = request()->input('lang', request()->header('Accept-Language', 'en'));
            if (str_starts_with(strtolower($locale), 'fr')) {
                $locale = 'fr';
            } else {
                $locale = 'en';
            }
            // Dispatch job to queue
            GeneratePlanJob::dispatch($aiJob->id, $clientCase->id, $user->id, $caseData, $analysisData, $locale, $userQuestion);

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
            $response['case_id']   = $aiJob->case_id;
            $response['client_id'] = $aiJob->client_id;
            $response['attempts']  = $aiJob->attempts;

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
    public function GetClientsDropdown($search = null, $date = null)
    {
        try {
            $clients = $this->clientCaseRepository->getDistinctClients(Auth::id(), $search, $date);
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

    /**
     * Get paginated clients list with search and date filter.
     */
    public function GetPaginatedClientsList($search = null, $perPage = 10, $date = null)
    {
        try {
            $clients  = $this->clientRepository->GetPaginatedClients(Auth::id(), $search, $perPage, $date);
            $response = General::setResponse('SUCCESS', 'Clients retrieved successfully.');
            $response['data'] = $clients;

            return $response;
        } catch (Exception $e) {
            return General::setResponse('OTHER_ERROR', $e->getMessage());
        }
    }

    /**
     * Create or update a client.
     */
    public function SaveClient($postData)
    {
        try {
            $validator = Validate::required($postData, ['client_id', 'client_alias']);
            if ($validator->fails()) {
                return General::setResponse('VALIDATION_ERROR', $validator->errors()->first());
            }

            DB::beginTransaction();
            $client = $this->clientRepository->StoreOrUpdate(Auth::id(), $postData);
            
            // If client_alias was updated, update existing cases for this client as well
            if (! empty($postData['client_alias']) && ! empty($postData['client_id'])) {
                DB::table('client_cases')
                    ->where('user_id', Auth::id())
                    ->where('client_id', $postData['client_id'])
                    ->update(['client_alias' => $postData['client_alias']]);
            }
            DB::commit();

            $response = General::setResponse('SUCCESS', 'Client saved successfully.');
            $response['data'] = $client;

            return $response;
        } catch (Exception $e) {
            DB::rollBack();

            return General::setResponse('OTHER_ERROR', $e->getMessage());
        }
    }

    /**
     * Delete a client and all associated cases & AI jobs.
     */
    public function DeleteClient($clientIdOrId)
    {
        try {
            $clientIdOrId = trim((string) $clientIdOrId);

            if ($clientIdOrId === '') {
                return General::setResponse('VALIDATION_ERROR', 'Client ID or ID is required.');
            }

            $userId = Auth::id();
            $deleted = $this->clientRepository->DeleteClient($userId, $clientIdOrId);

            if (! $deleted) {
                return General::setResponse('VALIDATION_ERROR', 'Client not found or you do not have permission to delete it.');
            }

            return General::setResponse('SUCCESS', 'Client and associated cases deleted successfully.');
        } catch (Exception $e) {
            return General::setResponse('OTHER_ERROR', $e->getMessage());
        }
    }

    /**
     * AI-powered Executive Summary of Client Cases and Action Plans.
     * Summarizes the client's historical trajectory, recurring patterns,
     * action plans, what worked/failed, and future recommendations.
     */
    public function SummarizeClientCases($postData)
    {
        try {
            $user = Auth::user();
            if (! $user) {
                return General::setResponse('VALIDATION_ERROR', 'User not authenticated.');
            }

            $clientId    = $postData['client_id'] ?? null;
            $caseId      = $postData['case_id'] ?? null;
            $clientAlias = $postData['client_alias'] ?? null;
            $focus       = $postData['focus'] ?? null;
            $limit       = (int) ($postData['limit'] ?? 30);

            // Determine language preference
            $locale = $postData['lang'] ?? request()->header('Accept-Language', 'en');
            if (str_starts_with(strtolower($locale), 'fr')) {
                $locale = 'fr';
            } else {
                $locale = 'en';
            }

            // Validate client_id if provided: ensure it belongs strictly to this user's account
            if (! empty($clientId)) {
                $userClient = $this->clientRepository->FindByClientId($user->id, $clientId)
                    ?? $this->clientCaseRepository->checkClientIdExists($user->id, $clientId);

                if (! $userClient) {
                    return General::setResponse('VALIDATION_ERROR', 'Client not found or does not belong to your account.');
                }

                if (empty($clientAlias) && ! empty($userClient->client_alias)) {
                    $clientAlias = $userClient->client_alias;
                }
            }

            // Validate case_id if provided: ensure it belongs strictly to this user's account
            if (! empty($caseId)) {
                $userCase = $this->clientCaseRepository->GetCaseDetails($caseId, $user->id);
                if (! $userCase) {
                    return General::setResponse('VALIDATION_ERROR', 'Case not found or does not belong to your account.');
                }

                if (empty($clientId) && ! empty($userCase->client_id)) {
                    $clientId = $userCase->client_id;
                }
                if (empty($clientAlias) && ! empty($userCase->client_alias)) {
                    $clientAlias = $userCase->client_alias;
                }
            }

            // Retrieve cases ordered chronologically for evolutionary analysis
            $cases = $this->clientCaseRepository->getCasesForSummary($user->id, $clientId, $caseId, $clientAlias, $limit);

            // Check if client requested a forced re-generation (e.g. ?regenerate=true, ?refresh=1, ?force=1)
            $forceRegenerate = filter_var($postData['regenerate'] ?? $postData['refresh'] ?? $postData['force'] ?? false, FILTER_VALIDATE_BOOLEAN);

            if (! $forceRegenerate) {
                $storedSummary = null;

                // 1. Check clients table if clientId is available
                if (! empty($clientId)) {
                    $clientRecord = DB::table('clients')
                        ->where('user_id', $user->id)
                        ->where('client_id', $clientId)
                        ->first();

                    if ($clientRecord && ! empty($clientRecord->ai_summary)) {
                        $storedSummary = is_string($clientRecord->ai_summary)
                            ? json_decode($clientRecord->ai_summary, true)
                            : $clientRecord->ai_summary;
                    }
                }

                // 2. Check client_cases table if not found in clients table
                if (empty($storedSummary)) {
                    if (! empty($caseId)) {
                        $caseRecord = DB::table('client_cases')
                            ->where('user_id', $user->id)
                            ->where('id', $caseId)
                            ->first();

                        if ($caseRecord && ! empty($caseRecord->client_summary)) {
                            $storedSummary = is_string($caseRecord->client_summary)
                                ? json_decode($caseRecord->client_summary, true)
                                : $caseRecord->client_summary;
                        }
                    }

                    if (empty($storedSummary) && $cases->isNotEmpty()) {
                        $caseWithSummary = $cases->first(fn($c) => ! empty($c->client_summary));
                        if ($caseWithSummary) {
                            $storedSummary = is_string($caseWithSummary->client_summary)
                                ? json_decode($caseWithSummary->client_summary, true)
                                : $caseWithSummary->client_summary;
                        }
                    }
                }

                // If stored summary exists, return it immediately without calling AI
                if (! empty($storedSummary)) {
                    $response         = General::setResponse('SUCCESS', 'Client cases summary retrieved successfully.');
                    $response['data'] = $storedSummary;
                    $response['meta'] = [
                        'client_id'    => $clientId,
                        'client_alias' => $clientAlias,
                        'cases_count'  => $cases->count(),
                        'lang'         => $locale,
                        'is_stored'    => true,
                    ];

                    return $response;
                }
            }

            // If user only requested stored summary and none exists
            if (filter_var($postData['stored_only'] ?? $postData['only_stored'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
                return General::setResponse('VALIDATION_ERROR', 'No stored summary found for this client.');
            }

            if ($cases->isEmpty()) {
                $emptyMsg = $locale === 'fr' 
                    ? 'Aucun cas précédent trouvé pour ce client.' 
                    : 'No historical cases found for this client.';

                $emptyData = [
                    'executive_summary'                    => $emptyMsg,
                    'client_profile_and_evolution'        => 'N/A',
                    'total_cases_analyzed'                => 0,
                    'cases_overview'                       => [],
                    'recurring_patterns_and_objections'   => [],
                    'client_red_flags'                     => [],
                    'proven_strategies_and_successes'      => [],
                    'pitfalls_and_lessons_learned'         => [],
                    'strategic_recommendations_for_future' => [],
                ];

                $response         = General::setResponse('SUCCESS', 'No cases found to summarize.');
                $response['data'] = $emptyData;
                $response['meta'] = [
                    'client_id'    => $clientId,
                    'client_alias' => $clientAlias,
                    'cases_count'  => 0,
                    'lang'         => $locale,
                ];

                return $response;
            }

            // Check if synchronous execution requested (default is asynchronous queue)
            $isSync = filter_var($postData['sync'] ?? false, FILTER_VALIDATE_BOOLEAN);

            if (! $isSync) {
                // Async Queue Mode (matching analyze-case and generate-plan)
                $aiJob = AiJob::create([
                    'user_id'   => $user->id,
                    'case_id'   => $caseId,
                    'client_id' => $clientId,
                    'job_type'  => 'summarize_cases',
                    'status'    => 'pending',
                    'attempts'  => 0,
                ]);

                SummarizeCasesJob::dispatch(
                    $aiJob->id,
                    $user->id,
                    $clientId,
                    $caseId,
                    $clientAlias,
                    $locale,
                    $focus,
                    $limit
                );

                // Auto-trigger background queue worker
                $this->spawnQueueWorker();

                $response              = General::setResponse('SUCCESS', 'Client cases summary queued. You will be notified when complete.');
                $response['job_id']    = $aiJob->id;
                $response['job_type']  = 'summarize_cases';
                $response['status']    = 'pending';
                $response['client_id'] = $clientId;
                $response['case_id']   = $caseId;
                $response['cases_count'] = $cases->count();

                return $response;
            }

            $resolvedClientAlias = $clientAlias;
            $resolvedClientId    = $clientId;

            $formattedCases = [];
            foreach ($cases as $c) {
                if (empty($resolvedClientAlias) && ! empty($c->client_alias)) {
                    $resolvedClientAlias = $c->client_alias;
                }
                if (empty($resolvedClientId) && ! empty($c->client_id)) {
                    $resolvedClientId = $c->client_id;
                }

                $caseDetails = $c->case_details;
                if (is_string($caseDetails)) {
                    $caseDetails = json_decode($caseDetails, true) ?: [];
                }

                $aiAnalysis = $c->ai_analysis;
                if (is_string($aiAnalysis)) {
                    $aiAnalysis = json_decode($aiAnalysis, true) ?: [];
                }

                $actionPlan = $c->action_plan;
                if (is_string($actionPlan)) {
                    $actionPlan = json_decode($actionPlan, true) ?: [];
                }

                $formattedCases[] = [
                    'id'               => $c->id,
                    'case_reference'   => $c->case_reference ?? ('Case #' . $c->id),
                    'client_id'        => $c->client_id,
                    'client_alias'     => $c->client_alias,
                    'date'             => $c->created_at?->format('Y-m-d H:i:s'),
                    'context_overview' => $c->context_overview,
                    'case_details'     => is_array($caseDetails) ? $caseDetails : [],
                    'ai_analysis'      => is_array($aiAnalysis) ? $aiAnalysis : [],
                    'action_plan'      => is_array($actionPlan) ? $actionPlan : [],
                    'plan_rating'      => $c->plan_rating,
                    'user_question'    => $c->user_question,
                ];
            }

            $userProfile = method_exists($user, 'getAiBehaviorProfile') ? $user->getAiBehaviorProfile() : '';

            $pythonUrl = config('services.pdf_service.base_url');
            $endpoint  = rtrim($pythonUrl, '/') . '/summarize-client-cases';

            $payload = [
                'client_id'    => $resolvedClientId,
                'client_alias' => $resolvedClientAlias ?? 'Client',
                'user_profile' => $userProfile,
                'cases'        => $formattedCases,
                'lang'         => $locale,
                'focus'        => $focus,
            ];

            Log::info('[ClientCaseCls] Requesting client cases summary from AI (sync)', [
                'user_id'      => $user->id,
                'client_id'    => $resolvedClientId,
                'client_alias' => $resolvedClientAlias,
                'cases_count'  => count($formattedCases),
                'endpoint'     => $endpoint,
            ]);

            $httpResponse = Http::timeout(240)->withHeaders([
                'Accept-Language' => $locale,
                'Content-Type'    => 'application/json',
            ])->post($endpoint, $payload);

            if (! $httpResponse->successful()) {
                Log::error('[ClientCaseCls] AI summary endpoint failed', [
                    'status' => $httpResponse->status(),
                    'body'   => $httpResponse->body(),
                ]);

                return General::setResponse('OTHER_ERROR', 'AI summarization service request failed (' . $httpResponse->status() . ').');
            }

            $summaryData = $httpResponse->json();

            if (isset($summaryData['error'])) {
                Log::error('[ClientCaseCls] AI service returned error', ['error' => $summaryData['error']]);
                return General::setResponse('OTHER_ERROR', $summaryData['error']);
            }

            // Persist client summary to clients and client_cases tables
            if (! empty($resolvedClientId)) {
                DB::table('clients')
                    ->where('user_id', $user->id)
                    ->where('client_id', $resolvedClientId)
                    ->update([
                        'ai_summary'         => json_encode($summaryData),
                        'summary_updated_at' => now(),
                    ]);

                DB::table('client_cases')
                    ->where('user_id', $user->id)
                    ->where('client_id', $resolvedClientId)
                    ->update([
                        'client_summary' => json_encode($summaryData),
                    ]);
            } elseif (! empty($caseId)) {
                DB::table('client_cases')
                    ->where('user_id', $user->id)
                    ->where('id', $caseId)
                    ->update([
                        'client_summary' => json_encode($summaryData),
                    ]);
            }

            $response         = General::setResponse('SUCCESS', 'Client cases summarized successfully.');
            $response['data'] = $summaryData;
            $response['meta'] = [
                'client_id'     => $resolvedClientId,
                'client_alias'  => $resolvedClientAlias,
                'cases_count'   => count($formattedCases),
                'lang'          => $locale,
            ];

            return $response;

        } catch (Exception $e) {
            Log::error('[ClientCaseCls] SummarizeClientCases error', ['error' => $e->getMessage()]);

            return General::setResponse('OTHER_ERROR', 'Failed to generate cases summary: ' . $e->getMessage());
        }
    }

    /**
     * Get client summary and metadata for PDF export.
     * If summary does not exist yet, it will generate it synchronously.
     */
    public function GetClientSummaryForExport($params)
    {
        try {
            $user = Auth::user();
            if (! $user) {
                return General::setResponse('VALIDATION_ERROR', 'Unauthorized.');
            }

            $clientId    = trim((string) ($params['client_id'] ?? $params['clientId'] ?? ''));
            $caseId      = $params['case_id'] ?? $params['caseId'] ?? null;
            $clientAlias = $params['client_alias'] ?? $params['clientAlias'] ?? null;

            // Validate client_id if provided
            if (! empty($clientId)) {
                $userClient = $this->clientRepository->FindByClientId($user->id, $clientId)
                    ?? $this->clientCaseRepository->checkClientIdExists($user->id, $clientId);

                if (! $userClient) {
                    return General::setResponse('VALIDATION_ERROR', 'Client not found or does not belong to your account.');
                }

                if (empty($clientAlias) && ! empty($userClient->client_alias)) {
                    $clientAlias = $userClient->client_alias;
                }
            }

            // Validate case_id if provided
            if (! empty($caseId)) {
                $userCase = $this->clientCaseRepository->GetCaseDetails($caseId, $user->id);
                if (! $userCase) {
                    return General::setResponse('VALIDATION_ERROR', 'Case not found or does not belong to your account.');
                }

                if (empty($clientId) && ! empty($userCase->client_id)) {
                    $clientId = $userCase->client_id;
                }
                if (empty($clientAlias) && ! empty($userCase->client_alias)) {
                    $clientAlias = $userCase->client_alias;
                }
            }

            if (empty($clientId) && empty($caseId) && empty($clientAlias)) {
                return General::setResponse('VALIDATION_ERROR', 'Please provide client_id or case_id.');
            }

            // Check if summary already exists in clients table
            $summaryData = null;
            if (! empty($clientId)) {
                $clientRecord = $this->clientRepository->FindByClientId($user->id, $clientId);
                if (! empty($clientRecord?->ai_summary)) {
                    $summaryData = is_string($clientRecord->ai_summary)
                        ? json_decode($clientRecord->ai_summary, true)
                        : $clientRecord->ai_summary;
                }
            }

            // Check client_cases table if not found in clients table
            if (empty($summaryData)) {
                $cases = $this->clientCaseRepository->getCasesForSummary($user->id, $clientId, $caseId, $clientAlias, 30);
                $caseWithSummary = $cases->first(fn($c) => ! empty($c->client_summary));
                if ($caseWithSummary) {
                    $summaryData = is_string($caseWithSummary->client_summary)
                        ? json_decode($caseWithSummary->client_summary, true)
                        : $caseWithSummary->client_summary;
                }
            }

            // If no summary exists yet, run synchronous generation
            if (empty($summaryData)) {
                $genParams = array_merge($params, ['sync' => true]);
                $genResponse = $this->SummarizeClientCases($genParams);

                if (! isset($genResponse['code']) || $genResponse['code'] !== 200 || empty($genResponse['data'])) {
                    return $genResponse;
                }

                $summaryData = $genResponse['data'];
            }

            // Count total cases
            $totalCases = 0;
            if (! empty($clientId)) {
                $totalCases = $this->clientCaseRepository->countClientCases($user->id, $clientId);
            }
            if ($totalCases === 0 && isset($summaryData['cases_overview']) && is_array($summaryData['cases_overview'])) {
                $totalCases = count($summaryData['cases_overview']);
            }

            $response = General::setResponse('SUCCESS', 'Client summary retrieved for export.');
            $response['data'] = [
                'summary'      => $summaryData,
                'client_id'    => $clientId,
                'client_alias' => $clientAlias ?? 'Client',
                'total_cases'  => $totalCases,
            ];

            return $response;
        } catch (Exception $e) {
            return General::setResponse('OTHER_ERROR', $e->getMessage());
        }
    }
}
