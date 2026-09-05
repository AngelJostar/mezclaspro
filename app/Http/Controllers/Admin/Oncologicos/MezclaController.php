<?php

namespace App\Http\Controllers\Admin\Oncologicos;

use App\Http\Controllers\Controller;
use App\Models\Oncologicos\DiluentPresentation;
use App\Models\Oncologicos\InspeccionMezcla;
use App\Models\Oncologicos\MedicineList;
use App\Models\Oncologicos\MedicineOnco;
use App\Models\Oncologicos\MedicinePresentation;
use App\Models\Oncologicos\Mezcla;
use App\Models\Oncologicos\MezclaMedicamento;
use App\Models\Oncologicos\SolicitudOnco;
use App\Services\MedicineRemainderService;
use App\Services\OncologicMedicationInventoryService;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;

class MezclaController extends Controller
{
    private function medicineListColumnFor(?SolicitudOnco $solicitud): string
    {
        return $solicitud?->tipo_solicitud === 'antibioticos'
            ? 'antibiotic_medicine_list_id'
            : 'onco_medicine_list_id';
    }

    private function nombreUsuario($user): string
    {
        $nombreCompleto = trim(($user?->name ?? '') . ' ' . ($user?->lastname ?? ''));

        return $user?->username
            ?: ($nombreCompleto !== '' ? $nombreCompleto : ($user?->email ?? 'Usuario'));
    }

    private function nombreUsuarioParaPdf(?string $nombre): string
    {
        $nombre = trim((string) $nombre);

        if ($nombre === '') {
            return '—';
        }

        $usuario = DB::table('users')
            ->select('username')
            ->where('username', $nombre)
            ->orWhere('name', $nombre)
            ->orWhereRaw("TRIM(CONCAT(COALESCE(name, ''), ' ', COALESCE(lastname, ''))) = ?", [$nombre])
            ->first();

        return $usuario?->username ?: $nombre;
    }

    private function redirectAfterMixtureAction(Mezcla $mezcla, Request $request)
    {
        $returnTo = trim((string) $request->input('return_to', ''));
        $safeReturnTo = '';

        if ($returnTo !== '') {
            if (
                str_starts_with($returnTo, '/')
                && ! str_starts_with($returnTo, '//')
                && (str_starts_with($returnTo, '/admin/') || str_contains($returnTo, '/admin/'))
            ) {
                $safeReturnTo = $returnTo;
            } else {
                $urlParts = parse_url($returnTo) ?: [];
                $path = (string) ($urlParts['path'] ?? '');
                $allowedHosts = array_filter([
                    $request->getHost(),
                    parse_url((string) config('app.url'), PHP_URL_HOST),
                ]);

                if (
                    isset($urlParts['host'])
                    && in_array($urlParts['host'], $allowedHosts, true)
                    && (str_starts_with($path, '/admin/') || str_contains($path, '/admin/'))
                ) {
                    $query = isset($urlParts['query']) ? '?' . $urlParts['query'] : '';
                    $safeReturnTo = $path . $query;
                }
            }
        }

        $isApprovalPopup = $request->boolean('approval_popup');
        $isDispensingPopup = $request->boolean('dispensing_popup');

        if ($isApprovalPopup || $isDispensingPopup) {
            $routeParams = ['mezcla' => $mezcla->id];

            if ($isApprovalPopup) {
                $routeParams['approval'] = 1;
                $routeParams['approval_popup'] = 1;
            }

            if ($isDispensingPopup) {
                $routeParams['modo'] = 'dispensacion';
                $routeParams['dispensing_popup'] = 1;
            }

            if ($safeReturnTo !== '') {
                $routeParams['return_to'] = $safeReturnTo;
            }

            return redirect()
                ->route('admin.oncologicos.mezclas.edit', $routeParams)
                ->with($isDispensingPopup ? 'dispensing_popup_done' : 'approval_popup_done', true)
                ->with($isDispensingPopup ? 'dispensing_popup_return_to' : 'approval_popup_return_to', $safeReturnTo ?: route('admin.solicitudes.index'));
        }

        if ($safeReturnTo !== '') {
            return redirect()->to($safeReturnTo);
        }

        return redirect()->route('admin.oncologicos.mezclas.index', $mezcla->solicitud->id);
    }

    private function fallbackBatchForList(object $currentBatch, int $unidades, int $laboratoryId, int $listaId, int $catalogId): ?object
    {
        $list = DB::table('medicine_lists')
            ->where('id', $listaId)
            ->first(['warehouse_id', 'backup_enabled', 'backup_warehouse_id']);
        $backupWarehouseId = (int) ($list?->backup_warehouse_id ?? 0);

        if (! $list || ! $list->backup_enabled || $backupWarehouseId <= 0
            || (int) ($currentBatch->warehouse_id ?? 0) !== (int) ($list->warehouse_id ?? 0)) {
            return null;
        }

        return DB::table('medicine_batches as mb')
            ->join('medicine_presentations as mp', 'mp.id', '=', 'mb.medicine_presentation_id')
            ->join('medicine_list_presentation as mlp', function ($join) use ($listaId) {
                $join->on('mlp.medicine_presentation_id', '=', 'mp.id')->where('mlp.medicine_list_id', $listaId);
            })
            ->where('mb.laboratory_id', $laboratoryId)
            ->where('mb.warehouse_id', $backupWarehouseId)
            ->where('mb.medicine_presentation_id', $currentBatch->medicine_presentation_id)
            ->where('mp.catalog_id', $catalogId)
            ->where('mp.is_available', 1)
            ->where('mb.stock_actual', '>=', $unidades)
            ->where(function ($query) {
                $query->whereNull('mb.caducidad')->orWhereDate('mb.caducidad', '>=', now()->toDateString());
            })
            ->orderByRaw('mb.caducidad IS NULL')
            ->orderBy('mb.caducidad')
            ->orderBy('mb.id')
            ->lockForUpdate()
            ->select('mb.id', 'mb.warehouse_id', 'mb.lote', 'mb.caducidad', 'mb.stock_actual', 'mb.stock_ml_actual', 'mb.stock_reservado',
                'mb.medicine_presentation_id', 'mp.id as presentation_id', 'mp.catalog_id', 'mp.presentacion',
                'mp.cantidad_medicamento', 'mp.volumen_diluyente', 'mp.legend', 'mp.marca', 'mp.precio_frasco',
                'mlp.precio as precio_lista')
            ->first();
    }

    public function index($id)
    {
        $solicitud = SolicitudOnco::with([
            'hospital.instituciones',
            'mezclas' => fn($q) => $q
                ->with(['medicamentos.medicamentoOnco.catalog'])
                ->orderBy('id'),
        ])->findOrFail($id);

        return view('admin.oncologicos.mezclas.index', compact('solicitud'));
    }


    private function releaseBatchConsumptionForMix(
        int $medicineBatchId,
        int $unidades,
        float $volumenAbiertoMl,
        int $laboratoryId,
        int $mezclaId,
        ?int $userId = null
    ): void {
        if ($medicineBatchId <= 0 || $unidades <= 0) {
            return;
        }

        $batch = DB::table('medicine_batches')
            ->where('id', $medicineBatchId)
            ->where('laboratory_id', $laboratoryId)
            ->lockForUpdate()
            ->first();

        if (!$batch) {
            throw new \Exception("No se encontró el lote {$medicineBatchId} para devolver inventario.");
        }

        $stockActual = (int) ($batch->stock_actual ?? 0);
        $stockMlActual = (float) ($batch->stock_ml_actual ?? 0);
        $nuevoStock  = $stockActual + $unidades;
        $nuevoStockMl = $stockMlActual + max(0, $volumenAbiertoMl);

        DB::table('medicine_batches')
            ->where('id', $medicineBatchId)
            ->update([
                'stock_actual' => $nuevoStock,
                'stock_ml_actual' => $nuevoStockMl,
                'is_active'    => $nuevoStock > 0 ? 1 : 0,
                'updated_at'   => now(),
            ]);

        DB::table('medicine_batch_movements')->insert([
            'medicine_batch_id'      => $medicineBatchId,
            'laboratory_id'          => $laboratoryId,
            'warehouse_id'           => $batch->warehouse_id,
            'user_id'                => $userId,
            'movement_type'          => 'cancelacion_salida',
            'quantity'               => $unidades,
            'quantity_ml'            => max(0, $volumenAbiertoMl),
            'stock_actual_before'    => $stockActual,
            'stock_actual_after'     => $nuevoStock,
            'stock_ml_before'        => $stockMlActual,
            'stock_ml_after'         => $nuevoStockMl,
            'stock_reservado_before' => (int) ($batch->stock_reservado ?? 0),
            'stock_reservado_after'  => (int) ($batch->stock_reservado ?? 0),
            'reference_type'         => 'mezcla',
            'reference_id'           => $mezclaId,
            'notes'                  => 'Devolución de inventario por edición de mezcla.',
            'created_at'             => now(),
            'updated_at'             => now(),
        ]);
    }

    private function consumeDiluentPresentationForMix(
        int $diluentPresentationId,
        int $laboratoryId,
        int $mezclaId,
        ?int $userId = null
    ): void {
        if ($diluentPresentationId <= 0) {
            return;
        }

        $alreadyConsumed = DB::table('diluent_stock_movements')
            ->where('reference_type', 'mezcla')
            ->where('reference_id', $mezclaId)
            ->where('movement_type', 'salida')
            ->exists();

        if ($alreadyConsumed) {
            return;
        }

        $presentation = DB::table('diluent_presentations')
            ->where('id', $diluentPresentationId)
            ->where('is_active', 1)
            ->where(function ($query) use ($laboratoryId) {
                $query->whereNull('laboratory_id')
                    ->orWhere('laboratory_id', $laboratoryId);
            })
            ->lockForUpdate()
            ->first();

        if (!$presentation) {
            throw new \Exception('La presentacion de diluyente seleccionada no existe o no pertenece al laboratorio.');
        }

        if ($presentation->laboratory_id === null) {
            return;
        }

        $stockActual = (float) ($presentation->stock_actual ?? 0);

        if ($stockActual < 1) {
            throw new \Exception("Stock insuficiente para el diluyente {$presentation->presentacion}. Disponible: {$stockActual}, solicitado: 1.");
        }

        $nuevoStock = $stockActual - 1;

        DB::table('diluent_presentations')
            ->where('id', $presentation->id)
            ->update([
                'stock_actual' => $nuevoStock,
                'is_active' => $nuevoStock > 0 ? 1 : 0,
                'updated_at' => now(),
            ]);

        DB::table('diluent_stock_movements')->insert([
            'diluent_presentation_id' => $presentation->id,
            'laboratory_id' => $laboratoryId,
            'warehouse_id' => $presentation->warehouse_id,
            'user_id' => $userId,
            'movement_type' => 'salida',
            'quantity' => 1,
            'stock_actual_before' => $stockActual,
            'stock_actual_after' => $nuevoStock,
            'reference_type' => 'mezcla',
            'reference_id' => $mezclaId,
            'notes' => 'Consumo de diluyente por aprobacion de mezcla.',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function resolveDiluentPresentationByFefo(
        int $diluentId,
        float $requiredVolumeMl,
        ?int $laboratoryId
    ): ?int {
        if ($diluentId <= 0) {
            return null;
        }

        $query = DB::table('diluent_presentations')
            ->where('diluent_id', $diluentId)
            ->where('is_active', 1)
            ->where('stock_actual', '>', 0)
            ->where(function ($q) {
                $q->whereNull('caducidad')
                    ->orWhereDate('caducidad', '>=', now()->toDateString());
            });

        $query->where(function ($q) use ($laboratoryId) {
            $q->whereNull('laboratory_id');

            if (!empty($laboratoryId)) {
                $q->orWhere('laboratory_id', $laboratoryId);
            }
        });

        if ($requiredVolumeMl > 0) {
            $query->where('volume_ml', '>=', $requiredVolumeMl);
        }

        $presentation = $query
            ->orderBy('volume_ml')
            ->orderByRaw('caducidad IS NULL ASC')
            ->orderBy('caducidad')
            ->first(['id']);

        return $presentation ? (int) $presentation->id : null;
    }

    private function consumeBatchForMix(
        int $batchId,
        int $unidades,
        int $laboratoryId,
        int $listaId,
        int $catalogId,
        int $mezclaId,
        ?int $userId = null,
        ?float $precioFrascoOverride = null
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
                'mb.warehouse_id',
                'mb.lote',
                'mb.caducidad',
                'mb.stock_actual',
                'mb.stock_ml_actual',
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
            $fallbackBatch = $this->fallbackBatchForList($batch, $unidades, $laboratoryId, $listaId, $catalogId);
            if (! $fallbackBatch) {
                throw new \Exception("Stock insuficiente para el lote {$batch->lote}. Disponible: {$stockActual}, solicitado: {$unidades}.");
            }
            $batch = $fallbackBatch;
            $stockActual = (int) $batch->stock_actual;
        }

        $nuevoStock = $stockActual - $unidades;
        $mlPorFrasco = (float) ($batch->volumen_diluyente ?? 0);
        $stockMlAntes = (float) ($batch->stock_ml_actual ?? ($stockActual * $mlPorFrasco));
        $cantidadMl = round($unidades * $mlPorFrasco, 2);
        $stockMlDespues = max(0, round($stockMlAntes - $cantidadMl, 2));

        DB::table('medicine_batches')
            ->where('id', $batch->id)
            ->update([
                'stock_actual' => $nuevoStock,
                'stock_ml_actual' => $stockMlDespues,
                'is_active'    => $nuevoStock > 0 ? 1 : 0,
                'updated_at'   => now(),
            ]);

        DB::table('medicine_batch_movements')->insert([
            'medicine_batch_id'      => $batch->id,
            'laboratory_id'          => $laboratoryId,
            'warehouse_id'           => $batch->warehouse_id,
            'user_id'                => $userId,
            'movement_type'          => 'salida',
            'quantity'               => $unidades,
            'quantity_ml'            => $cantidadMl,
            'stock_actual_before'    => $stockActual,
            'stock_actual_after'     => $nuevoStock,
            'stock_ml_before'        => $stockMlAntes,
            'stock_ml_after'         => $stockMlDespues,
            'stock_reservado_before' => (int) ($batch->stock_reservado ?? 0),
            'stock_reservado_after'  => (int) ($batch->stock_reservado ?? 0),
            'reference_type'         => 'mezcla',
            'reference_id'           => $mezclaId,
            'notes'                  => 'Consumo de inventario por edición de mezcla.',
            'created_at'             => now(),
            'updated_at'             => now(),
        ]);

        $precioFrasco = $precioFrascoOverride !== null
            ? (float) $precioFrascoOverride
            : (float) ($batch->precio_lista ?? $batch->precio_frasco ?? 0);

        return (object) [
            'batch_id'             => (int) $batch->id,
            'lote'                 => $batch->lote,
            'caducidad'            => $batch->caducidad,
            'presentation_id'      => (int) $batch->presentation_id,
            'presentacion'         => $batch->presentacion,
            'cantidad_medicamento' => $batch->cantidad_medicamento,
            'volumen_diluyente'    => $batch->volumen_diluyente,
            'legend'               => $batch->legend,
            'marca'                => $batch->marca,
            'precio_frasco'        => $precioFrasco,
            'subtotal'             => $precioFrasco * $unidades,
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
        $dosisPendienteMg = (float) $mezclaMedicamento->dosis;
        $marcaElegida = null;
        $alMenosUna = false;

        foreach ($presentacionesPayload as $pres) {
            $batchId  = isset($pres['batch_id']) ? (int) $pres['batch_id'] : 0;
            $unidades = isset($pres['frascos']) ? (int) $pres['frascos'] : 0;

            if ($batchId <= 0 || $unidades < 0) {
                continue;
            }

            $alMenosUna = true;

            if ($dosisPendienteMg <= 0.0001) {
                break;
            }

            $batchConsumido = app(OncologicMedicationInventoryService::class)->consume(
                $batchId,
                $unidades,
                $laboratoryId,
                $listaId,
                $catalogId,
                $mezclaId,
                $dosisPendienteMg,
                $userId,
                isset($pres['precio_frasco']) && $pres['precio_frasco'] !== ''
                    ? (float) $pres['precio_frasco']
                    : null,
                $mezclaMedicamento->charge_by
            );

            $marcaActual = trim((string) ($batchConsumido->marca ?? ''));

            if ($marcaActual !== '') {
                if ($marcaElegida === null) {
                    $marcaElegida = $marcaActual;
                } elseif (strcasecmp($marcaElegida, $marcaActual) !== 0) {
                    throw new \Exception(
                        "No puedes combinar marcas diferentes para el mismo medicamento. Marca seleccionada: {$marcaElegida}, marca detectada: {$marcaActual}."
                    );
                }
            }

            DB::table('mezcla_medicamento_presentaciones')->insert([
                'mezcla_medicamento_id'         => $mezclaMedicamento->id,
                'medicine_batch_id'             => $batchConsumido->batch_id,
                'unidades_usadas'               => $batchConsumido->charge_by === 'frasco'
                    ? $batchConsumido->billable_containers
                    : 0,
                'unidades_abiertas'             => $batchConsumido->opened_containers,
                'volumen_usado_ml'              => $batchConsumido->used_ml,
                'charge_by_snapshot'            => $batchConsumido->charge_by,

                'presentacion_snapshot'         => $batchConsumido->presentacion,
                'cantidad_medicamento_snapshot' => $batchConsumido->cantidad_medicamento,
                'volumen_diluyente_snapshot'    => $batchConsumido->volumen_diluyente,
                'legend_snapshot'               => $batchConsumido->legend,

                'lote_usado'                    => $batchConsumido->lote,
                'caducidad_usada'               => $batchConsumido->caducidad,
                'precio_frasco_snapshot'        => $batchConsumido->precio_frasco,
                'precio_unitario_snapshot'      => $batchConsumido->unit_price,
                'subtotal'                      => $batchConsumido->subtotal,
                'created_at'                    => now(),
                'updated_at'                    => now(),
            ]);

            $totalMlAportado += (float) $batchConsumido->used_ml;
            $dosisPendienteMg = max(0, $dosisPendienteMg - (float) $batchConsumido->dose_provided_mg);
        }

        if (!$alMenosUna) {
            throw new \Exception(
                "Debes seleccionar al menos una presentación/lote con unidades mayores a 0 para el medicamento del catálogo {$catalogId}."
            );
        }

        if ($dosisPendienteMg > 0.01) {
            throw new \Exception("Las presentaciones seleccionadas no cubren la dosis. Faltan " . round($dosisPendienteMg, 4) . " mg.");
        }

        return [
            'dosis_ml'       => $totalMlAportado > 0 ? round($totalMlAportado, 2) : null,
            'marca_snapshot' => $marcaElegida,
        ];
    }

    public function show($id)
    {
        $user = auth()->user();

        $mezcla = Mezcla::with([
            'solicitud.hospital',
            'medicamentos',
            'medicamentos.presentacionesUsadas.batch.presentation',
        ])->findOrFail($id);

        $esAdmin = $user->hasAnyRole(['Admin', 'Super Admin']);

        $hospitalSolicitudId = (int) ($mezcla->solicitud?->hospital_id ?? 0);
        $hospitalUserId      = (int) ($user->hospital_id ?? 0);

        if (
            !$esAdmin &&
            $hospitalUserId &&
            $hospitalSolicitudId &&
            $hospitalUserId !== $hospitalSolicitudId
        ) {
            abort(403, 'No autorizado para ver mezclas de otro hospital.');
        }

        $listaId = null;
        if ($hospitalSolicitudId > 0) {
            $listaId = DB::table('hospitals')
                ->where('id', $hospitalSolicitudId)
                ->value($this->medicineListColumnFor($mezcla->solicitud));
        }

        // En tu mezcla_medicamentos.medicamento_id guardas medicine_oncos.id
        $medIds = $mezcla->medicamentos->pluck('medicamento_id')->filter()->unique()->values();

        // Map: [medicine_oncos.id => catalog_id]
        $catalogIdPorMedicineOnco = collect();
        if ($medIds->isNotEmpty()) {
            $catalogIdPorMedicineOnco = DB::table('medicine_oncos as mo')
                ->whereIn('mo.id', $medIds)
                ->pluck('mo.catalog_id', 'mo.id');
        }

        // ==========================================================
        // ✅ Fallback masivo de presentaciones (evita N+1)
        // ==========================================================
        $presentacionFallbackPorMM = DB::table('mezcla_medicamento_presentaciones as mmp')
            ->join('medicine_batches as mb', 'mb.id', '=', 'mmp.medicine_batch_id')
            ->join('medicine_presentations as mp', 'mp.id', '=', 'mb.medicine_presentation_id')
            ->whereIn('mmp.mezcla_medicamento_id', $mezcla->medicamentos->pluck('id')->all())
            ->orderBy('mmp.id')
            ->get([
                'mmp.mezcla_medicamento_id as mm_id',
                'mp.presentacion as presentacion',
                'mp.id as presentation_id',
                'mp.catalog_id as catalog_id',
            ])
            ->groupBy('mm_id')
            ->map(function ($rows) {
                $first = $rows->first();
                return [
                    'presentacion'    => $first?->presentacion ? trim((string)$first->presentacion) : null,
                    'presentation_id' => $first?->presentation_id ? (int)$first->presentation_id : null,
                    'catalog_id'      => $first?->catalog_id ? (int)$first->catalog_id : null,
                ];
            });

        // ==========================================================
        // ✅ Presentaciones permitidas por la lista (solo si hay lista)
        // ==========================================================
        $presentacionesPermitidas = collect(); // [presentation_id => true]
        if ($listaId) {
            $presentacionesPermitidas = DB::table('medicine_list_presentation')
                ->where('medicine_list_id', $listaId)
                ->pluck('medicine_presentation_id')
                ->mapWithKeys(fn($pid) => [(int)$pid => true]);
        }

        $infoAdicional = [];

        foreach ($mezcla->medicamentos as $mm) {

            $medicineOncoId = $mm->medicamento_id; // medicine_oncos.id o null
            $catalogId = $medicineOncoId ? (int) ($catalogIdPorMedicineOnco->get($medicineOncoId) ?? 0) : 0;

            // ✅ Nombre mostrado: snapshot primero
            $denomSnap = trim((string) ($mm->denominacion_snapshot ?? ''));
            $marcaSnap = trim((string) ($mm->marca_snapshot ?? ''));

            $fallbackNombre = trim((string) ($mm->nombre_medicamento ?? 'Medicamento'));

            $nombreFinal = '—';
            if ($denomSnap !== '') {
                $nombreFinal = $marcaSnap !== '' ? "{$denomSnap} ({$marcaSnap})" : $denomSnap;
            } elseif ($fallbackNombre !== '') {
                $nombreFinal = $fallbackNombre;
            }

            // ✅ Presentación: snapshot primero desde presentacionesUsadas
            $presentacionSnap = null;
            $presentationIdUsada = null;

            $firstUsed = ($mm->presentacionesUsadas ?? collect())->first();
            if ($firstUsed) {
                $presentacionSnap = trim((string) ($firstUsed->presentacion_snapshot ?? ''));
            }

            // fallback masivo
            if (!$presentacionSnap) {
                $fb = $presentacionFallbackPorMM->get($mm->id);
                $presentacionSnap = $fb['presentacion'] ?? null;
                $presentationIdUsada = $fb['presentation_id'] ?? null;
            }

            // ✅ Diluyentes/vías (catálogo vivo, solo para opciones)
            $diluyentes = collect();
            $vias = collect();

            if ($catalogId > 0) {
                $diluyentes = DB::table('diluent_medicine_catalog as dmc')
                    ->join('diluents as d', 'dmc.diluent_id', '=', 'd.id')
                    ->where('dmc.medicine_catalog_id', $catalogId)
                    ->select('d.id', DB::raw('d.denominacion_generica as name'))
                    ->get();

                $vias = DB::table('administration_route_medicine_catalog as armc')
                    ->join('administration_routes as ar', 'armc.administration_route_id', '=', 'ar.id')
                    ->where('armc.medicine_catalog_id', $catalogId)
                    ->select('ar.id', 'ar.name')
                    ->get();
            }

            // ✅ Valores técnicos snapshot
            $requiresInfusor = (int) ($mm->requires_infusor_snapshot ?? 0);
            $concMin = $mm->conc_min_snapshot;
            $concMax = $mm->conc_max_snapshot;

            // ✅ Extra UI/debug: si hay lista, marcar si la presentación usada pertenece a la lista
            $presentacionEnLista = null;
            if ($listaId && $presentationIdUsada) {
                $presentacionEnLista = (bool) ($presentacionesPermitidas[(int)$presentationIdUsada] ?? false);
            }

            $infoAdicional[$mm->id] = [
                'denominacion' => $nombreFinal,
                'presentacion' => $presentacionSnap ?: '—',
                'diluyentes'   => $diluyentes,
                'vias'         => $vias,
                'requires_infusor' => $requiresInfusor,
                'conc_min'          => $concMin,
                'conc_max'          => $concMax,
                'medicine_list_id'      => $listaId,
                'presentacion_en_lista' => $presentacionEnLista,
            ];
        }

        return view('admin.oncologicos.mezclas.show', compact('mezcla', 'infoAdicional'));
    }


    public function edit($id)
    {
        $user = Auth::user();

        $mezcla = Mezcla::with([
            'solicitud.hospital.instituciones',
            'infusor',
            'diluentPresentation.diluent',
            'medicamentos.medicamentoOnco.catalog',
            'medicamentos.diluyente',
            'medicamentos.viaAdministracion',
            'medicamentos.presentacionesUsadas.batch.presentation',
        ])->findOrFail($id);

        $solicitud = $mezcla->solicitud;

        $esAdmin = $user->hasAnyRole(['Admin', 'Super Admin']);

        $hospitalSolicitudId = (int) ($solicitud?->hospital_id ?? 0);
        $hospitalUserId      = (int) ($user?->hospital_id ?? 0);

        if (
            !$esAdmin &&
            $hospitalUserId &&
            $hospitalSolicitudId &&
            $hospitalUserId !== $hospitalSolicitudId
        ) {
            abort(403, 'No autorizado para editar mezclas de otro hospital.');
        }

        // =========================
        // ✅ Laboratorio de la solicitud (obligatorio)
        // =========================
        $laboratoryId = (int) (optional($solicitud->hospital)->laboratory_id ?? 0);

        // fallback si por alguna razón no viene cargado hospital
        if ($laboratoryId <= 0 && !empty($solicitud->hospital_id)) {
            $laboratoryId = (int) DB::table('hospitals')
                ->where('id', (int) $solicitud->hospital_id)
                ->value('laboratory_id');
        }

        if ($laboratoryId <= 0) {
            abort(422, 'El hospital no tiene laboratorio asignado.');
        }

        // =========================
        // ✅ Lista por hospital (DE LA SOLICITUD)
        // =========================
        $listaId = DB::table('hospitals')
            ->where('id', $hospitalSolicitudId)
            ->value($this->medicineListColumnFor($solicitud));

        if (!$listaId) {
            abort(422, 'El hospital de la solicitud no tiene una lista de medicamentos configurada.');
        }

        // ==========================================================
        // ✅ Catálogos permitidos por lista (UNIÓN DE AMBAS FUENTES)
        //   A) medicine_medicine_lists -> medicine_oncos -> catalog_id
        //   B) medicine_list_presentation -> medicine_presentations -> catalog_id
        // ==========================================================
        $catsA = DB::table('medicine_medicine_lists as mml')
            ->join('medicine_oncos as mo', 'mml.medicine_id', '=', 'mo.id')
            ->where('mml.medicine_list_id', $listaId)
            ->pluck('mo.catalog_id')
            ->map(fn($x) => (int) $x);

        $catsB = DB::table('medicine_list_presentation as mlp')
            ->join('medicine_presentations as mp', 'mp.id', '=', 'mlp.medicine_presentation_id')
            ->where('mlp.medicine_list_id', $listaId)
            ->where('mp.is_available', 1)
            ->pluck('mp.catalog_id')
            ->map(fn($x) => (int) $x);

        $catalogIdsPermitidos = $catsA
            ->merge($catsB)
            ->filter(fn($x) => $x > 0)
            ->unique()
            ->values();

        if ($catalogIdsPermitidos->isEmpty()) {
            abort(422, 'La lista de medicamentos del hospital está vacía.');
        }

        // =========================
        // ✅ Catálogos (solo los permitidos)
        // =========================
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

        // =========================
        // ✅ Presentaciones permitidas por lista (para UI/validación)
        // =========================
        $presentacionesPermitidas = DB::table('medicine_list_presentation')
            ->where('medicine_list_id', $listaId)
            ->pluck('medicine_presentation_id')
            ->mapWithKeys(fn($pid) => [(int) $pid => true]);

        // =========================
        // ✅ Presentaciones por catálogo (con batches del laboratorio)
        // - Solo presentaciones en la lista
        // - Solo batches del laboratorio, vigentes y no vencidos
        // =========================
        $remanentesVigentes = DB::table('medicine_remainders')
            ->select('medicine_batch_id', DB::raw('SUM(current_ml) as remanente_ml'))
            ->where('domain', 'oncologico')
            ->where('laboratory_id', $laboratoryId)
            ->where('is_active', 1)
            ->where('current_ml', '>', 0)
            ->where(function ($query) {
                $query->whereNull('usable_until')->orWhere('usable_until', '>', now());
            })
            ->groupBy('medicine_batch_id');

        $presentacionesRaw = DB::table('medicine_presentations as mp')
            ->join('medicine_list_presentation as mlp', 'mlp.medicine_presentation_id', '=', 'mp.id')
            ->leftJoin('medicine_batches as mb', function ($join) use ($laboratoryId) {
                $join->on('mb.medicine_presentation_id', '=', 'mp.id')
                    ->where('mb.laboratory_id', '=', $laboratoryId)
                    ->where(function ($q) {
                        $q->whereNull('mb.caducidad')
                            ->orWhereDate('mb.caducidad', '>=', now()->toDateString());
                    });
            })
            ->leftJoinSub($remanentesVigentes, 'mr', function ($join) {
                $join->on('mr.medicine_batch_id', '=', 'mb.id');
            })
            ->where('mlp.medicine_list_id', $listaId)
            ->where('mp.is_available', 1)
            ->whereIn('mp.catalog_id', $catalogIdsPermitidos->all())
            ->select(
                'mp.id',
                'mp.catalog_id',
                'mp.presentacion',
                'mp.marca',
                'mp.contenido_valor',
                'mp.contenido_unidad',
                'mp.cantidad_medicamento',
                'mp.volumen_diluyente',
                'mp.precio_frasco',
                'mlp.charge_by',
                'mlp.precio',
                'mlp.precio_mg_override',
                'mlp.precio_ml_override',
                DB::raw('mb.id as batch_id'),
                DB::raw('mb.lote as lote'),
                DB::raw('mb.caducidad as caducidad'),
                DB::raw('mb.fecha_ingreso as fecha_ingreso'),
                DB::raw('mb.stock_inicial as stock_inicial'),
                DB::raw('mb.stock_actual as stock_actual'),
                DB::raw('mb.stock_reservado as stock_reservado'),
                DB::raw('COALESCE(mr.remanente_ml, 0) as remanente_ml')
            )
            ->orderBy('mp.presentacion')
            ->orderBy('mb.caducidad')
            ->get();

        $presentacionesPorCatalogo = $presentacionesRaw
            ->groupBy('catalog_id')
            ->map(function ($catalogRows) {
                return $catalogRows
                    ->groupBy('id')
                    ->map(function ($rows) {
                        $first = $rows->first();

                        return [
                            'id' => $first->id,
                            'catalog_id' => $first->catalog_id,
                            'presentacion' => $first->presentacion,
                            'marca' => $first->marca,
                            'contenido_valor' => $first->contenido_valor,
                            'contenido_unidad' => $first->contenido_unidad,
                            'cantidad_medicamento' => $first->cantidad_medicamento,
                            'volumen_diluyente' => $first->volumen_diluyente,
                            'precio_frasco' => $first->precio_frasco,
                            'charge_by' => $first->charge_by ?? 'frasco',
                            'precio' => $first->precio ?? null,
                            'precio_mg_override' => $first->precio_mg_override ?? null,
                            'precio_ml_override' => $first->precio_ml_override ?? null,
                            'batches' => $rows
                                ->filter(fn($r) => !empty($r->batch_id)
                                    && ((float) $r->stock_actual > 0 || (float) $r->remanente_ml > 0))
                                ->map(fn($r) => [
                                    'id' => $r->batch_id,
                                    'lote' => $r->lote,
                                    'caducidad' => $r->caducidad,
                                    'fecha_ingreso' => $r->fecha_ingreso,
                                    'stock_inicial' => $r->stock_inicial,
                                    'stock_actual' => $r->stock_actual,
                                    'stock_reservado' => $r->stock_reservado,
                                    'remanente_ml' => $r->remanente_ml,
                                ])
                                ->values(),
                        ];
                    })
                    ->values();
            });
        // =========================
        // ✅ Info adicional por catálogo
        // =========================
        $infoAdicional = [];
        foreach ($catalogos as $cat) {
            $diluyentes = DB::table('diluent_medicine_catalog as dmc')
                ->join('diluents as d', 'dmc.diluent_id', '=', 'd.id')
                ->where('dmc.medicine_catalog_id', $cat->id)
                ->select('d.id', DB::raw('d.denominacion_generica as name'))
                ->get();

            $vias = DB::table('administration_route_medicine_catalog as armc')
                ->join('administration_routes as ar', 'armc.administration_route_id', '=', 'ar.id')
                ->where('armc.medicine_catalog_id', $cat->id)
                ->select('ar.id', 'ar.name')
                ->get();

            $infoAdicional[$cat->id] = [
                'catalog_id'       => (int) $cat->id,
                'denominacion'     => $cat->denominacion,
                'diluyentes'       => $diluyentes,
                'vias'             => $vias,
                'requires_infusor' => (int) $cat->requires_infusor,
            ];
        }

        // =====================================================
        // ✅ CONSERVAR lo que ya trae la mezcla (si está fuera de lista)
        // =====================================================
        $catalogIdsEnMezcla = $mezcla->medicamentos
            ->map(fn($mm) => (int) (optional(optional($mm->medicamentoOnco)->catalog)->id ?? 0))
            ->filter()
            ->unique()
            ->values();

        foreach ($catalogIdsEnMezcla as $catId) {

            if (!isset($infoAdicional[$catId])) {
                $cat = DB::table('medicines_catalog')
                    ->where('id', $catId)
                    ->select('id', 'denominacion', 'requires_infusor')
                    ->first();

                if ($cat) {
                    $diluyentes = DB::table('diluent_medicine_catalog as dmc')
                        ->join('diluents as d', 'dmc.diluent_id', '=', 'd.id')
                        ->where('dmc.medicine_catalog_id', $cat->id)
                        ->select('d.id', DB::raw('d.denominacion_generica as name'))
                        ->get();

                    $vias = DB::table('administration_route_medicine_catalog as armc')
                        ->join('administration_routes as ar', 'armc.administration_route_id', '=', 'ar.id')
                        ->where('armc.medicine_catalog_id', $cat->id)
                        ->select('ar.id', 'ar.name')
                        ->get();

                    $infoAdicional[$cat->id] = [
                        'catalog_id'       => (int) $cat->id,
                        'denominacion'     => $cat->denominacion,
                        'diluyentes'       => $diluyentes,
                        'vias'             => $vias,
                        'requires_infusor' => (int) ($cat->requires_infusor ?? 0),
                        'fuera_de_lista'   => true,
                    ];

                    $yaExisteEnCatalogos = $catalogos->firstWhere('id', (int)$cat->id);
                    if (!$yaExisteEnCatalogos) {
                        $catalogos = $catalogos->push((object)[
                            'id' => (int)$cat->id,
                            'denominacion' => $cat->denominacion,
                            'requires_infusor' => (int)($cat->requires_infusor ?? 0),
                            'fuera_de_lista' => true,
                        ])->sortBy('denominacion')->values();
                    }
                }
            }

            if (!$presentacionesPorCatalogo->has($catId)) {
                $extras = DB::table('medicine_presentations as mp')
                    ->join('medicine_batches as mb', 'mb.medicine_presentation_id', '=', 'mp.id')
                    ->where('mp.catalog_id', $catId)
                    ->where('mp.is_available', 1)
                    ->where('mb.laboratory_id', $laboratoryId)
                    ->where(function ($q) {
                        $q->whereNull('mb.caducidad')
                            ->orWhereDate('mb.caducidad', '>=', now()->toDateString());
                    })
                    ->select(
                        'mp.id',
                        'mp.catalog_id',
                        'mp.presentacion',
                        'mp.marca',
                        'mp.contenido_valor',
                        'mp.contenido_unidad',
                        'mp.cantidad_medicamento',
                        'mp.volumen_diluyente',
                        'mp.precio_frasco',
                        DB::raw('mb.id as batch_id'),
                        DB::raw('mb.lote as lote'),
                        DB::raw('mb.caducidad as caducidad'),
                        DB::raw('mb.fecha_ingreso as fecha_ingreso'),
                        DB::raw('mb.stock_inicial as stock_inicial'),
                        DB::raw('mb.stock_actual as stock_actual'),
                        DB::raw('mb.stock_reservado as stock_reservado'),
                        DB::raw('0 as remanente_ml')
                    )
                    ->orderBy('mp.presentacion')
                    ->orderBy('mb.caducidad')
                    ->get();

                if ($extras->isNotEmpty()) {
                    $presentacionesPorCatalogo = $presentacionesPorCatalogo->put($catId, $extras
                        ->groupBy('id')
                        ->map(function ($rows) {
                            $first = $rows->first();

                            return [
                                'id' => $first->id,
                                'catalog_id' => $first->catalog_id,
                                'presentacion' => $first->presentacion,
                                'marca' => $first->marca,
                                'contenido_valor' => $first->contenido_valor,
                                'contenido_unidad' => $first->contenido_unidad,
                                'cantidad_medicamento' => $first->cantidad_medicamento,
                                'volumen_diluyente' => $first->volumen_diluyente,
                                'precio_frasco' => $first->precio_frasco,
                                'charge_by' => 'frasco',
                                'precio' => null,
                                'precio_mg_override' => null,
                                'precio_ml_override' => null,
                                'batches' => $rows
                                    ->filter(fn($r) => !empty($r->batch_id)
                                        && ((float) $r->stock_actual > 0 || (float) $r->remanente_ml > 0))
                                    ->map(fn($r) => [
                                        'id' => $r->batch_id,
                                        'lote' => $r->lote,
                                        'caducidad' => $r->caducidad,
                                        'fecha_ingreso' => $r->fecha_ingreso,
                                        'stock_inicial' => $r->stock_inicial,
                                        'stock_actual' => $r->stock_actual,
                                        'stock_reservado' => $r->stock_reservado,
                                        'remanente_ml' => $r->remanente_ml,
                                    ])
                                    ->values(),
                            ];
                        })
                        ->values());
                }
            }
        }

        // =========================
        // ✅ Presentaciones de diluyentes
        // =========================
        $selectedDiluentPresentationId = (int) ($mezcla->diluent_presentation_id ?? 0);

        $diluentPresentationsPorDiluyente = DiluentPresentation::query()
            ->where('is_active', true)
            ->where(function ($query) use ($laboratoryId) {
                $query->whereNull('laboratory_id')
                    ->orWhere('laboratory_id', $laboratoryId);
            })
            ->where(function ($query) use ($selectedDiluentPresentationId) {
                $query->whereNull('laboratory_id')
                    ->orWhere('stock_actual', '>', 0);

                if ($selectedDiluentPresentationId > 0) {
                    $query->orWhere('id', $selectedDiluentPresentationId);
                }
            })
            ->orderBy('volume_ml')
            ->get()
            ->groupBy('diluent_id')
            ->map(function ($group) {
                return $group->map(function ($p) {
                    return [
                        'id'                     => $p->id,
                        'diluent_id'             => $p->diluent_id,
                        'presentacion'           => $p->presentacion,
                        'volume_ml'              => $p->volume_ml,
                        'denominacion_comercial' => $p->denominacion_comercial,
                        'lote'                   => $p->lote,
                        'caducidad'              => $p->caducidad,
                        'stock_actual'           => $p->stock_actual,
                        'laboratory_id'           => $p->laboratory_id,
                    ];
                })->values();
            });

        // =========================
        // ✅ Infusores
        // =========================
        $infusors = DB::table('infusors')
            ->select('id', 'nombre_generico', 'nombre_comercial')
            ->where('is_active', true)
            ->orderBy('nombre_generico')
            ->orderBy('nombre_comercial')
            ->get();

        // ✅ Mapa: medicine_oncos.id -> catalog_id (legacy)
        $medOncoIds = $mezcla->medicamentos->pluck('medicamento_id')->filter()->unique()->values();
        $catalogIdPorMedicineOncoId = collect();

        if ($medOncoIds->isNotEmpty()) {
            $catalogIdPorMedicineOncoId = DB::table('medicine_oncos')
                ->whereIn('id', $medOncoIds)
                ->pluck('catalog_id', 'id');
        }

        return view('admin.oncologicos.mezclas.edit', [
            'mezcla'                           => $mezcla,
            'solicitud'                        => $solicitud,

            'medicamentos'                     => $catalogos,
            'catalogos'                        => $catalogos,
            'infoAdicional'                    => $infoAdicional,
            'presentacionesPorCatalogo'        => $presentacionesPorCatalogo,
            'infusors'                         => $infusors,
            'diluentPresentationsPorDiluyente' => $diluentPresentationsPorDiluyente,
            'catalogIdPorMedicineOncoId'       => $catalogIdPorMedicineOncoId,

            'medicineListId'                   => $listaId,
        ]);
    }



    public function update(Request $request, $id)
    {


        $generarLotePorMezcla = function (Mezcla $mezcla) {
            if ($mezcla->lote) return;

            $hoy = Carbon::today();

            $conteoHoy = Mezcla::whereDate('created_at', $hoy)
                ->whereNotNull('lote')
                ->lockForUpdate()
                ->count();

            $consecutivo = str_pad($conteoHoy + 1, 3, '0', STR_PAD_LEFT);

            $abbr = ['ENE', 'FEB', 'MAR', 'ABR', 'MAY', 'JUN', 'JUL', 'AGO', 'SEP', 'OCT', 'NOV', 'DIC'];
            $mesAbbr = $abbr[$hoy->month - 1];

            $dia  = $hoy->format('d');
            $anio = $hoy->format('y');

            $mezcla->lote = 'L' . $dia . $mesAbbr . $anio . $consecutivo;
        };

        $asegurarRemisionPorMezcla = function (Mezcla $mezcla) {
            if (!empty($mezcla->remision)) {
                return $mezcla->remision;
            }

            $maxSolicitud = SolicitudOnco::whereNotNull('remision')
                ->lockForUpdate()
                ->max(DB::raw('CAST(remision AS UNSIGNED)'));
            $maxMezcla = Mezcla::whereNotNull('remision')
                ->lockForUpdate()
                ->max(DB::raw('CAST(remision AS UNSIGNED)'));

            $nuevo = max((int) $maxSolicitud, (int) $maxMezcla) + 1;
            $remision = (string) $nuevo;

            $mezcla->remision = $remision;

            if ($mezcla->solicitud && empty($mezcla->solicitud->remision)) {
                $mezcla->solicitud->remision = $remision;
                $mezcla->solicitud->save();
            }

            return $remision;
        };

        $mezcla = Mezcla::with([
            'solicitud',
            'solicitud.hospital',
            'solicitud.user',
            'medicamentos',
            'medicamentos.presentacionesUsadas',
        ])->findOrFail($id);

        $user = auth()->user();
        $accion = (string) $request->input('accion', 'actualizar');

        $hospitalUserId      = (int) ($user->hospital_id ?? 0);
        $hospitalSolicitudId = (int) ($mezcla->solicitud->hospital_id ?? 0);

        $esAdmin = $user->hasAnyRole(['Admin', 'Super Admin']);
        if (!$esAdmin && $hospitalUserId && $hospitalSolicitudId && $hospitalUserId !== $hospitalSolicitudId) {
            abort(403, 'No autorizado para actualizar mezclas de otro hospital.');
        }

        if ($accion === 'rechazar') {
            if ($mezcla->operational_status !== 'pendiente') {
                return back()->withErrors(['error' => 'Solo una mezcla pendiente puede rechazarse.']);
            }

            DB::transaction(function () use ($mezcla) {
                $mezcla->estado = 'cancelada';
                $mezcla->save();
            });

            return $this->redirectAfterMixtureAction($mezcla, $request)
                ->with('success', 'Mezcla rechazada correctamente.');
        }

        $laboratoryId = (int) (optional($mezcla->solicitud->hospital)->laboratory_id ?? 0);

        if ($laboratoryId <= 0 && !empty($hospitalSolicitudId)) {
            $laboratoryId = (int) DB::table('hospitals')
                ->where('id', (int) $hospitalSolicitudId)
                ->value('laboratory_id');
        }

        if ($laboratoryId <= 0) {
            return back()->withErrors(['error' => 'Error al actualizar la mezcla: El hospital no tiene laboratorio asignado.']);
        }

        $listaId = DB::table('hospitals')
            ->where('id', $hospitalSolicitudId)
            ->value($this->medicineListColumnFor($mezcla->solicitud));

        if (!$listaId) {
            return back()->withErrors(['error' => 'El hospital de la solicitud no tiene una lista de medicamentos configurada.']);
        }

        $catsA = DB::table('medicine_medicine_lists as mml')
            ->join('medicine_oncos as mo', 'mml.medicine_id', '=', 'mo.id')
            ->where('mml.medicine_list_id', $listaId)
            ->pluck('mo.catalog_id')
            ->map(fn($x) => (int) $x);

        $catsB = DB::table('medicine_list_presentation as mlp')
            ->join('medicine_presentations as mp', 'mp.id', '=', 'mlp.medicine_presentation_id')
            ->where('mlp.medicine_list_id', $listaId)
            ->where('mp.is_available', 1)
            ->pluck('mp.catalog_id')
            ->map(fn($x) => (int) $x);

        $catalogosPermitidos = $catsA
            ->merge($catsB)
            ->filter(fn($x) => $x > 0)
            ->unique()
            ->values()
            ->toArray();

        if (count($catalogosPermitidos) === 0) {
            return back()->withErrors(['error' => 'La lista de medicamentos del hospital está vacía.']);
        }

        $presentacionesPermitidas = DB::table('medicine_list_presentation')
            ->where('medicine_list_id', $listaId)
            ->pluck('medicine_presentation_id')
            ->mapWithKeys(fn($pid) => [(int) $pid => true]);

        if ($accion === 'preparada') {
            if ($mezcla->estado !== 'dispensada') {
                return back()->withErrors(['error' => 'Solo una mezcla dispensada puede marcarse como preparada.']);
            }

            $preparoNombre = $this->nombreUsuario($user);

            DB::transaction(function () use ($mezcla, $generarLotePorMezcla, $asegurarRemisionPorMezcla, $preparoNombre) {
                $mezcla->estado = 'preparada';
                $generarLotePorMezcla($mezcla);
                $asegurarRemisionPorMezcla($mezcla);

                $mezcla->save();

                $inspeccion = InspeccionMezcla::firstOrCreate(
                    ['mezcla_id' => $mezcla->id],
                    [
                        'fecha_inspeccion' => now()->toDateString(),
                        'hora_inspeccion'  => now()->format('H:i:s'),
                        'reviso_nombre'    => '',
                        'aprobo_nombre'    => '',
                    ]
                );

                if ($inspeccion->preparo_nombre === '' || $inspeccion->preparo_nombre === null) {
                    $inspeccion->preparo_nombre = $preparoNombre;
                    $inspeccion->save();
                }
            });

            return $this->redirectAfterMixtureAction($mezcla, $request)
                ->with('success', 'Mezcla marcada como preparada. Se registró el responsable en la inspección.');
        }

        if ($accion === 'entregada') {
            if ($mezcla->estado !== 'revisada') {
                return back()->withErrors(['error' => 'Solo una mezcla revisada puede marcarse como entregada.']);
            }

            $liberoNombre = $this->nombreUsuario($user);

            DB::transaction(function () use ($mezcla, $liberoNombre) {
                $mezcla->estado = 'entregada';
                $mezcla->save();

                $inspeccion = InspeccionMezcla::firstOrCreate(
                    ['mezcla_id' => $mezcla->id],
                    [
                        'fecha_inspeccion' => now()->toDateString(),
                        'hora_inspeccion'  => now()->format('H:i:s'),
                        'reviso_nombre'    => '',
                        'aprobo_nombre'    => '',
                    ]
                );

                $inspeccion->libero_nombre = $liberoNombre;
                $inspeccion->save();

            });

            return $this->redirectAfterMixtureAction($mezcla, $request)
                ->with('success', 'Mezcla marcada como entregada y liberador registrado.');
        }

        $request->validate([
            'mezcla_json'      => 'required|json',
            'paciente_nombre'  => 'required|string|max:255',
            'servicio'         => 'required|string|max:255',
            'registro'         => 'required|string|max:255',
            'sexo'             => 'nullable|in:M,F',
            'fecha_nacimiento' => [
                'nullable',
                'date',
                'after:' . Carbon::now()->subYears(100)->format('Y-m-d'),
                'before:' . Carbon::today()->format('Y-m-d'),
            ],
            'peso'             => 'nullable|numeric|min:1|max:500',
            'piso'             => 'nullable|string|max:50',
            'cama'             => 'nullable|string|max:50',
            'diagnostico'      => 'nullable|string|max:255',
            'medico_nombre'    => 'nullable|string|max:255',
            'medico_cedula'    => 'nullable|string|max:255',
            'fecha_entrega'    => 'nullable|date|after_or_equal:today',
            'observaciones'    => 'nullable|string|max:500',
        ], [
            'fecha_nacimiento.after'  => 'La fecha de nacimiento no puede ser mayor a 100 años.',
            'fecha_nacimiento.before' => 'La fecha de nacimiento debe ser anterior a hoy.',
            'fecha_entrega.after_or_equal' => 'La fecha de entrega no puede ser anterior a hoy.',
        ]);

        $mezclaData = json_decode($request->mezcla_json, true);

        $resolverMedicineOnco = function (int $idRecibido) {
            if ($idRecibido <= 0) return null;

            // La UI envía catalog_id. En datos legacy puede existir coincidencia
            // entre medicine_oncos.id y catalog_id de otro medicamento, así que
            // aquí debemos priorizar catalog_id para no resolver el fármaco equivocado.
            $med = MedicineOnco::with('catalog')
                ->where('catalog_id', $idRecibido)
                ->first();

            if ($med) return $med;

            return MedicineOnco::with('catalog')->find($idRecibido);
        };

        DB::beginTransaction();

        try {
            $shouldDispense = $accion === 'dispensar';
            $volumenDilucion = (float) ($mezclaData['volumen_dilucion'] ?? 0);
            $tiempoInfusion  = $mezclaData['tiempo_infusion'] ?? null;

            if ($volumenDilucion <= 0) {
                throw new \Exception("La mezcla requiere un volumen de dilución válido (> 0).");
            }

            if ($tiempoInfusion === null || $tiempoInfusion === '') {
                throw new \Exception("La mezcla requiere un tiempo de infusión.");
            }

            $setInfusion = !empty($mezclaData['set_infusion']);
            $infusorId   = !empty($mezclaData['infusor_id']) ? (int) $mezclaData['infusor_id'] : null;

            if ($setInfusion && $infusorId) {
                throw new \Exception("Selecciona set de infusión o un infusor, no ambos.");
            }

            $medsPayload = $mezclaData['medicamentos'] ?? [];
            if (!is_array($medsPayload) || count($medsPayload) === 0) {
                throw new \Exception("La mezcla debe contener al menos un medicamento.");
            }

            $refDil = isset($medsPayload[0]['diluyente_id']) ? (string) $medsPayload[0]['diluyente_id'] : null;
            $refVia = isset($medsPayload[0]['via_administracion_id']) ? (string) $medsPayload[0]['via_administracion_id'] : null;

            foreach ($medsPayload as $m) {
                $d = isset($m['diluyente_id']) ? (string) $m['diluyente_id'] : null;
                $v = isset($m['via_administracion_id']) ? (string) $m['via_administracion_id'] : null;
                if ($d !== $refDil || $v !== $refVia) {
                    throw new \Exception("Todos los medicamentos deben tener el mismo diluyente y la misma vía de administración.");
                }
            }

            $diluentPresentationId = $mezclaData['diluent_presentation_id'] ?? null;

            if ($shouldDispense && !$diluentPresentationId) {
                $diluentPresentationId = $this->resolveDiluentPresentationByFefo(
                    (int) $refDil,
                    $volumenDilucion,
                    $laboratoryId
                );

                if (!$diluentPresentationId) {
                    throw new \Exception("No hay stock de diluyente disponible con caducidad vigente para el volumen requerido ({$volumenDilucion} mL).");
                }
            }

            if (! $shouldDispense) {
                $diluentPresentationId = null;
            }

            if ($shouldDispense && $diluentPresentationId) {
                $dilPres = DiluentPresentation::where('id', (int) $diluentPresentationId)
                    ->where('is_active', true)
                    ->where(function ($query) use ($laboratoryId) {
                        $query->whereNull('laboratory_id')
                            ->orWhere('laboratory_id', $laboratoryId);
                    })
                    ->first();

                if (!$dilPres) {
                    throw new \Exception("La presentación de diluyente seleccionada no existe o no está activa.");
                }

                if ($refDil && (int) $dilPres->diluent_id !== (int) $refDil) {
                    throw new \Exception("La presentación de diluyente no pertenece al diluyente seleccionado en la mezcla.");
                }
            }

            if ($shouldDispense && $diluentPresentationId && isset($dilPres) && $dilPres->laboratory_id !== null && (float) ($dilPres->stock_actual ?? 0) <= 0) {
                throw new \Exception("La presentación de diluyente seleccionada no tiene stock disponible.");
            }

            if ($shouldDispense && $diluentPresentationId && isset($dilPres)) {
                if ((float) ($dilPres->stock_actual ?? 0) <= 0) {
                    throw new \Exception("La presentacion de diluyente seleccionada no tiene stock disponible.");
                }

                if ($dilPres->caducidad && \Carbon\Carbon::parse($dilPres->caducidad)->lt(now()->startOfDay())) {
                    throw new \Exception("La presentacion de diluyente seleccionada ya esta caducada.");
                }
            }

            if ($infusorId) {
                $infusor = DB::table('infusors')
                    ->where('id', $infusorId)
                    ->where('is_active', true)
                    ->first();

                if (!$infusor) {
                    throw new \Exception("El infusor seleccionado no existe o no está activo.");
                }

                $hayMedQueAdmiteInfusor = false;
                foreach ($medsPayload as $m) {
                    $idRecibido = (int) ($m['medicamento_id'] ?? 0);
                    if ($idRecibido <= 0) continue;

                    $medOnco = $resolverMedicineOnco($idRecibido);
                    if ($medOnco && $medOnco->catalog && (int) $medOnco->catalog->requires_infusor === 1) {
                        $hayMedQueAdmiteInfusor = true;
                        break;
                    }
                }

                if (!$hayMedQueAdmiteInfusor) {
                    throw new \Exception("Para seleccionar un infusor, la mezcla debe contener al menos un medicamento que lo admita.");
                }
            }

            $mezcla->volumen_dilucion        = $volumenDilucion;
            $mezcla->tiempo_infusion         = $tiempoInfusion;
            $mezcla->fecha_entrega            = $request->fecha_entrega;
            $mezcla->set_infusion            = $setInfusion;
            $mezcla->infusor_id              = $infusorId;

            if ($shouldDispense) {
                $mezcla->diluent_presentation_id = $diluentPresentationId ?: null;
            } elseif ($accion === 'aprobar') {
                $mezcla->diluent_presentation_id = null;
            }

            $mezcla->save();

            if ($mezcla->solicitud) {
                $mezcla->solicitud->nombre_paciente   = $request->paciente_nombre;
                $mezcla->solicitud->servicio          = $request->servicio;
                $mezcla->solicitud->registro_paciente = $request->registro;
                $mezcla->solicitud->sexo              = $request->sexo;
                $mezcla->solicitud->fecha_nacimiento  = $request->fecha_nacimiento;
                $mezcla->solicitud->peso              = $request->peso;
                $mezcla->solicitud->piso              = $request->piso;
                $mezcla->solicitud->cama              = $request->cama;
                $mezcla->solicitud->diagnostico       = $request->diagnostico;
                $mezcla->solicitud->nombre_medico     = $request->medico_nombre;
                $mezcla->solicitud->cedula_medico     = $request->medico_cedula;
                $mezcla->solicitud->observaciones     = $request->observaciones;
                $mezcla->solicitud->fecha_entrega     = $mezcla->solicitud->mezclas()
                    ->whereNotNull('fecha_entrega')
                    ->min('fecha_entrega') ?? $request->fecha_entrega;
                $mezcla->solicitud->save();
            }

            // =====================================================
            // DEVOLVER INVENTARIO ANTERIOR
            // =====================================================
            $mmIds = $mezcla->medicamentos()->pluck('id');

            if ($mmIds->isNotEmpty()) {
                app(MedicineRemainderService::class)->rollbackReference('mezcla', $mezcla->id, $user->id);

                $presentacionesViejas = DB::table('mezcla_medicamento_presentaciones')
                    ->whereIn('mezcla_medicamento_id', $mmIds)
                    ->get(['medicine_batch_id', 'unidades_usadas', 'unidades_abiertas', 'volumen_diluyente_snapshot']);

                foreach ($presentacionesViejas as $pv) {
                    $this->releaseBatchConsumptionForMix(
                        (int) $pv->medicine_batch_id,
                        (int) ($pv->unidades_abiertas ?: $pv->unidades_usadas),
                        (float) ($pv->volumen_diluyente_snapshot ?? 0) * (int) ($pv->unidades_abiertas ?: $pv->unidades_usadas),
                        $laboratoryId,
                        $mezcla->id,
                        $user->id
                    );
                }

                DB::table('mezcla_medicamento_presentaciones')
                    ->whereIn('mezcla_medicamento_id', $mmIds)
                    ->delete();
            }

            $mezcla->medicamentos()->delete();

            // =====================================================
            // RECREAR MEDICAMENTOS + CONSUMIR NUEVO INVENTARIO
            // =====================================================
            foreach ($medsPayload as $m) {
                $idRecibido  = (int) ($m['medicamento_id'] ?? 0);
                $nombre      = $m['nombre'] ?? '';
                $diluyenteId = !empty($m['diluyente_id']) ? (int) $m['diluyente_id'] : null;
                $viaAdminId  = !empty($m['via_administracion_id']) ? (int) $m['via_administracion_id'] : null;
                $dosis       = (float) ($m['dosis'] ?? 0);

                if ($idRecibido <= 0) {
                    throw new \Exception("Medicamento inválido en la mezcla (medicamento_id vacío).");
                }

                if ($dosis <= 0) {
                    throw new \Exception("La dosis debe ser mayor a 0 para el medicamento ID {$idRecibido}.");
                }

                $medicine = $resolverMedicineOnco($idRecibido);
                if (!$medicine || !$medicine->catalog) {
                    throw new \Exception("No se encontró información del catálogo para el medicamento ID {$idRecibido}.");
                }

                $medicineOncoId = (int) $medicine->id;
                $catalog        = $medicine->catalog;
                $catalogId      = (int) ($medicine->catalog_id ?? 0);

                if (!in_array($catalogId, $catalogosPermitidos, true)) {
                    throw new \Exception("El medicamento (catálogo {$catalogId}) no pertenece a la lista del hospital.");
                }

                $concentracion = $volumenDilucion > 0 ? $dosis / $volumenDilucion : 0;
                $concMin = (float) ($catalog->conc_min ?? 0);
                $concMax = (float) ($catalog->conc_max ?? 0);

                if (($concMin > 0 || $concMax > 0) && ($concentracion < $concMin || $concentracion > $concMax)) {
                    throw new \Exception(
                        "La concentración de '{$catalog->denominacion}' está fuera del rango permitido ({$concMin} - {$concMax}). Dosis: {$dosis}, Volumen: {$volumenDilucion}."
                    );
                }

                $chargeBy = strtolower(trim((string) ($m['charge_by'] ?? ($catalog->charge_by ?? 'mg'))));
                if (!in_array($chargeBy, ['mg', 'ml', 'frasco', 'pieza'], true)) {
                    $chargeBy = 'mg';
                }
                if ($chargeBy === 'pieza') {
                    $chargeBy = 'frasco';
                }

                $mm = $mezcla->medicamentos()->create([
                    'medicamento_id'             => $medicineOncoId,
                    'nombre_medicamento'         => $nombre,
                    'denominacion_snapshot'      => $catalog->denominacion ?? null,
                    'marca_snapshot'             => null,
                    'requires_infusor_snapshot'  => (int) ($catalog->requires_infusor ?? 0),
                    'conc_min_snapshot'          => $catalog->conc_min ?? null,
                    'conc_max_snapshot'          => $catalog->conc_max ?? null,
                    'dosis'                      => $dosis,
                    'dosis_ml'                   => null,
                    'diluyente_id'               => $diluyenteId,
                    'via_administracion_id'      => $viaAdminId,
                    'charge_by'                  => $chargeBy,
                    'precio_mg_snapshot'         => $chargeBy === 'mg' && isset($m['precio_mg']) && $m['precio_mg'] !== ''
                        ? (float) $m['precio_mg']
                        : null,
                    'precio_ml_snapshot'         => $chargeBy === 'ml' && isset($m['precio_frasco']) && $m['precio_frasco'] !== ''
                        ? (float) $m['precio_frasco']
                        : null,
                ]);

                $presentacionesPayload = $m['presentaciones'] ?? [];
                $shouldConsumeMedicationInventory = $shouldDispense && $chargeBy !== 'mg';

                if ($shouldConsumeMedicationInventory) {
                    if (!is_array($presentacionesPayload) || count($presentacionesPayload) === 0) {
                        throw new \Exception("Debes seleccionar al menos una presentación con stock para '{$catalog->denominacion}'.");
                    }

                    $batchIds = collect($presentacionesPayload)
                        ->pluck('batch_id')
                        ->filter()
                        ->map(fn($id) => (int) $id)
                        ->unique()
                        ->values();

                    if ($batchIds->isEmpty()) {
                        throw new \Exception("No se seleccionó lote para '{$catalog->denominacion}'.");
                    }

                    $marcasSeleccionadas = DB::table('medicine_batches as mb')
                        ->join('medicine_presentations as mp', 'mp.id', '=', 'mb.medicine_presentation_id')
                        ->whereIn('mb.id', $batchIds)
                        ->where('mb.laboratory_id', $laboratoryId)
                        ->where('mp.catalog_id', $catalogId)
                        ->pluck('mp.marca')
                        ->map(fn($marca) => trim((string) $marca))
                        ->filter()
                        ->unique()
                        ->values();

                    if ($marcasSeleccionadas->count() > 1) {
                        throw new \Exception(
                            "Para '{$catalog->denominacion}' solo puedes seleccionar frascos de la misma marca."
                        );
                    }
                }

                $resultadoPresentaciones = [];

                if ($shouldConsumeMedicationInventory) {
                    $resultadoPresentaciones = $this->attachPresentacionesAndConsumeInventory(
                        $mm,
                        is_array($presentacionesPayload) ? $presentacionesPayload : [],
                        $catalogId,
                        (int) $listaId,
                        $laboratoryId,
                        $mezcla->id,
                        $user->id
                    );
                }

                if (!empty($resultadoPresentaciones['marca_snapshot'])) {
                    $mm->marca_snapshot = $resultadoPresentaciones['marca_snapshot'];
                }

                if (!empty($resultadoPresentaciones['dosis_ml'])) {
                    $mm->dosis_ml = $resultadoPresentaciones['dosis_ml'];
                }

                if ($chargeBy === 'mg') {
                    $precioMg = isset($m['precio_mg']) && $m['precio_mg'] !== ''
                        ? (float) $m['precio_mg']
                        : (float) ($medicine->precio_mg ?? 0);
                    if ($precioMg <= 0) {
                        $precioMg = 0;
                    }
                    $mm->precio_mg_snapshot = $precioMg;
                }

                $mm->save();
            }

            if ($accion === 'aprobar') {
                $mezcla->estado = 'aprobada';
                $generarLotePorMezcla($mezcla);
                $mezcla->save();

                $aproboNombre = $this->nombreUsuario($user);

                $inspeccion = InspeccionMezcla::firstOrCreate(
                    ['mezcla_id' => $mezcla->id],
                    [
                        'fecha_inspeccion' => Carbon::today()->toDateString(),
                        'hora_inspeccion'  => Carbon::now()->format('H:i:s'),
                        'reviso_nombre'    => '',
                        'aprobo_nombre'    => '',
                    ]
                );

                $inspeccion->valido_nombre = $aproboNombre;
                $inspeccion->fecha_validacion = Carbon::today()->toDateString();
                $inspeccion->hora_validacion = Carbon::now()->format('H:i:s');
                $inspeccion->save();
            }

            if ($accion === 'dispensar') {
                $mezcla->estado = 'dispensada';
                $this->consumeDiluentPresentationForMix(
                    (int) ($mezcla->diluent_presentation_id ?? 0),
                    $laboratoryId,
                    $mezcla->id,
                    $user->id
                );
                $mezcla->save();
            }

            DB::commit();

            return $this->redirectAfterMixtureAction($mezcla, $request)
                ->with(
                    'success',
                    match ($accion) {
                        'aprobar' => 'Mezcla aprobada correctamente.',
                        'dispensar' => 'Dispensacion guardada correctamente.',
                        default => 'Mezcla y solicitud actualizadas correctamente.',
                    }
                );
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Error al actualizar la mezcla: ' . $e->getMessage()]);
        }
    }

    public function destroy($id)
    {
        // Logic to delete a specific mix
        // Find the mix by ID and delete it
        // Redirect or return a response
    }

    public function ordenPreparacion(Mezcla $mezcla)
    {
        $mezcla = Mezcla::with([
            'solicitud.hospital',
            'inspeccion',
            'medicamentos.medicamentoOnco.catalog',
            'medicamentos.diluyente',
            'medicamentos.viaAdministracion',
            'medicamentos.presentacionesUsadas.batch.presentation', // ✅ presentation vive en batch
            'diluentPresentation.diluent',
            'infusor',
        ])->findOrFail($mezcla->id);

        // ===== FECHAS =====
        $fechaPreparacion = $mezcla->solicitud?->fecha_hora_preparada
            ? Carbon::parse($mezcla->solicitud->fecha_hora_preparada)
            : Carbon::parse($mezcla->created_at);

        $fechaLimiteUso = $mezcla->solicitud?->fecha_hora_limite_uso
            ? Carbon::parse($mezcla->solicitud->fecha_hora_limite_uso)
            : $fechaPreparacion->copy()->addHours(48);

        $hospital = optional(optional($mezcla->solicitud)->hospital)->name ?? 'No asignado';

        // ===== EQUIPO INFUSIÓN/INFUSOR =====
        $esSetInfusion = (bool) $mezcla->set_infusion;

        if ($esSetInfusion) {
            $equipoInfusion = (object) [
                'tipo'             => 'set',
                'lote'             => null,
                'caducidad'        => null,
                'nombre_comercial' => 'Set de infusión',
                'nombre_generico'  => 'Set de infusión',
            ];
        } else {
            $inf = $mezcla->infusor;
            $equipoInfusion = (object) [
                'tipo'             => 'infusor',
                'lote'             => optional($inf)->lote,
                'caducidad'        => optional($inf)->caducidad,
                'nombre_comercial' => optional($inf)->nombre_comercial,
                'nombre_generico'  => optional($inf)->nombre_generico,
            ];
        }

        // ===== NOMBRES INSPECCIÓN =====
        $validoNombre  = $this->nombreUsuarioParaPdf(optional($mezcla->inspeccion)->valido_nombre);
        $aproboNombre  = $this->nombreUsuarioParaPdf(optional($mezcla->inspeccion)->aprobo_nombre);
        $revisoNombre  = $this->nombreUsuarioParaPdf(optional($mezcla->inspeccion)->reviso_nombre);
        $preparoNombre = $this->nombreUsuarioParaPdf(optional($mezcla->inspeccion)->preparo_nombre);
        $liberoNombre  = $this->nombreUsuarioParaPdf(optional($mezcla->inspeccion)->libero_nombre);

        $preparadaPor = $preparoNombre ?: '—';

        // ===== DILUYENTE BASE =====
        $diluyenteBase = optional(optional($mezcla->diluentPresentation)->diluent)->denominacion_generica ?? '—';

        // ===== fallback presentaciones =====
        $catalogIds = $mezcla->medicamentos
            ->map(fn($mm) => optional(optional($mm->medicamentoOnco)->catalog)->id)
            ->filter()
            ->unique()
            ->values();

        $presentacionFallbackPorCatalogo = DB::table('medicine_presentations')
            ->whereIn('catalog_id', $catalogIds)
            ->where('is_available', 1)
            ->orderBy('presentacion')
            ->get()
            ->groupBy('catalog_id')
            ->map(fn($rows) => $rows->first());

        // ==========================================================
        // ✅ CÁLCULOS
        // ==========================================================
        $volumenTotalSolicitado = (float) ($mezcla->volumen_dilucion ?? 0);

        $legendProteccion = null;
        $totalVolMedicamentos = 0.0;

        // ✅ Regla: si cualquier medicamento requiere infusor, ocultar "Extraer"
        $ocultarExtraer = $mezcla->medicamentos->contains(function ($mm) {
            return (bool) (optional(optional($mm->medicamentoOnco)->catalog)->requires_infusor ?? false);
        });

        // ===== MEDICAMENTOS (para tablas) =====
        $medicamentos = $mezcla->medicamentos->flatMap(function ($mm) use (
            $mezcla,
            $volumenTotalSolicitado,
            &$legendProteccion,
            &$totalVolMedicamentos,
            $presentacionFallbackPorCatalogo
        ) {
            $presentaciones = $mm->presentacionesUsadas ?? collect();
            $dosisRestanteMg = (float) ($mm->dosis ?? 0);

            if ($presentaciones->isEmpty()) {
                $presentaciones = collect([null]);
            }

            return $presentaciones->map(function ($presUsed) use (
                $mm,
                $mezcla,
                &$legendProteccion,
                &$totalVolMedicamentos,
                $presentacionFallbackPorCatalogo,
                &$dosisRestanteMg
            ) {
                $batch = optional($presUsed)->batch;
                $presModel = optional($batch)->presentation;

                $catalog = optional(optional($mm->medicamentoOnco)->catalog);
                $catalogId = optional($catalog)->id;

                if (!$presModel && $catalogId) {
                    $fallback = $presentacionFallbackPorCatalogo->get($catalogId);

                    if ($fallback) {
                        $presModel = (object) $fallback;
                    }
                }

                $lote = optional($presUsed)->lote_usado ?? (optional($batch)->lote ?? null);
                $caducidad = optional($presUsed)->caducidad_usada ?? (optional($batch)->caducidad ?? null);

                if (!$legendProteccion) {
                    $candLegend = trim((string) optional($presModel)->legend);

                    if ($candLegend !== '') {
                        $legendProteccion = $candLegend;
                    }
                }

                $denom = trim((string) (optional($catalog)->denominacion ?? ''));
                $marca = trim((string) (optional($presModel)->marca ?? ''));

                $fallbackNombre = $mm->nombre_medicamento ?? 'Medicamento';
                $fallbackNombre = trim(preg_replace('/\s*\(undefined\)\s*/i', '', $fallbackNombre));

                $nombreMed = $denom !== ''
                    ? ($marca !== '' ? "{$denom} ({$marca})" : $denom)
                    : $fallbackNombre;

                $volOrdenPrep = 0.0;
                $volumenesPorFrasco = [];

                $cantidadMedicamentoMg = (float) (optional($presModel)->cantidad_medicamento ?? 0);
                $volumenDiluyenteMl = (float) (optional($presModel)->volumen_diluyente ?? 0);
                $unidadesUsadas = max(1, (int) (optional($presUsed)->unidades_usadas ?? 1));

                if ($dosisRestanteMg > 0 && $cantidadMedicamentoMg > 0 && $volumenDiluyenteMl > 0) {
                    for ($i = 0; $i < $unidadesUsadas && $dosisRestanteMg > 0; $i++) {
                        $mgTomadosDeEsteFrasco = min($dosisRestanteMg, $cantidadMedicamentoMg);

                        if ($mgTomadosDeEsteFrasco <= 0) {
                            continue;
                        }

                        $volumenTomadoDeEsteFrasco = ($mgTomadosDeEsteFrasco * $volumenDiluyenteMl) / $cantidadMedicamentoMg;

                        $volumenesPorFrasco[] = $volumenTomadoDeEsteFrasco;
                        $volOrdenPrep += $volumenTomadoDeEsteFrasco;
                        $dosisRestanteMg -= $mgTomadosDeEsteFrasco;
                    }
                }

                if ($volOrdenPrep <= 0) {
                    $volOrdenPrep = 0.0;
                }

                return (object) [
                    'lote' => $lote,
                    'caducidad' => $caducidad,
                    'unidades_usadas' => optional($presUsed)->unidades_usadas ?? null,
                    'denominacion' => optional($catalog)->denominacion ?? null,
                    'presentacion' => optional($presModel)->presentacion ?? null,
                    'forma_reconstitucion' => optional($presModel)->forma_reconstitucion ?? null,
                    'dosis' => $mm->dosis,

                    'volumen_orden_preparacion' => $volOrdenPrep,
                    'volumenes_por_frasco' => $volumenesPorFrasco,
                    'volumen_diluyente' => null,
                    'volumen_total' => $mezcla->volumen_dilucion,

                    'nombre_para_texto' => $nombreMed,
                ];
            });
        })->values();

        $medicamentosAgrupados = $medicamentos
            ->groupBy(function ($m) {
                return $m->nombre_para_texto;
            });

        $totalVolMedicamentos = $medicamentos->sum(function ($m) {
            return (float) ($m->volumen_orden_preparacion ?? 0);
        });

        if (!$legendProteccion) $legendProteccion = '—';

        // ✅ Diluyente restante GLOBAL (correcto)
        $extraerTotal = (float) $totalVolMedicamentos;

        $diluyenteRestante = $volumenTotalSolicitado - $extraerTotal;
        if ($diluyenteRestante < 0) $diluyenteRestante = 0.0;

        // Si quieres mostrarlo en tabla, pon el mismo valor en todas las filas
        $medicamentos->each(function ($m) use ($diluyenteRestante) {
            $m->volumen_diluyente = $diluyenteRestante;
        });

        $fmt = function ($n) {
            return rtrim(rtrim(number_format((float)$n, 2, '.', ''), '0'), '.');
        };

        $extraerTotalFmt          = $fmt($extraerTotal);
        $diluyenteRestanteFmt     = $fmt($diluyenteRestante);
        $volTotalFmt              = $fmt($volumenTotalSolicitado);

        // ===== Texto final "Agregar" =====
        $partesMed = $medicamentos
            ->flatMap(function ($m) use ($fmt) {
                $nom = $m->nombre_para_texto ?? 'Medicamento';
                $volumenesPorFrasco = collect($m->volumenes_por_frasco ?? []);

                if ($volumenesPorFrasco->isNotEmpty()) {
                    return $volumenesPorFrasco
                        ->filter(fn($v) => (float) $v > 0)
                        ->map(function ($v) use ($fmt, $nom) {
                            return $fmt($v) . " mL de {$nom}";
                        });
                }

                $volumenTotal = (float) ($m->volumen_orden_preparacion ?? 0);
                if ($volumenTotal <= 0) {
                    return [];
                }

                return [$fmt($volumenTotal) . " mL de {$nom}"];
            })
            ->values()
            ->all();

        $detalleAgregar = [];
        if (count($partesMed) > 0) {
            // ✅ Si hay medicamento con infusor: NO "a restante", sino "y VOLUMEN TOTAL ... al infusor"
            if ($ocultarExtraer) {
                $detalleAgregar[] = implode(' + ', $partesMed) . " y {$volTotalFmt} mL de {$diluyenteBase} al infusor.";
            } else {
                $detalleAgregar[] = implode(' + ', $partesMed) . " a {$diluyenteRestanteFmt} mL de {$diluyenteBase}";
            }
        }

        // ===== Concentración final =====
        $dosisTotalMg    = $mezcla->medicamentos->sum(fn($m) => (float) ($m->dosis ?? 0));
        $volumenFinalMl  = (float) ($mezcla->volumen_dilucion ?? 0);

        $concentracionFinal = null;
        if ($dosisTotalMg > 0 && $volumenFinalMl > 0) {
            $concentracionFinal = $dosisTotalMg / $volumenFinalMl;
        }

        $concentracionFinalFmt = $concentracionFinal !== null
            ? rtrim(rtrim(number_format($concentracionFinal, 4, '.', ''), '0'), '.')
            : '—';


        $pdf = Pdf::loadView('pdfs.oncologicos.orden-de-preparacion', [
            'mezcla'               => $mezcla,
            'medicamentos'         => $medicamentos,
            'medicamentosAgrupados' => $medicamentosAgrupados,
            'volumen_diluyente_restante' => $diluyenteRestanteFmt,
            'fecha_preparacion'    => $fechaPreparacion,
            'fecha_limite_uso'     => $fechaLimiteUso,
            'hospital'             => $hospital,

            'valido_nombre'        => $validoNombre,
            'aprobo_nombre'        => $aproboNombre,
            'reviso_nombre'        => $revisoNombre,
            'preparo_nombre'       => $preparoNombre,
            'libero_nombre'        => $liberoNombre,

            'preparadaPor'         => $preparadaPor,

            'equipoInfusion'       => $equipoInfusion,
            'concentracion_final'  => $concentracionFinalFmt,

            'diluyente_base'       => $diluyenteBase,
            'extraer_ml'           => $extraerTotalFmt,
            'detalle_agregar'      => $detalleAgregar,
            'legend_proteccion'    => $legendProteccion,

            // ✅ NUEVO: para ocultar Extraer en Blade
            'ocultarExtraer'       => $ocultarExtraer,
        ])->setPaper('letter', 'portrait');

        return $pdf->stream("orden-preparacion-{$mezcla->id}.pdf");
    }


    public function inspeccion(Mezcla $mezcla)
    {
        // ✅ Cargar todo lo útil para el PDF
        $mezcla = Mezcla::with([
            'solicitud.hospital',
            'inspeccion',
        ])->findOrFail($mezcla->id);

        // ✅ Asegurar que exista inspección (para evitar null en la vista)
        $inspeccion = $mezcla->inspeccion ?: InspeccionMezcla::firstOrCreate(
            ['mezcla_id' => $mezcla->id],
            [
                'fecha_inspeccion' => now()->toDateString(),
                'hora_inspeccion'  => now()->format('H:i:s'),

                // 👇 si tu tabla tiene NOT NULL en estos campos (como en tu update)
                'reviso_nombre'    => '',
                'aprobo_nombre'    => '',
            ]
        );

        $pdf = Pdf::loadView('pdfs.oncologicos.inspeccion', [
            'mezcla'     => $mezcla,
            'solicitud'  => $mezcla->solicitud,
            'inspeccion' => $inspeccion,
            'hospital'   => optional(optional($mezcla->solicitud)->hospital)->name ?? '—', // por si lo ocupas en Blade
        ])->setPaper('letter', 'portrait');

        return $pdf->stream("inspeccion-mezcla-{$mezcla->id}.pdf");
    }


    public function etiqueta(Mezcla $mezcla)
    {
        // ✅ Bloquear clientes (maneja Cliente/cliente)
        if (auth()->user()?->hasAnyRole(['Cliente', 'cliente', 'Institucion'])) {
            abort(403, 'No autorizado');
        }

        $mezcla = Mezcla::with([
            'solicitud.hospital',               // ✅ Cliente (hospital)
            'inspeccion',                       // ✅ preparo_nombre
            'diluentPresentation.diluent',      // ✅ diluyente
            'medicamentos.presentacionesUsadas.batch.presentation', // ✅ marca/leyenda/temp/estabilidad
            'medicamentos.medicamentoOnco.catalog',                 // ✅ denominacion genérica
        ])->findOrFail($mezcla->id);

        $aprobada = null;

        // =========================================
        // ✅ Cliente = Hospital
        // =========================================
        $cliente = optional(optional($mezcla->solicitud)->hospital)->name ?? '—';

        // =========================================
        // ✅ Preparada por (SOLO este campo desde inspeccion_mezclas)
        // =========================================
        $preparadaPor = $this->nombreUsuarioParaPdf(optional($mezcla->inspeccion)->preparo_nombre);

        // =========================================
        // 0) Observaciones (para etiqueta)
        // =========================================
        $observaciones = $mezcla->solicitud->observaciones ?? null;
        $showLabelLotExpiry = false;

        $medicineListColumn = $this->medicineListColumnFor($mezcla->solicitud);
        $medicineListId = (int) (optional(optional($mezcla->solicitud)->hospital)->{$medicineListColumn} ?? 0);
        if ($medicineListId > 0) {
            $showLabelLotExpiry = (bool) optional(MedicineList::find($medicineListId))->show_label_lot_expiry;
        }

        $qrUrl = URL::signedRoute('qr.oncologicos.mezclas.show', ['mezcla' => $mezcla->id]);
        $qrRenderer = new ImageRenderer(new RendererStyle(76, 1), new SvgImageBackEnd());
        $qrSvg = (new Writer($qrRenderer))->writeString($qrUrl);
        $qrImage = 'data:image/svg+xml;base64,' . base64_encode($qrSvg);

        // =========================================
        // 1) Medicamentos: nombre (denominación + marca) + dosis + lote + caducidad
        // =========================================
        $medicamentosTabla = $mezcla->medicamentos->map(function ($m) {

            $catalog = optional(optional($m->medicamentoOnco)->catalog);
            $denom   = trim((string) ($catalog->denominacion ?? ''));

            $presentaciones = $m->presentacionesUsadas ?? collect();
            $firstUsed = $presentaciones->first();

            // Presentation model (por relación cargada)
            $presentation = optional(optional($firstUsed)->batch)->presentation
                ?? optional($firstUsed)->presentation
                ?? null;

            $marca = trim((string) (optional($presentation)->marca ?? ''));

            // Nombre final
            if ($denom !== '') {
                $nombre = $marca !== '' ? "{$denom} ({$marca})" : $denom;
            } else {
                // Fallback: nombre_medicamento sin "(undefined)"
                $fallbackNombre = $m->nombre_medicamento ?? '—';
                $fallbackNombre = trim(preg_replace('/\s*\(undefined\)\s*/i', '', $fallbackNombre));
                $nombre = $fallbackNombre ?: '—';
            }

            $batch = optional($firstUsed)->batch;

            $lote = optional($firstUsed)->lote_usado
                ?? optional($batch)->lote
                ?? null;

            $cad = optional($firstUsed)->caducidad_usada
                ?? optional($batch)->caducidad
                ?? null;

            return (object) [
                'nombre' => $nombre,
                'dosis'  => $m->dosis ?? 0,
                'lote'   => $lote,
                'cad'    => $cad,
            ];
        });

        // =========================================
        // 2) Medicamento más restrictivo (menor stability_hours)
        // =========================================
        $chosenLegend = null;
        $chosenTempMin = null;
        $chosenTempMax = null;
        $chosenStabilityHours = null;

        foreach ($mezcla->medicamentos as $m) {
            $presentaciones = $m->presentacionesUsadas ?? collect();
            $firstUsed = $presentaciones->first();

            $presentation = optional(optional($firstUsed)->batch)->presentation
                ?? optional($firstUsed)->presentation
                ?? null;

            if (!$presentation) continue;

            $stability = isset($presentation->stability_hours) ? (int) $presentation->stability_hours : null;
            if (!$stability || $stability <= 0) continue;

            if ($chosenStabilityHours === null || $stability < $chosenStabilityHours) {
                $chosenStabilityHours = $stability;
                $chosenLegend = $presentation->legend ?? null;
                $chosenTempMin = $presentation->temp_min_c ?? null;
                $chosenTempMax = $presentation->temp_max_c ?? null;
            }
        }

        // Defaults visuales
        if (!$chosenLegend) $chosenLegend = '—';

        // =========================================
        // 3) Fechas preparación / límite
        // =========================================
        if (!empty($mezcla->inspeccion?->fecha_inspeccion)) {
            $fechaInspeccion = $mezcla->inspeccion->fecha_inspeccion;
            $horaInspeccion = $mezcla->inspeccion->hora_inspeccion ?: '00:00:00';
            $fechaPreparacion = Carbon::parse("{$fechaInspeccion} {$horaInspeccion}");
        } else {
            $fechaPreparacion = $mezcla->updated_at
                ? Carbon::parse($mezcla->updated_at)
                : null;
        }

        $fechaLimiteUso = null;
        if ($fechaPreparacion && $chosenStabilityHours) {
            $fechaLimiteUso = $fechaPreparacion->copy()->addHours($chosenStabilityHours);
        }

        // =========================================
        // 4) Texto del diluyente + lote/cad (desde diluent_presentation)
        // =========================================
        $diluyenteTexto = '—';
        $diluyenteLote = null;
        $diluyenteCad  = null;

        if ($mezcla->diluentPresentation) {
            $dp = $mezcla->diluentPresentation;

            $nombreDil = optional($dp->diluent)->denominacion_generica;
            $volumen   = $dp->volume_ml ?? null;
            $present   = $dp->presentacion ?? null;

            if ($nombreDil && $volumen) {
                $volFmt = rtrim(rtrim(number_format((float)$volumen, 2, '.', ''), '0'), '.');
                $diluyenteTexto = $nombreDil . ' de ' . $volFmt . ' ml';
            } elseif ($present) {
                $diluyenteTexto = $present;
            } elseif ($nombreDil) {
                $diluyenteTexto = $nombreDil;
            }

            $diluyenteLote = $dp->lote ?? null;
            $diluyenteCad  = $dp->caducidad ?? null;
        }

        // ✅ PDF (7.5cm x 5cm) en puntos: 212.6 x 141.7
        // OJO: si pones 'landscape' Dompdf rota y te invierte el tamaño.
        // Para que quede ANCHO 7.5cm y ALTO 5cm, usa 'portrait' con este array:
        $customPaper = [0, 0, 212.6, 141.7];

        $pdf = Pdf::loadView('pdfs.oncologicos.etiqueta', [
            'mezcla'            => $mezcla,
            'solicitud'         => $mezcla->solicitud,
            'aprobada'          => $aprobada,

            'cliente'           => $cliente,
            'preparadaPor'      => $preparadaPor,

            'medicamentos'      => $medicamentosTabla,

            'diluyenteTexto'    => $diluyenteTexto,
            'diluyenteLote'     => $diluyenteLote,
            'diluyenteCad'      => $diluyenteCad,

            'observaciones'     => $observaciones,

            'fechaPreparacion'  => $fechaPreparacion,
            'fechaLimiteUso'    => $fechaLimiteUso,
            'qrUrl'             => $qrUrl,
            'qrSvg'             => $qrSvg,
            'qrImage'           => $qrImage,
            'legendEtiqueta'    => $chosenLegend,
            'tempMinEtiqueta'   => $chosenTempMin,
            'tempMaxEtiqueta'   => $chosenTempMax,
            'stabilityEtiqueta' => $chosenStabilityHours,
            'showLabelLotExpiry' => $showLabelLotExpiry,
        ])->setPaper($customPaper, 'portrait'); // ✅ importante

        return $pdf->stream();
    }
}
