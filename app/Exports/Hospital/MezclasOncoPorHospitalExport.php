<?php

namespace App\Exports\Hospital;

use App\Models\Oncologicos\SolicitudOnco;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class MezclasOncoPorHospitalExport implements FromArray, WithHeadings, ShouldAutoSize
{
    public function __construct(private int $hospitalId) {}

    public function headings(): array
    {
        return [
            // Hospital / Usuario
            'Hospital',
            'Usuario',

            // Solicitud
            'Solicitud ID',
            'Servicio',
            'Estado Solicitud',
            'Fecha Solicitud',
            'Fecha Entrega',
            'Registro Paciente',
            'Paciente',
            'Sexo',
            'Fecha Nacimiento',
            'Edad',
            'Peso',
            'Cama',
            'Piso',
            'Alergias',
            'Diagnóstico',
            'Médico',
            'Cédula Médico',
            'Observaciones',
            'Remisión (solicitud)',

            // Mezcla
            'Mezcla ID',
            'Estado Mezcla',
            'Remisión (mezcla)',
            'Lote Mezcla',
            'Volumen Dilución',
            'Tiempo Infusión',
            'Set Infusión',
            'Infusor',

            // Medicamento (renglón)
            'MezclaMedicamento ID',
            'Medicamento (texto)',
            'Catálogo Denominación',
            'Marca/Comercial',
            'Requiere Infusor',
            'Conc Min',
            'Conc Max',
            'Dosis',
            'Dosis mL',
            'Diluyente',
            'Vía Administración',

            // Presentaciones usadas (resumen)
            'Presentaciones Usadas (lotes/caducidad/unidades)',

            // Cobro (misma lógica que remisión)
            'Unidad Cobro',
            'Cantidad Cobro',
            'Precio Unitario',
            'Subtotal',
        ];
    }

    public function array(): array
    {
        // 1) Traer solicitudes onco de usuarios del hospital
        $solicitudes = SolicitudOnco::with([
            'user.hospital',
            'user.medicineList.distributor',
            'mezclas.infusor',
            'mezclas.medicamentos.medicamentoOnco.catalog',
            'mezclas.medicamentos.diluyente',
            'mezclas.medicamentos.viaAdministracion',
            'mezclas.medicamentos.presentacionesUsadas.batch.presentation',
        ])
        ->whereHas('user', fn($q) => $q->where('hospital_id', $this->hospitalId))
        ->latest()
        ->get();

        $rows = [];

        foreach ($solicitudes as $solicitud) {

            $hospitalNombre = optional(optional($solicitud->user)->hospital)->name ?? '';
            $usuarioNombre  = trim(($solicitud->user->name ?? '').' '.($solicitud->user->lastname ?? ''));

            // === Configuración de cobro por LISTA (igual que tu remision) ===
            $lista       = optional($solicitud->user)->medicineList;
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

                    // ====== Presentaciones usadas (para resumen en una celda) ======
                    $presentaciones = $med->presentacionesUsadas ?? collect();
                    $presentacionesResumen = '';

                    if ($presentaciones->isNotEmpty()) {
                        $partes = [];
                        foreach ($presentaciones as $pu) {
                            $lote = $pu->lote_usado ?? '';
                            $cad  = $pu->caducidad_usada ?? '';
                            $uni  = $pu->unidades_usadas ?? 1;

                            $presTxt =
                                optional(optional($pu->batch)->presentation)->presentacion
                                ?? optional($pu->presentation)->presentacion
                                ?? '';

                            $partes[] = trim("{$presTxt} | lote: {$lote} | cad: {$cad} | unid: {$uni}");
                        }
                        $presentacionesResumen = implode(' ; ', $partes);
                    }

                    // ====== Calcular cobro igual que remisión ======
                    [$unidadCobro, $cantidad, $precioUnit, $subtotal] =
                        $this->calcularCobro($med, $presentaciones, $cfgPorPresentacion, $listaCharge);

                    // Info catálogo
                    $catalog = optional(optional($med->medicamentoOnco)->catalog);

                    $rows[] = [
                        // Hospital/Usuario
                        $hospitalNombre,
                        $usuarioNombre,

                        // Solicitud
                        $solicitud->id,
                        $solicitud->servicio,
                        $solicitud->estado,
                        $solicitud->created_at,
                        $solicitud->fecha_entrega,
                        $solicitud->registro_paciente,
                        $solicitud->nombre_paciente,
                        $solicitud->sexo,
                        $solicitud->fecha_nacimiento,
                        $solicitud->edad,
                        $solicitud->peso,
                        $solicitud->cama,
                        $solicitud->piso,
                        $solicitud->alergias,
                        $solicitud->diagnostico,
                        $solicitud->nombre_medico,
                        $solicitud->cedula_medico,
                        $solicitud->observaciones,
                        $solicitud->remision,

                        // Mezcla
                        $mezcla->id,
                        $mezcla->estado,
                        $mezcla->remision,
                        $mezcla->lote,
                        $mezcla->volumen_dilucion,
                        $mezcla->tiempo_infusion,
                        $mezcla->set_infusion ? 'SI' : 'NO',
                        optional($mezcla->infusor)->nombre_comercial
                            ?? optional($mezcla->infusor)->nombre_generico
                            ?? '',

                        // Medicamento
                        $med->id,
                        $med->nombre_medicamento,
                        $catalog->denominacion ?? '',
                        ($catalog->requires_infusor ?? false) ? 'SI' : 'NO',
                        $catalog->conc_min,
                        $catalog->conc_max,
                        $med->dosis,
                        $med->dosis_ml,
                        optional($med->diluyente)->denominacion_generica ?? '',
                        optional($med->viaAdministracion)->name ?? '',

                        // Presentaciones
                        $presentacionesResumen,

                        // Cobro
                        $unidadCobro,
                        $cantidad,
                        $precioUnit,
                        $subtotal,
                    ];
                }
            }
        }

        // Si no hay nada, igual devuelve encabezados pero sin filas (ok)
        return $rows;
    }

    private function calcularCobro($med, $presentaciones, $cfgPorPresentacion, $listaCharge): array
    {
        $cantidad    = 0.0;
        $precioUnit  = 0.0;
        $subtotal    = 0.0;
        $unidadCobro = null;

        if ($presentaciones->isEmpty()) {
            $unidadCobro = $listaCharge === 'mg' ? 'mg' : 'frasco';
            $cantidad    = $unidadCobro === 'mg' ? (float)($med->dosis ?? 0) : 1;
            $precioUnit  = 0.0;
            $subtotal    = 0.0;
        } else {

            $first = $presentaciones->first();

            $presentationId =
                optional($first->batch)->medicine_presentation_id
                ?? optional(optional($first->batch)->presentation)->id
                ?? optional($first->presentation)->id;

            $cfg = $presentationId ? $cfgPorPresentacion->get($presentationId) : null;
            $chargeBy = $cfg->charge_by ?? $listaCharge;

            $unidadCobro = $chargeBy === 'mg' ? 'mg' : 'frasco';

            if ($chargeBy === 'frasco') {

                $subtotal = 0.0;
                $unidadesTotales = 0.0;

                foreach ($presentaciones as $pu) {

                    $pid =
                        optional($pu->batch)->medicine_presentation_id
                        ?? optional(optional($pu->batch)->presentation)->id
                        ?? optional($pu->presentation)->id;

                    $cfgPres = $pid ? $cfgPorPresentacion->get($pid) : null;

                    $precioFrascoLista = (float)($cfgPres->precio ?? 0);
                    $unidades = (float)($pu->unidades_usadas ?? 0);
                    if ($unidades <= 0) $unidades = 1;

                    $unidadesTotales += $unidades;
                    $subtotal += ($precioFrascoLista * $unidades);
                }

                $cantidad = $unidadesTotales;
                $precioUnit = $unidadesTotales > 0 ? ($subtotal / $unidadesTotales) : 0.0;

            } else {

                $dosis = (float)($med->dosis ?? 0);
                $precioMgLista = (float)($cfg->precio_mg_override ?? 0);

                $cantidad   = $dosis;
                $precioUnit = $precioMgLista;
                $subtotal   = $cantidad * $precioUnit;
            }
        }

        return [
            $unidadCobro,
            round($cantidad, 4),
            round($precioUnit, 4),
            round($subtotal, 2),
        ];
    }
}
