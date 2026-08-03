<?php

namespace App\Http\Controllers\Admin\Oncologicos;

use App\Http\Controllers\Controller;
use App\Models\Oncologicos\Diluent;
use App\Models\Oncologicos\DiluentPresentation;
use App\Models\Oncologicos\Laboratory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DiluentPresentationController extends Controller
{
    public function index(Diluent $diluent)
    {
        $presentations = $diluent->presentations()
            ->with('laboratory')
            ->orderBy('presentacion')
            ->orderBy('caducidad')
            ->paginate(15);

        return view('admin.oncologicos.diluents.presentations.index', compact('diluent', 'presentations'));
    }

    public function create(Diluent $diluent)
    {
        $laboratories = $this->activeLaboratories();
        $existingLots = $this->existingLotsForForm($diluent);

        return view('admin.oncologicos.diluents.presentations.create', compact('diluent', 'laboratories', 'existingLots'));
    }

    public function store(Request $request, Diluent $diluent)
    {
        $data = $this->validatedData($request);
        $stock = (float) ($data['stock_actual'] ?? 0);
        $existingWasUsed = false;

        DB::transaction(function () use ($diluent, $data, $stock, &$existingWasUsed) {
            $existing = $this->findExistingLot($diluent, $data);

            if ($existing) {
                $existingWasUsed = true;
                $stockAntes = (float) ($existing->stock_actual ?? 0);
                $stockDespues = $stockAntes + $stock;

                $existing->update([
                    'presentacion' => $data['presentacion'],
                    'volume_ml' => $data['volume_ml'],
                    'denominacion_comercial' => $data['denominacion_comercial'] ?? null,
                    'fabricante' => $data['fabricante'] ?? null,
                    'caducidad' => $data['caducidad'] ?? null,
                    'fecha_ingreso' => $data['fecha_ingreso'] ?? null,
                    'stock_inicial' => (float) ($existing->stock_inicial ?? 0) + $stock,
                    'stock_actual' => $stockDespues,
                    'is_active' => true,
                ]);

                if ($stock > 0) {
                    $this->insertMovement(
                        $existing->fresh(),
                        'entrada',
                        $stock,
                        $stockAntes,
                        $stockDespues,
                        'Ingreso sumado a lote existente de diluyente.'
                    );
                }

                return;
            }

            $presentation = $diluent->presentations()->create([
                ...$data,
                'stock_inicial' => $stock,
                'stock_actual' => $stock,
                'stock_reservado' => 0,
            ]);

            if ($stock > 0) {
                $this->insertMovement(
                    $presentation,
                    'entrada',
                    $stock,
                    0,
                    $stock,
                    'Inventario inicial de diluyente.'
                );
            }
        });

        return redirect()
            ->route('admin.oncologicos.diluent_presentations.index', $diluent)
            ->with('success', $existingWasUsed
                ? 'El lote ya existia. Se sumo el stock al registro existente.'
                : 'Presentacion creada correctamente.');
    }

    public function edit(Diluent $diluent, DiluentPresentation $presentation)
    {
        abort_unless($presentation->diluent_id === $diluent->id, 404);

        $laboratories = $this->activeLaboratories();

        return view('admin.oncologicos.diluents.presentations.edit', compact('diluent', 'presentation', 'laboratories'));
    }

    public function update(Request $request, Diluent $diluent, DiluentPresentation $presentation)
    {
        abort_unless($presentation->diluent_id === $diluent->id, 404);

        $data = $this->validatedData($request);
        $nuevoStock = (float) ($data['stock_actual'] ?? 0);

        DB::transaction(function () use ($presentation, $data, $nuevoStock) {
            $stockAntes = (float) ($presentation->stock_actual ?? 0);

            $presentation->update([
                ...$data,
                'stock_actual' => $nuevoStock,
                'stock_inicial' => max((float) ($presentation->stock_inicial ?? 0), $nuevoStock),
            ]);

            $diferencia = $nuevoStock - $stockAntes;

            if (abs($diferencia) > 0.0001) {
                $this->insertMovement(
                    $presentation->fresh(),
                    $diferencia > 0 ? 'ajuste_entrada' : 'ajuste_salida',
                    abs($diferencia),
                    $stockAntes,
                    $nuevoStock,
                    'Ajuste manual de inventario de diluyente.'
                );
            }
        });

        return redirect()
            ->route('admin.oncologicos.diluent_presentations.index', $diluent)
            ->with('success', 'Presentacion actualizada correctamente.');
    }

    public function destroy(Diluent $diluent, DiluentPresentation $presentation)
    {
        abort_unless($presentation->diluent_id === $diluent->id, 404);

        $presentation->delete();

        return redirect()
            ->route('admin.oncologicos.diluent_presentations.index', $diluent)
            ->with('success', 'Presentacion eliminada correctamente.');
    }

    private function activeLaboratories()
    {
        return Laboratory::query()
            ->where('activo', true)
            ->orderBy('nombre')
            ->get(['id', 'nombre', 'estado']);
    }

    private function validatedData(Request $request): array
    {
        $data = $request->validate([
            'laboratory_id'           => 'nullable|integer|exists:laboratories,id',
            'presentacion'           => 'required|string|max:255',
            'volume_ml'              => 'required|numeric|min:0.01',
            'denominacion_comercial' => 'nullable|string|max:255',
            'fabricante'             => 'nullable|string|max:255',
            'lote'                   => 'nullable|string|max:100',
            'caducidad'              => 'nullable|date',
            'fecha_ingreso'          => 'nullable|date',
            'stock_actual'           => 'nullable|numeric|min:0',
            'is_active'              => 'sometimes|boolean',
        ]);

        $data['is_active'] = $request->boolean('is_active', true);
        $data['stock_actual'] = (float) ($data['stock_actual'] ?? 0);

        return $data;
    }

    private function findExistingLot(Diluent $diluent, array $data): ?DiluentPresentation
    {
        $lote = trim((string) ($data['lote'] ?? ''));

        if ($lote === '') {
            return null;
        }

        return DiluentPresentation::query()
            ->where('diluent_id', $diluent->id)
            ->when(
                empty($data['laboratory_id']),
                fn($query) => $query->whereNull('laboratory_id'),
                fn($query) => $query->where('laboratory_id', (int) $data['laboratory_id'])
            )
            ->whereRaw('LOWER(TRIM(lote)) = ?', [mb_strtolower($lote)])
            ->lockForUpdate()
            ->first();
    }

    private function existingLotsForForm(Diluent $diluent)
    {
        return $diluent->presentations()
            ->with('laboratory:id,nombre,estado')
            ->whereNotNull('lote')
            ->orderBy('lote')
            ->get()
            ->map(fn(DiluentPresentation $presentation) => [
                'id' => $presentation->id,
                'laboratory_id' => $presentation->laboratory_id,
                'laboratory_key' => $presentation->laboratory_id ? (string) $presentation->laboratory_id : '',
                'laboratory_name' => $presentation->laboratory?->nombre ?? 'General / sin laboratorio',
                'lote' => $presentation->lote,
                'lote_key' => mb_strtolower(trim((string) $presentation->lote)),
                'presentacion' => $presentation->presentacion,
                'volume_ml' => $presentation->volume_ml,
                'denominacion_comercial' => $presentation->denominacion_comercial,
                'fabricante' => $presentation->fabricante,
                'caducidad' => optional($presentation->caducidad)->format('Y-m-d'),
                'fecha_ingreso' => optional($presentation->fecha_ingreso)->format('Y-m-d'),
                'stock_actual' => $presentation->stock_actual,
            ])
            ->values();
    }

    private function insertMovement(
        DiluentPresentation $presentation,
        string $type,
        float $quantity,
        float $before,
        float $after,
        string $notes
    ): void {
        DB::table('diluent_stock_movements')->insert([
            'diluent_presentation_id' => $presentation->id,
            'laboratory_id' => $presentation->laboratory_id,
            'user_id' => Auth::id(),
            'movement_type' => $type,
            'quantity' => $quantity,
            'stock_actual_before' => $before,
            'stock_actual_after' => $after,
            'reference_type' => 'diluent_presentation',
            'reference_id' => $presentation->id,
            'notes' => $notes,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
