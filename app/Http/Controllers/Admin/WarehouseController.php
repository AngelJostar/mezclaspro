<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Oncologicos\DiluentPresentation;
use App\Models\Oncologicos\Laboratory;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WarehouseController extends Controller
{
    public function index(Request $request)
    {
        $laboratories = Laboratory::query()
            ->withCount([
                'hospitals',
                'warehouses',
                'warehouses as active_warehouses_count' => fn ($query) => $query->where('is_active', true),
            ])
            ->orderByDesc('activo')
            ->orderBy('nombre')
            ->get();

        $selectedLaboratory = $laboratories->firstWhere('id', $request->integer('laboratory_id'))
            ?? $laboratories->first();

        $warehouses = collect();
        $selectedWarehouse = null;
        $inventorySummary = $this->emptyInventorySummary();

        if ($selectedLaboratory) {
            $warehouses = $selectedLaboratory->warehouses()
                ->orderByDesc('is_active')
                ->orderBy('name')
                ->get();

            $selectedWarehouse = $warehouses->firstWhere('id', $request->integer('warehouse_id'))
                ?? $warehouses->first();

            $inventorySummary = $this->inventorySummary($selectedWarehouse);
        }

        return view('admin.warehouses.index', compact(
            'laboratories',
            'selectedLaboratory',
            'warehouses',
            'selectedWarehouse',
            'inventorySummary'
        ));
    }

    public function create(Request $request)
    {
        $laboratories = Laboratory::query()
            ->orderByDesc('activo')
            ->orderBy('nombre')
            ->get();

        $selectedLaboratoryId = $request->integer('laboratory_id') ?: $laboratories->first()?->id;

        return view('admin.warehouses.create', compact('laboratories', 'selectedLaboratoryId'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'laboratory_id' => ['required', 'integer', 'exists:laboratories,id'],
            'name' => ['required', 'string', 'max:255'],
            'state' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:500'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['is_active'] = $request->boolean('is_active');
        $warehouse = Warehouse::create($validated);

        return redirect()
            ->route('admin.warehouses.index', [
                'laboratory_id' => $warehouse->laboratory_id,
                'warehouse_id' => $warehouse->id,
            ])
            ->with('success', 'Almacen creado correctamente.');
    }

    public function edit(Warehouse $warehouse)
    {
        $laboratories = Laboratory::query()
            ->orderByDesc('activo')
            ->orderBy('nombre')
            ->get();

        return view('admin.warehouses.edit', compact('warehouse', 'laboratories'));
    }

    public function update(Request $request, Warehouse $warehouse)
    {
        $validated = $request->validate([
            'laboratory_id' => ['required', 'integer', 'exists:laboratories,id'],
            'name' => ['required', 'string', 'max:255'],
            'state' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:500'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['is_active'] = $request->boolean('is_active');
        $warehouse->update($validated);

        return redirect()
            ->route('admin.warehouses.index', [
                'laboratory_id' => $warehouse->laboratory_id,
                'warehouse_id' => $warehouse->id,
            ])
            ->with('success', 'La informacion del almacen se actualizo correctamente.');
    }

    public function purchaseOrders(Request $request)
    {
        $section = (string) $request->query('section', 'mine');
        $validSections = ['paid', 'pending', 'mine', 'rejected'];

        if (! in_array($section, $validSections, true)) {
            $section = 'mine';
        }

        $laboratories = Laboratory::query()
            ->withCount('warehouses')
            ->orderByDesc('activo')
            ->orderBy('nombre')
            ->get();

        $selectedLaboratory = $laboratories->firstWhere('id', $request->integer('laboratory_id'))
            ?? $laboratories->first();

        $purchaseOrders = collect();

        if ($selectedLaboratory) {
            $purchaseOrdersQuery = $selectedLaboratory->purchaseOrders()
                ->with([
                    'creator:id,name,lastname',
                    'laboratory:id,nombre',
                    'warehouse:id,name',
                ]);

            match ($section) {
                'paid' => $purchaseOrdersQuery->whereIn('status', ['pagada', 'pagado']),
                'pending' => $purchaseOrdersQuery->whereIn('status', ['pendiente_pago', 'pendiente de pago']),
                'mine' => $purchaseOrdersQuery->where('created_by', auth()->id()),
                'rejected' => $purchaseOrdersQuery->whereIn('status', ['rechazada', 'rechazado']),
            };

            $purchaseOrders = $purchaseOrdersQuery
                ->latest('requested_at')
                ->latest('id')
                ->get();
        }

        $sectionMeta = [
            'paid' => [
                'title' => 'Pagadas',
                'description' => 'Ordenes de compra cuyo pago ya fue registrado.',
                'empty' => 'No hay ordenes de compra pagadas para esta central de mezclas.',
            ],
            'pending' => [
                'title' => 'Pendientes de pago',
                'description' => 'Ordenes autorizadas que siguen pendientes de pago.',
                'empty' => 'No hay ordenes de compra pendientes de pago para esta central de mezclas.',
            ],
            'mine' => [
                'title' => 'Mis ordenes',
                'description' => 'Ordenes de compra creadas por tu usuario.',
                'empty' => 'Todavia no has creado ordenes de compra para esta central de mezclas.',
            ],
            'rejected' => [
                'title' => 'Rechazadas',
                'description' => 'Ordenes de compra que fueron rechazadas.',
                'empty' => 'No hay ordenes de compra rechazadas para esta central de mezclas.',
            ],
        ][$section];

        return view('admin.warehouses.purchase-orders', compact(
            'laboratories',
            'selectedLaboratory',
            'purchaseOrders',
            'section',
            'sectionMeta'
        ));
    }

    public function newPurchaseOrder(Request $request)
    {
        $requestedLaboratoryId = $request->integer('laboratory_id');
        $laboratory = Laboratory::query()
            ->when($requestedLaboratoryId, fn ($query) => $query->whereKey($requestedLaboratoryId))
            ->first();

        $laboratory ??= Laboratory::query()
            ->orderByDesc('activo')
            ->orderBy('nombre')
            ->first();

        if (! $laboratory) {
            return redirect()
                ->route('admin.warehouses.purchase-orders.index', ['section' => 'mine'])
                ->with('error', 'Registra una central de mezclas antes de crear una orden de compra.');
        }

        return redirect()->route('admin.oncologicos.laboratory.purchase-orders.create', $laboratory);
    }

    public function suppliesInventory(Request $request, Warehouse $warehouse)
    {
        $warehouse->load('laboratory');
        $search = trim((string) $request->query('search', ''));

        $supplies = DiluentPresentation::query()
            ->with('diluent:id,denominacion_generica')
            ->where('warehouse_id', $warehouse->id)
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($subquery) use ($search) {
                    $subquery->where('presentacion', 'like', '%' . $search . '%')
                        ->orWhere('denominacion_comercial', 'like', '%' . $search . '%')
                        ->orWhere('fabricante', 'like', '%' . $search . '%')
                        ->orWhere('lote', 'like', '%' . $search . '%')
                        ->orWhereHas('diluent', fn ($diluentQuery) => $diluentQuery
                            ->where('denominacion_generica', 'like', '%' . $search . '%'));
                });
            })
            ->orderByDesc('is_active')
            ->orderBy('caducidad')
            ->orderBy('id')
            ->paginate(40)
            ->withQueryString();

        $summary = DiluentPresentation::query()
            ->where('warehouse_id', $warehouse->id)
            ->where('is_active', true)
            ->selectRaw('COUNT(*) as batches_count')
            ->selectRaw('COALESCE(SUM(stock_actual), 0) as stock_total')
            ->first();

        return view('admin.warehouses.supplies', compact(
            'warehouse',
            'supplies',
            'summary',
            'search'
        ));
    }

    private function inventorySummary(?Warehouse $warehouse): array
    {
        if (! $warehouse) {
            return $this->emptyInventorySummary();
        }

        $medicineSummary = DB::table('medicine_batches as batches')
            ->join('medicine_presentations as presentations', 'presentations.id', '=', 'batches.medicine_presentation_id')
            ->join('medicines_catalog as catalog', 'catalog.id', '=', 'presentations.catalog_id')
            ->where('batches.warehouse_id', $warehouse->id)
            ->where('batches.is_active', 1)
            ->selectRaw("COALESCE(catalog.catalog_category, 'oncologicos') as category")
            ->selectRaw('COUNT(DISTINCT batches.id) as batches_count')
            ->selectRaw('COALESCE(SUM(batches.stock_actual), 0) as stock_total')
            ->groupBy('catalog.catalog_category')
            ->get()
            ->keyBy('category');

        $nutritionSummary = DB::table('medicine_laboratory_stocks')
            ->where('warehouse_id', $warehouse->id)
            ->selectRaw('COUNT(DISTINCT id) as batches_count')
            ->selectRaw('COALESCE(SUM(frascos_actuales), 0) as stock_total')
            ->first();

        $suppliesSummary = DB::table('diluent_presentations')
            ->where('warehouse_id', $warehouse->id)
            ->where('is_active', 1)
            ->selectRaw('COUNT(DISTINCT id) as batches_count')
            ->selectRaw('COALESCE(SUM(stock_actual), 0) as stock_total')
            ->first();

        return [
            'oncologicos' => $medicineSummary->get('oncologicos'),
            'antibioticos' => $medicineSummary->get('antibioticos'),
            'nutricionales' => $nutritionSummary,
            'insumos' => $suppliesSummary,
        ];
    }

    private function emptyInventorySummary(): array
    {
        return [
            'oncologicos' => null,
            'antibioticos' => null,
            'nutricionales' => null,
            'insumos' => null,
        ];
    }
}
