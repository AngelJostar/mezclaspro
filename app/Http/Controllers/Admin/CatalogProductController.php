<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Nutricionales\Category;
use App\Models\Nutricionales\Input;
use App\Models\Nutricionales\NutritionMedicineCatalog;
use App\Models\Nutricionales\NutritionMedicinePresentation;
use App\Models\Oncologicos\AdministrationRoute;
use App\Models\Oncologicos\Diluent;
use App\Models\Oncologicos\DiluentPresentation;
use App\Models\Oncologicos\MedicinesCatalog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CatalogProductController extends Controller
{
    public function create(string $category): View
    {
        $category = $this->normalizeCategory($category);

        return view('admin.catalogo-listas.product-form', [
            'category' => $category,
            'mode' => 'catalogo',
            'categories' => CatalogoListasController::CATEGORIES,
            'diluents' => Diluent::query()
                ->orderBy('denominacion_generica')
                ->get(['id', 'denominacion_generica']),
            'routes' => AdministrationRoute::query()
                ->orderBy('name')
                ->get(['id', 'name']),
            'concentrationUnit' => in_array($category, ['nutricionales', 'insumos'], true) ? 'ml' : 'mg',
        ]);
    }

    public function store(Request $request, string $category): RedirectResponse
    {
        $category = $this->normalizeCategory($category);
        $isSupplies = $category === 'insumos';

        $data = $request->validate([
            'generic_description' => ['required', 'string', 'max:255'],
            'concentration' => ['required', 'numeric', 'gt:0'],
            'presentation' => ['required', 'string', 'max:255'],
            'commercial_name' => ['required', 'string', 'max:255'],
            'manufacturer' => ['nullable', 'string', 'max:255'],
            'diluents' => ['nullable', 'array'],
            'diluents.*' => ['integer', 'exists:diluents,id'],
            'routes' => ['nullable', 'array'],
            'routes.*' => ['integer', 'exists:administration_routes,id'],
            'conc_min' => ['nullable', 'numeric', 'min:0'],
            'conc_max' => ['nullable', 'numeric', 'min:0'],
        ], [
            'generic_description.required' => $isSupplies
                ? 'Captura el nombre generico del insumo.'
                : 'Captura la descripcion generica.',
            'concentration.required' => $isSupplies
                ? 'Captura el volumen.'
                : 'Captura la concentracion.',
            'concentration.gt' => $isSupplies
                ? 'El volumen debe ser mayor que cero.'
                : 'La concentracion debe ser mayor que cero.',
            'presentation.required' => 'Captura la presentacion.',
            'commercial_name.required' => $isSupplies
                ? 'Captura el nombre comercial.'
                : 'Captura la descripcion distintiva o marca.',
        ]);

        if (
            ($data['conc_min'] ?? null) !== null
            && ($data['conc_max'] ?? null) !== null
            && (float) $data['conc_max'] < (float) $data['conc_min']
        ) {
            throw ValidationException::withMessages([
                'conc_max' => 'La concentracion maxima debe ser igual o mayor que la minima.',
            ]);
        }

        DB::transaction(function () use ($category, $data) {
            if ($category === 'insumos') {
                $this->storeSupplyProduct($data);

                return;
            }

            if ($category === 'nutricionales') {
                $this->storeNutritionProduct($data);

                return;
            }

            $this->storeMedicineProduct($category, $data);
        });

        $message = $isSupplies
            ? 'Insumo agregado correctamente al catalogo.'
            : 'Producto agregado correctamente al catalogo de '
                . CatalogoListasController::CATEGORIES[$category]['label'] . '.';

        return redirect()
            ->route('admin.catalogo-listas.catalog', ['category' => $category])
            ->with('success', $message);
    }

    private function storeMedicineProduct(string $category, array $data): void
    {
        $catalog = MedicinesCatalog::query()
            ->forCategory($category)
            ->where('denominacion', trim($data['generic_description']))
            ->first();

        if (!$catalog) {
            $catalog = MedicinesCatalog::create([
                'denominacion' => trim($data['generic_description']),
                'catalog_category' => $category,
                'conc_min' => $data['conc_min'],
                'conc_max' => $data['conc_max'],
                'requires_infusor' => false,
                'state' => true,
            ]);
        } else {
            $catalog->update([
                'conc_min' => $data['conc_min'] ?? $catalog->conc_min,
                'conc_max' => $data['conc_max'] ?? $catalog->conc_max,
                'state' => true,
            ]);
        }

        $catalog->diluents()->syncWithoutDetaching($data['diluents'] ?? []);
        $catalog->administrationRoutes()->syncWithoutDetaching($data['routes'] ?? []);

        $presentationExists = $catalog->presentations()
            ->where('presentacion', trim($data['presentation']))
            ->where('marca', trim($data['commercial_name']))
            ->exists();

        if ($presentationExists) {
            throw ValidationException::withMessages([
                'presentation' => 'Ya existe esta presentacion con la misma marca en la categoria seleccionada.',
            ]);
        }

        $catalog->presentations()->create([
            'presentacion' => trim($data['presentation']),
            'contenido_valor' => $data['concentration'],
            'contenido_unidad' => 'mg',
            'marca' => trim($data['commercial_name']),
            'cantidad_medicamento' => $data['concentration'],
            'is_available' => true,
        ]);
    }

    private function storeNutritionProduct(array $data): void
    {
        $genericDescription = trim($data['generic_description']);
        $catalog = NutritionMedicineCatalog::query()
            ->where('denominacion_generica', $genericDescription)
            ->first();

        if (!$catalog) {
            $defaultCategory = Category::query()->firstOrCreate(['name' => 'Otra']);
            $input = Input::create([
                'description' => $genericDescription,
                'unidad' => 'mL',
                'is_active' => true,
                'tipo_input' => 'ambos',
                'orden_enum' => ((int) Input::max('orden_enum')) + 1,
                'category_id' => $defaultCategory->id,
                'mult' => 1,
                'div' => 1,
            ]);

            $catalog = NutritionMedicineCatalog::create([
                'denominacion_generica' => $genericDescription,
                'category_id' => $defaultCategory->id,
                'input_id' => $input->id,
                'conc_min' => $data['conc_min'],
                'conc_max' => $data['conc_max'],
                'diluent_ids' => array_values($data['diluents'] ?? []),
                'administration_route_ids' => array_values($data['routes'] ?? []),
                'is_active' => true,
            ]);
        } else {
            $catalog->update([
                'conc_min' => $data['conc_min'] ?? $catalog->conc_min,
                'conc_max' => $data['conc_max'] ?? $catalog->conc_max,
                'diluent_ids' => $this->mergedIds($catalog->diluent_ids, $data['diluents'] ?? []),
                'administration_route_ids' => $this->mergedIds(
                    $catalog->administration_route_ids,
                    $data['routes'] ?? []
                ),
                'is_active' => true,
            ]);
        }

        $presentationExists = $catalog->presentations()
            ->where('presentacion', trim($data['presentation']))
            ->where('denominacion_comercial', trim($data['commercial_name']))
            ->exists();

        if ($presentationExists) {
            throw ValidationException::withMessages([
                'presentation' => 'Ya existe esta presentacion con la misma marca en la categoria seleccionada.',
            ]);
        }

        NutritionMedicinePresentation::create([
            'nutrition_medicine_catalog_id' => $catalog->id,
            'denominacion_comercial' => trim($data['commercial_name']),
            'presentacion' => trim($data['presentation']),
            'presentacion_ml' => $data['concentration'],
            'is_available' => true,
        ]);
    }

    private function storeSupplyProduct(array $data): void
    {
        $genericDescription = trim($data['generic_description']);
        $commercialName = trim($data['commercial_name']);
        $presentationName = trim($data['presentation']);

        $diluent = Diluent::query()->firstOrCreate([
            'denominacion_generica' => $genericDescription,
        ]);

        $presentationExists = $diluent->presentations()
            ->where('presentacion', $presentationName)
            ->where('denominacion_comercial', $commercialName)
            ->exists();

        if ($presentationExists) {
            throw ValidationException::withMessages([
                'presentation' => 'Ya existe esta presentacion con el mismo nombre comercial.',
            ]);
        }

        DiluentPresentation::create([
            'diluent_id' => $diluent->id,
            'presentacion' => $presentationName,
            'volume_ml' => $data['concentration'],
            'denominacion_comercial' => $commercialName,
            'fabricante' => trim((string) ($data['manufacturer'] ?? '')) ?: null,
            'stock_inicial' => 0,
            'stock_actual' => 0,
            'stock_reservado' => 0,
            'is_active' => true,
        ]);
    }

    private function mergedIds(?array $current, array $incoming): array
    {
        return collect($current ?? [])
            ->merge($incoming)
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function normalizeCategory(string $category): string
    {
        abort_unless(array_key_exists($category, CatalogoListasController::CATEGORIES), 404);

        return $category;
    }
}
