<?php

namespace App\Exports\Oncologicos;

use App\Models\Oncologicos\SolicitudOnco;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class SolicitudesOncoExport implements FromArray, WithHeadings, ShouldAutoSize
{
    public function array(): array
    {
        $rows = [];

        $solicitudes = SolicitudOnco::with([
            'hospital',
            'user.medicineList',
            'mezclas.medicamentos.medicamentoOnco.catalog',
            'mezclas.medicamentos.diluyente',
            'mezclas.medicamentos.presentacionesUsadas.batch.presentation',
        ])->get();

        foreach ($solicitudes as $solicitud) {

            $hospital = optional($solicitud->hospital)->name; // ✅ snapshot correcto
            $usuario  = trim((optional($solicitud->user)->name ?? '') . ' ' . (optional($solicitud->user)->lastname ?? ''));
            $lista    = optional($solicitud->user->medicineList);
            $listaCharge = $lista->charge_by ?? 'frasco';

            $cfgPorPresentacion = collect();

            if ($lista) {
                $cfgPorPresentacion = DB::table('medicine_list_presentation')
                    ->where('medicine_list_id', $lista->id)
                    ->get()
                    ->keyBy('medicine_presentation_id');
            }

            foreach ($solicitud->mezclas as $mezcla) {
                foreach ($mezcla->medicamentos as $med) {

                    // =========================
                    // ✅ Denominación (catalog)
                    // =========================
                    $denominacion = optional(optional($med->medicamentoOnco)->catalog)->denominacion
                        ?? $med->nombre_medicamento
                        ?? '—';

                    // =========================
                    // ✅ Marca (presentation)
                    // Tomamos la primera presentación usada (si existe)
                    // =========================
                    $firstUsed = ($med->presentacionesUsadas ?? collect())->first();
                    $presentation = optional(optional($firstUsed)->batch)->presentation
                        ?? optional($firstUsed)->presentation
                        ?? null;

                    $marca = $presentation?->marca ?? '—';

                    // ===== LÓGICA DE COBRO (MISMA QUE REMISIÓN) =====
                    $unidadCobro = '';
                    $cantidad    = 0;
                    $precioUnit  = 0;
                    $subtotal    = 0;

                    $presentaciones = $med->presentacionesUsadas ?? collect();

                    if ($presentaciones->isEmpty()) {
                        $unidadCobro = in_array($med->charge_by ?: $listaCharge, ['mg', 'ml'], true)
                            ? ($med->charge_by ?: $listaCharge)
                            : 'frasco';
                        $cantidad = $unidadCobro === 'mg'
                            ? (float) $med->dosis
                            : ($unidadCobro === 'ml' ? (float) $med->dosis_ml : 1);
                    } else {
                        $first = $presentaciones->first();
                        $presentationId =
                            optional($first->batch)->medicine_presentation_id
                            ?? optional(optional($first->batch)->presentation)->id;

                        $cfg = $cfgPorPresentacion->get($presentationId);
                        $chargeBy = $first->charge_by_snapshot ?? $cfg?->charge_by ?? $med->charge_by ?? $listaCharge;

                        $unidadCobro = in_array($chargeBy, ['mg', 'ml'], true) ? $chargeBy : 'frasco';

                        if ($chargeBy === 'frasco') {
                            foreach ($presentaciones as $pu) {
                                $pid =
                                    optional($pu->batch)->medicine_presentation_id
                                    ?? optional(optional($pu->batch)->presentation)->id;

                                $cfgPres = $cfgPorPresentacion->get($pid);
                                $precio  = (float) ($cfgPres->precio ?? 0);
                                $unidades = (float) ($pu->unidades_usadas ?? 1);

                                $cantidad += $unidades;
                                $subtotal += $precio * $unidades;
                            }

                            $precioUnit = $cantidad > 0 ? $subtotal / $cantidad : 0;
                        } elseif ($chargeBy === 'ml') {
                            $cantidad = (float) $presentaciones->sum('volumen_usado_ml');
                            $subtotal = (float) $presentaciones->sum('subtotal');
                            $precioUnit = $cantidad > 0
                                ? $subtotal / $cantidad
                                : (float) ($first->precio_unitario_snapshot ?? $cfg?->precio_ml_override ?? $med->precio_ml_snapshot ?? 0);
                        } else {
                            $cantidad   = (float) $med->dosis;
                            $precioUnit = (float) ($cfg?->precio_mg_override ?? 0);
                            $subtotal   = $cantidad * $precioUnit;
                        }
                    }

                    $rows[] = [
                        $hospital,
                        $usuario,
                        $lista->name ?? 'N/A',
                        $solicitud->id,
                        $solicitud->created_at,

                        // ✅ NUEVAS (después de fecha solicitud)
                        $denominacion,
                        $marca,

                        $solicitud->estado,
                        $mezcla->id,
                        $mezcla->estado,
                        $mezcla->volumen_dilucion,
                        $mezcla->tiempo_infusion,

                        $mezcla->set_infusion ? 'Sí' : 'No',

                        $med->dosis,
                        $unidadCobro,
                        $cantidad,
                        round($precioUnit, 4),

                        round($subtotal, 2),
                        optional($med->diluyente)->denominacion_generica,
                        $mezcla->remision,
                    ];
                }
            }
        }

        return $rows;
    }

    public function headings(): array
    {
        return [
            'Hospital',
            'Usuario',
            'Lista de precios',
            'ID Solicitud',
            'Fecha solicitud',

            // ✅ NUEVAS
            'Denominación',
            'Marca',

            'Estado solicitud',
            'ID Mezcla',
            'Estado mezcla',
            'Volumen dilución',
            'Tiempo infusión',

            'Set infusión',
            'Dosis',
            'Unidad cobro',
            'Cantidad cobro',

            'Precio unitario',
            'Subtotal',
            'Diluyente',
            'Remisión',
        ];
    }
}
