<?php

namespace App\Services;

use App\Models\MinimumStockSetting;
use App\Models\Oncologicos\Laboratory;
use App\Models\Oncologicos\LaboratoryPurchaseOrder;
use App\Models\Supplier;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class AutomaticPurchaseOrderService
{
    public function __construct(private MinimumStockCatalogService $catalog) {}

    public function available(): bool
    {
        return Schema::hasTable('minimum_stock_settings')
            && Schema::hasColumn('laboratory_purchase_orders', 'is_automatic');
    }

    public function reconcile(?int $laboratoryId = null): int
    {
        if (!$this->available()) return 0;

        $ids = MinimumStockSetting::query()->distinct()->pluck('laboratory_id')
            ->merge(LaboratoryPurchaseOrder::whereNotNull('automatic_open_key')->pluck('laboratory_id'))
            ->unique()->sort()->values();
        if ($laboratoryId !== null) $ids = $ids->filter(fn ($id) => (int) $id === $laboratoryId);
        $created = 0;
        foreach ($ids as $id) $created += $this->reconcileLaboratory((int) $id);
        return $created;
    }

    private function reconcileLaboratory(int $id): int
    {
        // Serialize reconciliation per central; the unique open key also protects against duplicates.
        return DB::transaction(function () use ($id) {
            $central = Laboratory::lockForUpdate()->find($id);
            if (!$central) return 0;
            $open = LaboratoryPurchaseOrder::where('laboratory_id', $id)->where('is_automatic', true)
                ->whereNotNull('automatic_open_key')->lockForUpdate()->get()->keyBy('automatic_open_key');
            $created = 0;
            $needed = [];
            $warehouses = $central->warehouses()->where('is_active', true)->get();
            $warehouse = $warehouses->count() === 1 ? $warehouses->first() : null;
            $rows = $this->catalog->rows($id)->keyBy('key');

            foreach ($central->activo ? $rows : [] as $row) {
                if (!$row->active || $row->minimum_stock === null || $row->maximum_stock === null
                    || $row->minimum_stock <= 0 || $row->maximum_stock < $row->minimum_stock
                    || $row->current_stock >= $row->minimum_stock) continue;
                $key = $id.':'.$row->key;
                $needed[] = $key;
                $order = $open->get($key);
                // An order that has moved beyond review must never be rewritten by the generator.
                if ($order && $order->status !== 'pendiente_revision') continue;
                $quantity = (int) ceil($row->maximum_stock - $row->current_stock);
                $supplier = $row->supplier?->status === Supplier::STATUS_ACTIVE ? $row->supplier : null;
                $description = $row->product.' - '.$row->presentation.' - '.$row->commercial_name;
                $snapshot = [
                    'product_type' => $row->type, 'presentation_id' => $row->id,
                    'product' => $row->product, 'dose' => $row->dose, 'presentation' => $row->presentation,
                    'commercial_name' => $row->commercial_name, 'supplier_id' => $supplier?->id,
                    'minimum_stock' => $row->minimum_stock, 'maximum_stock' => $row->maximum_stock,
                    'trigger_stock' => $order?->reorder_snapshot['trigger_stock'] ?? $row->current_stock,
                    'current_stock' => $row->current_stock, 'quantity' => $quantity,
                    'pricing_pending' => true,
                ];
                $values = [
                    'delivery_laboratory_id' => $id, 'warehouse_id' => $warehouse?->id,
                    'inventory_destination' => $row->category, 'department' => 'Compras',
                    'supplier' => $supplier?->name ?? 'Sin proveedor asignado',
                    'supplier_rfc' => $supplier?->rfc, 'supplier_contact' => $supplier?->contact_name,
                    'supplier_phone' => $supplier?->phone, 'supplier_email' => $supplier?->email,
                    'supplier_fax' => $supplier?->fax, 'supplier_address' => $supplier?->address,
                    'supplier_bank_details' => $supplier?->bank_details,
                    'order_type' => 'Reposicion automatica',
                    'delivery_attention' => $warehouse?->name,
                    'delivery_address' => $warehouse?->address ?: $central->direccion,
                    'details' => $description,
                    'items' => [[
                        'product_key' => $row->key, 'description' => $description,
                        'quantity' => $quantity, 'unit_price' => null, 'subtotal' => null,
                    ]],
                    'reorder_snapshot' => $snapshot,
                ];
                if ($order) {
                    $order->fill($values);
                    if ($order->isDirty()) $order->save();
                } else {
                    $order = LaboratoryPurchaseOrder::create($values + [
                        'laboratory_id' => $id, 'folio' => 'AUTO-'.Str::uuid(),
                        'requested_at' => today(), 'status' => 'pendiente_revision',
                        'is_automatic' => true, 'automatic_open_key' => $key,
                        'prepared_by' => 'Sistema - Stock minimo',
                        'notes' => 'Generada por stock inferior al punto de reorden. Pendiente de revision; no enviada al proveedor.',
                        'subtotal' => 0, 'discount' => 0, 'tax_rate' => 0, 'tax_amount' => 0, 'total' => 0,
                    ]);
                    $order->update(['folio' => 'OC-AUTO-'.str_pad((string) $order->id, 6, '0', STR_PAD_LEFT)]);
                    $created++;
                }
            }
            foreach ($open as $key => $order) {
                if ($order->status !== 'pendiente_revision' || in_array($key, $needed, true)) continue;
                $order->update([
                    'status' => 'cancelada', 'automatic_open_key' => null,
                    'reorder_snapshot' => array_replace($order->reorder_snapshot, [
                        'current_stock' => $rows->get($order->reorder_snapshot['product_type'].':'.$order->reorder_snapshot['presentation_id'])?->current_stock,
                        'closed_at' => now()->toIso8601String(),
                        'closed_reason' => 'Ya no requiere reposicion: stock recuperado, producto inactivo o configuracion retirada.',
                    ]),
                ]);
            }
            return $created;
        }, 3);
    }
}
