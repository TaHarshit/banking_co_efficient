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

    public const MAX_AI_RETRIES = 3;

    public int $tries = 1;

    public int $timeout = 960;

    public function __construct(
        protected $aiJobId,
        protected $caseId,
        protected $userId,
        protected $caseData     = null,
        protected $analysisData = null,
        protected $locale       = 'en'
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

        \App\General\General::sendNotificationV1(
            $this->userId,
            'Action Plan Generation Started',
            "Your negotiation action plan for case \"{$clientCase->client_alias}\" has started.",
            ['case_id' => $this->caseId]
        );

        $analysisData = $this->analysisData ?? $clientCase->ai_analysis;

        if (empty($analysisData)) {
            $this->markFailed($aiJob, $notificationsRepo, 'Missing AI analysis data. Please run case analysis first.', $clientCase->client_alias ?? '');
            return;
        }

        $caseData = $this->caseData ?? [
            'client_id'        => $clientCase->client_id,
            'client_alias'     => $clientCase->client_alias ?? 'Client',
            'context_overview' => $clientCase->context_overview ?? '',
            'case_details'     => ! empty($clientCase->case_details) ? $clientCase->case_details : (object) [],
        ];

        $pythonUrl = config('services.pdf_service.base_url');
        $endpoint  = rtrim($pythonUrl, '/') . '/generate-plan';

        $user        = $clientCase->user;
        $userProfile = $user ? $user->getAiBehaviorProfile() : '';

        // Retrieve historical cases for this client if client_id exists
        $clientHistory = [];
        if (! empty($clientCase->client_id)) {
            $prevCases = ClientCase::where('user_id', $this->userId)
                ->where('client_id', $clientCase->client_id)
                ->where('id', '!=', $clientCase->id)
                ->where(function ($q) {
                    $q->whereNotNull('ai_analysis')
                        ->orWhereNotNull('action_plan');
                })
                ->orderBy('created_at', 'desc')
                ->limit(3)
                ->get();

            foreach ($prevCases as $prev) {
                $clientHistory[] = [
                    'case_reference'      => $prev->case_reference,
                    'client_alias'        => $prev->client_alias,
                    'context_overview'    => $prev->context_overview,
                    'ai_recommendations'  => $prev->ai_analysis['ai_recommendations'] ?? [],
                    'ai_challenges'       => $prev->ai_analysis['ai_challenges'] ?? [],
                    'action_plan_summary' => $prev->action_plan['executive_summary'] ?? null,
                    'plan_rating'         => $prev->plan_rating,
                    'date'                => $prev->created_at?->format('Y-m-d'),
                ];
            }
        }

        if (empty($caseData) || ! is_array($caseData)) {
            $caseData = (object) [];
        }
        if (empty($analysisData) || ! is_array($analysisData)) {
            $analysisData = (object) [];
        }

        $payload = [
            'client_id'      => $clientCase->client_id,
            'case_data'      => $caseData,
            'analysis_data'  => $analysisData,
            'user_profile'   => $userProfile,
            'client_history' => $clientHistory,
        ];

        $lastError = 'Unknown error.';

        for ($attempt = 1; $attempt <= self::MAX_AI_RETRIES; $attempt++) {
            $aiJob->increment('attempts');
            Log::info("[GeneratePlanJob] Attempt {$attempt} for case #{$this->caseId}");

            try {
                $payload['lang'] = $this->locale;
                $response = Http::timeout(900)->withHeaders([
                    'Accept-Language' => $this->locale
                ])->withOptions([
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

                if (empty($planData) || isset($planData['error'])) {
                    $lastError = $planData['error'] ?? 'AI returned empty/invalid response.';
                    Log::warning("[GeneratePlanJob] Attempt {$attempt} AI error", ['error' => $lastError]);
                    $this->sleepBetweenRetries($attempt);
                    continue;
                }

                $requiredFields = ['executive_summary', 'meeting_objectives', 'action_plan', 'strategic_recommendations', 'critical_success_factors', 'plan_b'];
                $missingFields  = array_filter($requiredFields, fn($f) => ! isset($planData[$f]));

                if (! empty($missingFields)) {
                    $lastError = 'AI response missing required fields: ' . implode(', ', $missingFields);
                    Log::warning("[GeneratePlanJob] Attempt {$attempt} incomplete response", ['missing' => $missingFields]);
                    $this->sleepBetweenRetries($attempt);
                    continue;
                }

                $savedImages = $this->collectImageUrls([$caseData, $analysisData, $planData]);
                $planData['images'] = $savedImages;

                $clientCase->action_plan = $planData;
                $clientCase->save();

                $aiJob->update([
                    'status' => 'completed',
                    'result' => $planData,
                ]);

                \App\General\General::sendNotificationV1(
                    $this->userId,
                    'Action Plan Ready',
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

        $this->markFailed($aiJob, $notificationsRepo, $lastError, $clientCase->client_alias ?? '');
    }

    private function collectImageUrls(array $payloads): array
    {
        $images = [];
        $seen = [];

        $scan = function (mixed $node, bool $forceImageGroup = false) use (&$scan, &$images, &$seen): void {
            if (is_string($node)) {
                if (! $this->isImageSource($node, $forceImageGroup)) {
                    return;
                }

                $src = $this->normalizeImageSource($node);
                if (! $src || isset($seen[$src])) {
                    return;
                }

                $seen[$src] = true;
                $images[] = $src;
                return;
            }

            if (! is_array($node)) {
                return;
            }

            foreach ($node as $key => $child) {
                $scan($child, $forceImageGroup || $this->isImageFieldName((string) $key));
            }
        };

        foreach ($payloads as $payload) {
            $scan($payload);
        }

        return array_values($images);
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

        return url($value);
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
            'Action Plan Failed',
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