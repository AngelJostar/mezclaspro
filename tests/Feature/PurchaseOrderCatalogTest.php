<?php

namespace Tests\Feature;

use App\Models\Oncologicos\LaboratoryPurchaseOrder;
use App\Models\Warehouse;
use App\Models\User;
use App\Services\PurchaseOrderCatalogService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PurchaseOrderCatalogTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Isolated SQLite schema for the inventory tables used by these queries.
        foreach ([
            'laboratories' => ['nombre', 'direccion', 'activo'],
            'warehouses' => ['laboratory_id', 'name', 'address', 'is_active'],
            'users' => ['name', 'lastname'],
            'medicines_catalog' => ['denominacion', 'catalog_category'],
            'medicine_presentations' => ['catalog_id', 'presentacion', 'marca', 'fabricante'],
            'medicine_batches' => ['medicine_presentation_id', 'laboratory_id', 'warehouse_id', 'stock_actual', 'is_active'],
            'nutrition_medicines_catalog' => ['denominacion_generica'],
            'nutrition_medicine_presentations' => ['nutrition_medicine_catalog_id', 'presentacion', 'denominacion_comercial', 'fabricante'],
            'medicine_laboratory_stocks' => ['nutrition_medicine_presentation_id', 'laboratory_id', 'warehouse_id', 'frascos_actuales'],
            'diluents' => ['denominacion_generica'],
            'diluent_presentations' => ['diluent_id', 'laboratory_id', 'warehouse_id', 'presentacion', 'denominacion_comercial', 'fabricante'],
        ] as $name => $columns) {
            Schema::create($name, function (Blueprint $table) use ($columns) {
                $table->id();
                foreach ($columns as $column) {
                    $table->string($column)->nullable();
                }
                $table->timestamps();
            });
        }
        foreach ([
            '2026_08_11_000004_create_laboratory_purchase_orders_table.php',
            '2026_08_11_000006_expand_laboratory_purchase_orders.php',
            '2026_08_13_000002_add_delivery_destination_to_laboratory_purchase_orders.php',
            '2026_08_20_000001_add_inventory_destination_to_laboratory_purchase_orders.php',
            '2026_08_20_000007_create_suppliers_table.php',
        ] as $migration) {
            (require database_path('migrations/'.$migration))->up();
        }
        DB::table('laboratories')->insert([
            ['id' => 1, 'nombre' => 'Central Uno', 'activo' => 1],
            ['id' => 2, 'nombre' => 'Central Dos', 'activo' => 1],
            ['id' => 3, 'nombre' => 'Inactiva', 'activo' => 0],
        ]);
        DB::table('warehouses')->insert([
            ['id' => 1, 'laboratory_id' => 1, 'name' => 'Norte', 'is_active' => 1],
            ['id' => 2, 'laboratory_id' => 1, 'name' => 'Sur', 'is_active' => 1],
            ['id' => 3, 'laboratory_id' => 2, 'name' => 'Otra central', 'is_active' => 1],
            ['id' => 4, 'laboratory_id' => 1, 'name' => 'Inactivo', 'is_active' => 0],
        ]);
        foreach ([1 => 'oncologicos', 2 => 'antibioticos', 3 => 'oncologicos', 4 => 'oncologicos', 5 => null] as $id => $category) {
            DB::table('medicines_catalog')->insert(['id' => $id, 'denominacion' => 'Medicamento '.$id, 'catalog_category' => $category]);
            DB::table('medicine_presentations')->insert(['id' => $id, 'catalog_id' => $id, 'presentacion' => 'Frasco 100 mg', 'marca' => 'Marca']);
        }
        foreach ([[1, 1, 1], [1, 1, 1], [2, 1, 1], [3, 1, 2], [4, 2, 3], [5, 1, 1]] as [$presentation, $lab, $warehouse]) {
            DB::table('medicine_batches')->insert([
                'medicine_presentation_id' => $presentation, 'laboratory_id' => $lab,
                'warehouse_id' => $warehouse, 'stock_actual' => 0, 'is_active' => 0,
            ]);
        }
    }

    private function url(string $action = 'products'): string
    {
        return route('admin.oncologicos.laboratory.purchase-orders.'.$action, 1);
    }

    private function destination(array $overrides = []): array
    {
        return array_replace(['delivery_laboratory_id' => 1, 'warehouse_id' => 1, 'inventory_destination' => 'oncologicos'], $overrides);
    }

    private function withoutPurchaseAuthorization(): void
    {
        $this->withoutMiddleware([
            \App\Http\Middleware\Authenticate::class,
            \App\Http\Middleware\EnsureUserIsActive::class,
            \App\Http\Middleware\RestrictCapacitacionAccess::class,
            \App\Http\Middleware\EnsureGeneralUserMenuAccess::class,
            \Illuminate\Auth\Middleware\Authorize::class,
        ]);
    }

    public function test_catalog_is_scoped_deduplicated_and_includes_exhausted_products(): void
    {
        $this->withoutPurchaseAuthorization();
        $this->getJson($this->url().'?'.http_build_query($this->destination()))->assertOk()
            ->assertJsonCount(2, 'products')
            ->assertJsonPath('products.0.product_key', 'oncologicos:1')
            ->assertJsonPath('products.1.product_key', 'oncologicos:5');
        $this->getJson($this->url().'?'.http_build_query($this->destination(['inventory_destination' => 'antibioticos'])))
            ->assertOk()->assertJsonCount(1, 'products')->assertJsonPath('products.0.product_key', 'antibioticos:2');
        $this->getJson($this->url().'?'.http_build_query($this->destination(['warehouse_id' => 2])))
            ->assertOk()->assertJsonCount(1, 'products')->assertJsonPath('products.0.product_key', 'oncologicos:3');
    }

    public function test_nutrition_and_supplies_use_their_own_warehouse_catalogs(): void
    {
        DB::table('nutrition_medicines_catalog')->insert(['id' => 1, 'denominacion_generica' => 'Aminoacidos']);
        DB::table('nutrition_medicine_presentations')->insert(['id' => 1, 'nutrition_medicine_catalog_id' => 1, 'presentacion' => 'Bolsa 500 ml']);
        DB::table('medicine_laboratory_stocks')->insert(['nutrition_medicine_presentation_id' => 1, 'laboratory_id' => 1, 'warehouse_id' => 1, 'frascos_actuales' => 0]);
        DB::table('diluents')->insert(['id' => 1, 'denominacion_generica' => 'Solucion']);
        foreach ([1, 1, 2] as $warehouse) {
            DB::table('diluent_presentations')->insert(['diluent_id' => 1, 'laboratory_id' => 1, 'warehouse_id' => $warehouse, 'presentacion' => 'Bolsa 100 ml']);
        }
        $catalog = app(PurchaseOrderCatalogService::class);
        $this->assertSame(['nutricionales:1'], $catalog->products(Warehouse::find(1), 'nutricionales')->pluck('product_key')->all());
        $this->assertSame(['insumos:1'], $catalog->products(Warehouse::find(1), 'insumos')->pluck('product_key')->all());
        $this->assertEmpty($catalog->products(Warehouse::find(2), 'nutricionales'));
        $this->assertSame(['insumos:3'], $catalog->products(Warehouse::find(2), 'insumos')->pluck('product_key')->all());
    }

    public function test_endpoint_rejects_incomplete_invalid_and_cross_central_destinations(): void
    {
        $this->withoutPurchaseAuthorization();
        foreach ([
            [[], 'delivery_laboratory_id'],
            [$this->destination(['warehouse_id' => 3]), 'warehouse_id'],
            [$this->destination(['warehouse_id' => 4]), 'warehouse_id'],
            [$this->destination(['delivery_laboratory_id' => 3]), 'delivery_laboratory_id'],
            [$this->destination(['inventory_destination' => 'inventado']), 'inventory_destination'],
        ] as [$data, $error]) {
            $this->getJson($this->url().'?'.http_build_query($data))->assertUnprocessable()->assertJsonValidationErrors($error);
        }
    }

    private function payload(): array
    {
        return $this->destination() + [
            'department' => 'Compras', 'supplier' => 'Proveedor prueba', 'requested_at' => '2026-09-04',
            'invoice_to' => 'Empresa', 'invoice_address' => 'Domicilio', 'invoice_rfc' => 'AAA010101AAA',
            'delivery_address' => 'Destino', 'tax_rate' => 16, 'discount' => 10,
            'items' => [['product_key' => 'oncologicos:1', 'description' => 'Texto no confiable', 'quantity' => 2, 'unit_price' => 100]],
        ];
    }

    public function test_store_rejects_free_text_and_products_from_other_warehouses_or_subwarehouses(): void
    {
        $this->withoutPurchaseAuthorization();
        foreach (['', 'oncologicos:3', 'oncologicos:4', 'oncologicos:2', 'antibioticos:2', 'oncologicos:999'] as $key) {
            $data = $this->payload();
            $data['items'][0]['product_key'] = $key;
            $this->postJson($this->url('store'), $data)->assertUnprocessable()->assertJsonValidationErrors('items.0.product_key');
        }
        $this->assertSame(0, LaboratoryPurchaseOrder::count());
        $this->assertSame(0, DB::table('suppliers')->count());
    }

    public function test_store_preserves_all_lines_and_totals_with_canonical_catalog_descriptions(): void
    {
        $this->withoutPurchaseAuthorization();
        $data = $this->payload();
        $data['items'] = array_fill(0, 3, $data['items'][0]);
        $this->post($this->url('store'), $data)->assertSessionHasNoErrors()->assertRedirect();
        $order = LaboratoryPurchaseOrder::sole();
        $this->assertCount(3, $order->items);
        foreach ($order->items as $item) {
            $this->assertSame('oncologicos:1', $item['product_key']);
            $this->assertSame('Medicamento 1 - Frasco 100 mg - Marca', $item['description']);
        }
        $this->assertSame('600.00', $order->subtotal);
        $this->assertSame('684.40', $order->total);
        $this->assertSame('Norte - Central Uno', $order->delivery_attention);
        $this->assertSame('oncologicos', $order->inventory_destination);
        $this->assertEquals(1, $order->warehouse_id);
        $this->assertEquals(1, $order->delivery_laboratory_id);
    }

    public function test_one_order_cannot_mix_subwarehouses_or_warehouses_across_its_lines(): void
    {
        $this->withoutPurchaseAuthorization();
        foreach (['antibioticos:2', 'oncologicos:3', 'oncologicos:4'] as $foreignProduct) {
            $data = $this->payload();
            $data['items'] = array_fill(0, 4, $data['items'][0]);
            $data['items'][3]['product_key'] = $foreignProduct;
            $this->postJson($this->url('store'), $data)->assertUnprocessable()
                ->assertJsonValidationErrors('items.3.product_key');
        }
        $data = $this->payload();
        $data['inventory_destination'] = ['oncologicos', 'antibioticos'];
        $this->postJson($this->url('store'), $data)->assertUnprocessable()
            ->assertJsonValidationErrors('inventory_destination');
        $this->assertSame(0, LaboratoryPurchaseOrder::count());
        $this->assertSame(0, DB::table('suppliers')->count());
    }

    public function test_catalog_endpoint_requires_authentication_and_purchase_permission(): void
    {
        $this->getJson($this->url())->assertUnauthorized();
        $route = app('router')->getRoutes()->getByName('admin.oncologicos.laboratory.purchase-orders.products');
        $this->assertContains('can:oncologicos_laboratory_index', $route->gatherMiddleware());
        $this->assertContains('menu.access', $route->gatherMiddleware());
    }

    public function test_prepared_by_uses_the_logged_in_users_full_name_and_is_preserved_in_the_pdf(): void
    {
        $this->withoutPurchaseAuthorization();
        $user = User::create(['name' => 'Ana Maria', 'lastname' => 'Lopez Ruiz']);
        $data = $this->payload();
        $data['prepared_by'] = 'Nombre enviado manualmente';
        $this->actingAs($user)->post($this->url('store'), $data)->assertSessionHasNoErrors()->assertRedirect();

        $order = LaboratoryPurchaseOrder::sole()->load(['laboratory', 'deliveryLaboratory', 'warehouse', 'creator']);
        $this->assertSame($user->id, $order->created_by);
        $this->assertSame('Ana Maria Lopez Ruiz', $order->prepared_by);
        $html = view('admin.oncologicos.laboratory.purchase-orders.pdf', compact('order'))->render();
        $this->assertStringContainsString('>Ana Maria Lopez Ruiz</div>', $html);
        $this->assertStringNotContainsString('Nombre enviado manualmente', $html);

        // Historical orders retain the name captured when they were created.
        $order->creator->name = 'Otro nombre';
        $html = view('admin.oncologicos.laboratory.purchase-orders.pdf', compact('order'))->render();
        $this->assertStringContainsString('>Ana Maria Lopez Ruiz</div>', $html);
        $order->prepared_by = null;
        $html = view('admin.oncologicos.laboratory.purchase-orders.pdf', compact('order'))->render();
        $this->assertStringContainsString('>Otro nombre Lopez Ruiz</div>', $html);
    }
}
