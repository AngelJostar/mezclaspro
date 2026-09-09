<?php

namespace App\Services;

use App\Models\InspectionWaste;
use App\Models\Oncologicos\InspeccionMezcla;
use App\Models\Oncologicos\Mezcla;
use Illuminate\Support\Facades\DB;

class InspectionRejectionService
{
    // Called inside the inspection transaction with the mixture row locked.
    public function reject(Mezcla $mixture, InspeccionMezcla $inspection, string $reason): InspectionWaste
    {
        $mixture->load('solicitud.hospital', 'medicamentos.presentacionesUsadas', 'diluentPresentation');
        $presentations = $mixture->medicamentos->flatMap->presentacionesUsadas;
        $waste = InspectionWaste::create([
            'mezcla_id' => $mixture->id,
            'production_attempt' => $mixture->production_attempt,
            'user_id' => auth()->id(),
            'reason' => $reason,
            'snapshot' => [
                'mixture' => $mixture->attributesToArray(),
                'inspection' => $inspection->attributesToArray(),
                'medications' => $mixture->medicamentos->toArray(),
                'diluent' => $mixture->diluentPresentation?->toArray(),
                'request_id' => $mixture->solicitud_id,
                'category' => $mixture->solicitud?->tipo_solicitud,
                'hospital' => $mixture->solicitud?->hospital?->name,
                'laboratory' => DB::table('laboratories')->where('id', $mixture->solicitud?->hospital?->laboratory_id)->value('nombre'),
                'warehouses' => DB::table('medicine_batches as batches')
                    ->join('warehouses', 'warehouses.id', '=', 'batches.warehouse_id')
                    ->whereIn('batches.id', $presentations->pluck('medicine_batch_id'))
                    ->distinct()->pluck('warehouses.name')->all(),
                'reviewer' => $inspection->reviso_nombre,
            ],
        ]);

        // Freeze the consumed inventory under this attempt. Never return it to stock,
        // and never let the next dispensing edit roll it back or skip its diluent.
        foreach (['medicine_batch_movements', 'medicine_remainder_movements', 'diluent_stock_movements'] as $table) {
            DB::table($table)->where('reference_type', 'mezcla')->where('reference_id', $mixture->id)
                ->update(['reference_type' => 'inspection_waste', 'reference_id' => $waste->id]);
        }
        DB::table('medicine_remainders')->where('opened_for_type', 'mezcla')->where('opened_for_id', $mixture->id)
            ->update(['opened_for_type' => 'inspection_waste', 'opened_for_id' => $waste->id]);

        DB::table('mezcla_medicamento_presentaciones')
            ->whereIn('mezcla_medicamento_id', $mixture->medicamentos->modelKeys())->delete();
        $mixture->medicamentos()->update(['dosis_ml' => null, 'marca_snapshot' => null]);

        $validation = $inspection->only(['valido_nombre', 'fecha_validacion', 'hora_validacion']);
        $inspection->delete();
        InspeccionMezcla::create([
            'mezcla_id' => $mixture->id,
            'fecha_inspeccion' => now()->toDateString(),
            'hora_inspeccion' => now()->format('H:i:s'),
            'reviso_nombre' => '',
            'aprobo_nombre' => '',
            ...$validation,
        ]);

        $mixture->estado = 'aprobada';
        $mixture->production_attempt++;
        $mixture->lote = app(MixtureLotService::class)->next();
        $mixture->diluent_presentation_id = null;
        $mixture->save();

        return $waste;
    }
}
