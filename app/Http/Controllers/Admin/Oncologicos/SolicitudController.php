<?php

namespace App\Http\Controllers\Admin\Oncologicos;

use App\Exports\Oncologicos\SolicitudesOncoExport as OncologicosSolicitudesOncoExport;
use App\Http\Controllers\Controller;
use App\Models\Hospital;
use App\Models\Oncologicos\MedicineOnco;
use App\Models\Oncologicos\Mezcla;
use App\Models\Oncologicos\MezclaMedicamento;
use App\Models\Oncologicos\SolicitudOnco;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;
use Maatwebsite\Excel\Facades\Excel;

class SolicitudController extends Controller
{
    public function index()
    {
        return view('admin.oncologicos.solicitudes.index');
    }

    private function currentHospitalId(): int
    {
        return (int) (auth()->user()?->hospital_id ?? 0);
    }


    private function currentMedicineListId(): ?int
    {
        $user = auth()->user();
        $hospitalId = (int) ($user?->hospital_id ?? 0);

        if ($hospitalId <= 0) {
            return null;
        }

        if (!Schema::hasColumn('hospitals', 'onco_medicine_list_id')) {
            return null;
        }

        $listaId = DB::table('hospitals')
            ->where('id', $hospitalId)
            ->value('onco_medicine_list_id');

        return $listaId ? (int) $listaId : null;
    }

    private function consumeBatchForMix(
        int $batchId,
        int $unidades,
        int $laboratoryId,
        int $listaId,
        int $catalogId,
        int $mezclaId,
        ?int $userId = null
    ): object {
        if ($batchId <= 0) {
            throw new \Exception("Batch inválido.");
        }

        if ($unidades <= 0) {
            throw new \Exception("Las unidades usadas deben ser mayores a 0.");
        }

        $batch = DB::table('medicine_batches as mb')
            ->join('medicine_presentations as mp', 'mp.id', '=', 'mb.medicine_presentation_id')
            ->join('medicine_list_presentation as mlp', function ($join) use ($listaId) {
                $join->on('mlp.medicine_presentation_id', '=', 'mp.id')
                    ->where('mlp.medicine_list_id', '=', $listaId);
            })
            ->where('mb.id', $batchId)
            ->where('mb.laboratory_id', $laboratoryId)
            ->where('mp.catalog_id', $catalogId)
            ->where('mp.is_available', 1)
            ->where(function ($q) {
                $q->whereNull('mb.caducidad')
                    ->orWhereDate('mb.caducidad', '>=', now()->toDateString());
            })
            ->lockForUpdate()
            ->select(
                'mb.id',
                'mb.lote',
                'mb.caducidad',
                'mb.stock_actual',
                'mb.stock_reservado',
                'mb.medicine_presentation_id',

                'mp.id as presentation_id',
                'mp.catalog_id',
                'mp.presentacion',
                'mp.cantidad_medicamento',
                'mp.volumen_diluyente',
                'mp.legend',
                'mp.marca',
                'mp.precio_frasco',

                'mlp.precio as precio_lista'
            )
            ->first();

        if (!$batch) {
            throw new \Exception("No se encontró el lote seleccionado para el laboratorio/lista/catálogo correspondiente.");
        }

        $stockActual = (int) ($batch->stock_actual ?? 0);

        if ($stockActual < $unidades) {
            throw new \Exception("Stock insuficiente para el lote {$batch->lote}. Disponible: {$stockActual}, solicitado: {$unidades}.");
        }

        $nuevoStock = $stockActual - $unidades;

        DB::table('medicine_batches')
            ->where('id', $batch->id)
            ->update([
                'stock_actual' => $nuevoStock,
                'is_active'    => $nuevoStock > 0 ? 1 : 0,
                'updated_at'   => now(),
            ]);

        DB::table('medicine_batch_movements')->insert([
            'medicine_batch_id'         => $batch->id,
            'laboratory_id'             => $laboratoryId,
            'user_id'                   => $userId,
            'movement_type'             => 'salida',
            'quantity'                  => $unidades,
            'stock_actual_before'       => $stockActual,
            'stock_actual_after'        => $nuevoStock,
            'stock_reservado_before'    => (int) ($batch->stock_reservado ?? 0),
            'stock_reservado_after'     => (int) ($batch->stock_reservado ?? 0),
            'reference_type'            => 'mezcla',
            'reference_id'              => $mezclaId,
            'notes'                     => 'Consumo de inventario por creación de mezcla.',
            'created_at'                => now(),
            'updated_at'                => now(),
        ]);

        $precioFrasco = (float) ($batch->precio_lista ?? $batch->precio_frasco ?? 0);

        return (object) [
            'batch_id'                     => (int) $batch->id,
            'lote'                         => $batch->lote,
            'caducidad'                    => $batch->caducidad,
            'presentation_id'              => (int) $batch->presentation_id,
            'presentacion'                 => $batch->presentacion,
            'cantidad_medicamento'         => $batch->cantidad_medicamento,
            'volumen_diluyente'            => $batch->volumen_diluyente,
            'legend'                       => $batch->legend,
            'marca'                        => $batch->marca,
            'precio_frasco'                => $precioFrasco,
            'subtotal'                     => $precioFrasco * $unidades,
        ];
    }

    private function attachPresentacionesAndConsumeInventory(
        MezclaMedicamento $mezclaMedicamento,
        array $presentacionesPayload,
        int $catalogId,
        int $listaId,
        int $laboratoryId,
        int $mezclaId,
        ?int $userId = null
    ): array {
        $totalMlAportado = 0.0;
        $marcaElegida = null;
        $alMenosUna = false;

        foreach ($presentacionesPayload as $pres) {
            $batchId = isset($pres['batch_id']) ? (int) $pres['batch_id'] : 0;
            $unidades = isset($pres['frascos']) ? (int) $pres['frascos'] : 0;

            if ($batchId <= 0 || $unidades <= 0) {
                continue;
            }

            $alMenosUna = true;

            $batchConsumido = $this->consumeBatchForMix(
                $batchId,
                $unidades,
                $laboratoryId,
                $listaId,
                $catalogId,
                $mezclaId,
                $userId
            );

            DB::table('mezcla_medicamento_presentaciones')->insert([
                'mezcla_medicamento_id'         => $mezclaMedicamento->id,
                'medicine_batch_id'             => $batchConsumido->batch_id,
                'unidades_usadas'               => $unidades,

                'presentacion_snapshot'         => $batchConsumido->presentacion,
                'cantidad_medicamento_snapshot' => $batchConsumido->cantidad_medicamento,
                'volumen_diluyente_snapshot'    => $batchConsumido->volumen_diluyente,
                'legend_snapshot'               => $batchConsumido->legend,

                'lote_usado'                    => $batchConsumido->lote,
                'caducidad_usada'               => $batchConsumido->caducidad,
                'precio_frasco_snapshot'        => $batchConsumido->precio_frasco,
                'subtotal'                      => $batchConsumido->subtotal,

                'created_at'                    => now(),
                'updated_at'                    => now(),
            ]);

            if (!$marcaElegida && !empty($batchConsumido->marca)) {
                $marcaElegida = $batchConsumido->marca;
            }

            $volMl = (float) ($batchConsumido->volumen_diluyente ?? 0);
            if ($volMl > 0) {
                $totalMlAportado += ($volMl * $unidades);
            }
        }

        if (!$alMenosUna) {
            throw new \Exception("Debes seleccionar al menos una presentación/lote con unidades mayores a 0 para el medicamento del catálogo {$catalogId}.");
        }

        return [
            'dosis_ml'       => $totalMlAportado > 0 ? round($totalMlAportado, 2) : null,
            'marca_snapshot' => $marcaElegida,
        ];
    }



    /**
     * ✅ Laboratorio desde hospital (fuente de verdad)
     */
    private function currentLaboratoryId(): ?int
    {
        $user = auth()->user();
        $hospitalId = (int) ($user?->hospital_id ?? 0);
        if ($hospitalId <= 0) return null;

        $labId = DB::table('hospitals')
            ->where('id', $hospitalId)
            ->value('laboratory_id');

        return $labId ? (int) $labId : null;
    }

    public function create()
    {
        $listaId = $this->currentMedicineListId();

        if (!$listaId) {
            return redirect()
                ->route('admin.oncologicos.solicitudes.index')
                ->withErrors(['error' => 'Tu hospital no tiene una lista de medicamentos configurada.']);
        }

        // ✅ Validar que el hospital ya tenga laboratorio asignado
        $laboratoryId = $this->currentLaboratoryId();
        if (!$laboratoryId) {
            return redirect()
                ->route('admin.oncologicos.solicitudes.index')
                ->withErrors(['error' => 'Tu hospital no tiene laboratorio asignado. Configúralo desde Hospitales.']);
        }

        // ✅ Catálogos permitidos por lista (desde presentations)
        $catalogIdsPermitidos = DB::table('medicine_list_presentation as mlp')
            ->join('medicine_presentations as mp', 'mp.id', '=', 'mlp.medicine_presentation_id')
            ->where('mlp.medicine_list_id', $listaId)
            ->where('mp.is_available', 1)
            ->pluck('mp.catalog_id')
            ->map(fn($x) => (int) $x)
            ->unique()
            ->values();

        if ($catalogIdsPermitidos->isEmpty()) {
            return redirect()
                ->route('admin.oncologicos.solicitudes.index')
                ->withErrors(['error' => 'La lista de medicamentos del hospital está vacía.']);
        }

        // ✅ Catálogos activos PERO solo los de la lista
        $catalogos = DB::table('medicines_catalog as mc')
            ->where('mc.state', true)
            ->whereIn('mc.id', $catalogIdsPermitidos->all())
            ->select('mc.id', 'mc.denominacion', 'mc.requires_infusor')
            ->orderBy('mc.denominacion')
            ->get()
            ->map(function ($m) {
                $m->requires_infusor = (int) ($m->requires_infusor ?? 0);
                return $m;
            });

        $medicamentos = $catalogos; // alias para tu JS

        // ✅ Presentaciones por catálogo (desde lista)
        $presentaciones = DB::table('medicine_presentations as mp')
            ->join('medicine_list_presentation as mlp', 'mlp.medicine_presentation_id', '=', 'mp.id')
            ->where('mlp.medicine_list_id', $listaId)
            ->where('mp.is_available', 1)
            ->select(
                'mp.id',
                'mp.catalog_id',
                'mp.presentacion',
                'mp.marca',
                'mp.cantidad_medicamento',
                'mp.volumen_diluyente',
                'mp.precio_frasco',
                'mlp.charge_by',
                'mlp.precio',
                'mlp.precio_mg_override'
            )
            ->orderBy('mp.presentacion')
            ->get();

        $presentacionesPorCatalogo = $presentaciones
            ->groupBy('catalog_id')
            ->map(fn($rows) => $rows->values());

        // ✅ Info adicional (diluyentes/vías)
        $infoAdicional = [];
        foreach ($catalogos as $cat) {
            $diluyentes = DB::table('diluent_medicine_catalog')
                ->join('diluents', 'diluent_medicine_catalog.diluent_id', '=', 'diluents.id')
                ->where('diluent_medicine_catalog.medicine_catalog_id', $cat->id)
                ->select('diluents.id', DB::raw('diluents.denominacion_generica as name'))
                ->get();

            $vias = DB::table('administration_route_medicine_catalog')
                ->join('administration_routes', 'administration_route_medicine_catalog.administration_route_id', '=', 'administration_routes.id')
                ->where('administration_route_medicine_catalog.medicine_catalog_id', $cat->id)
                ->select('administration_routes.id', 'administration_routes.name')
                ->get();

            $infoAdicional[$cat->id] = [
                'diluyentes'       => $diluyentes,
                'vias'             => $vias,
                'requires_infusor' => (int) $cat->requires_infusor,
            ];
        }

        $infusors = DB::table('infusors')
            ->select('id', 'nombre_generico', 'nombre_comercial')
            ->where('is_active', true)
            ->orderBy('nombre_generico')
            ->orderBy('nombre_comercial')
            ->get();

        return view('admin.oncologicos.solicitudes.create', [
            'catalogos'                 => $catalogos,
            'medicamentos'              => $medicamentos,
            'presentacionesPorCatalogo' => $presentacionesPorCatalogo,
            'infoAdicional'             => $infoAdicional,
            'infusors'                  => $infusors,
        ]);
    }

    public function store(Request $request)
    {

        $request->validate([
            'paciente_nombre'  => 'required|string|max:255',
            'servicio'         => 'required|string|max:255',
            'registro'         => 'required|string|max:255',
            'sexo'             => 'required|in:M,F',

            'fecha_nacimiento' => [
                'required',
                'date',
                'after:' . Carbon::now()->subYears(100)->format('Y-m-d'),
                'before:' . Carbon::today()->format('Y-m-d'),
            ],

            'peso'             => 'required|numeric|min:1|max:500',
            'piso'             => 'required|string|max:50',
            'cama'             => 'required|string|max:50',
            'diagnostico'      => 'required|string|max:255',
            'alergias'         => 'nullable|string|max:255',
            'medico_nombre'    => 'required|string|max:255',
            'medico_cedula'    => 'required|string|max:255',
            'fecha_entrega'    => 'required|date|after_or_equal:today',
            'observaciones'    => 'nullable|string|max:500',
            'mezclas'          => 'required|string',
        ], [
            'fecha_nacimiento.after'  => 'La fecha de nacimiento no puede ser mayor a 100 años.',
            'fecha_nacimiento.before' => 'La fecha de nacimiento debe ser anterior a hoy.',
            'fecha_entrega.after_or_equal' => 'La fecha de entrega no puede ser anterior a hoy.',
        ]);

        $user = auth()->user();
        $hospitalId = (int) ($user?->hospital_id ?? 0);

        if ($hospitalId <= 0) {
            return back()->withErrors(['error' => 'Tu usuario no tiene hospital asignado.'])->withInput();
        }

        $laboratoryId = $this->currentLaboratoryId();
        if (!$laboratoryId) {
            return back()->withErrors(['error' => 'Tu hospital no tiene laboratorio asignado. Configúralo desde Hospitales.'])->withInput();
        }

        $listaId = $this->currentMedicineListId();

        if (!$listaId) {
            return back()->withErrors([
                'error' => 'Tu hospital no tiene una lista de medicamentos configurada.'
            ])->withInput();
        }

        $mezclas = json_decode($request->mezclas, true);

        if (!is_array($mezclas)) {
            return back()
                ->withErrors(['mezclas' => 'El formato del campo mezclas no es válido.'])
                ->withInput();
        }

        $catalogosPermitidos = DB::table('medicine_list_presentation as mlp')
            ->join('medicine_presentations as mp', 'mp.id', '=', 'mlp.medicine_presentation_id')
            ->where('mlp.medicine_list_id', $listaId)
            ->where('mp.is_available', 1)
            ->pluck('mp.catalog_id')
            ->map(fn($x) => (int) $x)
            ->unique()
            ->values()
            ->toArray();

        if (count($catalogosPermitidos) === 0) {
            return back()->withErrors(['error' => 'La lista de medicamentos del hospital está vacía.'])->withInput();
        }

        $listaInfoPorCatalogo = DB::table('medicine_list_presentation as mlp')
            ->join('medicine_presentations as mp', 'mp.id', '=', 'mlp.medicine_presentation_id')
            ->where('mlp.medicine_list_id', $listaId)
            ->where('mp.is_available', 1)
            ->get([
                'mp.catalog_id',
                'mlp.charge_by',
                'mlp.precio',
                'mlp.precio_mg_override',
            ])
            ->groupBy('catalog_id')
            ->map(function ($rows) {
                $first = $rows->first();
                return [
                    'charge_by' => $first?->charge_by,
                    'precio'    => $first?->precio,
                    'precio_mg_override' => $first?->precio_mg_override,
                ];
            });

        DB::beginTransaction();

        try {
            $solicitud = SolicitudOnco::create([
                'user_id'           => $user->id,
                'hospital_id'       => $hospitalId,
                'servicio'          => $request->servicio,
                'nombre_paciente'   => $request->paciente_nombre,
                'sexo'              => $request->sexo,
                'edad'              => null,
                'peso'              => $request->peso,
                'cama'              => $request->cama,
                'piso'              => $request->piso,
                'registro_paciente' => $request->registro,
                'fecha_nacimiento'  => $request->fecha_nacimiento,
                'diagnostico'       => $request->diagnostico,
                'alergias'          => $request->alergias ?? '',
                'fecha_entrega'     => $request->fecha_entrega,
                'observaciones'     => $request->observaciones,
                'nombre_medico'     => $request->medico_nombre,
                'cedula_medico'     => $request->medico_cedula,
                'estado'            => 'pendiente',
                'remision'          => null,
            ]);

            foreach ($mezclas as $index => $mezclaData) {
                $volumen = isset($mezclaData['volumen_dilucion']) ? (float) $mezclaData['volumen_dilucion'] : null;
                $tiempo  = $mezclaData['tiempo_infusion'] ?? null;

                if ($volumen === null || $volumen <= 0) {
                    throw new \Exception("La Mezcla #" . ($index + 1) . " requiere un volumen de dilución válido (> 0).");
                }
                if ($tiempo === null || $tiempo === '') {
                    throw new \Exception("La Mezcla #" . ($index + 1) . " requiere un tiempo de infusión.");
                }

                $setInfusion = !empty($mezclaData['set_infusion']) ? (bool) $mezclaData['set_infusion'] : false;
                $infusorId   = !empty($mezclaData['infusor_id']) ? (int) $mezclaData['infusor_id'] : null;

                if ($setInfusion && $infusorId) {
                    throw new \Exception("Mezcla #" . ($index + 1) . ": selecciona set de infusión o un infusor, no ambos.");
                }

                $meds = $mezclaData['medicamentos'] ?? [];
                if (!is_array($meds) || count($meds) === 0) {
                    throw new \Exception("La Mezcla #" . ($index + 1) . " no contiene medicamentos.");
                }

                $diluyenteRef = null;
                $viaRef       = null;
                $hayMedQueRequiereInfusor = false;

                foreach ($meds as $i => $medicamento) {
                    $catalogId = (int) ($medicamento['medicamento_id'] ?? 0);
                    $diluyente = $medicamento['diluyente_id'] ?? null;
                    $via       = $medicamento['via_administracion_id'] ?? null;

                    if ($catalogId <= 0) {
                        throw new \Exception("Mezcla #" . ($index + 1) . ": medicamento inválido.");
                    }

                    if (!in_array($catalogId, $catalogosPermitidos, true)) {
                        throw new \Exception("Mezcla #" . ($index + 1) . ": el medicamento (catálogo {$catalogId}) no pertenece a la lista del hospital.");
                    }

                    if ($i === 0) {
                        $diluyenteRef = $diluyente;
                        $viaRef       = $via;
                    } else {
                        if ((string)$diluyente !== (string)$diluyenteRef || (string)$via !== (string)$viaRef) {
                            throw new \Exception("Mezcla #" . ($index + 1) . ": todos los medicamentos deben tener el mismo diluyente y la misma vía de administración.");
                        }
                    }

                    $mc = DB::table('medicines_catalog as mc')
                        ->where('mc.id', $catalogId)
                        ->select('mc.requires_infusor', 'mc.denominacion', 'mc.conc_min', 'mc.conc_max')
                        ->first();

                    if (!$mc) {
                        throw new \Exception("No se encontró información del catálogo para el medicamento ID {$catalogId}.");
                    }

                    if ((int) $mc->requires_infusor === 1) {
                        $hayMedQueRequiereInfusor = true;
                    }
                }

                if ($infusorId) {
                    $infusor = DB::table('infusors')
                        ->where('id', $infusorId)
                        ->where('is_active', true)
                        ->first();

                    if (!$infusor) {
                        throw new \Exception("Mezcla #" . ($index + 1) . ": el infusor seleccionado no existe o no está activo.");
                    }

                    if (!$hayMedQueRequiereInfusor) {
                        throw new \Exception("Mezcla #" . ($index + 1) . ": para seleccionar un infusor, la mezcla debe contener al menos un medicamento que lo admita.");
                    }
                }

                $mezcla = Mezcla::create([
                    'solicitud_id'     => $solicitud->id,
                    'volumen_dilucion' => $volumen,
                    'tiempo_infusion'  => $tiempo,
                    'estado'           => 'pendiente',
                    'set_infusion'     => $setInfusion,
                    'infusor_id'       => $infusorId,
                ]);

                foreach ($meds as $medicamento) {
                    $catalogId = (int) ($medicamento['medicamento_id'] ?? 0);

                    $medicineOnco = MedicineOnco::firstOrCreate(
                        ['catalog_id' => $catalogId],
                        ['precio' => 0]
                    );

                    $catalog = $medicineOnco->catalog;
                    if (!$catalog) {
                        throw new \Exception("No se encontró información del catálogo para el medicamento ID {$catalogId}.");
                    }

                    $dosis = isset($medicamento['dosis']) ? (float) $medicamento['dosis'] : 0.0;
                    if ($dosis <= 0) {
                        throw new \Exception("La dosis debe ser mayor a 0 (catálogo {$catalogId}).");
                    }

                    $concentracion = $volumen > 0 ? $dosis / $volumen : 0;
                    $concMin = (float) ($catalog->conc_min ?? 0);
                    $concMax = (float) ($catalog->conc_max ?? 0);

                    if (($concMin > 0 || $concMax > 0) && ($concentracion < $concMin || $concentracion > $concMax)) {
                        throw new \Exception(
                            "La concentración de '{$catalog->denominacion}' está fuera del rango permitido ({$concMin} - {$concMax}). Dosis: {$dosis}, Volumen: {$volumen}."
                        );
                    }

                    $chargeBy = $medicamento['charge_by'] ?? ($listaInfoPorCatalogo[$catalogId]['charge_by'] ?? null) ?? ($catalog->charge_by ?? 'mg');
                    $chargeBy = strtolower(trim((string)$chargeBy));
                    if (!in_array($chargeBy, ['mg', 'frasco', 'pieza'], true)) $chargeBy = 'mg';
                    if ($chargeBy === 'pieza') $chargeBy = 'frasco';

                    $mc = DB::table('medicines_catalog as mc')
                        ->where('mc.id', $catalogId)
                        ->select('mc.requires_infusor', 'mc.denominacion', 'mc.conc_min', 'mc.conc_max')
                        ->first();

                    MezclaMedicamento::create([
                        'mezcla_id'                  => $mezcla->id,
                        'medicamento_id'             => $medicineOnco->id,
                        'nombre_medicamento'         => $medicamento['nombre'] ?? null,

                        'dosis'                      => $dosis,
                        'dosis_ml'                   => null,

                        'diluyente_id'               => $medicamento['diluyente_id'] ?? null,
                        'via_administracion_id'      => $medicamento['via_administracion_id'] ?? null,

                        'charge_by'                  => $chargeBy,
                        'precio_mg_snapshot'         => null,

                        'denominacion_snapshot'      => $mc->denominacion ?? ($catalog->denominacion ?? null),
                        'marca_snapshot'             => null,
                        'requires_infusor_snapshot'  => (int) ($mc->requires_infusor ?? ($catalog->requires_infusor ?? 0)),
                        'conc_min_snapshot'          => $mc->conc_min ?? ($catalog->conc_min ?? null),
                        'conc_max_snapshot'          => $mc->conc_max ?? ($catalog->conc_max ?? null),
                    ]);
                }
            }

            DB::commit();

            return redirect()
                ->route('admin.oncologicos.solicitudes.index')
                ->with('success', 'Solicitud registrada correctamente.');
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withErrors(['error' => $e->getMessage()])->withInput();
        }
    }

    public function show($id)
    {
        // ...
    }

    public function edit($id)
    {
        $user = Auth::user();

        $solicitud = SolicitudOnco::with([
            'hospital',
            'user:id,name,lastname,hospital_id',
            'mezclas',
            'mezclas.medicamentos',
            'mezclas.medicamentos.medicamentoOnco.catalog',
            'mezclas.medicamentos.presentacionesUsadas.batch.presentation',
            'mezclas.medicamentos.diluyente',
            'mezclas.medicamentos.viaAdministracion',
            'mezclas.diluentPresentation.diluent',
            'mezclas.infusor',
        ])->findOrFail($id);

        $esAdmin = $user->hasAnyRole(['Admin', 'Super Admin']);

        if (
            !$esAdmin &&
            $user->hospital_id &&
            $solicitud->hospital_id &&
            (int) $user->hospital_id !== (int) $solicitud->hospital_id
        ) {
            abort(403, 'No autorizado para editar solicitudes de otro hospital.');
        }

        $solicitud->loadMissing('user.hospital');

        $hospitalSolicitud = $solicitud->hospital ?? $solicitud->user?->hospital;

        if (!$hospitalSolicitud) {
            return redirect()
                ->route('admin.oncologicos.solicitudes.index')
                ->withErrors(['error' => 'La solicitud no tiene hospital asociado.']);
        }

        $listaId = $hospitalSolicitud->onco_medicine_list_id;

        if (!$listaId) {
            return redirect()
                ->route('admin.oncologicos.solicitudes.index')
                ->withErrors(['error' => 'El hospital de esta solicitud no tiene una lista de medicamentos oncológicos configurada.']);
        }

        $laboratoryId = $hospitalSolicitud->laboratory_id ?? null;

        if (!$laboratoryId) {
            return redirect()
                ->route('admin.oncologicos.solicitudes.index')
                ->withErrors(['error' => 'El hospital de esta solicitud no tiene laboratorio asignado.']);
        }

        $catalogIdsPermitidos = DB::table('medicine_list_presentation as mlp')
            ->join('medicine_presentations as mp', 'mp.id', '=', 'mlp.medicine_presentation_id')
            ->where('mlp.medicine_list_id', $listaId)
            ->where('mp.is_available', 1)
            ->pluck('mp.catalog_id')
            ->unique()
            ->values();

        $catalogos = DB::table('medicines_catalog as mc')
            ->where('mc.state', true)
            ->whereIn('mc.id', $catalogIdsPermitidos->all())
            ->select('mc.id as id', 'mc.denominacion', 'mc.requires_infusor')
            ->orderBy('mc.denominacion')
            ->get()
            ->map(function ($m) {
                $m->requires_infusor = (int)($m->requires_infusor ?? 0);
                return $m;
            });

        $presentaciones = DB::table('medicine_presentations as mp')
            ->join('medicine_list_presentation as mlp', 'mlp.medicine_presentation_id', '=', 'mp.id')
            ->where('mlp.medicine_list_id', $listaId)
            ->where('mp.is_available', 1)
            ->select(
                'mp.id',
                'mp.catalog_id',
                'mp.presentacion',
                'mp.marca',
                'mp.cantidad_medicamento',
                'mp.volumen_diluyente',
                'mp.precio_frasco',
                'mlp.charge_by',
                'mlp.precio',
                'mlp.precio_mg_override'
            )
            ->orderBy('mp.presentacion')
            ->get();

        $presentacionesPorCatalogo = $presentaciones
            ->groupBy('catalog_id')
            ->map(fn($rows) => $rows->values());

        $infoAdicional = [];
        foreach ($catalogos as $cat) {
            $diluyentes = DB::table('diluent_medicine_catalog')
                ->join('diluents', 'diluent_medicine_catalog.diluent_id', '=', 'diluents.id')
                ->where('diluent_medicine_catalog.medicine_catalog_id', $cat->id)
                ->select('diluents.id', DB::raw('diluents.denominacion_generica as name'))
                ->get();

            $vias = DB::table('administration_route_medicine_catalog')
                ->join('administration_routes', 'administration_route_medicine_catalog.administration_route_id', '=', 'administration_routes.id')
                ->where('administration_route_medicine_catalog.medicine_catalog_id', $cat->id)
                ->select('administration_routes.id', 'administration_routes.name')
                ->get();

            $infoAdicional[$cat->id] = [
                'catalog_id'       => $cat->id,
                'diluyentes'       => $diluyentes,
                'vias'             => $vias,
                'requires_infusor' => (int)$cat->requires_infusor,
            ];
        }

        $infusors = DB::table('infusors')
            ->select('id', 'nombre_generico', 'nombre_comercial')
            ->where('is_active', true)
            ->orderBy('nombre_generico')
            ->orderBy('nombre_comercial')
            ->get();

        $medSnapshots = $solicitud->mezclas
            ->flatMap(fn($mezcla) => $mezcla->medicamentos)
            ->mapWithKeys(function ($mm) {
                $catalog = optional(optional($mm->medicamentoOnco)->catalog);

                return [
                    $mm->id => [
                        'denominacion'     => $mm->denominacion_snapshot
                            ?? ($catalog->denominacion ?? $mm->nombre_medicamento ?? '—'),
                        'marca'            => $mm->marca_snapshot ?? null,
                        'requires_infusor' => $mm->requires_infusor_snapshot
                            ?? (int)($catalog->requires_infusor ?? 0),
                        'conc_min'         => $mm->conc_min_snapshot ?? ($catalog->conc_min ?? null),
                        'conc_max'         => $mm->conc_max_snapshot ?? ($catalog->conc_max ?? null),
                    ],
                ];
            });

        $solicitud->mezclas->each(function ($mezcla) {
            $mezcla->medicamentos->each(function ($mm) {
                $catalogId = optional($mm->medicamentoOnco)->catalog_id;
                $snapshot = trim((string) ($mm->denominacion_snapshot ?? ''));
                $relacionDenominacion = trim((string) (optional(optional($mm->medicamentoOnco)->catalog)->denominacion ?? ''));

                if ($snapshot !== '' && strcasecmp($snapshot, $relacionDenominacion) !== 0) {
                    $catalogIdPorSnapshot = DB::table('medicines_catalog')
                        ->whereRaw('LOWER(TRIM(denominacion)) = ?', [mb_strtolower($snapshot)])
                        ->value('id');

                    if ($catalogIdPorSnapshot) {
                        $catalogId = (int) $catalogIdPorSnapshot;
                    }
                }

                if ($catalogId) {
                    $mm->setAttribute('medicamento_id', (int) $catalogId);
                }
            });
        });

        return view('admin.oncologicos.solicitudes.edit', [
            'solicitud'                 => $solicitud,
            'medicamentos'              => $catalogos,
            'catalogos'                 => $catalogos,
            'presentacionesPorCatalogo' => $presentacionesPorCatalogo,
            'infoAdicional'             => $infoAdicional,
            'infusors'                  => $infusors,
            'hospitalNombre'            => optional($solicitud->hospital)->name,
            'medSnapshots'              => $medSnapshots,

            // ✅ ya no pasamos laboratories ni laboratoryId
        ]);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'paciente_nombre'  => 'required|string|max:255',
            'servicio'         => 'required|string|max:255',
            'registro'         => 'required|string|max:255',
            'sexo'             => 'required|in:M,F',
            'fecha_nacimiento' => [
                'required',
                'date',
                'after:' . Carbon::now()->subYears(100)->format('Y-m-d'),
                'before:' . Carbon::today()->format('Y-m-d'),
            ],
            'peso'             => 'required|numeric|min:1|max:200',
            'piso'             => 'required|string|max:50',
            'cama'             => 'required|string|max:50',
            'diagnostico'      => 'required|string|max:255',
            'alergias'         => 'nullable|string|max:255',
            'medico_nombre'    => 'required|string|max:255',
            'medico_cedula'    => 'required|string|max:255',
            'fecha_entrega'    => 'required|date|after_or_equal:today',
            'observaciones'    => 'nullable|string|max:500',
            'mezclas'          => 'required|string',
        ], [
            'fecha_nacimiento.after'  => 'La fecha de nacimiento no puede ser mayor a 100 años.',
            'fecha_nacimiento.before' => 'La fecha de nacimiento debe ser anterior a hoy.',
            'fecha_entrega.after_or_equal' => 'La fecha de entrega no puede ser anterior a hoy.',
        ]);

        $mezclas = json_decode($request->mezclas, true);
        if (!is_array($mezclas)) {
            return back()->withErrors(['mezclas' => 'El formato del campo mezclas no es válido.'])->withInput();
        }

        $user = auth()->user();

        $laboratoryId = $this->currentLaboratoryId();
        if (!$laboratoryId) {
            return back()->withErrors([
                'error' => 'Tu hospital no tiene laboratorio asignado. Configúralo desde Hospitales.'
            ])->withInput();
        }

        DB::beginTransaction();

        try {
            $solicitud = SolicitudOnco::with([
                'mezclas.medicamentos',
                'mezclas.medicamentos.medicamentoOnco.catalog',
            ])->findOrFail($id);

            // =====================================================
            // Seguridad por hospital
            // =====================================================
            $esAdmin = $user->hasAnyRole(['Admin', 'Super Admin']);

            if (
                !$esAdmin &&
                $user->hospital_id &&
                $solicitud->hospital_id &&
                (int) $user->hospital_id !== (int) $solicitud->hospital_id
            ) {
                abort(403, 'No autorizado para editar solicitudes de otro hospital.');
            }

            // =====================================================
            // Asegurar snapshot hospital_id (solo si NULL)
            // =====================================================
            if (empty($solicitud->hospital_id) && !empty($user->hospital_id)) {
                $solicitud->hospital_id = $user->hospital_id;
                $solicitud->save();
            }

            // =====================================================
            // Actualizar datos básicos
            // =====================================================
            $solicitud->update([
                'servicio'          => $request->servicio,
                'nombre_paciente'   => $request->paciente_nombre,
                'sexo'              => $request->sexo,
                'peso'              => $request->peso,
                'cama'              => $request->cama,
                'piso'              => $request->piso,
                'registro_paciente' => $request->registro,
                'fecha_nacimiento'  => $request->fecha_nacimiento,
                'diagnostico'       => $request->diagnostico,
                'alergias'          => $request->alergias ?? '',
                'fecha_entrega'     => $request->fecha_entrega,
                'observaciones'     => $request->observaciones,
                'nombre_medico'     => $request->medico_nombre,
                'cedula_medico'     => $request->medico_cedula,
            ]);

            // =====================================================
            // Separar mezclas existentes y nuevas
            // =====================================================
            $existentes = array_values(array_filter($mezclas, fn($m) => !empty($m['existente'])));
            $nuevas     = array_values(array_filter($mezclas, fn($m) => empty($m['existente'])));

            // =====================================================
            // Actualizar set/infusor de mezclas existentes
            // =====================================================
            foreach ($existentes as $payload) {
                $mezclaId = (int) ($payload['id'] ?? 0);
                if ($mezclaId <= 0) {
                    continue;
                }

                $mezclaModelo = $solicitud->mezclas->firstWhere('id', $mezclaId);
                if (!$mezclaModelo) {
                    continue;
                }

                $setInfusion = !empty($payload['set_infusion']);
                $infusorId   = !empty($payload['infusor_id']) ? (int) $payload['infusor_id'] : null;

                if ($setInfusion && $infusorId) {
                    throw new \Exception("Mezcla existente (ID {$mezclaId}): selecciona set de infusión o un infusor, no ambos.");
                }

                if ($infusorId) {
                    $infusor = DB::table('infusors')
                        ->where('id', $infusorId)
                        ->where('is_active', true)
                        ->first();

                    if (!$infusor) {
                        throw new \Exception("Mezcla existente (ID {$mezclaId}): el infusor seleccionado no existe o no está activo.");
                    }

                    $hayMedQueRequiereInfusor = $mezclaModelo->medicamentos->contains(function ($mm) {
                        if (!is_null($mm->requires_infusor_snapshot)) {
                            return (int) $mm->requires_infusor_snapshot === 1;
                        }

                        return (int) (optional(optional($mm->medicamentoOnco)->catalog)->requires_infusor ?? 0) === 1;
                    });

                    if (!$hayMedQueRequiereInfusor) {
                        throw new \Exception("Mezcla existente (ID {$mezclaId}): para seleccionar un infusor, la mezcla debe contener al menos un medicamento que lo admita.");
                    }
                }

                $mezclaModelo->update([
                    'set_infusion' => $setInfusion,
                    'infusor_id'   => $infusorId,
                ]);
            }

            // =====================================================
            // Crear nuevas mezclas + consumir inventario
            // =====================================================
            $listaId = $this->currentMedicineListId();
            if (!$listaId) {
                throw new \Exception('Tu hospital no tiene una lista de medicamentos configurada.');
            }

            $catalogosPermitidos = DB::table('medicine_list_presentation as mlp')
                ->join('medicine_presentations as mp', 'mp.id', '=', 'mlp.medicine_presentation_id')
                ->where('mlp.medicine_list_id', $listaId)
                ->where('mp.is_available', 1)
                ->pluck('mp.catalog_id')
                ->map(fn($x) => (int) $x)
                ->unique()
                ->values()
                ->toArray();

            if (count($catalogosPermitidos) === 0) {
                throw new \Exception('La lista de medicamentos del hospital está vacía.');
            }

            $listaInfoPorCatalogo = DB::table('medicine_list_presentation as mlp')
                ->join('medicine_presentations as mp', 'mp.id', '=', 'mlp.medicine_presentation_id')
                ->where('mlp.medicine_list_id', $listaId)
                ->where('mp.is_available', 1)
                ->get([
                    'mp.catalog_id',
                    'mlp.charge_by',
                    'mlp.precio',
                    'mlp.precio_mg_override',
                ])
                ->groupBy('catalog_id')
                ->map(function ($rows) {
                    $first = $rows->first();
                    return [
                        'charge_by' => $first?->charge_by,
                        'precio'    => $first?->precio,
                        'precio_mg_override' => $first?->precio_mg_override,
                    ];
                });

            foreach ($nuevas as $index => $mezclaData) {
                $volumen = isset($mezclaData['volumen_dilucion']) ? (float) $mezclaData['volumen_dilucion'] : null;
                $tiempo  = $mezclaData['tiempo_infusion'] ?? null;

                if ($volumen === null || $volumen <= 0) {
                    throw new \Exception("La nueva mezcla #" . ($index + 1) . " requiere un volumen de dilución válido (> 0).");
                }

                if ($tiempo === null || $tiempo === '') {
                    throw new \Exception("La nueva mezcla #" . ($index + 1) . " requiere un tiempo de infusión.");
                }

                $setInfusion = !empty($mezclaData['set_infusion']);
                $infusorId   = !empty($mezclaData['infusor_id']) ? (int) $mezclaData['infusor_id'] : null;

                if ($setInfusion && $infusorId) {
                    throw new \Exception("Nueva mezcla #" . ($index + 1) . ": selecciona set de infusión o un infusor, no ambos.");
                }

                $meds = $mezclaData['medicamentos'] ?? [];
                if (!is_array($meds) || count($meds) === 0) {
                    throw new \Exception("La nueva mezcla #" . ($index + 1) . " no contiene medicamentos.");
                }

                $diluyenteRef = null;
                $viaRef = null;
                $hayMedQueRequiereInfusor = false;
                $mcPorCatalogId = [];

                foreach ($meds as $i => $medicamento) {
                    $catalogId = (int) ($medicamento['medicamento_id'] ?? 0);
                    if ($catalogId <= 0) {
                        throw new \Exception("Nueva mezcla #" . ($index + 1) . ": medicamento inválido.");
                    }

                    if (!in_array($catalogId, $catalogosPermitidos, true)) {
                        throw new \Exception("Nueva mezcla #" . ($index + 1) . ": el medicamento (catálogo {$catalogId}) no pertenece a la lista del hospital.");
                    }

                    $diluyente = $medicamento['diluyente_id'] ?? null;
                    $via       = $medicamento['via_administracion_id'] ?? null;

                    if ($i === 0) {
                        $diluyenteRef = $diluyente;
                        $viaRef       = $via;
                    } else {
                        if ((string) $diluyente !== (string) $diluyenteRef || (string) $via !== (string) $viaRef) {
                            throw new \Exception("Nueva mezcla #" . ($index + 1) . ": todos los medicamentos deben tener el mismo diluyente y la misma vía de administración.");
                        }
                    }

                    $mc = DB::table('medicines_catalog as mc')
                        ->where('mc.id', $catalogId)
                        ->select('mc.id', 'mc.denominacion', 'mc.conc_min', 'mc.conc_max', 'mc.requires_infusor')
                        ->first();

                    if (!$mc) {
                        throw new \Exception("No se encontró información del catálogo para el medicamento ID {$catalogId}.");
                    }

                    $mcPorCatalogId[$catalogId] = $mc;

                    if ((int) $mc->requires_infusor === 1) {
                        $hayMedQueRequiereInfusor = true;
                    }
                }

                if ($infusorId) {
                    $infusor = DB::table('infusors')
                        ->where('id', $infusorId)
                        ->where('is_active', true)
                        ->first();

                    if (!$infusor) {
                        throw new \Exception("Nueva mezcla #" . ($index + 1) . ": el infusor seleccionado no existe o no está activo.");
                    }

                    if (!$hayMedQueRequiereInfusor) {
                        throw new \Exception("Nueva mezcla #" . ($index + 1) . ": para seleccionar un infusor, la mezcla debe contener al menos un medicamento que lo admita.");
                    }
                }

                $mezcla = Mezcla::create([
                    'solicitud_id'     => $solicitud->id,
                    'volumen_dilucion' => $volumen,
                    'tiempo_infusion'  => $tiempo,
                    'estado'           => 'pendiente',
                    'set_infusion'     => $setInfusion,
                    'infusor_id'       => $infusorId,
                ]);

                foreach ($meds as $medicamento) {
                    $catalogId = (int) ($medicamento['medicamento_id'] ?? 0);

                    $medicineOnco = MedicineOnco::firstOrCreate(
                        ['catalog_id' => $catalogId],
                        ['precio' => 0]
                    );

                    $mc = $mcPorCatalogId[$catalogId] ?? null;
                    if (!$mc) {
                        throw new \Exception("No se encontró información del catálogo para el medicamento ID {$catalogId}.");
                    }

                    $dosis = isset($medicamento['dosis']) ? (float) $medicamento['dosis'] : 0;
                    if ($dosis <= 0) {
                        throw new \Exception("La dosis debe ser mayor a 0 (catálogo {$catalogId}).");
                    }

                    $conc = $volumen > 0 ? $dosis / $volumen : 0;
                    $concMin = (float) ($mc->conc_min ?? 0);
                    $concMax = (float) ($mc->conc_max ?? 0);

                    if (($concMin > 0 || $concMax > 0) && ($conc < $concMin || $conc > $concMax)) {
                        throw new \Exception(
                            "La concentración de '{$mc->denominacion}' está fuera del rango permitido ({$concMin} - {$concMax}). Dosis: {$dosis}, Volumen: {$volumen}."
                        );
                    }

                    $chargeBy = $medicamento['charge_by'] ?? ($listaInfoPorCatalogo[$catalogId]['charge_by'] ?? null) ?? 'mg';
                    $chargeBy = strtolower(trim((string) $chargeBy));
                    if (!in_array($chargeBy, ['mg', 'frasco', 'pieza'], true)) {
                        $chargeBy = 'mg';
                    }
                    if ($chargeBy === 'pieza') {
                        $chargeBy = 'frasco';
                    }

                    MezclaMedicamento::create([
                        'mezcla_id'                  => $mezcla->id,
                        'medicamento_id'             => $medicineOnco->id,
                        'nombre_medicamento'         => $medicamento['nombre'] ?? null,

                        'dosis'                      => $dosis,
                        'dosis_ml'                   => null,

                        'diluyente_id'               => $medicamento['diluyente_id'] ?? null,
                        'via_administracion_id'      => $medicamento['via_administracion_id'] ?? null,

                        'charge_by'                  => $chargeBy,
                        'precio_mg_snapshot'         => null,

                        'denominacion_snapshot'      => $mc->denominacion ?? ($catalog->denominacion ?? null),
                        'marca_snapshot'             => null,
                        'requires_infusor_snapshot'  => (int) ($mc->requires_infusor ?? ($catalog->requires_infusor ?? 0)),
                        'conc_min_snapshot'          => $mc->conc_min ?? ($catalog->conc_min ?? null),
                        'conc_max_snapshot'          => $mc->conc_max ?? ($catalog->conc_max ?? null),
                    ]);
                }
            }

            DB::commit();

            return redirect()
                ->route('admin.oncologicos.solicitudes.index')
                ->with('success', 'Solicitud actualizada correctamente.');
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withErrors(['error' => $e->getMessage()])->withInput();
        }
    }

    public function cancelar(SolicitudOnco $solicitud)
    {
        DB::beginTransaction();

        try {
            $user = auth()->user();

            if ($user->hasAnyRole(['Cliente', 'Institucion'])) {
                if ($solicitud->estado !== 'pendiente') {
                    throw new \Exception('Solo puedes cancelar solicitudes que aún estén pendientes.');
                }

                $solicitud->estado = 'cancelada';
            } else {
                if ($solicitud->estado === 'finalizada') {
                    throw new \Exception('No se puede rechazar una solicitud finalizada.');
                }

                $solicitud->estado = 'no_aprobada';
            }

            $solicitud->save();

            DB::commit();

            session()->flash('swal', [
                'title' => 'Solicitud actualizada',
                'text' => $solicitud->estado === 'cancelada'
                    ? 'La solicitud fue cancelada correctamente.'
                    : 'La solicitud fue marcada como no aprobada.',
                'icon' => 'success',
            ]);

            return redirect()->route('admin.oncologicos.solicitudes.index');
        } catch (\Throwable $e) {
            DB::rollBack();

            return redirect()->back()->withErrors([
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function solicitud(SolicitudOnco $solicitud)
    {
        $solicitud = SolicitudOnco::with([
            'hospital',
            'user.hospital',
            'mezclas',
            'mezclas.medicamentos',
            'mezclas.medicamentos.medicamentoOnco.catalog',
            'mezclas.medicamentos.diluyente',
            'mezclas.medicamentos.viaAdministracion',
        ])->findOrFail($solicitud->id);

        $solicitud->mezclas->each(function ($mezcla) {
            $mezcla->medicamentos->each(function ($mm) {
                $mm->denominacion_doc = $mm->denominacion_snapshot
                    ?? optional(optional($mm->medicamentoOnco)->catalog)->denominacion
                    ?? $mm->nombre_medicamento
                    ?? '—';

                $mm->marca_doc = $mm->marca_snapshot ?? '—';

                $mm->requires_infusor_doc = !is_null($mm->requires_infusor_snapshot)
                    ? (int) $mm->requires_infusor_snapshot
                    : (int) (optional(optional($mm->medicamentoOnco)->catalog)->requires_infusor ?? 0);

                $mm->conc_min_doc = $mm->conc_min_snapshot
                    ?? optional(optional($mm->medicamentoOnco)->catalog)->conc_min;

                $mm->conc_max_doc = $mm->conc_max_snapshot
                    ?? optional(optional($mm->medicamentoOnco)->catalog)->conc_max;
            });
        });

        $hospitalDoc = optional($solicitud->hospital)->name
            ?? optional(optional($solicitud->user)->hospital)->name
            ?? '—';

        $pdf = Pdf::loadView('pdfs.oncologicos.solicitud', [
            'solicitud'   => $solicitud,
            'hospitalDoc' => $hospitalDoc,
        ]);

        return $pdf->stream("solicitud-{$solicitud->id}.pdf");
    }

    public function envio(SolicitudOnco $solicitud)
    {
        $solicitud_onco = SolicitudOnco::with([
            'hospital',
            'user.hospital',
            'mezclas',
            'mezclas.medicamentos',
            'mezclas.medicamentos.presentacionesUsadas.batch.presentation',
            'mezclas.medicamentos.medicamentoOnco.catalog',
            'mezclas.medicamentos.diluyente',
            'mezclas.medicamentos.viaAdministracion',
        ])->findOrFail($solicitud->id);

        $solicitud_onco->mezclas->each(function ($mezcla) {
            $mezcla->medicamentos->each(function ($mm) {
                $mm->denominacion_doc = $mm->denominacion_snapshot
                    ?? optional(optional($mm->medicamentoOnco)->catalog)->denominacion
                    ?? $mm->nombre_medicamento
                    ?? '—';

                $mm->marca_doc = $mm->marca_snapshot ?? '—';

                $mm->requires_infusor_doc = !is_null($mm->requires_infusor_snapshot)
                    ? (int) $mm->requires_infusor_snapshot
                    : (int) (optional(optional($mm->medicamentoOnco)->catalog)->requires_infusor ?? 0);

                $mm->conc_min_doc = $mm->conc_min_snapshot
                    ?? optional(optional($mm->medicamentoOnco)->catalog)->conc_min;

                $mm->conc_max_doc = $mm->conc_max_snapshot
                    ?? optional(optional($mm->medicamentoOnco)->catalog)->conc_max;

                $firstUsed = $mm->presentacionesUsadas->first();
                $presentation = optional(optional($firstUsed)->batch)->presentation;
                $mm->legend_proteccion_doc = trim((string) (optional($presentation)->legend ?? ''));
            });
        });

        $hospitalDoc = optional($solicitud_onco->hospital)->name
            ?? optional(optional($solicitud_onco->user)->hospital)->name
            ?? '—';

        $fechaEnvio = now()->format('d/m/Y H:i');

        $pdf = Pdf::loadView('pdfs.oncologicos.envio', [
            'solicitud'   => $solicitud_onco,
            'mezclas'     => $solicitud_onco->mezclas,
            'fechaEnvio'  => $fechaEnvio,
            'hospitalDoc' => $hospitalDoc,
        ])->setPaper('letter', 'portrait');

        return $pdf->stream("envio-{$solicitud_onco->id}.pdf");
    }

    public function remision(SolicitudOnco $solicitud)
    {
        $solicitud_onco = SolicitudOnco::with([
            'hospital',
            'user:id,hospital_id,name,lastname',

            'mezclas.infusor',
            'mezclas.medicamentos',
            'mezclas.medicamentos.presentacionesUsadas', // snapshots por renglón

            // fallback viejo:
            'mezclas.medicamentos.medicamentoOnco.catalog',
            'mezclas.medicamentos.presentacionesUsadas.batch.presentation',

            'mezclas.medicamentos.diluyente',
        ])->findOrFail($solicitud->id);

        // =========================================================
        // ✅ 1) OBTENER LISTA DE MEDICAMENTOS DEL HOSPITAL
        // =========================================================
        $hospitalId = (int) (
            $solicitud_onco->hospital_id
            ?? optional($solicitud_onco->user)->hospital_id
            ?? 0
        );

        $lista = null;
        $distributor = null;
        $listaCharge = 'frasco';

        if ($hospitalId > 0) {
            $listaId = DB::table('hospitals')
                ->where('id', $hospitalId)
                ->value('onco_medicine_list_id');

            $lista = $listaId
                ? DB::table('medicine_lists')->where('id', $listaId)->first()
                : null;

            if ($lista) {
                $listaCharge = $lista->charge_by ?? 'frasco';

                // distributors tiene medicine_list_id
                $distributorRow = DB::table('distributors')
                    ->where('medicine_list_id', $lista->id)
                    ->first();

                // si quieres devolverlo como modelo (opcional):
                // $distributor = $distributorRow ? \App\Models\Oncologicos\Distributor::find($distributorRow->id) : null;

                $distributor = $distributorRow; // suficiente para el PDF (nombre, direccion, logo_path)
            }
        }

        // =========================================================
        // ✅ 2) CONFIG POR PRESENTACIÓN (fallback si no hay snapshot)
        // =========================================================
        $cfgPorPresentacion = collect();

        if ($lista) {
            $cfgPorPresentacion = DB::table('medicine_list_presentation')
                ->where('medicine_list_id', $lista->id)
                ->get()
                ->keyBy('medicine_presentation_id');
        }

        $totalRemision = 0.0;

        foreach ($solicitud_onco->mezclas as $mezcla) {

            // =========================================================
            // 1) CALCULO MEDICAMENTOS (snapshot primero)
            // =========================================================
            foreach ($mezcla->medicamentos as $med) {

                // ✅ Nombre para PDF: snapshot -> catálogo -> nombre_medicamento
                $denom = $med->denominacion_snapshot
                    ?? optional(optional($med->medicamentoOnco)->catalog)->denominacion
                    ?? $med->nombre_medicamento
                    ?? '—';

                $marca = $med->marca_snapshot ?? null;

                $med->setAttribute('denominacion_doc', $denom);
                $med->setAttribute('marca_doc', $marca ?: '—');

                $presentaciones = $med->presentacionesUsadas ?? collect();

                $cantidad    = 0.0;
                $precioUnit  = 0.0;
                $subtotal    = 0.0;
                $unidadCobro = null;

                // ✅ 1A) SIN presentaciones usadas
                if ($presentaciones->isEmpty()) {
                    $unidadCobro = ($listaCharge === 'mg') ? 'mg' : 'frasco';
                    $cantidad    = ($unidadCobro === 'mg') ? (float)($med->dosis ?? 0) : 1;

                    // si no hay presentaciones, normalmente no hay precios/snapshots por frasco
                    // dejamos 0 como tú lo traías (robusto)
                    $precioUnit = 0.0;
                    $subtotal   = 0.0;
                } else {

                    // ✅ 1B) CON presentaciones usadas: usar snapshot de subtotal/precio
                    $sumSubtotal = 0.0;
                    $sumUnidades = 0.0;

                    foreach ($presentaciones as $pu) {
                        $unidades = (float)($pu->unidades_usadas ?? 0);
                        if ($unidades <= 0) $unidades = 1;

                        // ✅ snapshot: subtotal del renglón
                        $sub = $pu->subtotal;
                        if (!is_null($sub)) {
                            $sumSubtotal += (float)$sub;
                            $sumUnidades += $unidades;
                            continue;
                        }

                        // ✅ fallback: precio_frasco_snapshot
                        $precioSnap = $pu->precio_frasco_snapshot;
                        if (!is_null($precioSnap)) {
                            $sumSubtotal += ((float)$precioSnap * $unidades);
                            $sumUnidades += $unidades;
                            continue;
                        }

                        // ✅ fallback final: lista viva por presentationId
                        $pid =
                            optional($pu->batch)->medicine_presentation_id
                            ?? optional(optional($pu->batch)->presentation)->id
                            ?? optional($pu->presentation)->id;

                        $cfgPres = $pid ? $cfgPorPresentacion->get($pid) : null;
                        $precioFrascoLista = (float)($cfgPres->precio ?? 0);

                        $sumSubtotal += ($precioFrascoLista * $unidades);
                        $sumUnidades += $unidades;
                    }

                    // Si hay snapshots por frascos usados, se asume cobro por frasco
                    $unidadCobro = 'frasco';
                    $cantidad    = $sumUnidades;
                    $subtotal    = $sumSubtotal;
                    $precioUnit  = ($sumUnidades > 0) ? ($sumSubtotal / $sumUnidades) : 0.0;
                }

                $precioUnit = round($precioUnit, 4);
                $subtotal   = round($subtotal, 2);

                $med->setAttribute('unidad_cobro', $unidadCobro);
                $med->setAttribute('cantidad_cobro', $cantidad);
                $med->setAttribute('precio_unitario_calculado', $precioUnit);
                $med->setAttribute('subtotal_calculado', $subtotal);

                $totalRemision += $subtotal;
            }

            // =========================================================
            // 2) SUMAR INFUSOR SI APLICA (snapshot primero)
            // =========================================================

            $mezclaUsaInfusor = (bool)($mezcla->set_infusion ?? false) || !empty($mezcla->infusor_id);

            $requierePorRegla = $mezcla->medicamentos->contains(function ($med) {
                if (!is_null($med->requires_infusor_snapshot)) {
                    return (int)$med->requires_infusor_snapshot === 1;
                }
                return (int)(optional(optional($med->medicamentoOnco)->catalog)->requires_infusor ?? 0) === 1;
            });

            $infusorAplica = $mezclaUsaInfusor && $requierePorRegla && !empty($mezcla->infusor);

            if ($infusorAplica) {
                $precioInfusor = (float)($mezcla->infusor->precio ?? 0);

                $mezcla->setAttribute('infusor_aplica', true);
                $mezcla->setAttribute(
                    'infusor_nombre',
                    trim(($mezcla->infusor->nombre_generico ?? '') . ' ' . ($mezcla->infusor->nombre_comercial ?? '')) ?: 'Infusor'
                );
                $mezcla->setAttribute('infusor_precio', round($precioInfusor, 2));
                $mezcla->setAttribute('infusor_subtotal', round($precioInfusor, 2));

                $totalRemision += round($precioInfusor, 2);
            } else {
                $mezcla->setAttribute('infusor_aplica', false);
                $mezcla->setAttribute('infusor_precio', 0);
                $mezcla->setAttribute('infusor_subtotal', 0);
            }
        }

        $pdf = Pdf::loadView('pdfs.oncologicos.remision', [
            'solicitud'     => $solicitud_onco,
            'mezclas'       => $solicitud_onco->mezclas,
            'fechaEmision'  => now(),
            'totalRemision' => round($totalRemision, 2),
            'distributor'   => $distributor, // ✅ ahora viene por lista del hospital
        ])->setPaper('letter', 'portrait');

        return $pdf->stream("remision-{$solicitud_onco->id}.pdf");
    }

    public function exportarExcel()
    {
        return Excel::download(new OncologicosSolicitudesOncoExport, 'solicitudes_oncologicas.xlsx');
    }
}
