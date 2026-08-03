<?php

namespace App\Http\Controllers\Admin\Nutricionales;

use App\Exports\Nutricionales\NutritionInventoryExport;
use App\Http\Controllers\Controller;
use App\Models\Nutricionales\MedicineLaboratoryStock;
use App\Models\Nutricionales\MedicineStockMovement;
use App\Models\Nutricionales\NutritionLaboratoryActivePresentation;
use App\Models\Nutricionales\NutritionMedicineCatalog;
use App\Models\Nutricionales\NutritionMedicinePresentation;
use App\Models\Oncologicos\Laboratory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class NutritionStockController extends Controller
{
    public function selectLaboratory()
    {
        $laboratories = Laboratory::where('activo', 1)
            ->orderBy('nombre')
            ->get();

        return view('admin.nutricionales.stocks.select-laboratory', compact('laboratories'));
    }

    public function index(Request $request)
    {
        $laboratoryId = $request->integer('laboratory_id');

        if (!$laboratoryId) {
            return redirect()->route('admin.nutricionales.stocks.selectLaboratory');
        }

        $laboratory = Laboratory::where('activo', 1)->findOrFail($laboratoryId);

        $q = trim((string) $request->get('q', ''));
        $stockFilter = $request->get('stock');
        $today = now()->toDateString();

        $catalogsQuery = NutritionMedicineCatalog::query()
            ->with([
                'presentations' => function ($query) use ($laboratoryId, $stockFilter) {
                    $query->where('is_available', 1)
                        ->with([
                            'stocks' => function ($stockQuery) use ($laboratoryId, $stockFilter) {
                                $stockQuery->where('laboratory_id', $laboratoryId);

                                if ($stockFilter === '1') {
                                    $stockQuery->where('stock_ml_actual', '>', 0);
                                } elseif ($stockFilter === '0') {
                                    $stockQuery->where('stock_ml_actual', '<=', 0);
                                }

                                $stockQuery->orderBy('caducidad')
                                    ->orderBy('lote');
                            },
                        ])
                        ->orderBy('denominacion_comercial');
                },
                'input',
                'category',
            ])
            ->where('is_active', 1)
            ->orderBy('denominacion_generica');

        if ($q !== '') {
            $catalogsQuery->where(function ($sub) use ($q, $laboratoryId) {
                $sub->where('denominacion_generica', 'like', "%{$q}%")
                    ->orWhereHas('input', function ($inputQuery) use ($q) {
                        $inputQuery->where('description', 'like', "%{$q}%");
                    })
                    ->orWhereHas('presentations', function ($presentationQuery) use ($q) {
                        $presentationQuery->where('is_available', 1)
                            ->where(function ($presentationSubQuery) use ($q) {
                                $presentationSubQuery
                                    ->where('denominacion_comercial', 'like', "%{$q}%")
                                    ->orWhere('presentacion', 'like', "%{$q}%")
                                    ->orWhere('fabricante', 'like', "%{$q}%");
                            });
                    })
                    ->orWhereHas('presentations.stocks', function ($stockQuery) use ($q, $laboratoryId) {
                        $stockQuery->where('laboratory_id', $laboratoryId)
                            ->where('lote', 'like', "%{$q}%");
                    });
            });
        }

        $catalogs = $catalogsQuery->get();

        /*
    |--------------------------------------------------------------------------
    | Totales por presentación
    |--------------------------------------------------------------------------
    | Calculamos manualmente el stock total por nutrition_medicine_presentation_id
    | para evitar que se mezclen lotes de distintas presentaciones.
    */
        $presentationIds = $catalogs
            ->flatMap(function ($catalog) {
                return $catalog->presentations->pluck('id');
            })
            ->unique()
            ->values();

        $totalesPorPresentacion = collect();

        if ($presentationIds->isNotEmpty()) {
            $totalesPorPresentacion = DB::table('medicine_laboratory_stocks')
                ->select(
                    'nutrition_medicine_presentation_id',
                    DB::raw('SUM(stock_ml_actual) as stock_total_ml'),
                    DB::raw('SUM(frascos_actuales) as frascos_total')
                )
                ->where('laboratory_id', $laboratoryId)
                ->whereIn('nutrition_medicine_presentation_id', $presentationIds)
                ->groupBy('nutrition_medicine_presentation_id')
                ->get()
                ->keyBy('nutrition_medicine_presentation_id');
        }

        foreach ($catalogs as $catalog) {
            foreach ($catalog->presentations as $presentation) {
                $totales = $totalesPorPresentacion->get($presentation->id);

                $presentation->stock_total_ml = $totales
                    ? (float) $totales->stock_total_ml
                    : 0;

                $presentation->frascos_total = $totales
                    ? (float) $totales->frascos_total
                    : 0;
            }
        }

        if ($stockFilter === '1') {
            $catalogs = $catalogs->filter(function ($catalog) {
                return $catalog->presentations->contains(function ($presentation) {
                    return (float) ($presentation->stock_total_ml ?? 0) > 0;
                });
            })->values();
        } elseif ($stockFilter === '0') {
            $catalogs = $catalogs->filter(function ($catalog) {
                return $catalog->presentations->contains(function ($presentation) {
                    return (float) ($presentation->stock_total_ml ?? 0) <= 0;
                });
            })->values();
        }

        $activeSelections = NutritionLaboratoryActivePresentation::where('laboratory_id', $laboratoryId)
            ->whereDate('selected_date', $today)
            ->get()
            ->keyBy('nutrition_medicine_catalog_id');

        return view('admin.nutricionales.stocks.index', compact(
            'catalogs',
            'laboratory',
            'laboratoryId',
            'activeSelections',
            'q',
            'stockFilter'
        ));
    }

    public function saveActivePresentations(Request $request)
    {
        $request->validate([
            'laboratory_id' => 'required|exists:laboratories,id',
            'active_presentations' => 'nullable|array',
        ]);

        $laboratoryId = (int) $request->laboratory_id;
        $today = now()->toDateString();
        $activePresentations = $request->input('active_presentations', []);

        DB::beginTransaction();

        try {
            NutritionLaboratoryActivePresentation::where('laboratory_id', $laboratoryId)
                ->whereDate('selected_date', $today)
                ->delete();

            foreach ($activePresentations as $catalogId => $presentationId) {
                if (!$presentationId) {
                    continue;
                }

                $presentation = NutritionMedicinePresentation::where('id', $presentationId)
                    ->where('is_available', 1)
                    ->first();

                if (!$presentation) {
                    continue;
                }

                if ((int) $presentation->nutrition_medicine_catalog_id !== (int) $catalogId) {
                    continue;
                }

                $hasStock = MedicineLaboratoryStock::where('laboratory_id', $laboratoryId)
                    ->where('nutrition_medicine_presentation_id', $presentationId)
                    ->where('is_active', 1)
                    ->where('stock_ml_actual', '>', 0)
                    ->exists();

                if (!$hasStock) {
                    throw new \Exception("La presentación {$presentation->denominacion_comercial} no tiene stock disponible para activarse.");
                }

                NutritionLaboratoryActivePresentation::create([
                    'laboratory_id' => $laboratoryId,
                    'nutrition_medicine_catalog_id' => $catalogId,
                    'nutrition_medicine_presentation_id' => $presentationId,
                    'selected_date' => $today,
                ]);
            }

            DB::commit();

            session()->flash('swal', [
                'title' => '¡Bien hecho!',
                'text' => 'Las presentaciones activas del día se guardaron correctamente.',
                'icon' => 'success',
            ]);

            return redirect()->route('admin.nutricionales.stocks.index', [
                'laboratory_id' => $laboratoryId,
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();

            return redirect()->back()
                ->withErrors(['error' => $e->getMessage()])
                ->withInput();
        }
    }

    public function ingresoForm(Request $request)
    {
        $laboratoryId = $request->integer('laboratory_id');

        if (!$laboratoryId) {
            return redirect()->route('admin.nutricionales.stocks.selectLaboratory');
        }

        $laboratory = Laboratory::where('activo', 1)->findOrFail($laboratoryId);

        $catalogs = NutritionMedicineCatalog::with([
            'presentations' => function ($query) use ($laboratoryId) {
                $query->where('is_available', 1)
                    ->with([
                        'stocks' => function ($stockQuery) use ($laboratoryId) {
                            $stockQuery->where('laboratory_id', $laboratoryId)
                                ->orderBy('caducidad')
                                ->orderBy('lote');
                        },
                    ])
                    ->orderBy('denominacion_comercial');
            },
            'input',
            'category',
        ])
            ->where('is_active', 1)
            ->orderBy('denominacion_generica')
            ->get();

        return view('admin.nutricionales.stocks.ingreso', compact(
            'laboratory',
            'laboratoryId',
            'catalogs'
        ));
    }

    private function validarLoteNoUsadoEnOtraPresentacion(
        int $laboratoryId,
        string $lote,
        int $presentationId,
        ?int $stockIdIgnorar = null
    ): void {
        $query = MedicineLaboratoryStock::with('presentation.catalog')
            ->where('laboratory_id', $laboratoryId)
            ->where('lote', trim($lote))
            ->where('nutrition_medicine_presentation_id', '!=', $presentationId);

        if ($stockIdIgnorar) {
            $query->where('id', '!=', $stockIdIgnorar);
        }

        $stockExistente = $query->first();

        if ($stockExistente) {
            $presentacion = $stockExistente->presentation?->denominacion_comercial ?? 'otra presentación';
            $generico = $stockExistente->presentation?->catalog?->denominacion_generica ?? 'otro medicamento';

            throw new \Exception(
                "El lote {$lote} ya está registrado en {$generico} / {$presentacion}. No se permite usar el mismo lote en presentaciones diferentes."
            );
        }
    }

    public function registrarIngreso(Request $request)
    {
        $request->validate([
            'laboratory_id' => 'required|exists:laboratories,id',
            'nutrition_medicine_presentation_id' => 'required|exists:nutrition_medicine_presentations,id',
            'lote' => 'required|string|max:255',
            'caducidad' => 'required|date',
            'fecha_ingreso' => 'nullable|date',
            'numero_factura' => 'nullable|string|max:255',
            'frascos_ingresados' => 'required|numeric|min:0.01',
            'notes' => 'nullable|string|max:500',
        ]);

        DB::beginTransaction();

        try {
            $laboratoryId = (int) $request->laboratory_id;
            $frascosIngresados = (float) $request->frascos_ingresados;
            $fechaIngreso = $request->fecha_ingreso ?: now()->toDateString();
            $lote = trim($request->lote);

            $presentation = NutritionMedicinePresentation::where('id', $request->nutrition_medicine_presentation_id)
                ->where('is_available', 1)
                ->firstOrFail();

            if (!$presentation->presentacion_ml || (float) $presentation->presentacion_ml <= 0) {
                throw new \Exception('La presentación seleccionada no tiene configurado el campo presentacion_ml.');
            }

            $this->validarLoteNoUsadoEnOtraPresentacion(
                $laboratoryId,
                $lote,
                (int) $presentation->id
            );

            $stock = MedicineLaboratoryStock::where('laboratory_id', $laboratoryId)
                ->where('nutrition_medicine_presentation_id', $presentation->id)
                ->where('lote', $lote)
                ->lockForUpdate()
                ->first();

            $mlPorFrasco = (float) $presentation->presentacion_ml;
            $mlIngresados = $frascosIngresados * $mlPorFrasco;

            if ($stock) {
                $stockMlAntes = (float) $stock->stock_ml_actual;
                $frascosAntes = (float) $stock->frascos_actuales;

                $stock->update([
                    'frascos_iniciales' => (float) $stock->frascos_iniciales + $frascosIngresados,
                    'frascos_actuales' => (float) $stock->frascos_actuales + $frascosIngresados,
                    'stock_ml_inicial' => (float) $stock->stock_ml_inicial + $mlIngresados,
                    'stock_ml_actual' => (float) $stock->stock_ml_actual + $mlIngresados,
                    'caducidad' => $request->caducidad,
                    'fecha_ingreso' => $fechaIngreso,
                    'numero_factura' => $request->numero_factura,
                    'is_active' => true,
                ]);
            } else {
                $stockMlAntes = 0;
                $frascosAntes = 0;

                $stock = MedicineLaboratoryStock::create([
                    'laboratory_id' => $laboratoryId,
                    'nutrition_medicine_presentation_id' => $presentation->id,
                    'frascos_iniciales' => $frascosIngresados,
                    'frascos_actuales' => $frascosIngresados,
                    'stock_ml_inicial' => $mlIngresados,
                    'stock_ml_actual' => $mlIngresados,
                    'lote' => $lote,
                    'caducidad' => $request->caducidad,
                    'fecha_ingreso' => $fechaIngreso,
                    'numero_factura' => $request->numero_factura,
                    'is_active' => true,
                ]);
            }

            MedicineStockMovement::create([
                'medicine_laboratory_stock_id' => $stock->id,
                'user_id' => auth()->id(),
                'tipo' => 'entrada',
                'cantidad_ml' => $mlIngresados,
                'cantidad_frascos' => $frascosIngresados,
                'stock_antes' => $stockMlAntes,
                'stock_despues' => $stockMlAntes + $mlIngresados,
                'frascos_antes' => $frascosAntes,
                'frascos_despues' => $frascosAntes + $frascosIngresados,
                'reference_type' => 'IngresoInventarioNutricional',
                'reference_id' => $stock->id,
                'notes' => $request->notes ?: 'Ingreso de inventario nutricional',
            ]);

            DB::commit();

            session()->flash('swal', [
                'title' => 'Ingreso registrado',
                'text' => $stockMlAntes > 0
                    ? 'El lote ya existía para esta presentación. Se sumó al inventario existente.'
                    : 'El ingreso de inventario se registró correctamente.',
                'icon' => 'success',
            ]);

            return redirect()->route('admin.nutricionales.stocks.index', [
                'laboratory_id' => $laboratoryId,
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();

            return redirect()->back()
                ->withErrors(['error' => $e->getMessage()])
                ->withInput();
        }
    }



    public function edit(MedicineLaboratoryStock $stock)
    {
        if (!auth()->user()->hasRole('Super Admin')) {
            abort(403, 'No tienes permiso para editar el inventario.');
        }

        $stock->load([
            'presentation.catalog',
            'laboratory',
        ]);

        return view('admin.nutricionales.stocks.edit', compact('stock'));
    }

    public function update(Request $request, MedicineLaboratoryStock $stock)
    {
        if (!auth()->user()->hasRole('Super Admin')) {
            abort(403, 'No tienes permiso para editar el inventario.');
        }

        $request->validate([
            'lote' => 'required|string|max:255',
            'caducidad' => 'required|date',
            'fecha_ingreso' => 'nullable|date',
            'numero_factura' => 'nullable|string|max:255',
            'frascos_iniciales' => 'required|numeric|min:0',
            'frascos_actuales' => 'required|numeric|min:0',
            'is_active' => 'required|boolean',
            'notes' => 'nullable|string|max:500',
        ]);

        DB::beginTransaction();

        try {
            $stock->load('presentation');

            $presentacionMl = (float) ($stock->presentation->presentacion_ml ?? 0);

            if ($presentacionMl <= 0) {
                throw new \Exception('La presentación no tiene definido el campo presentacion_ml.');
            }

            $frascosIniciales = (float) $request->frascos_iniciales;
            $frascosActuales = (float) $request->frascos_actuales;

            if ($frascosActuales > $frascosIniciales) {
                throw new \Exception('Los frascos actuales no pueden ser mayores a los frascos iniciales.');
            }

            $stockAntes = (float) $stock->stock_ml_actual;
            $frascosAntes = (float) $stock->frascos_actuales;

            // 🔥 recalcular ml
            $stockMlInicial = $frascosIniciales * $presentacionMl;
            $stockMlActual = $frascosActuales * $presentacionMl;

            $this->validarLoteNoUsadoEnOtraPresentacion(
                (int) $stock->laboratory_id,
                trim($request->lote),
                (int) $stock->nutrition_medicine_presentation_id,
                (int) $stock->id
            );

            // 🔥 actualizar stock
            $stock->update([
                'lote' => trim($request->lote),
                'caducidad' => $request->caducidad,
                'fecha_ingreso' => $request->fecha_ingreso,
                'numero_factura' => $request->numero_factura,
                'frascos_iniciales' => $frascosIniciales,
                'frascos_actuales' => $frascosActuales,
                'stock_ml_inicial' => $stockMlInicial,
                'stock_ml_actual' => $stockMlActual,
                'is_active' => (bool) $request->is_active,
            ]);

            // 🔥 registrar movimiento si hubo cambio
            if ($stockAntes != $stockMlActual || $frascosAntes != $frascosActuales) {

                $tipo = $stockMlActual > $stockAntes ? 'entrada' : 'ajuste';

                MedicineStockMovement::create([
                    'medicine_laboratory_stock_id' => $stock->id,
                    'user_id' => auth()->id(),
                    'tipo' => $tipo,
                    'cantidad_ml' => abs($stockMlActual - $stockAntes),
                    'cantidad_frascos' => abs($frascosActuales - $frascosAntes),
                    'stock_antes' => $stockAntes,
                    'stock_despues' => $stockMlActual,
                    'frascos_antes' => $frascosAntes,
                    'frascos_despues' => $frascosActuales,
                    'reference_type' => 'EditManualStock',
                    'reference_id' => $stock->id,
                    'notes' => $request->notes ?: 'Edición manual de inventario por Super Admin',
                ]);
            }

            DB::commit();

            session()->flash('swal', [
                'title' => 'Actualizado',
                'text' => 'El lote se actualizó correctamente.',
                'icon' => 'success',
            ]);

            return redirect()->route('admin.nutricionales.stocks.index', [
                'laboratory_id' => $stock->laboratory_id,
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();

            return redirect()->back()
                ->withErrors(['error' => $e->getMessage()])
                ->withInput();
        }
    }

    public function bulkUpdate(Request $request)
    {
        if (!auth()->user()->hasRole('Super Admin')) {
            abort(403, 'No tienes permiso para editar directamente el inventario.');
        }

        $request->validate([
            'laboratory_id' => 'required|exists:laboratories,id',
            'items' => 'required|string',
        ]);

        $laboratoryId = (int) $request->laboratory_id;
        $items = json_decode($request->items, true);

        if (!is_array($items)) {
            return redirect()->back()
                ->withErrors(['error' => 'El formato de los cambios es inválido.'])
                ->withInput();
        }

        DB::beginTransaction();

        try {
            foreach ($items as $item) {
                $presentationId = !empty($item['nutrition_medicine_presentation_id'])
                    ? (int) $item['nutrition_medicine_presentation_id']
                    : null;

                $stockId = !empty($item['stock_id']) ? (int) $item['stock_id'] : null;

                if (!$presentationId) {
                    continue;
                }

                $lote = trim((string) ($item['lote'] ?? ''));
                $caducidad = !empty($item['caducidad']) ? $item['caducidad'] : null;
                $fechaIngreso = !empty($item['fecha_ingreso']) ? $item['fecha_ingreso'] : null;

                $frascosIniciales = isset($item['frascos_iniciales'])
                    ? (float) $item['frascos_iniciales']
                    : 0;

                $frascosActuales = isset($item['frascos_actuales'])
                    ? (float) $item['frascos_actuales']
                    : 0;

                $isActive = isset($item['is_active'])
                    ? (bool) $item['is_active']
                    : true;

                if ($frascosIniciales < 0 || $frascosActuales < 0) {
                    throw new \Exception('Los frascos no pueden ser negativos.');
                }

                if ($frascosActuales > $frascosIniciales) {
                    throw new \Exception("En el lote {$lote}, los frascos actuales no pueden ser mayores a los iniciales.");
                }

                if ($lote === '') {
                    throw new \Exception('Todos los registros deben tener lote.');
                }

                if (!$caducidad) {
                    throw new \Exception("El lote {$lote} debe tener fecha de caducidad.");
                }

                $presentation = NutritionMedicinePresentation::find($presentationId);

                if (!$presentation) {
                    throw new \Exception("La presentación {$presentationId} no existe.");
                }

                $presentacionMl = (float) ($presentation->presentacion_ml ?? 0);

                if ($presentacionMl <= 0) {
                    throw new \Exception("La presentación {$presentationId} no tiene configurado el campo presentacion_ml.");
                }

                $this->validarLoteNoUsadoEnOtraPresentacion(
                    $laboratoryId,
                    $lote,
                    $presentationId,
                    $stockId
                );

                $stockMlInicial = $frascosIniciales * $presentacionMl;
                $stockMlActual = $frascosActuales * $presentacionMl;

                $stock = null;

                if ($stockId) {
                    $stock = MedicineLaboratoryStock::where('id', $stockId)
                        ->where('laboratory_id', $laboratoryId)
                        ->lockForUpdate()
                        ->first();
                }

                if (!$stock) {
                    $stock = MedicineLaboratoryStock::where('laboratory_id', $laboratoryId)
                        ->where('nutrition_medicine_presentation_id', $presentationId)
                        ->where('lote', $lote)
                        ->lockForUpdate()
                        ->first();
                }

                if (!$stock) {
                    $stock = MedicineLaboratoryStock::create([
                        'laboratory_id' => $laboratoryId,
                        'nutrition_medicine_presentation_id' => $presentationId,
                        'frascos_iniciales' => $frascosIniciales,
                        'frascos_actuales' => $frascosActuales,
                        'stock_ml_inicial' => $stockMlInicial,
                        'stock_ml_actual' => $stockMlActual,
                        'lote' => $lote,
                        'caducidad' => $caducidad,
                        'fecha_ingreso' => $fechaIngreso,
                        'numero_factura' => $request->numero_factura,
                        'is_active' => $isActive,
                    ]);

                    MedicineStockMovement::create([
                        'medicine_laboratory_stock_id' => $stock->id,
                        'user_id' => auth()->id(),
                        'tipo' => 'entrada',
                        'cantidad_ml' => $stockMlActual,
                        'cantidad_frascos' => $frascosActuales,
                        'stock_antes' => 0,
                        'stock_despues' => $stockMlActual,
                        'frascos_antes' => 0,
                        'frascos_despues' => $frascosActuales,
                        'reference_type' => 'StockInicialMasivo',
                        'reference_id' => $stock->id,
                        'notes' => 'Alta inicial desde pantalla masiva de stock nutricional por Super Admin',
                    ]);

                    continue;
                }

                $stockAntes = (float) $stock->stock_ml_actual;
                $frascosAntes = (float) $stock->frascos_actuales;

                $stock->update([
                    'frascos_iniciales' => $frascosIniciales,
                    'frascos_actuales' => $frascosActuales,
                    'stock_ml_inicial' => $stockMlInicial,
                    'stock_ml_actual' => $stockMlActual,
                    'lote' => $lote,
                    'caducidad' => $caducidad,
                    'fecha_ingreso' => $fechaIngreso,
                    'numero_factura' => $request->numero_factura,
                    'is_active' => $isActive,
                ]);

                if ($stockAntes != $stockMlActual || $frascosAntes != $frascosActuales) {
                    $tipo = $stockMlActual > $stockAntes ? 'entrada' : 'ajuste';

                    MedicineStockMovement::create([
                        'medicine_laboratory_stock_id' => $stock->id,
                        'user_id' => auth()->id(),
                        'tipo' => $tipo,
                        'cantidad_ml' => abs($stockMlActual - $stockAntes),
                        'cantidad_frascos' => abs($frascosActuales - $frascosAntes),
                        'stock_antes' => $stockAntes,
                        'stock_despues' => $stockMlActual,
                        'frascos_antes' => $frascosAntes,
                        'frascos_despues' => $frascosActuales,
                        'reference_type' => 'BulkUpdateSuperAdmin',
                        'reference_id' => $stock->id,
                        'notes' => 'Actualización directa de stock nutricional por Super Admin',
                    ]);
                }
            }

            DB::commit();

            session()->flash('swal', [
                'title' => '¡Bien hecho!',
                'text' => 'Los cambios de stock se guardaron con éxito.',
                'icon' => 'success',
            ]);

            return redirect()->route('admin.nutricionales.stocks.index', [
                'laboratory_id' => $laboratoryId,
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();

            return redirect()->back()
                ->withErrors(['error' => $e->getMessage()])
                ->withInput();
        }
    }

    public function mermaForm(MedicineLaboratoryStock $stock)
    {
        $stock->load(['presentation.catalog', 'laboratory']);

        return view('admin.nutricionales.stocks.merma', compact('stock'));
    }

    public function registrarMerma(Request $request, MedicineLaboratoryStock $stock)
    {
        $request->validate([
            'cantidad_ml' => 'required|numeric|min:0.01',
            'notes' => 'required|string|max:500',
        ]);

        DB::beginTransaction();

        try {
            $stock->load('presentation');

            $cantidadMl = (float) $request->cantidad_ml;
            $stockAntes = (float) $stock->stock_ml_actual;
            $frascosAntes = (float) $stock->frascos_actuales;

            if ($cantidadMl > $stockAntes) {
                throw new \Exception("La merma no puede ser mayor al stock actual ({$stockAntes} ml).");
            }

            $presentacionMl = (float) ($stock->presentation->presentacion_ml ?? 0);

            if ($presentacionMl <= 0) {
                throw new \Exception('La presentación no tiene configurado el campo presentacion_ml.');
            }

            $frascosMerma = $cantidadMl / $presentacionMl;

            $stockDespues = $stockAntes - $cantidadMl;
            $frascosDespues = max(0, $frascosAntes - $frascosMerma);

            $stock->update([
                'stock_ml_actual' => $stockDespues,
                'frascos_actuales' => $frascosDespues,
                'is_active' => $stockDespues > 0,
            ]);

            MedicineStockMovement::create([
                'medicine_laboratory_stock_id' => $stock->id,
                'user_id' => auth()->id(),
                'tipo' => 'merma',
                'cantidad_ml' => $cantidadMl,
                'cantidad_frascos' => $frascosMerma,
                'stock_antes' => $stockAntes,
                'stock_despues' => $stockDespues,
                'frascos_antes' => $frascosAntes,
                'frascos_despues' => $frascosDespues,
                'reference_type' => 'MermaManual',
                'reference_id' => $stock->id,
                'notes' => $request->notes,
            ]);

            DB::commit();

            session()->flash('swal', [
                'title' => 'Merma registrada',
                'text' => 'La merma se registró correctamente.',
                'icon' => 'success',
            ]);

            return redirect()->route('admin.nutricionales.stocks.index', [
                'laboratory_id' => $stock->laboratory_id,
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();

            return redirect()->back()
                ->withErrors(['error' => $e->getMessage()])
                ->withInput();
        }
    }

    public function movimientos(MedicineLaboratoryStock $stock)
    {
        $stock->load(['presentation.catalog', 'laboratory']);

        $movements = MedicineStockMovement::where('medicine_laboratory_stock_id', $stock->id)
            ->with('user')
            ->latest()
            ->paginate(20);

        return view('admin.nutricionales.stocks.movimientos', compact('stock', 'movements'));
    }

    public function exportarExcel(Request $request)
    {
        $laboratoryId = $request->integer('laboratory_id');

        if (!$laboratoryId) {
            return redirect()
                ->route('admin.nutricionales.stocks.selectLaboratory')
                ->withErrors(['error' => 'Debes seleccionar un laboratorio para exportar el inventario.']);
        }

        $laboratory = Laboratory::where('activo', 1)->find($laboratoryId);

        if (!$laboratory) {
            return back()->withErrors(['error' => 'Laboratorio invalido.']);
        }

        $fileName = 'inventario_nutricional_' . str_replace(' ', '_', strtolower($laboratory->nombre)) . '.xlsx';

        return Excel::download(
            new NutritionInventoryExport(
                $laboratoryId,
                (string) $request->get('q', ''),
                (string) $request->get('stock', '')
            ),
            $fileName
        );
    }
}
