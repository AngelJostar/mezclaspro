<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ConsumableItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ConsumableCatalogController extends Controller
{
    public function create(Request $request)
    {
        if (!$request->boolean('modal')) {
            return redirect()->route('admin.catalogo-listas.catalog', [
                'category' => 'insumos', 'tipo_insumo' => 'consumibles', 'nuevo_consumible' => 1,
            ]);
        }

        return view('admin.catalogo-listas.partials.consumable-create-form');
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['unit'] = 'pieza';
        DB::transaction(function () use ($data) {
            $item = ConsumableItem::create(['name' => $data['name'], 'unit' => $data['unit'], 'is_active' => true]);
            $item->catalogPresentations()->createMany($data['presentations']);
        });
        $redirect = redirect()->route('admin.catalogo-listas.catalog', ['category' => 'insumos', 'tipo_insumo' => 'consumibles'])
            ->with('success', 'Consumible creado correctamente.');
        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Consumible creado correctamente.', 'redirect' => $redirect->getTargetUrl(),
            ], 201);
        }

        return $redirect;
    }

    public function edit(ConsumableItem $item) { $item->load('catalogPresentations'); return view('admin.catalogo-listas.consumable-form', ['item' => $item, 'mode' => 'edit']); }

    public function update(Request $request, ConsumableItem $item)
    {
        $data = $this->validated($request, $item);
        DB::transaction(function () use ($data, $item) {
            $item->update(['name' => $data['name'], 'unit' => $data['unit']]);
            foreach ($data['presentations'] as $presentation) {
                $id = $presentation['id'] ?? null;
                unset($presentation['id']);
                $id ? $item->catalogPresentations()->whereKey($id)->update($presentation) : $item->catalogPresentations()->create($presentation);
            }
        });
        return redirect()->route('admin.catalogo-listas.catalog', ['category' => 'insumos', 'tipo_insumo' => 'consumibles'])->with('success', 'Consumible actualizado correctamente.');
    }

    private function validated(Request $request, ?ConsumableItem $item = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:consumable_items,name'.($item ? ','.$item->id : '')],
            'unit' => ['required', 'string', 'max:40'],
            'presentations' => ['required', 'array', 'min:1'],
            'presentations.*.id' => ['nullable', 'integer'],
            'presentations.*.presentation' => ['required', 'string', 'max:255'],
            'presentations.*.commercial_name' => ['nullable', 'string', 'max:255'],
            'presentations.*.manufacturer' => ['nullable', 'string', 'max:255'],
        ]);
    }
}
