<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Oncologicos\DiluentPresentation;
use App\Models\ConsumableLot;
use App\Models\ProductionSupplyRequest;
use App\Models\ProductionSupplyRequestLine;
use App\Models\Warehouse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ProductionSupplyRequestController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->string('status')->toString();
        $requests = ProductionSupplyRequest::query()->with(['warehouse', 'requester', 'lines'])
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->latest('id')->paginate(20)->withQueryString();
        return view('admin.production-supplies.index', compact('requests', 'status'));
    }

    public function create(): View
    {
        $this->authorizePermission(request(), 'oncologicos_laboratory_create');
        return view('admin.production-supplies.create', [
            'warehouses' => Warehouse::query()->where('is_active', true)->with('laboratory')->orderBy('name')->get(),
            'supplies' => $this->supplies(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizePermission($request, 'oncologicos_laboratory_create');
        $data = $request->validate([
            'warehouse_id' => ['required', 'exists:warehouses,id'], 'observations' => ['nullable', 'string', 'max:2000'],
            'lines' => ['required', 'array', 'min:1'], 'lines.*.supply_id' => ['required', 'integer', 'exists:consumable_lots,id'],
            'lines.*.quantity' => ['required', 'numeric', 'gt:0'], 'lines.*.notes' => ['nullable', 'string', 'max:500'],
        ]);
        $lines = collect($data['lines'])
            ->groupBy('supply_id')
            ->map(fn ($items, $supplyId) => [
                'supply_id' => $supplyId,
                'quantity' => $items->sum(fn ($item) => (float) $item['quantity']),
                'notes' => $items->pluck('notes')->filter()->unique()->implode(' | ') ?: null,
            ])->values();

        $requestModel = DB::transaction(function () use ($data, $lines, $request) {
            $requestModel = ProductionSupplyRequest::create([
                'folio' => 'INS-'.now()->format('Ymd').'-'.str_pad((string) ((ProductionSupplyRequest::max('id') ?? 0) + 1), 5, '0', STR_PAD_LEFT),
                'warehouse_id' => $data['warehouse_id'], 'area' => 'Producción', 'status' => ProductionSupplyRequest::STATUS_REQUESTED,
                'observations' => $data['observations'] ?? null, 'requested_by' => $request->user()->id, 'requested_at' => now(),
            ]);
            foreach ($lines as $line) {
                $supply = ConsumableLot::query()->whereKey($line['supply_id'])->where('warehouse_id', $data['warehouse_id'])->where('is_active', true)->first();
                if (! $supply) throw ValidationException::withMessages(['lines' => 'Uno de los insumos no pertenece al almacén seleccionado o es un diluyente.']);
                $requestModel->lines()->create(['consumable_lot_id' => $supply->id, 'requested_quantity' => $line['quantity'], 'notes' => $line['notes'] ?? null]);
            }
            return $requestModel;
        });
        return redirect()->route('admin.production-supplies.show', $requestModel)->with('success', 'Solicitud enviada a Almacén.');
    }

    public function show(ProductionSupplyRequest $productionSupplyRequest): View
    {
        $productionSupplyRequest->load(['warehouse.laboratory', 'requester', 'approver', 'supplier', 'receiver', 'lines.supply.diluent', 'lines.consumableLot.item']);
        return view('admin.production-supplies.show', ['supplyRequest' => $productionSupplyRequest]);
    }

    public function approve(Request $request, ProductionSupplyRequest $productionSupplyRequest): RedirectResponse
    {
        $this->authorizePermission($request, 'oncologicos_laboratory_edit');
        abort_unless($productionSupplyRequest->status === ProductionSupplyRequest::STATUS_REQUESTED, 422);
        $data = $request->validate(['lines' => ['required', 'array'], 'lines.*.approved_quantity' => ['required', 'numeric', 'min:0'], 'resolution_notes' => ['nullable', 'string', 'max:2000']]);
        DB::transaction(function () use ($data, $request, $productionSupplyRequest) {
            $allApproved = true;
            foreach ($productionSupplyRequest->lines()->lockForUpdate()->get() as $line) {
                $quantity = (float) ($data['lines'][$line->id]['approved_quantity'] ?? 0);
                $line->update(['approved_quantity' => $quantity]);
                $allApproved = $allApproved && $quantity >= $line->requested_quantity;
            }
            $productionSupplyRequest->update(['status' => $allApproved ? ProductionSupplyRequest::STATUS_APPROVED : ProductionSupplyRequest::STATUS_PARTIALLY_APPROVED, 'resolution_notes' => $data['resolution_notes'] ?? null, 'approved_by' => $request->user()->id, 'approved_at' => now()]);
        });
        return back()->with('success', 'Solicitud aprobada.');
    }

    public function reject(Request $request, ProductionSupplyRequest $productionSupplyRequest): RedirectResponse
    {
        $this->authorizePermission($request, 'oncologicos_laboratory_edit');
        abort_unless($productionSupplyRequest->status === ProductionSupplyRequest::STATUS_REQUESTED, 422);
        $data = $request->validate(['resolution_notes' => ['required', 'string', 'max:2000']]);
        $productionSupplyRequest->update(['status' => ProductionSupplyRequest::STATUS_REJECTED, 'resolution_notes' => $data['resolution_notes'], 'approved_by' => $request->user()->id, 'approved_at' => now()]);
        return back()->with('success', 'Solicitud rechazada.');
    }

    public function supply(Request $request, ProductionSupplyRequest $productionSupplyRequest): RedirectResponse
    {
        $this->authorizePermission($request, 'oncologicos_laboratory_edit');
        abort_unless(in_array($productionSupplyRequest->status, [ProductionSupplyRequest::STATUS_APPROVED, ProductionSupplyRequest::STATUS_PARTIALLY_APPROVED], true), 422);
        $data = $request->validate(['lines' => ['required', 'array'], 'lines.*.supplied_quantity' => ['required', 'numeric', 'min:0']]);
        DB::transaction(function () use ($data, $request, $productionSupplyRequest) {
            foreach ($productionSupplyRequest->lines()->with(['supply', 'consumableLot'])->lockForUpdate()->get() as $line) {
                $quantity = (float) ($data['lines'][$line->id]['supplied_quantity'] ?? 0);
                $stockLot = $line->consumableLot ?: $line->supply;
                if (! $stockLot || $quantity > (float) $line->approved_quantity || $quantity > (float) $stockLot->stock_actual) throw ValidationException::withMessages(['lines' => 'No puedes surtir más de lo aprobado o disponible.']);
                $before = (float) $stockLot->stock_actual;
                $stockLot->update(['stock_actual' => $before - $quantity]);
                $line->update(['supplied_quantity' => $quantity]);
                if ($line->supply) { DB::table('diluent_stock_movements')->insert(['diluent_presentation_id' => $stockLot->id, 'laboratory_id' => $stockLot->laboratory_id, 'user_id' => $request->user()->id, 'movement_type' => 'production_supply_out', 'quantity' => $quantity, 'stock_actual_before' => $before, 'stock_actual_after' => $before - $quantity, 'reference_type' => ProductionSupplyRequest::class, 'reference_id' => $productionSupplyRequest->id, 'notes' => 'Salida a Producción · '.$productionSupplyRequest->folio, 'created_at' => now(), 'updated_at' => now()]); }
            }
            $productionSupplyRequest->update(['status' => ProductionSupplyRequest::STATUS_SUPPLIED, 'supplied_by' => $request->user()->id, 'supplied_at' => now()]);
        });
        return back()->with('success', 'Salida de inventario registrada. Pendiente de recepción por Producción.');
    }

    public function receive(Request $request, ProductionSupplyRequest $productionSupplyRequest): RedirectResponse
    {
        $this->authorizePermission($request, 'oncologicos_laboratory_edit');
        abort_unless($productionSupplyRequest->status === ProductionSupplyRequest::STATUS_SUPPLIED, 422);
        $data = $request->validate(['lines' => ['required', 'array'], 'lines.*.received_quantity' => ['required', 'numeric', 'min:0']]);
        DB::transaction(function () use ($data, $request, $productionSupplyRequest) {
            foreach ($productionSupplyRequest->lines()->get() as $line) {
                $quantity = (float) ($data['lines'][$line->id]['received_quantity'] ?? 0);
                if ($quantity > (float) $line->supplied_quantity) throw ValidationException::withMessages(['lines' => 'La recepción no puede superar lo surtido.']);
                $line->update(['received_quantity' => $quantity]);
            }
            $productionSupplyRequest->update(['status' => ProductionSupplyRequest::STATUS_RECEIVED, 'received_by' => $request->user()->id, 'received_at' => now()]);
        });
        return back()->with('success', 'Producción confirmó la recepción.');
    }

    private function supplies()
    {
        return ConsumableLot::query()->with('item')->where('is_active', true)->where('stock_actual', '>', 0)->orderBy('warehouse_id')->orderBy('presentation')->get();
    }

    private function authorizePermission(Request $request, string $permission): void
    {
        abort_unless(
            $request->user()->can($permission),
            403,
            'No tienes permiso para realizar esta acción en el almacén.'
        );
    }
}
