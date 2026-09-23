<?php

namespace App\Console\Commands;

use App\Services\AutomaticPurchaseOrderService;
use Illuminate\Console\Command;

class GenerateAutomaticPurchaseOrders extends Command
{
    protected $signature = 'inventory:reorder {--laboratory= : ID de la central}';
    protected $description = 'Genera ordenes de reposicion pendientes de revision, sin enviarlas al proveedor.';

    public function handle(AutomaticPurchaseOrderService $service): int
    {
        $id = $this->option('laboratory');
        if ($id !== null && (!ctype_digit((string) $id) || (int) $id < 1)) {
            $this->error('La central debe ser un ID valido.');
            return self::FAILURE;
        }
        if (!$service->available()) {
            $this->error('Falta aplicar la migracion de ordenes automaticas.');
            return self::FAILURE;
        }
        $count = $service->reconcile($id === null ? null : (int) $id);
        $this->info("Ordenes automaticas creadas: {$count}. Sin envios al proveedor.");
        return self::SUCCESS;
    }
}
