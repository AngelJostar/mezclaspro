<?php

namespace App\Services;

use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StockReorderMonitor
{
    private bool $dirty = false;

    public function record(QueryExecuted $query): void
    {
        // Observe both Eloquent and query-builder writes. Read stock only after the operation ends.
        if (preg_match('/^\s*(?:update|insert(?:\s+ignore)?\s+into|delete\s+from)\s+[`"]?(medicine_batches|medicine_laboratory_stocks|diluent_presentations|consumable_lots|minimum_stock_settings|medicine_presentations|nutrition_medicine_presentations|diluent_catalog_presentations|consumable_catalog_presentations|consumable_items|medicines_catalog|suppliers|laboratories|warehouses)[`"]?\s/i', $query->sql)) {
            $this->dirty = true;
        }
    }

    public function flush(): void
    {
        if (!$this->dirty || DB::transactionLevel() !== 0) return;
        $this->dirty = false;
        try {
            app(AutomaticPurchaseOrderService::class)->reconcile();
        } catch (\Throwable $exception) {
            // Inventory writes have already committed; reconciliation can be retried by the scheduler.
            Log::error('No se pudieron actualizar las ordenes automaticas.', ['exception' => $exception::class]);
        }
    }
}
