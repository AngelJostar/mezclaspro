<?php

namespace Tests\Feature;

use App\Models\Oncologicos\Diluent;
use App\Models\Oncologicos\DiluentCatalogPresentation;
use App\Models\Oncologicos\DiluentPresentation;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class DiluentWarehouseInventoryFlowTest extends TestCase
{
    use DatabaseTransactions;

    public function test_catalog_presentation_can_receive_a_lot_and_add_to_an_existing_lot(): void
    {
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
        $warehouse = Warehouse::query()->where('is_active', true)->firstOrFail();
        $user = User::factory()->create(['hospital_id' => null, 'lastname' => 'Simulación']);
        foreach (['oncologicos_laboratory_create', 'oncologicos_laboratory_index'] as $permissionName) {
            $user->givePermissionTo(Permission::firstOrCreate(['name' => $permissionName, 'guard_name' => 'web']));
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $diluent = Diluent::create(['denominacion_generica' => 'SIMULACION DILUYENTE '.uniqid()]);
        $catalog = DiluentCatalogPresentation::create(['diluent_id' => $diluent->id, 'presentation' => 'Bolsa 500 mL', 'volume_ml' => 500, 'commercial_name' => 'Prueba', 'manufacturer' => 'CMP', 'is_active' => true]);

        $payload = ['catalog_presentation_id' => $catalog->id, 'lote' => 'DIL-'.uniqid(), 'caducidad' => now()->addYear()->toDateString(), 'fecha_ingreso' => now()->toDateString(), 'cantidad' => 10];
        $this->actingAs($user)->post(route('admin.warehouses.supplies.store', $warehouse), $payload)->assertRedirect(route('admin.warehouses.supplies.index', $warehouse));
        $lot = DiluentPresentation::where('catalog_presentation_id', $catalog->id)->firstOrFail();
        $this->assertSame(10.0, (float) $lot->stock_actual);

        $this->actingAs($user)->post(route('admin.warehouses.supplies.store', $warehouse), array_merge($payload, ['cantidad' => 3]))->assertRedirect();
        $this->assertSame(13.0, (float) $lot->fresh()->stock_actual);
        $this->actingAs($user)->get(route('admin.warehouses.supplies.index', $warehouse))->assertOk()->assertSee($diluent->denominacion_generica)->assertSee('Bolsa 500 mL');
    }
}
