<?php

namespace Tests\Feature;

use App\Models\Oncologicos\LaboratoryPurchaseOrder;
use App\Models\User;
use App\Support\PurchaseNavigation;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\Fixtures\PurchaseNavigationData;
use Tests\TestCase;

class PurchaseNavigationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(PurchaseNavigationData::seed());
    }

    public function test_status_filters_and_central_selection_are_combined(): void
    {
        foreach (['paid' => 'pagada', 'pending' => 'pendiente_pago', 'rejected' => 'rechazada'] as $section => $status) {
            foreach ([1, 2] as $central) {
                $this->get(route('admin.warehouses.purchase-orders.index', ['section' => $section, 'laboratory_id' => $central]))
                    ->assertOk()->assertViewHas('selectedLaboratory', fn ($lab) => $lab->id === $central)
                    ->assertViewHas('purchaseOrders', fn ($orders) => $orders->pluck('folio')->all() === ['OC-'.$central.'-'.$status])
                    ->assertSee('aria-label="Centrales de compras"', false)
                    ->assertSeeInOrder(['Todas', 'Pagadas', 'Pendientes de pago', 'Rechazadas', 'Nueva OC'])
                    ->assertSee(route('admin.purchases.create', ['laboratory_id' => $central]))
                    ->assertViewHas('laboratories', fn ($labs) => $labs->firstWhere('id', 1)->active_warehouses_count === 1);
            }
        }
    }

    public function test_all_shows_every_status_and_creator_only_in_the_selected_central(): void
    {
        foreach ([1, 2] as $central) {
            $this->get(route('admin.warehouses.purchase-orders.index', ['section' => 'all', 'laboratory_id' => $central]))
                ->assertOk()->assertViewHas('section', 'all')
                ->assertViewHas('purchaseOrders', fn ($orders) => $orders->count() === 4
                    && $orders->every(fn ($order) => $order->laboratory_id === $central)
                    && $orders->pluck('created_by')->unique()->count() === 2
                    && $orders->pluck('status')->unique()->count() === 4)
                ->assertSee(route('admin.warehouses.purchase-orders.index', ['section' => 'all', 'laboratory_id' => 3]));
        }
    }

    public function test_all_includes_unpriced_automatic_orders_without_a_zero_amount_or_download(): void
    {
        $order = LaboratoryPurchaseOrder::create([
            'laboratory_id' => 1, 'folio' => 'OC-AUTO-TEST', 'supplier' => 'Sin proveedor asignado',
            'details' => 'Reposicion automatizada de prueba',
            'requested_at' => '2026-09-22', 'status' => 'pendiente_revision', 'total' => 0,
            'is_automatic' => true, 'reorder_snapshot' => ['pricing_pending' => true],
        ]);
        $this->get(route('admin.warehouses.purchase-orders.index', ['section' => 'all', 'laboratory_id' => 1]))
            ->assertOk()->assertViewHas('purchaseOrders', fn ($orders) => $orders->count() === 5)
            ->assertSeeText('OC-AUTO-TEST')->assertSeeText('Pendiente de revision')
            ->assertSeeText('Por cotizar')->assertDontSeeText('$0.00')
            ->assertDontSee(route('admin.oncologicos.laboratory.purchase-orders.download', [1, $order]));
    }

    public function test_mine_retains_ownership_and_invalid_central_falls_back(): void
    {
        $this->get(route('admin.warehouses.purchase-orders.index', ['section' => 'mine', 'laboratory_id' => 2]))
            ->assertOk()->assertViewHas('purchaseOrders', fn ($orders) => $orders->count() === 3
                && $orders->every(fn ($order) => $order->created_by === 1 && $order->laboratory_id === 2));
        $this->get(route('admin.warehouses.purchase-orders.index', ['section' => 'invalid', 'laboratory_id' => 999]))
            ->assertOk()->assertViewHas('section', 'mine')->assertViewHas('selectedLaboratory', fn ($lab) => $lab->id === 1);
    }

    public function test_new_order_uses_selected_central_and_has_same_navigation(): void
    {
        $this->get(route('admin.purchases.create', ['laboratory_id' => 2]))
            ->assertRedirect(route('admin.oncologicos.laboratory.purchase-orders.create', 2));
        $this->get(route('admin.oncologicos.laboratory.purchase-orders.create', 2))
            ->assertOk()->assertViewHas('laboratory', fn ($lab) => $lab->id === 2)
            ->assertViewHas('laboratories', fn ($labs) => $labs->count() === 7)
            ->assertViewIs('admin.oncologicos.laboratory.purchase-orders.new')
            ->assertSee('data-purchase-order-popup aria-haspopup="dialog"', false)
            ->assertSeeText('Nueva OC')
            ->assertDontSee('id="purchase-order-form"', false)
            ->assertDontSee('purchase-order-config', false)
            ->assertDontSeeText('Nueva orden de compra')
            ->assertDontSeeText('Generar orden')
            ->assertSee(route('admin.warehouses.purchase-orders.index', ['section' => 'paid', 'laboratory_id' => 2]))
            ->assertSee(route('admin.oncologicos.laboratory.purchase-orders.create', 1));
    }

    public function test_new_order_popup_preserves_central_and_renders_only_the_form(): void
    {
        $this->get(route('admin.warehouses.purchase-orders.index', ['section' => 'all', 'laboratory_id' => 2]))
            ->assertOk()->assertSee('data-purchase-order-popup aria-haspopup="dialog"', false);
        $this->get(route('admin.purchases.create', ['laboratory_id' => 2, 'purchase_popup' => 1]))
            ->assertRedirect(route('admin.oncologicos.laboratory.purchase-orders.create', ['laboratory' => 2, 'purchase_popup' => 1]));
        $this->get(route('admin.oncologicos.laboratory.purchase-orders.create', ['laboratory' => 2, 'purchase_popup' => 1]))
            ->assertOk()->assertSee('po-popup-form')->assertSee('Generar orden')->assertSee('Cerrar')
            ->assertDontSee('data-purchase-navigation', false)
            ->assertSee('action="'.route('admin.oncologicos.laboratory.purchase-orders.store', ['laboratory' => 2, 'purchase_popup' => 1]).'"', false);
    }

    public function test_empty_central_list_renders_without_errors(): void
    {
        DB::table('laboratory_purchase_orders')->delete();
        DB::table('warehouses')->delete();
        DB::table('laboratories')->delete();
        $this->get(route('admin.warehouses.purchase-orders.index'))->assertOk()
            ->assertSeeText('No hay centrales de mezclas registradas.')->assertDontSee('data-purchase-carousel', false);
    }

    public function test_minimum_stock_moves_out_of_filters_and_preserves_central_on_its_own_page(): void
    {
        $url = route('admin.purchases.minimum-stock', ['laboratory_id' => 2]);
        foreach ([route('admin.warehouses.purchase-orders.index', ['section' => 'paid', 'laboratory_id' => 2]),
            route('admin.oncologicos.laboratory.purchase-orders.create', 2)] as $source) {
            $this->get($source)->assertOk()->assertDontSee($url)->assertDontSeeText('Stock Mínimo');
        }
        $this->get($url)->assertOk()->assertViewIs('admin.warehouses.minimum-stock')
            ->assertViewHas('selectedLaboratory', fn ($lab) => $lab->id === 2)
            ->assertSee(route('admin.purchases.minimum-stock', ['laboratory_id' => 1]));
    }

    public function test_sidebar_contains_orders_stock_and_suppliers_and_sections_obey_permissions(): void
    {
        $sidebar = view('layouts.includes.admin.aside', ['pendingSolicitudesCount' => 0, 'myPurchaseOrdersCount' => 3])->render();
        $this->assertStringContainsString('&Oacute;rdenes de compra', $sidebar);
        $this->assertStringContainsString('Stock m&iacute;nimo', $sidebar);
        $this->assertLessThan(strpos($sidebar, 'Stock m&iacute;nimo'), strpos($sidebar, '&Oacute;rdenes de compra'));
        $this->assertLessThan(strpos($sidebar, 'Proveedores'), strpos($sidebar, 'Stock m&iacute;nimo'));
        $this->assertStringNotContainsString('Mis Ordenes', $sidebar);
        $this->assertStringContainsString('Proveedores', $sidebar);
        foreach (['Nueva OC', 'Pagadas', 'Pendientes de Pago', 'Rechazadas'] as $label) $this->assertStringNotContainsString($label, $sidebar);

        $user = User::findOrFail(2);
        $user->assignRole(Role::create(['name' => 'Usuario general', 'guard_name' => 'web']));
        foreach (['menu.compras', 'menu.compras.paid', 'oncologicos_laboratory_index'] as $permission) {
            $user->givePermissionTo(Permission::create(['name' => $permission, 'guard_name' => 'web']));
        }
        $this->actingAs($user);
        $this->assertSame(['paid', 'minimum-stock'], array_keys(PurchaseNavigation::sections($user, 2)));
        $sidebar = view('layouts.includes.admin.aside', ['pendingSolicitudesCount' => 0, 'myPurchaseOrdersCount' => 0])->render();
        $this->assertStringContainsString(route('admin.warehouses.purchase-orders.index', ['section' => 'paid']), $sidebar);
        $this->get(route('admin.warehouses.purchase-orders.index', ['section' => 'paid']))->assertOk()->assertDontSeeText('Nueva OC');
        $this->get(route('admin.purchases.create'))->assertForbidden();
        $this->get(route('admin.purchases.minimum-stock'))->assertOk()->assertSeeText('Stock Mínimo');
        $this->get(route('admin.warehouses.purchase-orders.index', ['section' => 'pending']))->assertForbidden();
        $this->get(route('admin.warehouses.purchase-orders.index', ['section' => 'all']))->assertForbidden();
        $migration = require database_path('migrations/2026_09_22_000007_add_all_purchase_orders_permission.php');
        $migration->up();
        $migration->up();
        $this->get(route('admin.warehouses.purchase-orders.index', ['section' => 'all']))->assertForbidden();
        $user->givePermissionTo('menu.compras.all');
        $this->assertSame(['all', 'paid', 'minimum-stock'], array_keys(PurchaseNavigation::sections($user, 2)));
        $this->get(route('admin.warehouses.purchase-orders.index', ['section' => 'all', 'laboratory_id' => 2]))
            ->assertOk()->assertViewHas('purchaseOrders', fn ($orders) => $orders->count() === 4);
        $user->revokePermissionTo('menu.compras');
        $this->get(route('admin.purchases.minimum-stock'))->assertForbidden();
    }
}
