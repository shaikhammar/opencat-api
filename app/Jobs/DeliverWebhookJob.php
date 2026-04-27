<?php

namespace App\Jobs;

use App\Models\Webhook;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;

class DeliverWebhookJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(
        public readonly int   $webhookId,
        public readonly array $payload,
    ) {}

    /** Exponential back-off: 5 s → 25 s → 125 s. */
    public function backoff(): array
    {
        return [5, 25, 125];
    }

    public function handle(): void
    {
        $webhook = Webhook::find($this->webhookId);

        // Webhook deleted after dispatch — nothing to do.
        if ($webhook === null) {
            return;
        }

        $body      = json_encode($this->payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
        $signature = 'sha256=' . hash_hmac('sha256', $body, $webhook->secret);

        $response = Http::withHeaders([
            'Content-Type'              => 'application/json',
            'X-CatFramework-Signature'  => $signature,
        ])->withBody($body, 'application/json')
          ->timeout(10)
          ->post($webhook->url);

        // Non-2xx triggers a retry via the standard ShouldQueue retry mechanism.
        if (!$response->successful()) {
            throw new \RuntimeException(
                "Webhook delivery failed for {$webhook->url}: HTTP {$response->status()}"
            );
        }
    }
}
