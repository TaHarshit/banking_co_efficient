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

class AnalyzeCaseJob implements ShouldQueue
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
        protected $userId
    ) {}

    public function handle(NotificationsRepository $notificationsRepo): void
    {
        $aiJob = AiJob::find($this->aiJobId);
        if (! $aiJob) {
            Log::error('[AnalyzeCaseJob] AiJob record not found', ['ai_job_id' => $this->aiJobId]);
            return;
        }

        $clientCase = ClientCase::find($this->caseId);
        if (! $clientCase) {
            $this->markFailed($aiJob, $notificationsRepo, 'Case not found.');
            return;
        }

        $aiJob->update(['status' => 'processing']);

        \App\General\General::sendNotificationV1(
            $this->userId,
            '🔄 AI Analysis Started',
            "Your negotiation analysis for case \"{$clientCase->client_alias}\" has started.",
            ['case_id' => $this->caseId]
        );

        $pythonUrl = config('services.pdf_service.base_url');
        $endpoint  = rtrim($pythonUrl, '/') . '/analyze-case';

        // Build user profile from the user model
        $user        = $clientCase->user;
        $userProfile = $user ? $user->getAiBehaviorProfile() : '';

        $payload = [
            'client_alias'     => $clientCase->client_alias,
            'context_overview' => $clientCase->context_overview ?? '',
            'case_details'     => $clientCase->case_details ?? [],
            'user_profile'     => $userProfile,
        ];

        $lastError = 'Unknown error.';

        for ($attempt = 1; $attempt <= self::MAX_AI_RETRIES; $attempt++) {
            $aiJob->increment('attempts');
            Log::info("[AnalyzeCaseJob] Attempt {$attempt} for case #{$this->caseId}");

            try {
                $response = Http::timeout(900)->withOptions([
                    'curl' => [
                        CURLOPT_TIMEOUT        => 900,
                        CURLOPT_CONNECTTIMEOUT => 60,
                    ],
                ])->post($endpoint, $payload);

                if (! $response->successful()) {
                    $lastError = 'Python service HTTP error: ' . $response->status() . ' - ' . $response->body();
                    Log::warning("[AnalyzeCaseJob] Attempt {$attempt} HTTP error", ['error' => $lastError]);
                    $this->sleepBetweenRetries($attempt);
                    continue;
                }

                $analysisData = $response->json();

                // Detect blank or error responses from Python
                if (empty($analysisData) || isset($analysisData['error'])) {
                    $lastError = $analysisData['error'] ?? 'AI returned empty/invalid response.';
                    Log::warning("[AnalyzeCaseJob] Attempt {$attempt} AI error", ['error' => $lastError]);
                    $this->sleepBetweenRetries($attempt);
                    continue;
                }

                // Success — save result
                $clientCase->ai_analysis = $analysisData;
                $clientCase->save();

                $aiJob->update([
                    'status' => 'completed',
                    'result' => $analysisData,
                ]);

                General::sendNotificationV1(
                    $this->userId,
                    '✅ AI Analysis Complete',
                    "Your negotiation analysis for case \"{$clientCase->client_alias}\" is ready.",
                    ['case_id' => $this->caseId]
                );

                Log::info("[AnalyzeCaseJob] Completed successfully for case #{$this->caseId}");
                return;

            } catch (\Exception $e) {
                $lastError = $e->getMessage();
                Log::error("[AnalyzeCaseJob] Attempt {$attempt} exception", ['error' => $lastError]);
                $this->sleepBetweenRetries($attempt);
            }
        }

        // All retries exhausted
        $this->markFailed($aiJob, $notificationsRepo, $lastError, $clientCase->client_alias ?? '');
    }

    private function sleepBetweenRetries(int $attempt): void
    {
        // Exponential backoff: 5s, 15s, 30s
        $seconds = min(5 * (2 ** ($attempt - 1)), 30);
        Log::info("[AnalyzeCaseJob] Waiting {$seconds}s before retry...");
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
            '❌ AI Analysis Failed',
            "The AI analysis {$caseLabel} could not be completed. Error: {$error}",
            ['case_id' => $this->caseId]
        );

        Log::error("[AnalyzeCaseJob] Marked as failed for case #{$this->caseId}", ['error' => $error]);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('[AnalyzeCaseJob] Job hard-failed (queue level)', [
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
