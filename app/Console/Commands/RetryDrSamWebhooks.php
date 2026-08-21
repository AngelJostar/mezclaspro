<?php

namespace App\Console\Commands;

use App\Services\Integrations\DrSam\DrSamWebhookNotifier;
use Illuminate\Console\Command;

class RetryDrSamWebhooks extends Command
{
    protected $signature = 'integration:retry-webhooks {--limit=50 : Maximo por corrida}';
    protected $description = 'Reintenta avisos de integracion pendientes hacia Dr. Sam';

    public function handle(DrSamWebhookNotifier $notifier): int
    {
        $result = $notifier->retryPending((int) $this->option('limit'));
        $this->info("Webhooks entregados: {$result['delivered']}; fallidos: {$result['failed']}.");

        return self::SUCCESS;
    }
}
