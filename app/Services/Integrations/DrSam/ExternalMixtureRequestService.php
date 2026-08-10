<?php

namespace App\Services\Integrations\DrSam;

use App\Models\ExternalMixtureRequest;
use App\Models\Hospital;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Illuminate\Validation\ValidationException;

class ExternalMixtureRequestService
{
    public function __construct(private MixturePrevalidationService $prevalidation)
    {
    }

    public function store(array $payload): array
    {
        $hospital = Hospital::query()
            ->where('external_code', $payload['medical_unit_code'])
            ->where('is_active', true)
            ->firstOrFail();
        $hash = $this->hash($payload);

        return DB::transaction(function () use ($hospital, $payload, $hash): array {
            $existing = ExternalMixtureRequest::query()
                ->where('local_external_id', $payload['local_external_id'])
                ->lockForUpdate()
                ->first();

            if ($existing) {
                if (! hash_equals($existing->payload_hash, $hash)) {
                    throw new ConflictHttpException('La clave de idempotencia ya fue usada con un payload diferente.');
                }

                return [$existing, false];
            }

            $result = $this->prevalidation->validate($hospital, $payload);
            if (! $result['valid']) {
                throw ValidationException::withMessages([
                    'items' => collect($result['errors'])->pluck('message')->filter()->values()->all(),
                ]);
            }

            $record = ExternalMixtureRequest::query()->create([
                'remote_request_id' => (string) Str::uuid(),
                'local_external_id' => $payload['local_external_id'],
                'hospital_id' => $hospital->id,
                'catalog_type' => $payload['catalog_type'],
                'catalog_version' => $result['catalog_version'],
                'status' => 'received',
                'payload_hash' => $hash,
                'payload' => $payload,
                'received_at' => now(),
            ]);

            return [$record, true];
        });
    }

    private function hash(array $payload): string
    {
        return hash('sha256', json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
    }
}
