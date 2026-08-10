<?php

namespace App\Console\Commands;

use App\Models\ExternalMixtureRequest;
use App\Services\Integrations\DrSam\ExternalMixtureMaterializer;
use App\Services\Integrations\DrSam\ExternalMixtureStatusService;
use Illuminate\Console\Command;

class MaterializeExternalMixtureRequests extends Command
{
    protected $signature = 'integration:materialize-mixtures {--id= : ID interno externo} {--limit=50 : Maximo por corrida}';
    protected $description = 'Materializa solicitudes externas recibidas o fallidas en los flujos NPT y oncologico';

    public function handle(ExternalMixtureMaterializer $materializer, ExternalMixtureStatusService $statuses): int
    {
        $records = ExternalMixtureRequest::query()
            ->when($this->option('id'), fn ($query, $id) => $query->whereKey($id))
            ->when(! $this->option('id'), fn ($query) => $query->whereIn('status', ['received', 'materialization_failed']))
            ->orderBy('id')
            ->limit((int) $this->option('limit'))
            ->get();
        $failed = 0;

        foreach ($records as $record) {
            if (! $materializer->materialize($record)) {
                $failed++;
            }
        }

        ExternalMixtureRequest::query()
            ->whereNotNull('materialized_id')
            ->orderBy('id')
            ->limit((int) $this->option('limit'))
            ->get()
            ->each(fn (ExternalMixtureRequest $record) => $statuses->refresh($record));

        $this->info("Solicitudes procesadas: {$records->count()}; fallidas: {$failed}.");

        return $failed === 0 ? self::SUCCESS : self::FAILURE;
    }
}
