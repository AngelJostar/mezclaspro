<?php

namespace App\Http\Controllers;

use App\Models\Nutricionales\Solicitud;
use App\Models\Nutricionales\SolicitudInput;
use App\Models\Oncologicos\Mezcla;
use Illuminate\View\View;

class LabelQrController extends Controller
{
    public function showOnco(Mezcla $mezcla): View
    {
        $mezcla->loadMissing([
            'solicitud.hospital',
            'solicitud.user',
            'medicamentos.presentacionesUsadas.batch.presentation',
            'medicamentos.medicamentoOnco.catalog',
            'diluentPresentation.diluent',
            'inspeccion',
        ]);

        $medicamentos = $mezcla->medicamentos->map(function ($medicamento) {
            $catalogo = $medicamento->medicamentoOnco?->catalog;
            $presentaciones = $medicamento->presentacionesUsadas ?? collect();
            $primeraPresentacion = $presentaciones->first();
            $presentation = $primeraPresentacion?->batch?->presentation ?? $primeraPresentacion?->presentation;

            $nombreGenerico = trim((string) ($catalogo->denominacion ?? $medicamento->nombre_medicamento ?? 'Medicamento'));
            $marca = trim((string) ($presentation?->marca ?? ''));
            $nombre = $marca !== '' ? $nombreGenerico . ' (' . $marca . ')' : $nombreGenerico;

            return [
                'nombre' => $nombre,
                'dosis_mg' => $medicamento->dosis,
                'presentacion' => $presentation?->presentacion,
                'lote' => $primeraPresentacion?->lote_usado ?? $primeraPresentacion?->batch?->lote,
                'caducidad' => $primeraPresentacion?->caducidad_usada ?? $primeraPresentacion?->batch?->caducidad,
            ];
        });

        return view('qr.oncologicos-mezcla', [
            'mezcla' => $mezcla,
            'solicitud' => $mezcla->solicitud,
            'hospital' => $mezcla->solicitud?->hospital,
            'paciente' => $mezcla->solicitud?->user?->name,
            'medicamentos' => $medicamentos,
        ]);
    }

    public function showNutri(Solicitud $solicitud): View
    {
        $solicitud->loadMissing([
            'user.hospital',
            'solicitud_detail',
            'solicitud_patient',
            'inspeccionNutricional',
        ]);

        $insumos = SolicitudInput::query()
            ->where('solicitud_id', $solicitud->id)
            ->whereNotIn('input_id', function ($query) {
                $query->select('id')
                    ->from('inputs')
                    ->where('category_id', 6);
            })
            ->whereNotIn('input_id', [40])
            ->with(['input', 'presentation.catalog'])
            ->get()
            ->map(function ($item) {
                return [
                    'nombre' => $item->presentation?->catalog?->denominacion_generica
                        ?? $item->input?->description
                        ?? 'Insumo',
                    'dosis' => $item->valor,
                    'unidad' => $item->input?->unidad,
                    'presentacion' => $item->presentation?->presentacion,
                    'marca' => $item->presentation?->denominacion_comercial,
                    'lote' => $item->lote,
                    'caducidad' => $item->caducidad,
                ];
            });

        return view('qr.nutricionales-solicitud', [
            'solicitud' => $solicitud,
            'hospital' => $solicitud->user?->hospital,
            'paciente' => $solicitud->solicitud_patient,
            'detalle' => $solicitud->solicitud_detail,
            'insumos' => $insumos,
        ]);
    }
}
