<?php

namespace App\Services\Integrations\DrSam;

use App\Models\ExternalMixtureRequest;
use Illuminate\Support\Facades\Http;

class DrSamWebhookNotifier
{
    public function statusChanged(ExternalMixtureRequest $request): bool
    {
        $url = (string) config('services.dr_sam.webhook_url');
        $secret = (string) config('services.dr_sam.webhook_secret');

        if ($url === '' || $secret === '') {
            return false;
        }

        $payload = [
            'request_id' => $request->remote_request_id,
            'status' => $request->status,
            'event_at' => now()->toIso8601String(),
        ];
        $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        $timestamp = now()->timestamp;

        return Http::asJson()
            ->withHeaders([
                'X-CBTA-Timestamp' => (string) $timestamp,
                'X-CBTA-Signature' => hash_hmac('sha256', $timestamp.'.'.$json, $secret),
            ])
            ->withBody($json, 'application/json')
            ->timeout((int) config('services.dr_sam.timeout', 5))
            ->post($url)
            ->successful();
    }
}
