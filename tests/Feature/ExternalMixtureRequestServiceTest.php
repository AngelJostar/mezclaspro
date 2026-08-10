<?php

namespace Tests\Feature;

use App\Models\ExternalMixtureRequest;
use App\Models\Hospital;
use App\Services\Integrations\DrSam\ExternalMixtureRequestService;
use App\Services\Integrations\DrSam\MixturePrevalidationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Tests\TestCase;

class ExternalMixtureRequestServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_an_idempotent_external_request_without_duplicating_it(): void
    {
        $hospital = Hospital::query()->create([
            'external_code' => 'CBTA-HOSP-01',
            'name' => 'Hospital Integrado',
            'adress' => 'Direccion de prueba',
            'is_active' => true,
        ]);
        $payload = $this->payload();
        $this->mock(MixturePrevalidationService::class)
            ->shouldReceive('validate')
            ->once()
            ->withArgs(fn (Hospital $received, array $data): bool => $received->is($hospital) && $data === $payload)
            ->andReturn($this->validPrevalidation());
        $service = app(ExternalMixtureRequestService::class);

        [$first, $created] = $service->store($payload);
        [$second, $createdAgain] = $service->store($payload);

        $this->assertTrue($created);
        $this->assertFalse($createdAgain);
        $this->assertSame($first->id, $second->id);
        $this->assertSame('received', $first->status);
        $this->assertDatabaseCount('external_mixture_requests', 1);
    }

    public function test_it_rejects_reusing_an_idempotency_key_with_a_different_payload(): void
    {
        Hospital::query()->create([
            'external_code' => 'CBTA-HOSP-01',
            'name' => 'Hospital Integrado',
            'adress' => 'Direccion de prueba',
            'is_active' => true,
        ]);
        $this->mock(MixturePrevalidationService::class)
            ->shouldReceive('validate')
            ->once()
            ->andReturn($this->validPrevalidation());
        $service = app(ExternalMixtureRequestService::class);
        $service->store($this->payload());

        $this->expectException(ConflictHttpException::class);
        $service->store(array_replace_recursive($this->payload(), ['items' => [['quantity' => 200]]]));
    }

    private function payload(): array
    {
        return [
            'local_external_id' => '019ca6bc-b8f0-7bd7-a819-913ccfc1045d',
            'medical_unit_code' => 'CBTA-HOSP-01',
            'catalog_type' => 'npt',
            'catalog_version' => 'npt-v3',
            'patient' => ['external_id' => 'PAC-01', 'name' => 'Paciente Integrado'],
            'clinical' => ['diagnosis' => 'Diagnostico de prueba'],
            'items' => [[
                'product_code' => 'GLUCOSE-50',
                'presentation_code' => 'GLUCOSE-50-500ML',
                'quantity' => 100,
                'unit' => 'ml',
            ]],
        ];
    }

    private function validPrevalidation(): array
    {
        return [
            'valid' => true,
            'catalog_type' => 'npt',
            'catalog_version' => 'npt-v3',
            'items' => [],
            'errors' => [],
        ];
    }
}
