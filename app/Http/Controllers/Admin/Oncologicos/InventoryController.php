<?php

namespace App\Http\Controllers\Admin\Oncologicos;

use App\Exports\Oncologicos\OncologicosInventoryExport;
use App\Http\Controllers\Controller;
use App\Models\Oncologicos\Laboratory;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class InventoryController extends Controller
{

    public function selectLaboratory(Request $request)
    {
        $laboratories = Laboratory::query()
            ->where('activo', true)
            ->orderBy('nombre')
            ->get(['id', 'nombre', 'estado']);

        return view('admin.oncologicos.inventory.select-laboratory', [
            'laboratories' => $laboratories,
        ]);
    }


    public function setLaboratory(Request $request)
    {
        $data = $request->validate([
            'laboratory_id' => 'required|integer|exists:laboratories,id',
        ]);

        return redirect()->route('admin.oncologicos.inventory.index', [
            'laboratory_id' => $data['laboratory_id'],
        ]);
    }

    private function validarLoteNoUsadoEnOtraPresentacion(
        int $laboratoryId,
        string $lote,
        int $presentationId
    ): void {
        $batch = DB::table('medicine_batches as mb')
            ->join('medicine_presentations as mp', 'mp.id', '=', 'mb.medicine_presentation_id')
            ->join('medicines_catalog as mc', 'mc.id', '=', 'mp.catalog_id')
            ->where('mb.laboratory_id', $laboratoryId)
            ->where('mb.lote', trim($lote))
            ->where('mb.medicine_presentation_id', '!=', $presentationId)
            ->select(
                'mb.lote',
                'mp.presentacion',
                'mp.marca',
                'mc.denominacion'
            )
            ->first();

        if ($batch) {
            throw new \Exception(
                "El lote {$lote} ya está registrado en otro medicamento/presentación: {$batch->denominacion} - {$batch->presentacion} {$batch->marca}."
            );
        }
    }

    public function ingresoForm(Request $request)
    {
        $laboratoryId = (int) $request->get('laboratory_id');

        if ($laboratoryId <= 0) {
            return redirect()->route('admin.oncologicos.inventory.selectLaboratory');
        }

        $laboratory = Laboratory::where('activo', 1)->findOrFail($laboratoryId);

        $catalogs = DB::table('medicines_catalog as mc')
            ->join('medicine_presentations as mp', 'mp.catalog_id', '=', 'mc.id')
            ->leftJoin('medicine_batches as mb', function ($join) use ($laboratoryId) {
                $join->on('mb.medicine_presentation_id', '=', 'mp.id')
                    ->where('mb.laboratory_id', '=', $laboratoryId)
                    ->where('mb.is_active', '=', 1);
            })
            ->select([
                'mc.id as catalog_id',
                'mc.denominacion',
                'mp.id as presentation_id',
                'mp.presentacion',
                'mp.contenido_valor',
                'mp.contenido_unidad',
                'mp.marca',
                'mp.fabricante',
                'mp.precio_frasco',
                'mb.id as batch_id',
                'mb.lote',
                'mb.caducidad',
                'mb.stock_actual',
            ])
            ->orderBy('mc.denominacion')
            ->orderBy('mp.presentacion')
            ->orderBy('mb.caducidad')
            ->get()
            ->groupBy('catalog_id');

        return view('admin.oncologicos.inventory.ingreso', compact(
            'laboratory',
            'laboratoryId',
            'catalogs'
        ));
    }
    public function registrarIngreso(Request $request)
    {
        $request->validate([
            'laboratory_id' => 'required|exists:laboratories,id',
            'medicine_presentation_id' => 'required|exists:medicine_presentations,id',
            'lote' => 'required|string|max:255',
            'caducidad' => 'required|date',
            'fecha_ingreso' => 'nullable|date',
            'frascos_ingresados' => 'required|numeric|min:1',
            'notes' => 'nullable|string|max:500',
        ]);

        DB::beginTransaction();

        try {
            $laboratoryId = (int) $request->laboratory_id;
            $presentationId = (int) $request->medicine_presentation_id;
            $lote = trim($request->lote);
            $frascosIngresados = (float) $request->frascos_ingresados;
            $fechaIngreso = $request->fecha_ingreso ?: now()->toDateString();

            $this->validarLoteNoUsadoEnOtraPresentacion(
                $laboratoryId,
                $lote,
                $presentationId
            );

            $batch = DB::table('medicine_batches')
                ->where('laboratory_id', $laboratoryId)
                ->where('medicine_presentation_id', $presentationId)
                ->where('lote', $lote)
                ->lockForUpdate()
                ->first();

            if ($batch) {
                $stockAntes = (float) $batch->stock_actual;
                $stockDespues = $stockAntes + $frascosIngresados;

                DB::table('medicine_batches')
                    ->where('id', $batch->id)
                    ->update([
                        'stock_inicial' => (float) $batch->stock_inicial + $frascosIngresados,
                        'stock_actual' => $stockDespues,
                        'caducidad' => $request->caducidad,
                        'fecha_ingreso' => $fechaIngreso,
                        'is_active' => 1,
                        'updated_at' => now(),
                    ]);

                $batchId = $batch->id;
                $mensaje = 'El lote ya existía para esta presentación. Se sumó al inventario existente.';
            } else {
                $stockAntes = 0;
                $stockDespues = $frascosIngresados;

                $batchId = DB::table('medicine_batches')->insertGetId([
                    'laboratory_id' => $laboratoryId,
                    'medicine_presentation_id' => $presentationId,
                    'lote' => $lote,
                    'caducidad' => $request->caducidad,
                    'fecha_ingreso' => $fechaIngreso,
                    'stock_inicial' => $frascosIngresados,
                    'stock_actual' => $frascosIngresados,
                    'stock_reservado' => 0,
                    'costo_unitario' => null,
                    'is_current' => 1,
                    'is_active' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $mensaje = 'Lote creado correctamente.';
            }

            $this->insertMovement([
                'medicine_batch_id' => $batchId,
                'laboratory_id' => $laboratoryId,
                'user_id' => Auth::id(),
                'movement_type' => 'entrada',
                'quantity' => $frascosIngresados,
                'stock_actual_before' => $stockAntes,
                'stock_actual_after' => $stockDespues,
                'stock_reservado_before' => 0,
                'stock_reservado_after' => 0,
                'reference_type' => 'IngresoInventarioOncologico',
                'reference_id' => $batchId,
                'notes' => $request->notes ?: 'Ingreso de inventario oncológico',
            ]);

            DB::commit();

            session()->flash('swal', [
                'title' => 'Ingreso registrado',
                'text' => $mensaje,
                'icon' => 'success',
            ]);

            return redirect()->route('admin.oncologicos.inventory.index', [
                'laboratory_id' => $laboratoryId,
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();

            return redirect()->back()
                ->withErrors(['error' => $e->getMessage()])
                ->withInput();
        }
    }

    public function index(Request $request)
    {
        $stock = (string) $request->get('stock', '');
        $q = trim((string) $request->get('q', ''));
        $category = in_array($request->get('category'), ['oncologicos', 'antibioticos'], true)
            ? (string) $request->get('category')
            : '';
        $laboratoryId = (int) $request->get('laboratory_id');

        if ($laboratoryId <= 0) {
            return redirect()->route('admin.oncologicos.inventory.selectLaboratory');
        }

        $lab = Laboratory::query()
            ->where('id', $laboratoryId)
            ->first();

        if (!$lab) {
            return redirect()
                ->route('admin.oncologicos.inventory.selectLaboratory')
                ->withErrors(['error' => 'Laboratorio inválido.']);
        }

        $rows = DB::table('medicines_catalog as mc')
            ->join('medicine_presentations as mp', 'mp.catalog_id', '=', 'mc.id')
            ->leftJoin('medicine_batches as mb', function ($join) use ($laboratoryId) {
                $join->on('mb.medicine_presentation_id', '=', 'mp.id')
                    ->where('mb.laboratory_id', '=', $laboratoryId)
                    ->where('mb.is_active', '=', 1);
            })
            ->when($category !== '', fn($query) => $query->where('mc.catalog_category', $category))
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($w) use ($q) {
                    $w->where('mc.denominacion', 'like', "%{$q}%")
                        ->orWhere('mp.presentacion', 'like', "%{$q}%")
                        ->orWhere('mp.marca', 'like', "%{$q}%")
                        ->orWhere('mb.lote', 'like', "%{$q}%");
                });
            })
            ->when($stock !== '', function ($query) use ($stock) {
                if ($stock === '1') {
                    $query->whereNotNull('mb.id')
                        ->where('mb.stock_actual', '>', 0);
                } elseif ($stock === '0') {
                    $query->where(function ($w) {
                        $w->whereNull('mb.id')
                            ->orWhere('mb.stock_actual', '<=', 0);
                    });
                }
            })
            ->select([
                'mc.id as catalog_id',
                'mc.denominacion',
                'mc.state',
                'mc.requires_infusor',
                'mc.conc_min',
                'mc.conc_max',

                'mp.id as presentation_id',
                'mp.presentacion',
                'mp.contenido_valor',
                'mp.contenido_unidad',
                'mp.marca',
                'mp.fabricante',
                'mp.volumen_diluyente',
                'mp.precio_frasco',
                'mp.legend',
                'mp.temp_min_c',
                'mp.temp_max_c',
                'mp.stability_hours',
                'mp.is_available',

                'mb.id as batch_id',
                'mb.lote',
                'mb.caducidad',
                'mb.fecha_ingreso',
                'mb.stock_inicial',
                'mb.stock_actual',
                'mb.stock_reservado',
                'mb.is_active',
            ])
            ->orderBy('mc.denominacion')
            ->orderBy('mp.presentacion')
            ->orderByRaw('CASE WHEN mb.caducidad IS NULL THEN 1 ELSE 0 END')
            ->orderBy('mb.caducidad')
            ->orderBy('mb.id')
            ->get();

        $groupedRows = $rows
            ->groupBy('catalog_id')
            ->map(function ($catalogRows) {
                $firstCatalog = $catalogRows->first();

                return [
                    'catalog_id' => $firstCatalog->catalog_id,
                    'denominacion' => $firstCatalog->denominacion,
                    'state' => $firstCatalog->state,
                    'requires_infusor' => $firstCatalog->requires_infusor,
                    'conc_min' => $firstCatalog->conc_min,
                    'conc_max' => $firstCatalog->conc_max,

                    'presentations' => $catalogRows
                        ->groupBy('presentation_id')
                        ->map(function ($presentationRows) {
                            $firstPresentation = $presentationRows->first();

                            $batches = $presentationRows
                                ->filter(fn($row) => !is_null($row->batch_id))
                                ->values();

                            return [
                                'presentation_id' => $firstPresentation->presentation_id,
                                'presentacion' => $firstPresentation->presentacion,
                                'contenido_valor' => $firstPresentation->contenido_valor,
                                'contenido_unidad' => $firstPresentation->contenido_unidad,
                                'marca' => $firstPresentation->marca,
                                'fabricante' => $firstPresentation->fabricante ?? null,
                                'volumen_diluyente' => $firstPresentation->volumen_diluyente,
                                'precio_frasco' => $firstPresentation->precio_frasco,
                                'legend' => $firstPresentation->legend,
                                'temp_min_c' => $firstPresentation->temp_min_c,
                                'temp_max_c' => $firstPresentation->temp_max_c,
                                'stability_hours' => $firstPresentation->stability_hours,
                                'is_available' => $firstPresentation->is_available,

                                'stock_total' => $batches->sum(fn($batch) => (float) ($batch->stock_actual ?? 0)),
                                'stock_reservado_total' => $batches->sum(fn($batch) => (float) ($batch->stock_reservado ?? 0)),
                                'batches' => $batches,
                                'batch_fefo' => $batches->first(),
                            ];
                        })
                        ->values(),
                ];
            })
            ->values();



        return view('admin.oncologicos.inventory.index', [
            'rows' => $rows,
            'groupedRows' => $groupedRows,
            'q' => $q,
            'stock' => $stock,
            'category' => $category,
            'laboratoryId' => $laboratoryId,
            'laboratory' => $lab,
        ]);
    }


    public function bulkUpdate(Request $request)
    {
        $laboratoryId = (int) $request->input('laboratory_id');

        if ($laboratoryId <= 0) {
            return back()->withErrors(['error' => 'Falta laboratory_id.']);
        }

        $labExists = DB::table('laboratories')
            ->where('id', $laboratoryId)
            ->exists();

        if (!$labExists) {
            return back()->withErrors(['error' => 'Laboratorio inválido.']);
        }

        $raw = $request->input('items');

        if (!$raw) {
            return back()->withErrors(['error' => 'No se recibieron cambios para guardar.']);
        }

        $items = json_decode($raw, true);

        if (!is_array($items)) {
            return back()->withErrors(['error' => 'Formato inválido: items no es un JSON válido.']);
        }

        if (count($items) === 0) {
            return back()->with('success', 'Sin cambios por guardar.');
        }

        foreach ($items as $i => $it) {
            $items[$i]['batch_id'] = isset($it['batch_id']) && $it['batch_id'] !== '' ? (int) $it['batch_id'] : null;
            $items[$i]['presentation_id'] = isset($it['presentation_id']) && $it['presentation_id'] !== '' ? (int) $it['presentation_id'] : null;
            $items[$i]['lote'] = isset($it['lote']) ? trim((string) $it['lote']) : '';
            $items[$i]['caducidad'] = isset($it['caducidad']) ? trim((string) $it['caducidad']) : '';
            $items[$i]['fecha_ingreso'] = isset($it['fecha_ingreso']) ? trim((string) $it['fecha_ingreso']) : '';
            $items[$i]['stock_inicial'] = $this->toIntOrNull($it['stock_inicial'] ?? null);
            $items[$i]['stock_actual'] = $this->toIntOrNull($it['stock_actual'] ?? null);

            if ($items[$i]['batch_id'] === null && !$items[$i]['presentation_id']) {
                return back()->withErrors([
                    'error' => "Falta presentation_id en el item #" . ($i + 1) . " (no hay batch_id)."
                ]);
            }

            if ($items[$i]['lote'] === '') {
                return back()->withErrors([
                    'error' => "Falta lote en el item #" . ($i + 1)
                ]);
            }

            if ($items[$i]['caducidad'] === '') {
                return back()->withErrors([
                    'error' => "Falta caducidad en el item #" . ($i + 1)
                ]);
            }

            try {
                Carbon::createFromFormat('Y-m-d', $items[$i]['caducidad']);
            } catch (\Throwable $e) {
                return back()->withErrors([
                    'error' => "Caducidad inválida (YYYY-MM-DD) en el item #" . ($i + 1)
                ]);
            }

            if ($items[$i]['fecha_ingreso'] !== '') {
                try {
                    Carbon::createFromFormat('Y-m-d', $items[$i]['fecha_ingreso']);
                } catch (\Throwable $e) {
                    return back()->withErrors([
                        'error' => "Fecha de ingreso inválida (YYYY-MM-DD) en el item #" . ($i + 1)
                    ]);
                }
            }

            if (($items[$i]['stock_inicial'] ?? 0) < 0) {
                return back()->withErrors([
                    'error' => "stock_inicial no puede ser negativo en el item #" . ($i + 1)
                ]);
            }

            if (($items[$i]['stock_actual'] ?? 0) < 0) {
                return back()->withErrors([
                    'error' => "stock_actual no puede ser negativo en el item #" . ($i + 1)
                ]);
            }
        }

        DB::beginTransaction();

        try {
            $updated = 0;
            $created = 0;
            $movements = 0;
            $userId = Auth::id();

            $batchIds = array_values(array_unique(array_filter(array_map(fn($x) => $x['batch_id'], $items))));

            if (!empty($batchIds)) {
                $batches = DB::table('medicine_batches')
                    ->whereIn('id', $batchIds)
                    ->select(
                        'id',
                        'laboratory_id',
                        'medicine_presentation_id',
                        'stock_actual',
                        'stock_inicial'
                    )
                    ->get()
                    ->keyBy('id');

                $faltantes = [];
                $foraneos = [];

                foreach ($batchIds as $id) {
                    if (!isset($batches[$id])) {
                        $faltantes[] = $id;
                        continue;
                    }

                    if ((int) $batches[$id]->laboratory_id !== $laboratoryId) {
                        $foraneos[] = $id;
                    }
                }

                if (!empty($faltantes)) {
                    throw new \Exception("No existen los siguientes batch_id: " . implode(', ', $faltantes));
                }

                if (!empty($foraneos)) {
                    throw new \Exception("Los siguientes batch_id no pertenecen al laboratorio seleccionado: " . implode(', ', $foraneos));
                }
            } else {
                $batches = collect();
            }

            foreach ($items as $it) {
                if ($it['batch_id'] === null) {
                    $presentationExists = DB::table('medicine_presentations')
                        ->where('id', $it['presentation_id'])
                        ->exists();

                    if (!$presentationExists) {
                        throw new \Exception("No existe la presentación con ID {$it['presentation_id']}.");
                    }

                    $duplicate = DB::table('medicine_batches')
                        ->where('laboratory_id', $laboratoryId)
                        ->where('medicine_presentation_id', $it['presentation_id'])
                        ->where('lote', $it['lote'])
                        ->exists();

                    if ($duplicate) {
                        throw new \Exception("Ya existe el lote '{$it['lote']}' para la presentación {$it['presentation_id']} en este laboratorio.");
                    }

                    $stockInicial = $it['stock_inicial'] ?? 0;
                    $stockActual = $it['stock_actual'] ?? $stockInicial;

                    $newBatchId = DB::table('medicine_batches')->insertGetId([
                        'laboratory_id' => $laboratoryId,
                        'medicine_presentation_id' => $it['presentation_id'],
                        'lote' => $it['lote'],
                        'caducidad' => $it['caducidad'],
                        'fecha_ingreso' => $it['fecha_ingreso'] !== '' ? $it['fecha_ingreso'] : null,
                        'stock_inicial' => $stockInicial,
                        'stock_actual' => $stockActual,
                        'stock_reservado' => 0,
                        'costo_unitario' => null,
                        'is_current' => 1,
                        'is_active' => $stockActual > 0 ? 1 : 0,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    if ($stockActual > 0) {
                        $this->insertMovement([
                            'medicine_batch_id' => $newBatchId,
                            'laboratory_id' => $laboratoryId,
                            'user_id' => $userId,
                            'movement_type' => 'entrada',
                            'quantity' => $stockActual,
                            'stock_actual_before' => 0,
                            'stock_actual_after' => $stockActual,
                            'stock_reservado_before' => 0,
                            'stock_reservado_after' => 0,
                            'reference_type' => 'inventory_bulk_create',
                            'reference_id' => null,
                            'notes' => 'Alta inicial de lote desde inventario.',
                        ]);
                        $movements++;
                    }

                    $created++;
                    continue;
                }

                $existing = $batches[$it['batch_id']];

                $oldStockActual = (int) $existing->stock_actual;
                $newStockActual = $it['stock_actual'] ?? $oldStockActual;

                $updateData = [
                    'lote' => $it['lote'],
                    'caducidad' => $it['caducidad'],
                    'fecha_ingreso' => $it['fecha_ingreso'] !== '' ? $it['fecha_ingreso'] : null,
                    'stock_inicial' => $it['stock_inicial'] ?? (int) $existing->stock_inicial,
                    'stock_actual' => $newStockActual,
                    'is_active' => $newStockActual > 0 ? 1 : 0,
                    'updated_at' => now(),
                ];

                $affected = DB::table('medicine_batches')
                    ->where('id', $it['batch_id'])
                    ->update($updateData);

                if ($newStockActual !== $oldStockActual) {
                    $difference = $newStockActual - $oldStockActual;

                    $this->insertMovement([
                        'medicine_batch_id' => $it['batch_id'],
                        'laboratory_id' => $laboratoryId,
                        'user_id' => $userId,
                        'movement_type' => $difference > 0 ? 'ajuste_positivo' : 'ajuste_negativo',
                        'quantity' => abs($difference),
                        'stock_actual_before' => $oldStockActual,
                        'stock_actual_after' => $newStockActual,
                        'stock_reservado_before' => 0,
                        'stock_reservado_after' => 0,
                        'reference_type' => 'inventory_bulk_update',
                        'reference_id' => $it['batch_id'],
                        'notes' => 'Ajuste manual desde pantalla de inventario.',
                    ]);
                    $movements++;
                }

                $updated += (int) $affected;
            }

            DB::commit();

            return back()->with(
                'success',
                "Guardado ok. Actualizados: {$updated}. Nuevos lotes creados: {$created}. Movimientos generados: {$movements}."
            );
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Error al guardar inventario: ' . $e->getMessage()]);
        }
    }

    public function storeBatch(Request $request, $presentation)
    {
        $presentationId = (int) $presentation;
        $laboratoryId = (int) $request->input('laboratory_id');

        if ($laboratoryId <= 0) {
            return back()->withErrors(['error' => 'Falta laboratory_id.']);
        }

        $request->validate([
            'lote' => 'required|string|max:100',
            'caducidad' => 'required|date|after_or_equal:today',
            'fecha_ingreso' => 'nullable|date',
            'stock_inicial' => 'required|integer|min:1',
        ], [
            'caducidad.after_or_equal' => 'La caducidad no puede ser anterior a hoy.',
            'stock_inicial.required' => 'Debes ingresar la cantidad de frascos.',
            'stock_inicial.min' => 'La cantidad debe ser mayor a 0.',
        ]);

        DB::beginTransaction();

        try {
            $labExists = DB::table('laboratories')
                ->where('id', $laboratoryId)
                ->exists();

            if (!$labExists) {
                throw new \Exception('Laboratorio inválido.');
            }

            $presentationExists = DB::table('medicine_presentations')
                ->where('id', $presentationId)
                ->exists();

            if (!$presentationExists) {
                throw new \Exception("No existe la presentación con ID {$presentationId}.");
            }

            $lote = trim((string) $request->lote);
            $caducidad = $request->caducidad;
            $fechaIngreso = $request->filled('fecha_ingreso') ? $request->fecha_ingreso : now()->toDateString();
            $cantidadIngresada = (int) $request->stock_inicial;

            $this->validarLoteNoUsadoEnOtraPresentacion(
                $laboratoryId,
                $lote,
                $presentationId
            );

            $batch = DB::table('medicine_batches')
                ->where('laboratory_id', $laboratoryId)
                ->where('medicine_presentation_id', $presentationId)
                ->where('lote', $lote)
                ->lockForUpdate()
                ->first();

            if ($batch) {
                $stockAntes = (int) $batch->stock_actual;
                $stockInicialAntes = (int) $batch->stock_inicial;
                $stockDespues = $stockAntes + $cantidadIngresada;

                DB::table('medicine_batches')
                    ->where('id', $batch->id)
                    ->update([
                        'stock_inicial' => $stockInicialAntes + $cantidadIngresada,
                        'stock_actual' => $stockDespues,
                        'caducidad' => $caducidad,
                        'fecha_ingreso' => $fechaIngreso,
                        'is_active' => 1,
                        'updated_at' => now(),
                    ]);

                $batchId = $batch->id;
                $mensaje = 'El lote ya existía para esta presentación. Se sumó al inventario existente.';
            } else {
                $batchId = DB::table('medicine_batches')->insertGetId([
                    'laboratory_id' => $laboratoryId,
                    'medicine_presentation_id' => $presentationId,
                    'lote' => $lote,
                    'caducidad' => $caducidad,
                    'fecha_ingreso' => $fechaIngreso,
                    'stock_inicial' => $cantidadIngresada,
                    'stock_actual' => $cantidadIngresada,
                    'stock_reservado' => 0,
                    'costo_unitario' => null,
                    'is_current' => 1,
                    'is_active' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $stockAntes = 0;
                $stockDespues = $cantidadIngresada;
                $mensaje = 'Lote creado correctamente.';
            }

            $this->insertMovement([
                'medicine_batch_id' => $batchId,
                'laboratory_id' => $laboratoryId,
                'user_id' => Auth::id(),
                'movement_type' => 'entrada',
                'quantity' => $cantidadIngresada,
                'stock_actual_before' => $stockAntes,
                'stock_actual_after' => $stockDespues,
                'stock_reservado_before' => 0,
                'stock_reservado_after' => 0,
                'reference_type' => 'inventory_create_batch',
                'reference_id' => $batchId,
                'notes' => $mensaje,
            ]);

            DB::commit();

            return back()->with('success', $mensaje);
        } catch (\Throwable $e) {
            DB::rollBack();

            return back()->withErrors([
                'error' => 'Error al registrar lote: ' . $e->getMessage(),
            ]);
        }
    }

    private function insertMovement(array $data): void
    {
        DB::table('medicine_batch_movements')->insert([
            'medicine_batch_id' => $data['medicine_batch_id'],
            'laboratory_id' => $data['laboratory_id'],
            'user_id' => $data['user_id'] ?? null,
            'movement_type' => $data['movement_type'],
            'quantity' => $data['quantity'],
            'stock_actual_before' => $data['stock_actual_before'] ?? 0,
            'stock_actual_after' => $data['stock_actual_after'] ?? 0,
            'stock_reservado_before' => $data['stock_reservado_before'] ?? 0,
            'stock_reservado_after' => $data['stock_reservado_after'] ?? 0,
            'reference_type' => $data['reference_type'] ?? null,
            'reference_id' => $data['reference_id'] ?? null,
            'notes' => $data['notes'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }


    private function toIntOrNull($value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int) $value;
    }


    public function exportarExcel(Request $request)
    {
        $laboratoryId = (int) $request->get('laboratory_id');

        if ($laboratoryId <= 0) {
            return redirect()
                ->route('admin.oncologicos.inventory.selectLaboratory')
                ->withErrors(['error' => 'Debes seleccionar un laboratorio para exportar el inventario.']);
        }

        $laboratory = Laboratory::find($laboratoryId);

        if (!$laboratory) {
            return back()->withErrors(['error' => 'Laboratorio inválido.']);
        }

        $fileName = 'inventario_oncologico_' . str_replace(' ', '_', strtolower($laboratory->nombre)) . '.xlsx';

        return Excel::download(
            new OncologicosInventoryExport(
                $laboratoryId,
                (string) $request->get('q', ''),
                (string) $request->get('stock', '')
            ),
            $fileName
        );
    }
}
