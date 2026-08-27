<?php

namespace App\Exports\Instituciones;

use App\Models\Institucion;
use App\Models\Nutricionales\Solicitud as NutricionalSolicitud;
use App\Models\Oncologicos\Mezcla;
use App\Services\InstitutionReportTemplateService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class InstitucionHospitalExport implements FromArray, WithHeadings, ShouldAutoSize, WithStyles
{
    private ?array $templateContext = null;

    public function __construct(private int $institucionId) {}

    public function array(): array
    {
        $institucion = Institucion::with('hospitals')->findOrFail($this->institucionId);
        $hospitalIds = $institucion->hospitals->pluck('id')->values()->all();

        if (empty($hospitalIds)) {
            return [];
        }

        $rows = collect()
            ->concat($this->buildOncoRows($institucion, $hospitalIds))
            ->concat($this->buildNutriRows($institucion, $hospitalIds))
            ->sortBy([
                ['hospital_nombre', 'asc'],
                ['fecha_orden', 'asc'],
                ['remision', 'asc'],
                ['orden', 'asc'],
            ])
            ->values()
            ->map(function (array $row) {
                return [
                    $row['institucion'],
                    $row['unidad'],
                    $row['nombre_medico'],
                    $row['nombre_paciente'],
                    $row['remision'],
                    $row['fecha_remision'],
                    $row['cantidad'],
                    $row['descripcion'],
                    $row['pv_unitario'],
                    $row['pv_total'],
                    $row['empresa'],
                    $row['conciliable'],
                    $row['folio_factura_uuid'],
                    $row['folio_factura_interno'],
                ];
            })
            ->all();

        return app(InstitutionReportTemplateService::class)->projectRows(
            InstitutionReportTemplateService::HOSPITAL,
            $rows
        );
    }

    public function headings(): array
    {
        return app(InstitutionReportTemplateService::class)->headingRows(
            InstitutionReportTemplateService::HOSPITAL,
            $this->context()
        );
    }

    public function styles(Worksheet $sheet): array
    {
        app(InstitutionReportTemplateService::class)->styleWorksheet(
            $sheet,
            InstitutionReportTemplateService::HOSPITAL,
            $this->context()
        );

        return [];
    }

    private function context(): array
    {
        if ($this->templateContext !== null) {
            return $this->templateContext;
        }

        $institucion = Institucion::findOrFail($this->institucionId);

        return $this->templateContext = [
            'institucion' => $institucion->nombre,
            'fecha_generacion' => now()->format('d/m/Y H:i'),
        ];
    }

    private function buildOncoRows(Institucion $institucion, array $hospitalIds): Collection
    {
        $mezclas = Mezcla::with([
            'billing',
            'solicitud.hospital',
            'solicitud.user.medicineList',
            'solicitud.user.hospital',
            'medicamentos.medicamentoOnco.catalog',
            'medicamentos.presentacionesUsadas.batch.presentation',
            'medicamentos.diluyente',
            'infusor',
        ])
            ->whereHas('solicitud', function ($query) use ($hospitalIds) {
                $query->whereIn('hospital_id', $hospitalIds);
            })
            ->get();

        $rows = collect();

        foreach ($mezclas as $mezcla) {
            $solicitud = $mezcla->solicitud;
            $hospital = $solicitud?->hospital;
            $billing = $mezcla->billing;
            $lista = $solicitud?->user?->medicineList;
            $listaCharge = $lista->charge_by ?? 'frasco';

            $cfgPorPresentacion = collect();
            if ($lista) {
                $cfgPorPresentacion = DB::table('medicine_list_presentation')
                    ->where('medicine_list_id', $lista->id)
                    ->get()
                    ->keyBy('medicine_presentation_id');
            }

            $medicationTotal = 0.0;
            $rowOrder = 1;

            foreach ($mezcla->medicamentos as $med) {
                [$cantidad, $precioUnitario, $subtotal] = $this->resolveOncoPricing($med, $cfgPorPresentacion, $listaCharge);
                $medicationTotal += $subtotal;

                $descripcion = $med->denominacion_snapshot
                    ?? optional(optional($med->medicamentoOnco)->catalog)->denominacion
                    ?? $med->nombre_medicamento
                    ?? 'Medicamento oncológico';

                $rows->push($this->baseRow(
                    institucion: $institucion,
                    hospitalNombre: $hospital?->name,
                    medico: $solicitud?->nombre_medico,
                    paciente: $solicitud?->nombre_paciente,
                    remision: $mezcla->remision ?: '—',
                    fecha: optional($mezcla->fecha_entrega ?? $solicitud?->fecha_entrega ?? $solicitud?->created_at)?->format('d/m/y') ?? '—',
                    descripcion: $descripcion,
                    cantidad: $this->formatNumber($cantidad, 2),
                    unitario: $precioUnitario,
                    total: $subtotal,
                    billing: $billing,
                    fechaOrden: $mezcla->fecha_entrega ?? $solicitud?->fecha_entrega ?? $solicitud?->created_at,
                    rowOrder: $rowOrder++,
                    empresa: $institucion->razon_social ?: $institucion->nombre
                ));
            }

            $billingTotal = $this->parseMoney($billing?->precio_total);
            $serviceTotal = $this->resolveOncoMixingServiceTotal($lista, $billingTotal, $medicationTotal);

            $rows->push($this->baseRow(
                institucion: $institucion,
                hospitalNombre: $hospital?->name,
                medico: $solicitud?->nombre_medico,
                paciente: $solicitud?->nombre_paciente,
                remision: $mezcla->remision ?: '—',
                fecha: optional($mezcla->fecha_entrega ?? $solicitud?->fecha_entrega ?? $solicitud?->created_at)?->format('d/m/y') ?? '—',
                descripcion: 'Servicio de Mezclado',
                cantidad: '1',
                unitario: $serviceTotal,
                total: $serviceTotal,
                billing: $billing,
                fechaOrden: $mezcla->fecha_entrega ?? $solicitud?->fecha_entrega ?? $solicitud?->created_at,
                rowOrder: $rowOrder,
                empresa: $institucion->razon_social ?: $institucion->nombre
            ));
        }

        return $rows;
    }

    private function buildNutriRows(Institucion $institucion, array $hospitalIds): Collection
    {
        $solicitudes = NutricionalSolicitud::with([
            'billing',
            'user.hospital',
            'solicitud_detail',
            'solicitud_patient',
            'input.input.nutritionMedicineCatalog',
            'input.presentation',
        ])
            ->whereHas('user', function ($query) use ($hospitalIds) {
                $query->whereIn('hospital_id', $hospitalIds);
            })
            ->get();

        $rows = collect();

        foreach ($solicitudes as $solicitud) {
            $hospital = $solicitud?->user?->hospital;
            $billing = $solicitud->billing;
            $inputs = $solicitud->input ?? collect();
            $rowOrder = 1;

            $serviceItem = $inputs->first(fn($item) => $this->isNutriService($item));
            $ivaItems = $inputs->filter(fn($item) => $this->isNutriIvaItem($item))->values();
            $medItems = $inputs->filter(function ($item) {
                return !$this->isNutriService($item) && !$this->isNutriIvaItem($item);
            });

            $medicationsTotal = round((float) $medItems->sum(fn($item) => (float) ($item->precio_ml ?? 0)), 2);
            $ivaTotal = round((float) $ivaItems->sum(fn($item) => (float) ($item->precio_ml ?? 0)), 2);
            $serviceTotal = round((float) ($serviceItem?->precio_ml ?? 0), 2);
            $computedGrandTotal = round($medicationsTotal + $ivaTotal + $serviceTotal, 2);

            if ($medicationsTotal > 0) {
                $rows->push($this->baseRow(
                    institucion: $institucion,
                    hospitalNombre: $hospital?->name,
                    medico: $solicitud?->solicitud_detail?->nombre_medico,
                    paciente: trim(($solicitud?->solicitud_patient?->nombre_paciente ?? '') . ' ' . ($solicitud?->solicitud_patient?->apellidos_paciente ?? '')),
                    remision: $solicitud->remision ?: '—',
                    fecha: optional($solicitud?->solicitud_detail?->fecha_hora_entrega ?? $solicitud?->created_at)?->format('d/m/y') ?? '—',
                    descripcion: 'Medicamento Nutricion Parenteral',
                    cantidad: '1',
                    unitario: $medicationsTotal,
                    total: $medicationsTotal,
                    billing: $billing,
                    fechaOrden: $solicitud?->solicitud_detail?->fecha_hora_entrega ?? $solicitud?->created_at,
                    rowOrder: $rowOrder++,
                    empresa: $institucion->razon_social ?: $institucion->nombre,
                    fallbackPrecioTotal: $computedGrandTotal
                ));
            }

            foreach ($ivaItems as $ivaItem) {
                $rows->push($this->baseRow(
                    institucion: $institucion,
                    hospitalNombre: $hospital?->name,
                    medico: $solicitud?->solicitud_detail?->nombre_medico,
                    paciente: trim(($solicitud?->solicitud_patient?->nombre_paciente ?? '') . ' ' . ($solicitud?->solicitud_patient?->apellidos_paciente ?? '')),
                    remision: $solicitud->remision ?: '—',
                    fecha: optional($solicitud?->solicitud_detail?->fecha_hora_entrega ?? $solicitud?->created_at)?->format('d/m/y') ?? '—',
                    descripcion: $this->nutriIvaDescription($ivaItem),
                    cantidad: '1',
                    unitario: (float) ($ivaItem->precio_ml ?? 0),
                    total: (float) ($ivaItem->precio_ml ?? 0),
                    billing: $billing,
                    fechaOrden: $solicitud?->solicitud_detail?->fecha_hora_entrega ?? $solicitud?->created_at,
                    rowOrder: $rowOrder++,
                    empresa: $institucion->razon_social ?: $institucion->nombre,
                    fallbackPrecioTotal: $computedGrandTotal
                ));
            }

            $rows->push($this->baseRow(
                institucion: $institucion,
                hospitalNombre: $hospital?->name,
                medico: $solicitud?->solicitud_detail?->nombre_medico,
                paciente: trim(($solicitud?->solicitud_patient?->nombre_paciente ?? '') . ' ' . ($solicitud?->solicitud_patient?->apellidos_paciente ?? '')),
                remision: $solicitud->remision ?: '—',
                fecha: optional($solicitud?->solicitud_detail?->fecha_hora_entrega ?? $solicitud?->created_at)?->format('d/m/y') ?? '—',
                descripcion: 'Servicio de Mezclado',
                cantidad: '1',
                unitario: $serviceTotal,
                total: $serviceTotal,
                billing: $billing,
                fechaOrden: $solicitud?->solicitud_detail?->fecha_hora_entrega ?? $solicitud?->created_at,
                rowOrder: $rowOrder,
                empresa: $institucion->razon_social ?: $institucion->nombre,
                fallbackPrecioTotal: $computedGrandTotal
            ));
        }

        return $rows;
    }

    private function resolveOncoPricing($med, Collection $cfgPorPresentacion, string $listaCharge): array
    {
        $cantidad = 0.0;
        $precioUnit = 0.0;
        $subtotal = 0.0;
        $presentaciones = $med->presentacionesUsadas ?? collect();

        if ($presentaciones->isEmpty()) {
            $unidadCobro = $listaCharge === 'mg' ? 'mg' : 'frasco';
            $cantidad = $unidadCobro === 'mg' ? (float) ($med->dosis ?? 0) : 1.0;

            if ($unidadCobro === 'mg') {
                $precioUnit = (float) ($med->precio_mg_snapshot ?? 0);
                $subtotal = $cantidad * $precioUnit;
            }

            return [round($cantidad, 2), round($precioUnit, 4), round($subtotal, 2)];
        }

        $first = $presentaciones->first();
        $presentationId =
            optional($first->batch)->medicine_presentation_id
            ?? optional(optional($first->batch)->presentation)->id
            ?? optional($first->presentation)->id;

        $cfg = $presentationId ? $cfgPorPresentacion->get($presentationId) : null;
        $chargeBy = $cfg->charge_by ?? $listaCharge;

        if ($chargeBy === 'frasco') {
            foreach ($presentaciones as $pu) {
                $unidades = (float) ($pu->unidades_usadas ?? 0);
                if ($unidades <= 0) {
                    $unidades = 1;
                }

                if (!is_null($pu->subtotal)) {
                    $cantidad += $unidades;
                    $subtotal += (float) $pu->subtotal;
                    continue;
                }

                if (!is_null($pu->precio_frasco_snapshot)) {
                    $cantidad += $unidades;
                    $subtotal += ((float) $pu->precio_frasco_snapshot * $unidades);
                    continue;
                }

                $pid =
                    optional($pu->batch)->medicine_presentation_id
                    ?? optional(optional($pu->batch)->presentation)->id
                    ?? optional($pu->presentation)->id;

                $cfgPres = $pid ? $cfgPorPresentacion->get($pid) : null;
                $precioFrasco = (float) ($cfgPres->precio ?? 0);

                $cantidad += $unidades;
                $subtotal += ($precioFrasco * $unidades);
            }

            $precioUnit = $cantidad > 0 ? $subtotal / $cantidad : 0.0;

            return [round($cantidad, 2), round($precioUnit, 4), round($subtotal, 2)];
        }

        $cantidad = (float) ($med->dosis ?? 0);
        $precioUnit = (float) ($cfg->precio_mg_override ?? $med->precio_mg_snapshot ?? 0);
        $subtotal = $cantidad * $precioUnit;

        return [round($cantidad, 2), round($precioUnit, 4), round($subtotal, 2)];
    }

    private function resolveOncoMixingServiceTotal($lista, float $billingTotal, float $medicationTotal): float
    {
        if ($lista && (bool) ($lista->has_mixing_service ?? false)) {
            return round((float) ($lista->mixing_service_price ?? 0), 2);
        }

        return $billingTotal > 0 ? max($billingTotal - $medicationTotal, 0) : 0.0;
    }

    private function baseRow(
        Institucion $institucion,
        ?string $hospitalNombre,
        ?string $medico,
        ?string $paciente,
        ?string $remision,
        string $fecha,
        string $descripcion,
        string $cantidad,
        float $unitario,
        float $total,
        $billing,
        $fechaOrden,
        int $rowOrder,
        string $empresa,
        ?float $fallbackPrecioTotal = null
    ): array {
        $precioTotalGlobal = $billing?->precio_total;
        if (($precioTotalGlobal === null || $precioTotalGlobal === '') && $fallbackPrecioTotal !== null) {
            $precioTotalGlobal = $this->formatMoney($fallbackPrecioTotal);
        }

        return [
            'institucion' => $institucion->nombre,
            'unidad' => $hospitalNombre ?: '—',
            'nombre_medico' => $medico ?: '—',
            'nombre_paciente' => trim((string) $paciente) !== '' ? trim((string) $paciente) : '—',
            'remision' => $remision ?: '—',
            'fecha_remision' => $fecha,
            'cantidad' => $cantidad,
            'descripcion' => $descripcion,
            'pv_unitario' => $this->formatMoney($unitario),
            'pv_total' => $this->formatMoney($total),
            'empresa' => $empresa,
            'precio_total' => $precioTotalGlobal ?: '—',
            'conciliable' => $billing?->conciliable ?: '—',
            'folio_factura_uuid' => $billing?->folio_factura_uuid ?: '—',
            'folio_factura_interno' => $billing?->folio_interno ?: '—',
            'hospital_nombre' => $hospitalNombre ?: '',
            'fecha_orden' => $fechaOrden ? strtotime((string) $fechaOrden) : 0,
            'orden' => $rowOrder,
        ];
    }

    private function isNutriService($item): bool
    {
        $description = mb_strtolower(trim((string) ($item?->input?->description ?? '')));
        return str_contains($description, 'preparación para npt')
            || str_contains($description, 'preparacion para npt');
    }

    private function isNutriIvaItem($item): bool
    {
        $description = mb_strtolower(trim((string) ($item?->input?->description ?? '')));

        return str_contains($description, 'bolsa eva')
            || str_contains($description, 'set de infusión')
            || str_contains($description, 'set de infusion');
    }

    private function nutriIvaDescription($item): string
    {
        $description = trim((string) ($item?->input?->description ?? ''));
        if ($description === '') {
            return 'Producto con IVA';
        }

        if (stripos($description, 'bolsa eva') !== false) {
            return 'Bolsa EVA';
        }

        if (stripos($description, 'set de infusión') !== false || stripos($description, 'set de infusion') !== false) {
            return 'Set de Infusión';
        }

        return $description;
    }

    private function formatMoney(float $value): string
    {
        return number_format($value, 2, '.', ',');
    }

    private function formatNumber(float $value, int $decimals = 2): string
    {
        return number_format($value, $decimals, '.', ',');
    }

    private function parseMoney($value): float
    {
        if ($value === null) {
            return 0.0;
        }

        if (is_numeric($value)) {
            return (float) $value;
        }

        $normalized = preg_replace('/[^0-9.\-]/', '', (string) $value);
        return is_numeric($normalized) ? (float) $normalized : 0.0;
    }
}
