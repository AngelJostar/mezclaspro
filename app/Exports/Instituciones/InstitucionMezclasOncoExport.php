<?php

namespace App\Exports\Instituciones;

use App\Models\Institucion;
use App\Models\Oncologicos\Mezcla;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class InstitucionMezclasOncoExport implements FromArray, WithHeadings, ShouldAutoSize
{
    public function __construct(private int $institucionId) {}

    public function array(): array
    {
        $rows = [];

        $institucion = Institucion::with('hospitals')->findOrFail($this->institucionId);
        $hospitalIds = $institucion->hospitals->pluck('id')->values()->all();

        if (empty($hospitalIds)) {
            return [];
        }

        $mezclas = Mezcla::with([
            'solicitud.hospital',
            'solicitud.user',
            'solicitud.user.medicineList',
            'medicamentos.medicamentoOnco.catalog',
            'medicamentos.diluyente',
            'medicamentos.presentacionesUsadas.batch.presentation',
        ])
            ->whereHas('solicitud', function ($q) use ($hospitalIds) {
                $q->whereIn('hospital_id', $hospitalIds);
            })
            ->get()
            ->sortBy(function ($mezcla) {
                return optional(optional($mezcla->solicitud)->hospital)->name ?? '';
            });

        foreach ($mezclas as $mezcla) {
            $solicitud = $mezcla->solicitud;
            $user = optional($solicitud)->user;
            $hospital = optional($solicitud?->hospital)->name;
            $usuario = trim(($user?->name ?? '') . ' ' . ($user?->lastname ?? ''));

            $lista = $user?->medicineList;
            $listaCharge = $lista->charge_by ?? 'frasco';
            $cfgPorPresentacion = collect();

            if ($lista) {
                $cfgPorPresentacion = DB::table('medicine_list_presentation')
                    ->where('medicine_list_id', $lista->id)
                    ->get()
                    ->keyBy('medicine_presentation_id');
            }

            foreach ($mezcla->medicamentos as $med) {
                $denominacion = optional(optional($med->medicamentoOnco)->catalog)->denominacion
                    ?? $med->nombre_medicamento
                    ?? '-';

                $firstUsed = ($med->presentacionesUsadas ?? collect())->first();
                $presentation = optional(optional($firstUsed)->batch)->presentation
                    ?? optional($firstUsed)->presentation
                    ?? null;

                $marca = $presentation?->marca ?? '-';
                $unidadCobro = '';
                $cantidad = 0;
                $precioUnit = 0;
                $subtotal = 0;
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
                            $precio = (float) ($cfgPres->precio ?? 0);
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
                        $cantidad = (float) $med->dosis;
                        $precioUnit = (float) ($cfg?->precio_mg_override ?? 0);
                        $subtotal = $cantidad * $precioUnit;
                    }
                }

                $rows[] = [
                    $institucion->nombre ?? '',
                    $hospital,
                    $usuario,
                    $lista->name ?? 'N/A',
                    $solicitud?->id ?? '-',
                    $solicitud?->created_at ?? '-',
                    $denominacion,
                    $marca,
                    $solicitud?->estado ?? '-',
                    $mezcla->id,
                    $mezcla->estado,
                    $mezcla->volumen_dilucion,
                    $mezcla->tiempo_infusion,
                    $mezcla->set_infusion ? 'Si' : 'No',
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

        return $rows;
    }

    public function headings(): array
    {
        return [
            'Institucion',
            'Hospital',
            'Usuario',
            'Lista de precios',
            'ID Solicitud',
            'Fecha solicitud',
            'Denominacion',
            'Marca',
            'Estado solicitud',
            'ID Mezcla',
            'Estado mezcla',
            'Volumen dilucion',
            'Tiempo infusion',
            'Set infusion',
            'Dosis',
            'Unidad cobro',
            'Cantidad cobro',
            'Precio unitario',
            'Subtotal',
            'Diluyente',
            'Remision',
        ];
    }
}
