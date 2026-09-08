<?php

namespace Tests\Feature;

use App\Models\ConsumableCatalogPresentation;
use App\Models\ConsumableItem;
use App\Models\ConsumableLot;
use App\Models\ProductionSupplyRequest;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Http\Middleware\VerifyCsrfToken;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ProductionConsumableRequestFlowTest extends TestCase
{
    use DatabaseTransactions;

    public function test_consumable_request_can_be_created_approved_supplied_and_received(): void
    {
        $this->withoutMiddleware(VerifyCsrfToken::class);
        $warehouse = Warehouse::query()->where('is_active', true)->firstOrFail();
        $user = User::factory()->create(['hospital_id' => null, 'lastname' => 'Simulación']);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        foreach (['oncologicos_laboratory_index', 'oncologicos_laboratory_create', 'oncologicos_laboratory_edit'] as $name) {
            $user->givePermissionTo(Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']));
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $user = $user->fresh();
        $this->assertTrue($user->can('oncologicos_laboratory_create'));

        $item = ConsumableItem::create(['name' => 'SIMULACION CONSUMIBLE', 'unit' => 'pieza', 'is_active' => true]);
        $presentation = ConsumableCatalogPresentation::create(['consumable_item_id' => $item->id, 'presentation' => 'Caja de prueba', 'commercial_name' => 'Prueba', 'manufacturer' => 'CMP', 'is_active' => true]);
        $lot = ConsumableLot::create(['consumable_item_id' => $item->id, 'catalog_presentation_id' => $presentation->id, 'warehouse_id' => $warehouse->id, 'presentation' => 'Caja de prueba', 'brand' => 'Prueba', 'manufacturer' => 'CMP', 'lot' => 'SIM-'.uniqid(), 'received_at' => now(), 'stock_actual' => 20, 'is_active' => true]);

        $this->actingAs($user)->post(route('admin.production-supplies.store'), [
            'warehouse_id' => $warehouse->id,
            'observations' => 'Simulación automática',
            'lines' => [['supply_id' => $lot->id, 'quantity' => 5]],
        ])->assertRedirect();

        $request = ProductionSupplyRequest::query()->where('requested_by', $user->id)
            ->whereHas('lines', fn ($query) => $query->where('consumable_lot_id', $lot->id))
            ->firstOrFail();
        $line = $request->lines()->firstOrFail();
        $this->actingAs($user)->get(route('admin.production-supplies.show', $request))->assertOk()->assertSee('SIMULACION CONSUMIBLE');

        $this->actingAs($user)->patch(route('admin.production-supplies.approve', $request), ['lines' => [$line->id => ['approved_quantity' => 5]]])->assertRedirect();
        $this->assertSame(ProductionSupplyRequest::STATUS_APPROVED, $request->fresh()->status);

        $this->actingAs($user)->patch(route('admin.production-supplies.supply', $request), ['lines' => [$line->id => ['supplied_quantity' => 5]]])->assertRedirect();
        $this->assertSame(ProductionSupplyRequest::STATUS_SUPPLIED, $request->fresh()->status);
        $this->assertSame(15.0, (float) $lot->fresh()->stock_actual);

        $this->actingAs($user)->patch(route('admin.production-supplies.receive', $request), ['lines' => [$line->id => ['received_quantity' => 5]]])->assertRedirect();
        $this->assertSame(ProductionSupplyRequest::STATUS_RECEIVED, $request->fresh()->status);
        $this->assertSame(5.0, (float) $line->fresh()->received_quantity);
    }

    public function test_supply_cannot_reduce_a_consumable_lot_below_zero(): void
    {
        $this->withoutMiddleware(VerifyCsrfToken::class);
        $warehouse = Warehouse::query()->where('is_active', true)->firstOrFail();
        $user = User::factory()->create(['hospital_id' => null, 'lastname' => 'Simulación']);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        foreach (['oncologicos_laboratory_index', 'oncologicos_laboratory_create', 'oncologicos_laboratory_edit'] as $name) {
            $user->givePermissionTo(Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']));
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $user = $user->fresh();
        $item = ConsumableItem::create(['name' => 'SIMULACION SIN STOCK', 'unit' => 'pieza', 'is_active' => true]);
        $presentation = ConsumableCatalogPresentation::create(['consumable_item_id' => $item->id, 'presentation' => 'Unidad', 'is_active' => true]);
        $lot = ConsumableLot::create(['consumable_item_id' => $item->id, 'catalog_presentation_id' => $presentation->id, 'warehouse_id' => $warehouse->id, 'presentation' => 'Unidad', 'lot' => 'SIN-'.uniqid(), 'received_at' => now(), 'stock_actual' => 2, 'is_active' => true]);

        $this->actingAs($user)->post(route('admin.production-supplies.store'), ['warehouse_id' => $warehouse->id, 'lines' => [['supply_id' => $lot->id, 'quantity' => 3]]])->assertRedirect();
        $request = ProductionSupplyRequest::query()->where('requested_by', $user->id)
            ->whereHas('lines', fn ($query) => $query->where('consumable_lot_id', $lot->id))
            ->firstOrFail();
        $line = $request->lines()->firstOrFail();
        $this->actingAs($user)->patch(route('admin.production-supplies.approve', $request), ['lines' => [$line->id => ['approved_quantity' => 3]]])->assertRedirect();
        $this->actingAs($user)->patch(route('admin.production-supplies.supply', $request), ['lines' => [$line->id => ['supplied_quantity' => 3]]])->assertSessionHasErrors('lines');
        $this->assertSame(2.0, (float) $lot->fresh()->stock_actual);
        $this->assertSame(ProductionSupplyRequest::STATUS_APPROVED, $request->fresh()->status);
    }
}
