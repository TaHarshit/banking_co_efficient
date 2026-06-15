<?php

namespace App\Jobs;

use App\General\General;
use App\Models\AiJob;
use App\Models\ClientCase;
use App\Repositories\Api\NotificationsRepository;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeneratePlanJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Maximum number of AI-level retry attempts (not queue retries).
     */
    public const MAX_AI_RETRIES = 3;

    /**
     * Laravel queue: do NOT auto-retry at the queue level — we handle retries ourselves.
     */
    public int $tries = 1;

    /**
     * Timeout in seconds for the whole job.
     */
    public int $timeout = 960;

    public function __construct(
        protected $aiJobId,
        protected $caseId,
        protected $userId,
        protected $caseData     = null,
        protected $analysisData = null
    ) {}

    public function handle(NotificationsRepository $notificationsRepo): void
    {
        $aiJob = AiJob::find($this->aiJobId);
        if (! $aiJob) {
            Log::error('[GeneratePlanJob] AiJob record not found', ['ai_job_id' => $this->aiJobId]);
            return;
        }

        $clientCase = ClientCase::find($this->caseId);
        if (! $clientCase) {
            $this->markFailed($aiJob, $notificationsRepo, 'Case not found.');
            return;
        }

        $aiJob->update(['status' => 'processing']);

        General::sendNotificationV1(
            $this->userId,
            '🔄 Action Plan Generation Started',
            "Your negotiation action plan for case \"{$clientCase->client_alias}\" has started.",
            ['case_id' => $this->caseId]
        );

        $caseData     = $this->caseData     ?? $clientCase->case_details;
        $analysisData = $this->analysisData ?? $clientCase->ai_analysis;

        if (! $caseData || ! $analysisData) {
            $this->markFailed($aiJob, $notificationsRepo, 'Missing case data or analysis data. Please run case analysis first.', $clientCase->client_alias ?? '');
            return;
        }

        $pythonUrl = config('services.pdf_service.base_url');
        $endpoint  = rtrim($pythonUrl, '/') . '/generate-plan';

        $user        = $clientCase->user;
        $userProfile = $user ? $user->getAiBehaviorProfile() : '';

        $payload = [
            'case_data'     => $caseData,
            'analysis_data' => $analysisData,
            'user_profile'  => $userProfile,
        ];

        $lastError = 'Unknown error.';

        for ($attempt = 1; $attempt <= self::MAX_AI_RETRIES; $attempt++) {
            $aiJob->increment('attempts');
            Log::info("[GeneratePlanJob] Attempt {$attempt} for case #{$this->caseId}");

            try {
                $response = Http::timeout(900)->withOptions([
                    'curl' => [
                        CURLOPT_TIMEOUT        => 900,
                        CURLOPT_CONNECTTIMEOUT => 60,
                    ],
                ])->post($endpoint, $payload);

                if (! $response->successful()) {
                    $lastError = 'Python service HTTP error: ' . $response->status() . ' - ' . $response->body();
                    Log::warning("[GeneratePlanJob] Attempt {$attempt} HTTP error", ['error' => $lastError]);
                    $this->sleepBetweenRetries($attempt);
                    continue;
                }

                $planData = $response->json();

                // Detect blank or error responses from Python
                if (empty($planData) || isset($planData['error'])) {
                    $lastError = $planData['error'] ?? 'AI returned empty/invalid response.';
                    Log::warning("[GeneratePlanJob] Attempt {$attempt} AI error", ['error' => $lastError]);
                    $this->sleepBetweenRetries($attempt);
                    continue;
                }

                // Validate required fields
                $requiredFields = ['executive_summary', 'meeting_objectives', 'action_plan', 'strategic_recommendations', 'critical_success_factors', 'plan_b'];
                $missingFields  = array_filter($requiredFields, fn($f) => ! isset($planData[$f]));

                if (! empty($missingFields)) {
                    $lastError = 'AI response missing required fields: ' . implode(', ', $missingFields);
                    Log::warning("[GeneratePlanJob] Attempt {$attempt} incomplete response", ['missing' => $missingFields]);
                    $this->sleepBetweenRetries($attempt);
                    continue;
                }

                // Success — save result
                $clientCase->action_plan = $planData;
                $clientCase->save();

                $aiJob->update([
                    'status' => 'completed',
                    'result' => $planData,
                ]);

                // Notify user 
                Log::info("user id is ".$this->userId);
                
                General::sendNotificationV1(
                    $this->userId,
                    '✅ Action Plan Ready',
                    "Your negotiation action plan for case \"{$clientCase->client_alias}\" has been generated.",
                    ['case_id' => $this->caseId]
                );

                Log::info("[GeneratePlanJob] Completed successfully for case #{$this->caseId}");
                return;

            } catch (\Exception $e) {
                $lastError = $e->getMessage();
                Log::error("[GeneratePlanJob] Attempt {$attempt} exception", ['error' => $lastError]);
                $this->sleepBetweenRetries($attempt);
            }
        }

        // All retries exhausted
        $this->markFailed($aiJob, $notificationsRepo, $lastError, $clientCase->client_alias ?? '');
    }

    private function sleepBetweenRetries(int $attempt): void
    {
        $seconds = min(5 * (2 ** ($attempt - 1)), 30);
        Log::info("[GeneratePlanJob] Waiting {$seconds}s before retry...");
        sleep($seconds);
    }

    private function markFailed(AiJob $aiJob, NotificationsRepository $notificationsRepo, string $error, string $alias = ''): void
    {
        $aiJob->update([
            'status'        => 'failed',
            'error_message' => $error,
        ]);

        $caseLabel = $alias ? "for case \"{$alias}\"" : '';
        \App\General\General::sendNotificationV1(
            $this->userId,
            '❌ Action Plan Failed',
            "The action plan generation {$caseLabel} could not be completed. Error: {$error}",
            ['case_id' => $this->caseId]
        );

        Log::error("[GeneratePlanJob] Marked as failed for case #{$this->caseId}", ['error' => $error]);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('[GeneratePlanJob] Job hard-failed (queue level)', [
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
