<?php

namespace App\Http\Controllers\Admin\Oncologicos;

use App\Exports\Oncologicos\MedicineListExport;
use App\Http\Controllers\Controller;
use App\Models\Oncologicos\Distributor;
use App\Models\Oncologicos\MedicineList;
use App\Models\Oncologicos\MedicinesCatalog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class MedicineController extends Controller
{
    public function index()
    {
        return view('admin.oncologicos.medicines.index');
    }

    public function create()
    {
        $catalogos = MedicinesCatalog::with([
            'presentations' => function ($q) {
                $this->scopeActivePresentations($q)
                    ->orderBy('presentacion');
            }
        ])
            ->whereHas('presentations', function ($q) {
                $this->scopeActivePresentations($q);
            })
            ->orderBy('denominacion')
            ->get();

        $distributor = null;

        return view(
            'admin.oncologicos.medicines.create',
            compact('catalogos', 'distributor')
        );
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'          => 'required|string|max:255',
            'description'   => 'nullable|string',
            'active_brands' => 'nullable|boolean',
            'charge_by'     => 'required|in:mg,frasco',
            'show_label_lot_expiry' => 'nullable|boolean',
            'has_mixing_service' => 'nullable|boolean',
            'mixing_service_price' => 'nullable|numeric|min:0',

            'medicamentos'                   => 'required|array|min:1',
            'medicamentos.*.presentation_id' => 'required|exists:medicine_presentations,id',
            'medicamentos.*.precio'          => 'required|numeric|min:0',

            'distributor_name'    => 'nullable|string|max:255|required_with:distributor_address,distributor_logo',
            'distributor_address' => 'nullable|string|max:500|required_with:distributor_name,distributor_logo',
            'distributor_logo'    => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ], [
            'distributor_name.required_with'    => 'Indica el nombre del distribuidor.',
            'distributor_address.required_with' => 'Indica la dirección del distribuidor.',
        ]);

        $items = collect($request->input('medicamentos', []))
            ->filter(fn($m) => !empty($m['presentation_id']) && $m['precio'] !== null && $m['precio'] !== '')
            ->values();

        if ($items->isEmpty()) {
            return back()->withInput()->withErrors([
                'medicamentos' => 'Debes ingresar al menos una presentación válida con precio.',
            ]);
        }

        if ($items->pluck('presentation_id')->duplicates()->isNotEmpty()) {
            return back()->withInput()->withErrors([
                'medicamentos' => 'No puedes repetir la misma presentación más de una vez en la lista.',
            ]);
        }

        if (!$this->allPresentationsAreSelectable($items->pluck('presentation_id'))) {
            return back()->withInput()->withErrors([
                'medicamentos' => 'Solo puedes agregar presentaciones activas del catálogo.',
            ]);
        }

        try {
            DB::beginTransaction();

            $chargeBy = $request->input('charge_by', 'mg');

            $lista = MedicineList::create([
                'name'          => $request->name,
                'description'   => $request->description,
                'active_brands' => $request->boolean('active_brands', false),
                'charge_by'     => $chargeBy,
                'show_label_lot_expiry' => $request->boolean('show_label_lot_expiry', false),
                'has_mixing_service' => $request->boolean('has_mixing_service', false),
                'mixing_service_price' => $request->boolean('has_mixing_service', false)
                    ? (float) $request->input('mixing_service_price', 0)
                    : 0,
            ]);

            $hasDistributor =
                $request->filled('distributor_name') ||
                $request->filled('distributor_address') ||
                $request->hasFile('distributor_logo');

            if ($hasDistributor) {
                $logoPath = null;

                if ($request->hasFile('distributor_logo')) {
                    $logoPath = $request->file('distributor_logo')->store('distributors', 'public');
                }

                Distributor::create([
                    'medicine_list_id' => $lista->id,
                    'nombre'           => $request->input('distributor_name'),
                    'direccion'        => $request->input('distributor_address'),
                    'logo_path'        => $logoPath,
                ]);
            }

            $pivotData = [];

            foreach ($items as $item) {
                $presentationId  = (int) $item['presentation_id'];
                $precioCapturado = (float) $item['precio'];

                $pivotData[$presentationId] = [
                    'charge_by'          => $chargeBy,
                    'precio'             => $chargeBy === 'frasco' ? $precioCapturado : null,
                    'precio_mg_override' => $chargeBy === 'mg' ? $precioCapturado : null,
                ];
            }

            if (empty($pivotData)) {
                DB::rollBack();

                return back()->withInput()->withErrors([
                    'medicamentos' => 'No se pudo construir ninguna relación de presentaciones con la lista.',
                ]);
            }

            $lista->presentations()->sync($pivotData);

            DB::commit();

            return redirect()
                ->route('admin.oncologicos.medicines.index')
                ->with('success', 'Lista de medicamentos creada correctamente.');
        } catch (\Throwable $e) {
            DB::rollBack();

            return back()->withInput()->withErrors([
                'error' => 'Error al crear la lista: ' . $e->getMessage(),
            ]);
        }
    }

    public function edit(string $id)
    {
        $lista = MedicineList::with([
            'presentations.catalog',
            'distributor'
        ])->findOrFail($id);

        $catalogos = MedicinesCatalog::with([
            'presentations' => function ($q) {
                $this->scopeActivePresentations($q)
                    ->orderBy('presentacion');
            }
        ])
            ->whereHas('presentations', function ($q) {
                $this->scopeActivePresentations($q);
            })
            ->orderBy('denominacion')
            ->get();

        $presentationsInList = $lista->presentations
            ->loadMissing('catalog')
            ->groupBy('catalog_id');

        $catalogos = $catalogos->map(function ($catalogo) use ($presentationsInList) {
            $extraPresentations = $presentationsInList->get($catalogo->id, collect());

            if ($extraPresentations->isNotEmpty()) {
                $catalogo->setRelation(
                    'presentations',
                    $catalogo->presentations
                        ->concat($extraPresentations)
                        ->unique('id')
                        ->sortBy(function ($presentation) {
                            return mb_strtolower(trim((string) ($presentation->presentacion ?? '')), 'UTF-8');
                        })
                        ->values()
                );
            }

            return $catalogo;
        });

        $missingCatalogIds = $presentationsInList
            ->keys()
            ->diff($catalogos->pluck('id'))
            ->values();

        if ($missingCatalogIds->isNotEmpty()) {
            $missingCatalogs = MedicinesCatalog::whereIn('id', $missingCatalogIds)
                ->orderBy('denominacion')
                ->get()
                ->map(function ($catalogo) use ($presentationsInList) {
                    $catalogo->setRelation(
                        'presentations',
                        $presentationsInList->get($catalogo->id, collect())
                            ->unique('id')
                            ->sortBy(function ($presentation) {
                                return mb_strtolower(trim((string) ($presentation->presentacion ?? '')), 'UTF-8');
                            })
                            ->values()
                    );

                    return $catalogo;
                });

            $catalogos = $catalogos
                ->concat($missingCatalogs)
                ->sortBy(function ($catalogo) {
                    return mb_strtolower(trim((string) ($catalogo->denominacion ?? '')), 'UTF-8');
                })
                ->values();
        }

        $listaItems = $lista->presentations->map(function ($pres) {
            return [
                'catalog_id'      => $pres->catalog_id,
                'presentation_id' => $pres->id,
                'charge_by'       => $pres->pivot->charge_by ?? 'mg',
                'precio'          => ($pres->pivot->charge_by ?? 'mg') === 'frasco'
                    ? ($pres->pivot->precio ?? null)
                    : ($pres->pivot->precio_mg_override ?? null),
            ];
        })
            ->values();

        return view('admin.oncologicos.medicines.edit', [
            'lista'       => $lista,
            'catalogos'   => $catalogos,
            'listaItems'  => $listaItems,
            'distributor' => $lista->distributor,
        ]);
    }

    public function update(Request $request, string $id)
    {
        $request->validate([
            'name'           => 'required|string|max:255',
            'description'    => 'nullable|string',
            'active_brands'  => 'nullable|boolean',
            'charge_by'      => 'required|in:mg,frasco',
            'show_label_lot_expiry' => 'nullable|boolean',
            'has_mixing_service' => 'nullable|boolean',
            'mixing_service_price' => 'nullable|numeric|min:0',

            'distributor_nombre'    => 'nullable|string|max:255',
            'distributor_direccion' => 'nullable|string|max:500',
            'distributor_logo'      => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'distributor_delete'    => 'nullable|boolean',

            'medicamentos'                   => 'required|array|min:1',
            'medicamentos.*.catalog_id'      => 'required|exists:medicines_catalog,id',
            'medicamentos.*.presentation_id' => 'required|exists:medicine_presentations,id',
            'medicamentos.*.precio'          => 'required|numeric|min:0',
        ]);

        $rows = collect($request->input('medicamentos', []))
            ->filter(
                fn($m) =>
                !empty($m['catalog_id']) &&
                !empty($m['presentation_id']) &&
                $m['precio'] !== null &&
                $m['precio'] !== ''
            )
            ->values();

        if ($rows->isEmpty()) {
            return back()->withInput()->withErrors([
                'medicamentos' => 'Debes ingresar al menos una presentación con precio.',
            ]);
        }

        if ($rows->pluck('presentation_id')->duplicates()->isNotEmpty()) {
            return back()->withInput()->withErrors([
                'medicamentos' => 'No puedes repetir la misma presentación más de una vez en la lista.',
            ]);
        }

        try {
            DB::beginTransaction();

            $lista = MedicineList::with('distributor')->findOrFail($id);

            $chargeByGlobal = $request->input('charge_by', 'mg');

            if (!$this->allPresentationsAreSelectable($rows->pluck('presentation_id'))) {
                DB::rollBack();

                return back()->withInput()->withErrors([
                    'medicamentos' => 'Solo puedes agregar presentaciones activas del catálogo.',
                ]);
            }

            $lista->update([
                'name'          => $request->name,
                'description'   => $request->description,
                'active_brands' => $request->boolean('active_brands', false),
                'charge_by'     => $chargeByGlobal,
                'show_label_lot_expiry' => $request->boolean('show_label_lot_expiry', false),
                'has_mixing_service' => $request->boolean('has_mixing_service', false),
                'mixing_service_price' => $request->boolean('has_mixing_service', false)
                    ? (float) $request->input('mixing_service_price', 0)
                    : 0,
            ]);

            if ($request->boolean('distributor_delete')) {
                if ($lista->distributor) {
                    if (!empty($lista->distributor->logo_path)) {
                        Storage::disk('public')->delete($lista->distributor->logo_path);
                    }
                    $lista->distributor->delete();
                }
            } else {
                $distNombre    = trim((string) $request->input('distributor_nombre', ''));
                $distDireccion = trim((string) $request->input('distributor_direccion', ''));

                $hayDatosDistributor =
                    ($distNombre !== '') ||
                    ($distDireccion !== '') ||
                    $request->hasFile('distributor_logo');

                if ($hayDatosDistributor) {
                    $distributor = $lista->distributor ?: new Distributor();
                    $distributor->medicine_list_id = $lista->id;
                    $distributor->nombre = $distNombre;
                    $distributor->direccion = $distDireccion;

                    if ($request->hasFile('distributor_logo')) {
                        if (!empty($distributor->logo_path)) {
                            Storage::disk('public')->delete($distributor->logo_path);
                        }

                        $path = $request->file('distributor_logo')->store('distributors/logos', 'public');
                        $distributor->logo_path = $path;
                    }

                    $distributor->save();
                }
            }

            $pivotData = [];

            foreach ($rows as $row) {
                $presentationId = (int) $row['presentation_id'];
                $precio = (float) $row['precio'];

                $pivotData[$presentationId] = [
                    'charge_by'          => $chargeByGlobal,
                    'precio'             => $chargeByGlobal === 'frasco' ? $precio : null,
                    'precio_mg_override' => $chargeByGlobal === 'mg' ? $precio : null,
                ];
            }

            if (empty($pivotData)) {
                DB::rollBack();

                return back()->withInput()->withErrors([
                    'medicamentos' => 'No se pudo construir ninguna relación de presentaciones con la lista.',
                ]);
            }

            $lista->presentations()->sync($pivotData);

            DB::commit();

            return redirect()
                ->route('admin.oncologicos.medicines.index')
                ->with('success', 'Lista de medicamentos actualizada correctamente.');
        } catch (\Throwable $e) {
            DB::rollBack();

            return back()->withInput()->withErrors([
                'error' => 'Error al actualizar la lista: ' . $e->getMessage(),
            ]);
        }
    }

    public function destroy(string $id)
    {
        try {
            DB::beginTransaction();

            $lista = MedicineList::with('distributor')->findOrFail($id);

            if ($lista->distributor && !empty($lista->distributor->logo_path)) {
                Storage::disk('public')->delete($lista->distributor->logo_path);
            }

            if ($lista->distributor) {
                $lista->distributor->delete();
            }

            $lista->presentations()->detach();
            $lista->delete();

            DB::commit();

            return redirect()->route('admin.oncologicos.medicines.index')
                ->with('success', 'Lista de medicamentos eliminada correctamente.');
        } catch (\Throwable $e) {
            DB::rollBack();

            return back()->withErrors([
                'error' => 'Error al eliminar la lista: ' . $e->getMessage()
            ]);
        }
    }

    public function exportarExcel(MedicineList $medicineList)
    {
        $filename = 'lista_precios_' . $medicineList->id . '.xlsx';

        return Excel::download(new MedicineListExport($medicineList->id), $filename);
    }

    private function scopeActivePresentations($query)
    {
        return $query
            ->where('is_available', 1);
    }

    private function allPresentationsAreSelectable($presentationIds): bool
    {
        $ids = collect($presentationIds)
            ->filter()
            ->map(fn($id) => (int) $id)
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return false;
        }

        $validCount = \App\Models\Oncologicos\MedicinePresentation::query()
            ->whereIn('id', $ids)
            ->where(function ($query) {
                $this->scopeActivePresentations($query);
            })
            ->count();

        return $validCount === $ids->count();
    }
}
