<?php

namespace Tests\Feature;

use App\Models\MinimumStockSetting;
use App\Models\Oncologicos\LaboratoryPurchaseOrder;
use App\Models\User;
use App\Services\AutomaticPurchaseOrderService;
use App\Services\StockReorderMonitor;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Tests\Fixtures\MinimumStockData;
use Tests\Fixtures\PurchaseNavigationData;
use Tests\TestCase;

class AutomaticPurchaseOrderTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(PurchaseNavigationData::seed());
        MinimumStockData::seed();
        Mail::fake();
        Http::preventStrayRequests();
    }

    private function generate(): int
    {
        return app(AutomaticPurchaseOrderService::class)->reconcile();
    }

    private function lowStock(): void
    {
        // Five pieces remain in the second warehouse; two in the first.
        DB::table('medicine_batches')->where('id', 1)->update(['stock_actual' => 2]);
    }

    public function test_strict_threshold_creates_real_pending_order_with_snapshot_and_no_external_send(): void
    {
        DB::table('medicine_batches')->where('id', 1)->update(['stock_actual' => 5]);
        $this->assertSame(0, $this->generate(), 'Equal to reorder point must not generate an order.');
        $this->lowStock();
        $this->assertSame(1, $this->generate());
        $order = LaboratoryPurchaseOrder::where('is_automatic', true)->sole();
        $this->assertSame('pendiente_revision', $order->status);
        $this->assertSame('1:medicine:1', $order->automatic_open_key);
        $this->assertSame('Proveedor A', $order->supplier);
        $this->assertSame('compras-a@example.test', $order->supplier_email);
        $this->assertSame(23, $order->items[0]['quantity']);
        $this->assertNull($order->items[0]['unit_price']);
        $this->assertTrue($order->reorder_snapshot['pricing_pending']);
        $this->assertEquals(7, $order->reorder_snapshot['trigger_stock']);
        $this->assertNull($order->warehouse_id, 'Do not guess among multiple active warehouses.');
        $this->assertNull($order->created_by, 'Do not attribute a system order to the browsing user.');
        $this->assertStringStartsWith('OC-AUTO-', $order->folio);
        Mail::assertNothingSent();
        Http::assertNothingSent();
    }

    public function test_repeated_runs_update_one_draft_and_recovery_closes_it_before_a_new_cycle(): void
    {
        $this->lowStock();
        $this->generate();
        $order = LaboratoryPurchaseOrder::where('is_automatic', true)->sole();
        $folio = $order->folio;
        $this->assertSame(0, $this->generate());
        DB::table('medicine_batches')->where('id', 1)->update(['stock_actual' => 0]);
        $this->assertSame(0, $this->generate());
        $order->refresh();
        $this->assertSame($folio, $order->folio);
        $this->assertSame(25, $order->items[0]['quantity']);
        $this->assertEquals(7, $order->reorder_snapshot['trigger_stock']);
        $this->assertEquals(5, $order->reorder_snapshot['current_stock']);
        DB::table('medicine_batches')->where('id', 1)->update(['stock_actual' => 5]);
        $this->generate();
        $this->assertSame('cancelada', $order->fresh()->status);
        $this->assertNull($order->fresh()->automatic_open_key);
        $this->assertNotEmpty($order->fresh()->reorder_snapshot['closed_reason']);
        $this->lowStock();
        $this->assertSame(1, $this->generate());
        $this->assertSame(2, LaboratoryPurchaseOrder::where('is_automatic', true)->count());
        $this->assertSame(1, LaboratoryPurchaseOrder::whereNotNull('automatic_open_key')->count());
    }

    public function test_missing_or_inactive_supplier_remains_pending_and_changes_refresh_draft(): void
    {
        $this->lowStock();
        MinimumStockSetting::first()->update(['supplier_id' => 3]);
        $this->generate();
        $order = LaboratoryPurchaseOrder::where('is_automatic', true)->sole();
        $this->assertSame('Sin proveedor asignado', $order->supplier);
        $this->assertNull($order->supplier_email);
        MinimumStockSetting::first()->update(['supplier_id' => 2, 'maximum_stock' => 24]);
        $this->generate();
        $this->assertSame('Proveedor B', $order->fresh()->supplier);
        $this->assertSame(17, $order->fresh()->items[0]['quantity']);
        $this->assertSame(1, LaboratoryPurchaseOrder::where('is_automatic', true)->count());
    }

    public function test_types_centrals_and_fractional_stock_do_not_collide(): void
    {
        foreach ([['nutrition', 1, 10, 20], ['medicine', 3, 6, 18], ['diluent', 1, 24, 30], ['consumable', 1, 65, 100]] as [$type, $id, $min, $max]) {
            MinimumStockSetting::create(['laboratory_id' => 1, 'product_type' => $type, 'presentation_id' => $id,
                'minimum_stock' => $min, 'maximum_stock' => $max]);
        }
        MinimumStockSetting::create(['laboratory_id' => 2, 'product_type' => 'medicine', 'presentation_id' => 1, 'minimum_stock' => 81, 'maximum_stock' => 100]);
        $this->assertSame(5, $this->generate());
        $orders = LaboratoryPurchaseOrder::where('is_automatic', true)->get()->keyBy('automatic_open_key');
        $this->assertSame(11, $orders['1:nutrition:1']->items[0]['quantity']);
        $this->assertSame('antibioticos', $orders['1:medicine:3']->inventory_destination);
        $this->assertSame(18, $orders['1:medicine:3']->items[0]['quantity']);
        $this->assertSame(7, $orders['1:diluent:1']->items[0]['quantity']);
        $this->assertSame(38, $orders['1:consumable:1']->items[0]['quantity']);
        $this->assertSame(2, (int) $orders['2:medicine:1']->warehouse_id);
        $this->assertSame(20, $orders['2:medicine:1']->items[0]['quantity']);
    }

    public function test_inactive_products_centrals_and_incomplete_limits_do_not_generate(): void
    {
        $this->lowStock();
        foreach ([['minimum_stock' => null], ['maximum_stock' => null], ['minimum_stock' => 0], ['maximum_stock' => 5]] as $values) {
            MinimumStockSetting::first()->update($values + ['minimum_stock' => 10, 'maximum_stock' => 30]);
            $this->assertSame(0, $this->generate());
        }
        MinimumStockSetting::first()->update(['minimum_stock' => 10, 'maximum_stock' => 30]);
        DB::table('medicine_presentations')->where('id', 1)->update(['is_available' => false]);
        $this->assertSame(0, $this->generate());
        DB::table('medicine_presentations')->where('id', 1)->update(['is_available' => true]);
        DB::table('laboratories')->where('id', 1)->update(['activo' => false]);
        $this->assertSame(0, $this->generate());
        DB::table('laboratories')->where('id', 1)->update(['activo' => true]);
        $this->assertSame(1, $this->generate());
        MinimumStockSetting::query()->delete();
        $this->generate();
        $this->assertSame('cancelada', LaboratoryPurchaseOrder::where('is_automatic', true)->sole()->status);
    }

    public function test_monitor_reconciles_query_builder_writes_after_commit_not_during_transaction_or_rollback(): void
    {
        $monitor = app(StockReorderMonitor::class);
        $monitor->flush();
        DB::beginTransaction();
        $this->lowStock();
        $monitor->flush();
        $this->assertSame(0, LaboratoryPurchaseOrder::where('is_automatic', true)->count());
        DB::rollBack();
        $monitor->flush();
        $this->assertSame(0, LaboratoryPurchaseOrder::where('is_automatic', true)->count());
        DB::transaction(fn () => $this->lowStock());
        $monitor->flush();
        $this->assertSame(1, LaboratoryPurchaseOrder::where('is_automatic', true)->count());
        $monitor->flush();
        $this->assertSame(1, LaboratoryPurchaseOrder::where('is_automatic', true)->count());
    }

    public function test_list_filter_detail_permissions_and_no_zero_price_pdf(): void
    {
        $this->lowStock();
        $this->generate();
        $order = LaboratoryPurchaseOrder::where('is_automatic', true)->sole();
        $url = route('admin.purchases.minimum-stock', ['laboratory_id' => 1, 'view' => 'automated']);
        $this->get($url)->assertOk()->assertSeeText($order->folio)->assertSeeText('Pendiente de revision')->assertSeeText('Por cotizar')
            ->assertSeeText('Cantidad a comprar (piezas)')->assertViewHas('automaticOrders', fn ($orders) => $orders->count() === 1);
        $this->get($url.'&tipo=nutricionales')->assertOk()->assertViewHas('automaticOrders', fn ($orders) => $orders->isEmpty());
        $this->get(route('admin.purchases.minimum-stock', ['laboratory_id' => 2, 'view' => 'automated']))
            ->assertOk()->assertDontSeeText($order->folio);
        $detail = route('admin.purchases.minimum-stock.orders.show', [1, $order]);
        $this->get($detail)->assertOk()->assertSeeText('Entrega y facturacion')->assertSeeText('Por cotizar')->assertSeeText($order->folio);
        $this->get(route('admin.purchases.minimum-stock.orders.show', [2, $order]))->assertNotFound();
        $this->get(route('admin.purchases.minimum-stock.orders.show', [1, 1]))->assertNotFound();
        $this->get(route('admin.oncologicos.laboratory.purchase-orders.download', [1, $order]))->assertUnprocessable();
        $user = User::findOrFail(2);
        $user->assignRole(Role::create(['name' => 'Usuario general', 'guard_name' => 'web']));
        $user->givePermissionTo(Permission::create(['name' => 'oncologicos_laboratory_index', 'guard_name' => 'web']));
        $this->actingAs($user)->get($detail)->assertForbidden();
        $this->get($url)->assertForbidden();
    }

    public function test_command_is_repeatable_and_does_not_change_manual_or_reviewed_orders(): void
    {
        $manual = LaboratoryPurchaseOrder::where('is_automatic', false)->get()->toJson();
        $this->lowStock();
        $this->artisan('inventory:reorder', ['--laboratory' => 1])->assertSuccessful();
        $order = LaboratoryPurchaseOrder::where('is_automatic', true)->sole();
        $order->update(['status' => 'enviada']);
        $snapshot = $order->fresh()->toJson();
        $this->artisan('inventory:reorder')->assertSuccessful();
        $this->assertSame($snapshot, $order->fresh()->toJson());
        $this->assertSame($manual, LaboratoryPurchaseOrder::where('is_automatic', false)->get()->toJson());
        $this->assertSame(1, LaboratoryPurchaseOrder::where('is_automatic', true)->count());
        $this->artisan('inventory:reorder', ['--laboratory' => 'invalid'])->assertFailed();
        Mail::assertNothingSent();
    }

    public function test_saving_limits_generates_without_visiting_automatic_orders(): void
    {
        $this->lowStock();
        $this->patchJson(route('admin.purchases.minimum-stock.update', [1, 'medicine', 1]), [
            'minimum_stock' => 10, 'maximum_stock' => 30,
        ])->assertOk();
        $this->assertSame(1, LaboratoryPurchaseOrder::where('is_automatic', true)->count());
        $this->assertSame('pendiente_revision', LaboratoryPurchaseOrder::where('is_automatic', true)->sole()->status);
    }

    public function test_database_constraint_rejects_duplicate_open_order(): void
    {
        $this->lowStock();
        $this->generate();
        $duplicate = LaboratoryPurchaseOrder::where('is_automatic', true)->sole()->replicate();
        $duplicate->folio = 'DUPLICATE-TEST';
        $this->expectException(\Illuminate\Database\QueryException::class);
        $duplicate->save();
    }

    public function test_history_separates_closed_orders_and_preserves_filters_and_detail_return(): void
    {
        $this->lowStock();
        $this->generate();
        $closed = LaboratoryPurchaseOrder::where('is_automatic', true)->sole();
        DB::table('medicine_batches')->where('id', 1)->update(['stock_actual' => 12]);
        $this->generate();
        $this->lowStock();
        $this->generate();
        $open = LaboratoryPurchaseOrder::whereNotNull('automatic_open_key')->sole();
        $query = ['laboratory_id' => 1, 'view' => 'history', 'tipo' => 'oncologicos'];
        $url = route('admin.purchases.minimum-stock', $query);
        $this->get($url)->assertOk()->assertViewHas('stockView', 'history')
            ->assertViewHas('automaticOrders', fn ($orders) => $orders->pluck('id')->all() === [$closed->id])
            ->assertSeeText('OC Automatizadas Historial')->assertSeeText('Cancelada')
            ->assertSeeText($closed->folio)->assertDontSeeText($open->folio)
            ->assertSee(route('admin.purchases.minimum-stock', array_replace($query, ['laboratory_id' => 2])))
            ->assertSee(route('admin.purchases.minimum-stock', array_replace($query, ['tipo' => 'nutricionales'])));
        $this->get(route('admin.purchases.minimum-stock', array_replace($query, ['view' => 'automated'])))
            ->assertOk()->assertViewHas('automaticOrders', fn ($orders) => $orders->pluck('id')->all() === [$open->id]);
        foreach (['laboratory_id' => 2, 'tipo' => 'nutricionales'] as $field => $value) {
            $this->get(route('admin.purchases.minimum-stock', array_replace($query, [$field => $value])))
                ->assertOk()->assertViewHas('automaticOrders', fn ($orders) => $orders->isEmpty())
                ->assertSeeText('No hay ordenes de compra automatizadas en el historial para esta seleccion.');
        }
        $this->get(route('admin.purchases.minimum-stock.orders.show', [1, $closed, 'view' => 'history', 'tipo' => 'oncologicos']))
            ->assertOk()->assertSee($url)->assertSeeText($closed->folio);
        $this->assertSame(2, LaboratoryPurchaseOrder::where('is_automatic', true)->count());
    }
}
