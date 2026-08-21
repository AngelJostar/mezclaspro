<?php

namespace Tests\Feature;

use App\Models\ExternalMixtureRequest;
use App\Models\ExternalMixtureWebhookDelivery;
use App\Models\Hospital;
use App\Services\Integrations\DrSam\DrSamWebhookNotifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

class DrSamWebhookRetryTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_failed_webhook_is_persisted_and_delivered_by_the_retry_command(): void
    {
        config()->set('services.dr_sam.webhook_url', 'https://dr-sam.test/api/integrations/cbta/mixture-status');
        config()->set('services.dr_sam.webhook_secret', 'webhook-secret');
        Http::fakeSequence()
            ->push(['message' => 'temporary failure'], 503)
            ->push(['accepted' => true], 200);

        $request = $this->externalRequest();
        $this->assertFalse(app(DrSamWebhookNotifier::class)->statusChanged($request));

        $delivery = ExternalMixtureWebhookDelivery::query()->firstOrFail();
        $this->assertSame('failed', $delivery->status);
        $this->assertSame(1, $delivery->attempts);
        $this->assertSame(503, $delivery->http_status);
        $this->assertNotNull($delivery->next_attempt_at);
        $eventId = $delivery->event_id;

        $delivery->update(['next_attempt_at' => now()->subSecond()]);
        $this->artisan('integration:retry-webhooks')->assertSuccessful();

        $delivery->refresh();
        $this->assertSame('delivered', $delivery->status);
        $this->assertSame(2, $delivery->attempts);
        $this->assertSame($eventId, $delivery->event_id);
        $this->assertNotNull($delivery->delivered_at);
        Http::assertSentCount(2);
    }

    public function test_the_same_status_transition_is_not_enqueued_twice(): void
    {
        config()->set('services.dr_sam.webhook_url', 'https://dr-sam.test/api/integrations/cbta/mixture-status');
        config()->set('services.dr_sam.webhook_secret', 'webhook-secret');
        Http::fake(['https://dr-sam.test/*' => Http::response(['accepted' => true])]);
        $request = $this->externalRequest();

        $notifier = app(DrSamWebhookNotifier::class);
        $this->assertTrue($notifier->statusChanged($request));
        $this->assertTrue($notifier->statusChanged($request));

        $this->assertDatabaseCount('external_mixture_webhook_deliveries', 1);
        Http::assertSentCount(1);
    }

    private function externalRequest(): ExternalMixtureRequest
    {
        $hospital = Hospital::query()->create([
            'external_code' => 'DRSAM-WEBHOOK',
            'name' => 'Hospital Webhook',
            'adress' => 'Direccion',
            'is_active' => true,
        ]);

        return ExternalMixtureRequest::query()->create([
            'remote_request_id' => (string) Str::uuid(),
            'local_external_id' => (string) Str::uuid(),
            'hospital_id' => $hospital->id,
            'catalog_type' => 'npt',
            'status' => 'ready',
            'payload_hash' => str_repeat('a', 64),
            'payload' => [],
            'received_at' => now(),
        ]);
    }
}
