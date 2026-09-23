<?php

namespace Tests\Feature;

use App\Models\MinimumStockSetting;
use App\Models\User;
use App\Services\MinimumStockCatalogService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\Fixtures\MinimumStockData;
use Tests\Fixtures\PurchaseNavigationData;
use Tests\TestCase;

class MinimumStockTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(PurchaseNavigationData::seed());
        MinimumStockData::seed();
    }

    private function endpoint(int $central = 1, string $type = 'medicine', int $id = 1): string
    {
        return route('admin.purchases.minimum-stock.update', [$central, $type, $id]);
    }

    public function test_catalog_rows_and_navigation_match_stock_page_and_selected_central(): void
    {
        $this->get(route('admin.purchases.minimum-stock', ['laboratory_id' => 1]))->assertOk()
            ->assertViewHas('stockRows', fn ($rows) => $rows->count() === 6
                && $rows->firstWhere('key', 'medicine:1')->minimum_stock === 10
                && !$rows->firstWhere('key', 'medicine:2')->active)
            ->assertSeeText('Stock minimo')->assertSeeText('OC Automatizadas')->assertSeeText('OC Automatizadas Historial')
            ->assertSeeText('Punto de reorden (piezas)')->assertDontSeeText('Stock maximo (piezas)')
            ->assertSee('aria-label="Punto de reorden de Acido folinico Ampolleta 50 mg/4 ml"', false)
            ->assertDontSeeText('Pagadas')->assertDontSeeText('Pendientes de pago')->assertDontSeeText('Rechazadas')
            ->assertSeeText('Proveedor predeterminado')->assertSeeText('compras-a@example.test');
        $this->get(route('admin.purchases.minimum-stock', ['laboratory_id' => 2]))->assertOk()
            ->assertViewHas('stockRows', fn ($rows) => $rows->every(fn ($row) => $row->minimum_stock === null && $row->supplier === null));
    }

    public function test_limits_and_supplier_are_saved_independently_by_central_without_changing_catalog(): void
    {
        $this->patchJson($this->endpoint(), ['minimum_stock' => 8, 'maximum_stock' => 24])
            ->assertOk()->assertJsonPath('minimum_stock', 8)->assertJsonPath('supplier.id', 1);
        $this->patchJson($this->endpoint(), ['supplier_id' => 2])->assertOk()
            ->assertJsonPath('minimum_stock', 8)->assertJsonPath('maximum_stock', 24)
            ->assertJsonPath('supplier.email', 'compras-b@example.test');
        $this->patchJson($this->endpoint(2), ['minimum_stock' => 3, 'maximum_stock' => 7, 'supplier_id' => 1])
            ->assertOk()->assertJsonPath('minimum_stock', 3);
        $this->assertSame(8, MinimumStockSetting::where('laboratory_id', 1)->first()->minimum_stock);
        $this->assertSame('1', DB::table('medicine_presentations')->where('id', 1)->value('is_available'));
        $this->assertSame(2, MinimumStockSetting::count());
        $this->patchJson($this->endpoint(), ['supplier_id' => null])->assertOk()->assertJsonPath('supplier', null);
    }

    public function test_product_type_filter_uses_catalog_category_and_keeps_inventory_and_settings(): void
    {
        foreach (['todas' => ['medicine:1', 'medicine:2', 'medicine:3', 'nutrition:1', 'diluent:1', 'consumable:1'],
            'oncologicos' => ['medicine:1', 'medicine:2'], 'nutricionales' => ['nutrition:1'],
            'antibioticos' => ['medicine:3']] as $type => $keys) {
            $this->get(route('admin.purchases.minimum-stock', ['laboratory_id' => 1, 'tipo' => $type]))
                ->assertOk()->assertViewHas('stockType', $type)
                ->assertViewHas('stockRows', function ($rows) use ($keys) {
                    $this->assertEqualsCanonicalizing($keys, $rows->pluck('key')->all());
                    return true;
                });
        }
        $this->get(route('admin.purchases.minimum-stock', ['laboratory_id' => 1, 'tipo' => 'oncologicos']))
            ->assertViewHas('stockRows', fn ($rows) => $rows->firstWhere('key', 'medicine:1')->current_stock === 17.0
                && $rows->firstWhere('key', 'medicine:1')->minimum_stock === 10
                && !$rows->firstWhere('key', 'medicine:2')->active);
        $this->get(route('admin.purchases.minimum-stock', ['laboratory_id' => 2, 'tipo' => 'oncologicos']))
            ->assertViewHas('stockRows', fn ($rows) => $rows->firstWhere('key', 'medicine:1')->current_stock === 80.0);
    }

    public function test_product_carousel_preserves_central_and_tab_and_invalid_type_falls_back(): void
    {
        $this->get(route('admin.purchases.minimum-stock', ['laboratory_id' => 2, 'tipo' => 'nutricionales', 'view' => 'automated']))
            ->assertOk()->assertSee('aria-label="Tipo de producto"', false)
            ->assertSeeInOrder(['Todas', 'Nutricionales', 'Oncologicos', 'Antibioticos', 'Stock minimo'])
            ->assertSee(route('admin.purchases.minimum-stock', ['laboratory_id' => 1, 'view' => 'automated', 'tipo' => 'nutricionales']))
            ->assertSee(route('admin.purchases.minimum-stock', ['laboratory_id' => 2, 'tipo' => 'nutricionales']))
            ->assertSee(route('admin.purchases.minimum-stock', ['laboratory_id' => 2, 'view' => 'automated', 'tipo' => 'antibioticos']))
            ->assertSee(route('admin.purchases.minimum-stock', ['laboratory_id' => 2, 'view' => 'automated']));
        foreach (['incorrecto', ['oncologicos']] as $invalid) {
            $this->get(route('admin.purchases.minimum-stock', ['tipo' => $invalid]))
                ->assertOk()->assertViewHas('stockType', 'todas')->assertViewHas('stockRows', fn ($rows) => $rows->count() === 6);
        }
        DB::table('medicine_presentations')->where('catalog_id', 2)->delete();
        $this->get(route('admin.purchases.minimum-stock', ['tipo' => 'antibioticos']))
            ->assertOk()->assertViewHas('stockRows', fn ($rows) => $rows->isEmpty())
            ->assertSeeText('No hay productos del tipo seleccionado.');
    }

    public function test_current_stock_sums_lots_and_warehouses_by_presentation_and_central(): void
    {
        $catalog = app(MinimumStockCatalogService::class);
        $rows = $catalog->rows(1)->keyBy('key');
        foreach (['medicine:1' => 17, 'medicine:2' => 4, 'medicine:3' => 0,
            'nutrition:1' => 9.75, 'diluent:1' => 23, 'consumable:1' => 62] as $key => $amount) {
            $this->assertSame((float) $amount, $rows[$key]->current_stock, $key);
        }
        $this->assertFalse($rows['medicine:2']->active, 'Catalog status does not erase real inventory.');
        $other = $catalog->rows(2)->keyBy('key');
        $this->assertSame(80.0, $other['medicine:1']->current_stock);
        foreach (['nutrition:1', 'diluent:1', 'consumable:1'] as $key) $this->assertSame(90.0, $other[$key]->current_stock);
        $this->assertTrue($catalog->rows(3)->every(fn ($row) => $row->current_stock === 0.0));
    }

    public function test_current_stock_is_live_read_only_and_does_not_count_reserved_pieces_twice(): void
    {
        $catalog = app(MinimumStockCatalogService::class);
        DB::table('medicine_batches')->where('id', 1)->update(['stock_actual' => 9, 'stock_reservado' => 8]);
        $this->assertSame(14.0, $catalog->rows(1)->firstWhere('key', 'medicine:1')->current_stock);
        $this->patchJson($this->endpoint(), ['minimum_stock' => 3, 'maximum_stock' => 20, 'current_stock' => 999])->assertOk();
        $this->assertSame(14.0, $catalog->rows(1)->firstWhere('key', 'medicine:1')->current_stock);
        $this->assertSame(3, MinimumStockSetting::first()->minimum_stock);
        $html = $this->get(route('admin.purchases.minimum-stock', ['laboratory_id' => 1]))->assertOk()
            ->assertSeeText('Stock actual (piezas)')->getContent();
        $dom = new \DOMDocument;
        @$dom->loadHTML('<?xml encoding="UTF-8">'.$html);
        $xpath = new \DOMXPath($dom);
        $this->assertSame('14', trim($xpath->query('//tr[@data-stock-key="medicine:1"]/td[@data-stock-current]')->item(0)->textContent));
        $this->assertCount(0, $xpath->query('//td[@data-stock-current]//*[self::input or self::button]'));
        $this->assertCount(12, $xpath->query('//table[contains(@class,"minimum-stock-table")]//th'));
    }

    public function test_current_stock_keeps_physical_lots_without_warehouse_and_expired_pieces(): void
    {
        DB::table('medicine_batches')->insert(['medicine_presentation_id' => 1, 'laboratory_id' => 1,
            'warehouse_id' => null, 'stock_actual' => 2, 'caducidad' => '2000-01-01']);
        $this->assertSame(19.0, app(MinimumStockCatalogService::class)->rows(1)->firstWhere('key', 'medicine:1')->current_stock);
    }

    public function test_invalid_values_suppliers_and_products_do_not_modify_saved_limits(): void
    {
        foreach ([
            ['minimum_stock' => -1, 'maximum_stock' => 10],
            ['minimum_stock' => 30, 'maximum_stock' => 10],
            ['minimum_stock' => 1.5, 'maximum_stock' => 10],
            ['minimum_stock' => 1], ['maximum_stock' => 10], [],
            ['minimum_stock' => 0, 'maximum_stock' => 1000001],
            ['supplier_id' => 999], ['supplier_id' => 3],
        ] as $payload) $this->patchJson($this->endpoint(), $payload)->assertUnprocessable();
        $this->assertSame(10, MinimumStockSetting::first()->minimum_stock);
        $this->assertSame(1, MinimumStockSetting::count());
        $this->patchJson($this->endpoint(7), ['supplier_id' => 1])->assertUnprocessable();
        $this->patchJson($this->endpoint(1, 'unknown'), ['supplier_id' => 1])->assertNotFound();
        $this->patchJson($this->endpoint(1, 'medicine', 999), ['supplier_id' => 1])->assertNotFound();
        $this->patchJson($this->endpoint(999), ['supplier_id' => 1])->assertNotFound();
    }

    public function test_types_do_not_collide_and_legacy_installations_without_diluent_catalog_render(): void
    {
        foreach (['nutrition', 'diluent', 'consumable'] as $type) {
            $this->patchJson($this->endpoint(1, $type), ['minimum_stock' => 1, 'maximum_stock' => 5])->assertOk();
        }
        $this->assertSame(4, MinimumStockSetting::count());
        Schema::drop('diluent_catalog_presentations');
        $this->assertCount(5, app(MinimumStockCatalogService::class)->rows(1));
        $this->patchJson($this->endpoint(1, 'diluent'), ['supplier_id' => 1])->assertNotFound();
    }

    public function test_automated_tab_preserves_central_without_generating_orders(): void
    {
        $before = DB::table('laboratory_purchase_orders')->count();
        $this->get(route('admin.purchases.minimum-stock', ['laboratory_id' => 2, 'view' => 'automated']))
            ->assertOk()->assertViewHas('stockView', 'automated')->assertViewHas('stockRows', fn ($rows) => $rows->isEmpty())
            ->assertSeeText('No hay ordenes de compra automatizadas para esta seleccion.')
            ->assertSee(route('admin.purchases.minimum-stock', ['laboratory_id' => 1, 'view' => 'automated']));
        $this->assertSame($before, DB::table('laboratory_purchase_orders')->count());
    }

    public function test_user_without_stock_menu_cannot_update_settings(): void
    {
        $user = User::findOrFail(2);
        $user->assignRole(Role::create(['name' => 'Usuario general', 'guard_name' => 'web']));
        $user->givePermissionTo(Permission::create(['name' => 'oncologicos_laboratory_index', 'guard_name' => 'web']));
        $this->actingAs($user);
        $this->patchJson($this->endpoint(), ['minimum_stock' => 1, 'maximum_stock' => 2])->assertForbidden();
        $this->assertSame(10, MinimumStockSetting::first()->minimum_stock);
    }
}
