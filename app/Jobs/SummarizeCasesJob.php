<?php

namespace App\Jobs;

use App\General\General;
use App\Models\AiJob;
use App\Models\User;
use App\Repositories\Api\ClientCaseRepository;
use App\Repositories\Api\NotificationsRepository;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SummarizeCasesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public const MAX_AI_RETRIES = 3;

    public int $tries = 1;

    public int $timeout = 960;

    public function __construct(
        protected $aiJobId,
        protected $userId,
        protected $clientId = null,
        protected $caseId = null,
        protected $clientAlias = null,
        protected $locale = 'en',
        protected $focus = null,
        protected $limit = 30
    ) {}

    public function handle(ClientCaseRepository $clientCaseRepo, NotificationsRepository $notificationsRepo): void
    {
        $aiJob = AiJob::find($this->aiJobId);
        if (! $aiJob) {
            Log::error('[SummarizeCasesJob] AiJob record not found', ['ai_job_id' => $this->aiJobId]);
            return;
        }

        $aiJob->update(['status' => 'processing']);

        $user = User::find($this->userId);
        if (! $user) {
            $this->markFailed($aiJob, 'User not found.');
            return;
        }

        // Retrieve cases ordered chronologically for evolutionary analysis
        $cases = $clientCaseRepo->getCasesForSummary(
            $this->userId,
            $this->clientId,
            $this->caseId,
            $this->clientAlias,
            $this->limit
        );

        if ($cases->isEmpty()) {
            $emptyMsg = $this->locale === 'fr'
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

            $aiJob->update([
                'status' => 'completed',
                'result' => $emptyData,
            ]);

            Log::info('[SummarizeCasesJob] No cases found; completed with empty summary structure.', [
                'ai_job_id' => $this->aiJobId,
                'client_id' => $this->clientId,
            ]);

            return;
        }

        $resolvedClientAlias = $this->clientAlias;
        $resolvedClientId    = $this->clientId;

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
            'lang'         => $this->locale,
            'focus'        => $this->focus,
        ];

        $lastError = 'Unknown error occurred during AI summarization.';

        for ($attempt = 1; $attempt <= self::MAX_AI_RETRIES; $attempt++) {
            $aiJob->increment('attempts');
            Log::info("[SummarizeCasesJob] Attempt {$attempt} for ai_job #{$this->aiJobId}", [
                'client_id'   => $resolvedClientId,
                'cases_count' => count($formattedCases),
            ]);

            try {
                $httpResponse = Http::timeout(240)->withHeaders([
                    'Accept-Language' => $this->locale,
                    'Content-Type'    => 'application/json',
                ])->post($endpoint, $payload);

                if (! $httpResponse->successful()) {
                    $lastError = "AI service request failed with HTTP status {$httpResponse->status()}: " . $httpResponse->body();
                    Log::warning("[SummarizeCasesJob] Attempt {$attempt} failed HTTP", ['status' => $httpResponse->status()]);
                    $this->sleepBetweenRetries($attempt);
                    continue;
                }

                $summaryData = $httpResponse->json();

                if (isset($summaryData['error'])) {
                    $lastError = $summaryData['error'];
                    Log::warning("[SummarizeCasesJob] Attempt {$attempt} AI error: {$lastError}");
                    $this->sleepBetweenRetries($attempt);
                    continue;
                }

                // Success: save result to ai_jobs
                $aiJob->update([
                    'status' => 'completed',
                    'result' => $summaryData,
                ]);

                // Persist client summary to clients and client_cases tables
                if (! empty($resolvedClientId)) {
                    DB::table('clients')
                        ->where('user_id', $this->userId)
                        ->where('client_id', $resolvedClientId)
                        ->update([
                            'ai_summary'         => json_encode($summaryData),
                            'summary_updated_at' => now(),
                        ]);

                    DB::table('client_cases')
                        ->where('user_id', $this->userId)
                        ->where('client_id', $resolvedClientId)
                        ->update([
                            'client_summary' => json_encode($summaryData),
                        ]);
                } elseif (! empty($this->caseId)) {
                    DB::table('client_cases')
                        ->where('user_id', $this->userId)
                        ->where('id', $this->caseId)
                        ->update([
                            'client_summary' => json_encode($summaryData),
                        ]);
                }

                Log::info("[SummarizeCasesJob] Completed and persisted successfully for ai_job #{$this->aiJobId}");

                // Send notification
                General::sendNotificationV1(
                    $this->userId,
                    'Client Cases Summary Ready',
                    "The strategic summary and overview for \"{$resolvedClientAlias}\" is ready.",
                    [
                        'job_id'    => $this->aiJobId,
                        'client_id' => $resolvedClientId,
                    ]
                );

                return;

            } catch (Exception $e) {
                $lastError = $e->getMessage();
                Log::error("[SummarizeCasesJob] Attempt {$attempt} exception: {$lastError}");
                $this->sleepBetweenRetries($attempt);
            }
        }

        // Exhausted retries
        $this->markFailed($aiJob, $lastError);
    }

    private function sleepBetweenRetries(int $attempt): void
    {
        $seconds = min(5 * (2 ** ($attempt - 1)), 25);
        Log::info("[SummarizeCasesJob] Waiting {$seconds}s before retry...");
        sleep($seconds);
    }

    private function markFailed(AiJob $aiJob, string $error): void
    {
        $aiJob->update([
            'status'        => 'failed',
            'error_message' => $error,
        ]);

        General::sendNotificationV1(
            $this->userId,
            '❌ Summary Generation Failed',
            "The client cases summary could not be completed. Error: {$error}",
            ['job_id' => $this->aiJobId]
        );

        Log::error("[SummarizeCasesJob] Marked as failed for ai_job #{$this->aiJobId}", ['error' => $error]);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('[SummarizeCasesJob] Job hard-failed at queue level', [
            'ai_job_id' => $this->aiJobId,
            'error'     => $exception->getMessage(),
        ]);

        $aiJob = AiJob::find($this->aiJobId);
        if ($aiJob && $aiJob->status !== 'completed') {
            $aiJob->update([
                'status'        => 'failed',
                'error_message' => $exception->getMessage(),
            ]);
        }
    }
}
