<?php

namespace App\Services\Integrations\DrSam;

use App\Models\ExternalMixtureRequest;
use App\Models\ExternalMixtureWebhookDelivery;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

class DrSamWebhookNotifier
{
    public function statusChanged(ExternalMixtureRequest $request): bool
    {
        if (! $this->configured()) {
            return false;
        }

        $payload = [
            'event_id' => (string) Str::uuid(),
            'event_type' => 'mixture.status_changed',
            'request_id' => $request->remote_request_id,
            'status' => $request->status,
            'source_updated_at' => $request->updated_at?->toIso8601String(),
            'event_at' => now()->toIso8601String(),
        ];
        $hashPayload = $payload;
        unset($hashPayload['event_id'], $hashPayload['event_at']);
        $payloadHash = hash('sha256', json_encode($hashPayload, JSON_THROW_ON_ERROR));

        $delivery = ExternalMixtureWebhookDelivery::query()->firstOrCreate(
            [
                'external_mixture_request_id' => $request->getKey(),
                'event_type' => $payload['event_type'],
                'payload_hash' => $payloadHash,
            ],
            [
                'event_id' => $payload['event_id'],
                'status' => 'pending',
                'payload' => $payload,
                'next_attempt_at' => now(),
            ]
        );

        return $delivery->status === 'delivered' || $this->deliver($delivery);
    }

    public function retryPending(int $limit = 50): array
    {
        $delivered = 0;
        $failed = 0;

        ExternalMixtureWebhookDelivery::query()
            ->whereIn('status', ['pending', 'failed'])
            ->where('attempts', '<', $this->maxAttempts())
            ->where(fn ($query) => $query->whereNull('next_attempt_at')->orWhere('next_attempt_at', '<=', now()))
            ->orderBy('id')
            ->limit($limit)
            ->get()
            ->each(function (ExternalMixtureWebhookDelivery $delivery) use (&$delivered, &$failed): void {
                $this->deliver($delivery) ? $delivered++ : $failed++;
            });

        return compact('delivered', 'failed');
    }

    public function deliver(ExternalMixtureWebhookDelivery $delivery): bool
    {
        if ($delivery->status === 'delivered') {
            return true;
        }

        $attempt = $delivery->attempts + 1;
        $json = json_encode($delivery->payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        $timestamp = now()->timestamp;

        try {
            $response = Http::asJson()
                ->withHeaders([
                    'X-CBTA-Timestamp' => (string) $timestamp,
                    'X-CBTA-Signature' => hash_hmac('sha256', $timestamp.'.'.$json, (string) config('services.dr_sam.webhook_secret')),
                ])
                ->withBody($json, 'application/json')
                ->connectTimeout((int) config('services.dr_sam.connect_timeout', 3))
                ->timeout((int) config('services.dr_sam.timeout', 5))
                ->post((string) config('services.dr_sam.webhook_url'));

            if ($response->successful()) {
                $delivery->update([
                    'status' => 'delivered', 'attempts' => $attempt, 'http_status' => $response->status(),
                    'last_error' => null, 'last_attempt_at' => now(), 'next_attempt_at' => null, 'delivered_at' => now(),
                ]);

                return true;
            }

            $this->recordFailure($delivery, $attempt, 'HTTP '.$response->status().': '.mb_substr($response->body(), 0, 1000), $response->status());
        } catch (Throwable $exception) {
            $this->recordFailure($delivery, $attempt, mb_substr($exception->getMessage(), 0, 1000));
        }

        return false;
    }

    private function recordFailure(ExternalMixtureWebhookDelivery $delivery, int $attempt, string $error, ?int $httpStatus = null): void
    {
        $exhausted = $attempt >= $this->maxAttempts();
        $delay = min(360, (int) 2 ** max(0, $attempt - 1));

        $delivery->update([
            'status' => $exhausted ? 'exhausted' : 'failed', 'attempts' => $attempt,
            'http_status' => $httpStatus, 'last_error' => $error, 'last_attempt_at' => now(),
            'next_attempt_at' => $exhausted ? null : now()->addMinutes($delay),
        ]);
    }

    private function configured(): bool
    {
        return filled(config('services.dr_sam.webhook_url')) && filled(config('services.dr_sam.webhook_secret'));
    }

    private function maxAttempts(): int
    {
        return max(1, (int) config('services.dr_sam.webhook_max_attempts', 8));
    }
}
