<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Oncologicos\Laboratory;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WarehouseController extends Controller
{
    public function index(Request $request)
    {
        $laboratories = Laboratory::query()
            ->withCount(['hospitals', 'warehouses'])
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

            $inventorySummary = $this->inventorySummary($selectedLaboratory);
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

    public function destroy(Warehouse $warehouse)
    {
        $laboratoryId = $warehouse->laboratory_id;
        $warehouseName = $warehouse->name;

        $warehouse->delete();

        return redirect()
            ->route('admin.warehouses.index', ['laboratory_id' => $laboratoryId])
            ->with('success', "El almacen {$warehouseName} se elimino correctamente.");
    }

    public function purchaseOrders(Request $request)
    {
        $laboratories = Laboratory::query()
            ->withCount('warehouses')
            ->orderByDesc('activo')
            ->orderBy('nombre')
            ->get();

        $selectedLaboratory = $laboratories->firstWhere('id', $request->integer('laboratory_id'))
            ?? $laboratories->first();

        $purchaseOrders = $selectedLaboratory
            ? $selectedLaboratory->purchaseOrders()->latest('requested_at')->latest('id')->get()
            : collect();

        return view('admin.warehouses.purchase-orders', compact(
            'laboratories',
            'selectedLaboratory',
            'purchaseOrders'
        ));
    }

    private function inventorySummary(Laboratory $laboratory): array
    {
        $medicineSummary = DB::table('medicine_batches as batches')
            ->join('medicine_presentations as presentations', 'presentations.id', '=', 'batches.medicine_presentation_id')
            ->join('medicines_catalog as catalog', 'catalog.id', '=', 'presentations.catalog_id')
            ->where('batches.laboratory_id', $laboratory->id)
            ->where('batches.is_active', 1)
            ->selectRaw("COALESCE(catalog.catalog_category, 'oncologicos') as category")
            ->selectRaw('COUNT(DISTINCT batches.id) as batches_count')
            ->selectRaw('COALESCE(SUM(batches.stock_actual), 0) as stock_total')
            ->groupBy('catalog.catalog_category')
            ->get()
            ->keyBy('category');

        $nutritionSummary = DB::table('medicine_laboratory_stocks')
            ->where('laboratory_id', $laboratory->id)
            ->selectRaw('COUNT(DISTINCT id) as batches_count')
            ->selectRaw('COALESCE(SUM(frascos_actuales), 0) as stock_total')
            ->first();

        return [
            'oncologicos' => $medicineSummary->get('oncologicos'),
            'antibioticos' => $medicineSummary->get('antibioticos'),
            'nutricionales' => $nutritionSummary,
        ];
    }

    private function emptyInventorySummary(): array
    {
        return [
            'oncologicos' => null,
            'antibioticos' => null,
            'nutricionales' => null,
        ];
    }
}
