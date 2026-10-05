<?php

namespace App\Services;

use App\Models\Hospital;
use App\Models\Nutricionales\Solicitud;
use App\Models\Oncologicos\Diluent;
use App\Models\Oncologicos\DiluentPresentation;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class NutritionDiluentService
{
    public function optionsForHospital(Hospital $hospital, ?Solicitud $solicitud = null): Collection
    {
        $selectedIds = $solicitud?->diluents()->pluck('diluent_presentation_id')->all() ?? [];

        return Diluent::query()
            ->where('available_for_nutrition', true)
            ->with(['presentations' => function ($query) use ($hospital, $selectedIds) {
                $query->with(['catalogPresentation', 'warehouse'])
                    ->where('laboratory_id', $hospital->laboratory_id)
                    ->where(function ($availability) use ($selectedIds) {
                        $availability->where(function ($active) {
                            $active->where('is_active', true)
                                ->where('stock_actual', '>', 0)
                                ->where(function ($expiry) {
                                    $expiry->whereNull('caducidad')->orWhereDate('caducidad', '>=', today());
                                });
                        });
                        if ($selectedIds !== []) {
                            $availability->orWhereIn('id', $selectedIds);
                        }
                    })
                    ->orderByRaw('caducidad IS NULL ASC')
                    ->orderBy('caducidad')
                    ->orderBy('id');
            }])
            ->orderBy('denominacion_generica')
            ->get()
            ->filter(fn (Diluent $diluent) => $diluent->presentations->isNotEmpty())
            ->values();
    }

    public function syncFromRequest(Request $request, Solicitud $solicitud, Hospital $hospital): float
    {
        $payload = $request->input('nutrition_diluents', []);
        if (! is_array($payload)) {
            throw ValidationException::withMessages(['nutrition_diluents' => 'Los diluyentes enviados no son válidos.']);
        }

        $validator = Validator::make(['nutrition_diluents' => $payload], [
            'nutrition_diluents' => ['array'],
            'nutrition_diluents.*.presentation_id' => ['nullable', 'integer', 'exists:diluent_presentations,id'],
            'nutrition_diluents.*.volume_ml' => ['nullable', 'numeric', 'min:0', 'max:100000'],
        ]);
        $validator->validate();

        $lines = collect($payload)->map(function ($row, $diluentId) use ($hospital) {
            $presentationId = (int) ($row['presentation_id'] ?? 0);
            $volume = (float) ($row['volume_ml'] ?? 0);

            if ($presentationId === 0 && $volume <= 0) return null;
            if ($presentationId === 0 || $volume <= 0) {
                throw ValidationException::withMessages([
                    "nutrition_diluents.$diluentId.volume_ml" => 'Selecciona una presentación y captura un volumen mayor a cero.',
                ]);
            }

            $presentation = DiluentPresentation::query()
                ->with(['diluent', 'catalogPresentation'])
                ->whereKey($presentationId)
                ->where('diluent_id', (int) $diluentId)
                ->where('laboratory_id', $hospital->laboratory_id)
                ->where('is_active', true)
                ->first();

            if (! $presentation || ! $presentation->diluent?->available_for_nutrition) {
                throw ValidationException::withMessages([
                    "nutrition_diluents.$diluentId.presentation_id" => 'La presentación seleccionada no está autorizada para solicitudes nutricionales.',
                ]);
            }
            if ($presentation->caducidad && $presentation->caducidad->isBefore(today())) {
                throw ValidationException::withMessages([
                    "nutrition_diluents.$diluentId.presentation_id" => 'El lote seleccionado está caducado.',
                ]);
            }
            if ((float) $presentation->stock_actual <= 0) {
                throw ValidationException::withMessages([
                    "nutrition_diluents.$diluentId.presentation_id" => 'El lote seleccionado no tiene existencia disponible.',
                ]);
            }

            return compact('presentation', 'volume');
        })->filter()->values();

        $solicitud->diluents()->delete();
        foreach ($lines as $line) {
            /** @var DiluentPresentation $presentation */
            $presentation = $line['presentation'];
            $solicitud->diluents()->create([
                'diluent_id' => $presentation->diluent_id,
                'diluent_presentation_id' => $presentation->id,
                'volume_ml' => $line['volume'],
                'generic_name' => $presentation->diluent->denominacion_generica,
                'commercial_name' => $presentation->denominacion_comercial ?: $presentation->catalogPresentation?->commercial_name,
                'presentation_name' => $presentation->presentacion ?: $presentation->catalogPresentation?->presentation,
                'lot' => $presentation->lote,
                'expires_at' => $presentation->caducidad,
            ]);
        }

        return (float) $lines->sum('volume');
    }
}
