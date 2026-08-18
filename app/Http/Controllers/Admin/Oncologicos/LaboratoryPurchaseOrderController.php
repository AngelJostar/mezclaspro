<?php

namespace App\Http\Controllers\Admin\Oncologicos;

use App\Http\Controllers\Controller;
use App\Models\Warehouse;
use App\Models\Oncologicos\Laboratory;
use App\Models\Oncologicos\LaboratoryPurchaseOrder;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class LaboratoryPurchaseOrderController extends Controller
{
    public function create(Laboratory $laboratory): View
    {
        $deliveryLaboratories = Laboratory::query()
            ->with(['warehouses' => fn ($query) => $query
                ->where('is_active', true)
                ->orderBy('name')])
        ->where(function ($query) use ($laboratory) {
            $query->where('activo', true)
                ->orWhere('id', $laboratory->getKey());
        })
            ->orderBy('nombre')
            ->get();

        return view('admin.oncologicos.laboratory.purchase-orders.create', compact(
            'laboratory',
            'deliveryLaboratories',
        ));
    }

    public function store(Request $request, Laboratory $laboratory): RedirectResponse
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
            'delivery_laboratory_id' => ['required', 'integer', 'exists:laboratories,id'],
            'warehouse_id' => [
                'required',
                'integer',
                Rule::exists('warehouses', 'id')->where(
                    fn ($query) => $query
                        ->where('laboratory_id', $request->integer('delivery_laboratory_id'))
                        ->where('is_active', true)
                ),
            ],
            'delivery_attention' => ['nullable', 'string', 'max:255'],
            'delivery_address' => ['required', 'string', 'max:2000'],
            'delivery_schedule' => ['nullable', 'string', 'max:255'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.description' => ['required', 'string', 'max:500'],
            'items.*.quantity' => ['required', 'numeric', 'gt:0'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'discount' => ['nullable', 'numeric', 'min:0'],
            'tax_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'notes' => ['nullable', 'string', 'max:10000'],
            'prepared_by' => ['nullable', 'string', 'max:255'],
        ]);

        $deliveryLaboratory = Laboratory::findOrFail($validated['delivery_laboratory_id']);
        $warehouse = Warehouse::query()
            ->where('laboratory_id', $deliveryLaboratory->id)
            ->where('is_active', true)
            ->findOrFail($validated['warehouse_id']);

        $validated['delivery_attention'] = $warehouse->name . ' - ' . $deliveryLaboratory->nombre;

        $items = collect($validated['items'])
            ->map(function (array $item): array {
                $quantity = round((float) $item['quantity'], 4);
                $unitPrice = round((float) $item['unit_price'], 2);

                return [
                    'description' => trim($item['description']),
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
}
