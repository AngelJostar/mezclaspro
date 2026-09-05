<?php

namespace App\Http\Controllers\Admin\Oncologicos;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use App\Models\Warehouse;
use App\Models\Oncologicos\Laboratory;
use App\Models\Oncologicos\LaboratoryPurchaseOrder;
use App\Services\PurchaseOrderCatalogService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LaboratoryPurchaseOrderController extends Controller
{
    public function create(Laboratory $laboratory): View
    {
        $deliveryLaboratories = Laboratory::query()
            ->with(['warehouses' => fn ($query) => $query
                ->where('is_active', true)
                ->orderBy('name')])
            ->where('activo', true)
            ->whereHas('warehouses', fn ($query) => $query->where('is_active', true))
            ->orderBy('nombre')
            ->get();

        $supplierOptions = Supplier::query()
            ->availableForPurchases()
            ->orderBy('name')
            ->get()
            ->map(fn (Supplier $supplier): array => [
                'name' => $supplier->name,
                'rfc' => $supplier->rfc,
                'contact_name' => $supplier->contact_name,
                'phone' => $supplier->phone,
                'email' => $supplier->email,
                'fax' => $supplier->fax,
                'category' => $supplier->category,
                'address' => $supplier->address,
                'bank_details' => $supplier->bank_details,
            ])
            ->values();

        return view('admin.oncologicos.laboratory.purchase-orders.create', compact(
            'laboratory',
            'deliveryLaboratories',
            'supplierOptions',
        ))->with('inventoryDestinations', LaboratoryPurchaseOrder::INVENTORY_DESTINATIONS);
    }

    public function products(Request $request, Laboratory $laboratory, PurchaseOrderCatalogService $catalog): JsonResponse
    {
        $validated = $request->validate($this->destinationRules($request));
        $warehouse = Warehouse::findOrFail($validated['warehouse_id']);

        return response()->json([
            'products' => $catalog->products($warehouse, $validated['inventory_destination']),
        ])->header('Cache-Control', 'no-store');
    }

    private function destinationRules(Request $request): array
    {
        return [
            'delivery_laboratory_id' => [
                'required', 'integer', Rule::exists('laboratories', 'id')->where('activo', true),
            ],
            'warehouse_id' => [
                'required', 'integer',
                Rule::exists('warehouses', 'id')->where(fn ($query) => $query
                    ->where('laboratory_id', $request->integer('delivery_laboratory_id'))
                    ->where('is_active', true)),
            ],
            'inventory_destination' => [
                'required', 'string', Rule::in(array_keys(LaboratoryPurchaseOrder::INVENTORY_DESTINATIONS)),
            ],
        ];
    }

    public function store(Request $request, Laboratory $laboratory, PurchaseOrderCatalogService $catalog): RedirectResponse
    {
        $validated = $request->validate([
            'department' => ['required', 'string', 'max:255'],
            'supplier' => ['required', 'string', 'max:255'],
            'supplier_rfc' => ['nullable', 'string', 'max:20'],
            'supplier_bank_details' => ['nullable', 'string', 'max:2000'],
            'supplier_address' => ['nullable', 'string', 'max:2000'],
            'supplier_contact' => ['nullable', 'string', 'max:255'],
            'supplier_phone' => ['nullable', 'string', 'max:40'],
            'quotation_number' => ['nullable', 'string', 'max:255'],
            'order_type' => ['nullable', 'string', 'max:80'],
            'supplier_email' => ['nullable', 'email', 'max:255'],
            'supplier_fax' => ['nullable', 'string', 'max:40'],
            'requested_at' => ['required', 'date'],
            'proposed_delivery_at' => ['nullable', 'date'],
            'urgent_delivery_time' => ['nullable', 'string', 'max:255'],
            'invoice_to' => ['required', 'string', 'max:255'],
            'invoice_address' => ['required', 'string', 'max:2000'],
            'invoice_rfc' => ['required', 'string', 'max:20'],
            'invoice_emails' => ['nullable', 'string', 'max:1000'],
            ...$this->destinationRules($request),
            'delivery_attention' => ['nullable', 'string', 'max:255'],
            'delivery_address' => ['required', 'string', 'max:2000'],
            'delivery_schedule' => ['nullable', 'string', 'max:255'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_key' => ['required', 'string', 'max:80'],
            'items.*.quantity' => ['required', 'numeric', 'gt:0'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'discount' => ['nullable', 'numeric', 'min:0'],
            'tax_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'notes' => ['nullable', 'string', 'max:10000'],
        ]);

        $creator = $request->user();
        $validated['prepared_by'] = trim(($creator?->name ?? '') . ' ' . ($creator?->lastname ?? ''));

        $deliveryLaboratory = Laboratory::findOrFail($validated['delivery_laboratory_id']);
        $warehouse = Warehouse::query()
            ->where('laboratory_id', $deliveryLaboratory->id)
            ->where('is_active', true)
            ->findOrFail($validated['warehouse_id']);

        $validated['delivery_attention'] = $warehouse->name . ' - ' . $deliveryLaboratory->nombre;

        $products = $catalog->products($warehouse, $validated['inventory_destination'])->keyBy('product_key');
        $validated['items'] = array_values($validated['items']);
        $errors = [];
        foreach ($validated['items'] as $index => $item) {
            if (! $products->has($item['product_key'])) {
                $errors["items.$index.product_key"] = 'Partida '.($index + 1).': selecciona un producto del almacén y subalmacén indicados.';
            }
        }
        if ($errors) {
            throw ValidationException::withMessages($errors);
        }

        $items = collect($validated['items'])
            ->map(function (array $item) use ($products): array {
                $quantity = round((float) $item['quantity'], 4);
                $unitPrice = round((float) $item['unit_price'], 2);

                return [
                    'product_key' => $item['product_key'],
                    'description' => $products[$item['product_key']]['description'],
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'subtotal' => round($quantity * $unitPrice, 2),
                ];
            })
            ->values()
            ->all();

        $subtotal = round((float) collect($items)->sum('subtotal'), 2);
        $discount = min(round((float) ($validated['discount'] ?? 0), 2), $subtotal);
        $taxRate = round((float) $validated['tax_rate'], 2);
        $taxAmount = round(($subtotal - $discount) * ($taxRate / 100), 2);
        $total = round($subtotal - $discount + $taxAmount, 2);
        $details = collect($items)
            ->map(fn(array $item) => sprintf('%s | %s | $%s', $item['description'], $item['quantity'], number_format($item['unit_price'], 2, '.', '')))
            ->implode("\n");

        unset($validated['items']);

        $order = DB::transaction(function () use ($laboratory, $validated, $items, $details, $subtotal, $discount, $taxRate, $taxAmount, $total) {
            $order = $laboratory->purchaseOrders()->create([
                ...$validated,
                'folio' => 'PENDIENTE-' . Str::uuid(),
                'details' => $details,
                'items' => $items,
                'subtotal' => $subtotal,
                'discount' => $discount,
                'tax_rate' => $taxRate,
                'tax_amount' => $taxAmount,
                'total' => $total,
                'status' => 'enviada',
                'created_by' => auth()->id(),
            ]);

            $order->update([
                'folio' => 'OC' . str_pad((string) $order->id, 3, '0', STR_PAD_LEFT),
            ]);

            $this->syncSupplierCatalog($validated);

            return $order;
        });

        return redirect()
            ->route('admin.oncologicos.laboratory.purchase-orders.download', [$laboratory, $order]);
    }

    public function download(Laboratory $laboratory, LaboratoryPurchaseOrder $purchaseOrder)
    {
        abort_unless($purchaseOrder->laboratory_id === $laboratory->id, 404);

        $purchaseOrder->loadMissing(['laboratory', 'deliveryLaboratory', 'warehouse', 'creator']);

        return Pdf::loadView('admin.oncologicos.laboratory.purchase-orders.pdf', [
            'order' => $purchaseOrder,
        ])
            ->setPaper('letter', 'portrait')
            ->download($purchaseOrder->folio . '.pdf');
    }

    private function syncSupplierCatalog(array $orderData): void
    {
        $name = trim((string) $orderData['supplier']);
        $rfc = filled($orderData['supplier_rfc'] ?? null)
            ? Str::upper(trim((string) $orderData['supplier_rfc']))
            : null;

        $supplier = Supplier::query()
            ->when(
                $rfc,
                fn ($query) => $query->where('rfc', $rfc),
                fn ($query) => $query->whereRaw('LOWER(name) = ?', [Str::lower($name)])
            )
            ->first() ?? new Supplier();

        $supplier->fill([
            'name' => $name,
            'rfc' => $rfc,
            'contact_name' => $orderData['supplier_contact'] ?? null,
            'phone' => $orderData['supplier_phone'] ?? null,
            'email' => $orderData['supplier_email'] ?? null,
            'fax' => $orderData['supplier_fax'] ?? null,
            'category' => $orderData['order_type'] ?? null,
            'address' => $orderData['supplier_address'] ?? null,
            'bank_details' => $orderData['supplier_bank_details'] ?? null,
        ]);

        if (! $supplier->exists) {
            $supplier->status = Supplier::STATUS_ACTIVE;
            $supplier->created_by = auth()->id();
        }

        $supplier->save();
    }
}
