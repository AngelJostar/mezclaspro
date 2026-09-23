<?php

namespace Tests\Feature;

use App\Models\RequestQuotation;
use App\Models\User;
use App\Exports\RequestQuotationsExport;
use App\Notifications\QuotationAssigned;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\Permission\Models\Role;
use Tests\Fixtures\RequestQuotationCaptureData as Fixture;
use Tests\TestCase;

class QuotationSellerTest extends TestCase
{
    private User $admin;
    private User $seller;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = Fixture::seed();
        $this->seller = User::create(['name' => 'Vendedora', 'lastname' => 'Prueba',
            'username' => 'ventas.prueba', 'password' => bcrypt('test-password'), 'is_active' => true]);
        $this->seller->assignRole(Role::findOrCreate('Vendedor', 'web'), Role::findOrCreate('Capacitacion', 'web'));
        $this->actingAs($this->admin);
    }

    private function store(array $data)
    {
        return $this->postJson(route('admin.solicitudes.cotizacion.store'), $data);
    }

    private function screen(array $filters = [])
    {
        return $this->get(route('admin.solicitudes.cotizacion.index', $filters));
    }

    public function test_assignment_is_saved_displayed_exported_and_delivered_once_on_send(): void
    {
        $payload = Fixture::payload() + ['seller_id' => $this->seller->id];
        $this->store($payload)->assertOk();
        $quote = RequestQuotation::latest('id')->firstOrFail();
        $this->assertEquals($this->seller->id, $quote->seller_id);
        $this->assertArrayNotHasKey('seller_id', $quote->clinical_data);
        $this->assertSame(0, $this->seller->notifications()->count());
        $this->getJson(route('admin.solicitudes.cotizacion.show', $quote))->assertOk()
            ->assertJsonPath('seller_id', $this->seller->id)->assertJsonPath('seller_name', 'Vendedora Prueba');
        $this->screen()->assertOk()->assertSee('Vendedora Prueba')->assertSee('name="seller_id"', false);

        $payload['action'] = 'send';
        $url = route('admin.solicitudes.cotizacion.update', $quote);
        $this->putJson($url, $payload)->assertOk();
        $this->putJson($url, $payload)->assertForbidden();
        $this->store($payload)->assertOk();
        $this->assertSame(1, $this->seller->notifications()->count());
        $notification = $this->seller->notifications()->first();
        $this->assertSame(QuotationAssigned::class, $notification->type);
        $this->assertEquals($quote->id, $notification->data['quotation_id']);
        $this->assertStringContainsString('estado=recibidas', $notification->data['url']);

        $this->actingAs($this->seller);
        $this->screen(['estado' => 'recibidas'])->assertOk()
            ->assertViewHas('quotations', fn ($rows) => $rows->modelKeys() === [$quote->id]);
        Excel::fake();
        $this->get(route('admin.solicitudes.cotizacion.export'))->assertOk();
        Excel::assertDownloaded('Cotizaciones-'.now()->format('Y-m-d').'.xlsx', function (RequestQuotationsExport $export) use ($quote) {
            $this->assertSame([$quote->id], $export->collection()->modelKeys());
            $this->assertSame('Vendedora Prueba', $export->map($quote->fresh())[6]);
            return true;
        });
    }

    public function test_seller_is_automatically_assigned_and_cannot_forge_another_recipient(): void
    {
        $this->actingAs($this->seller);
        $this->screen()->assertOk()->assertSee('Vendedora Prueba')->assertDontSee('name="seller_id"', false)
            ->assertDontSee('href="'.route('admin.solicitudes.index').'"', false)
            ->assertDontSee('href="'.route('admin.catalogo-listas.index').'"', false);
        foreach (['oncologicos', 'nutricionales'] as $category) {
            $payload = Fixture::payload($category);
            $payload['action'] = 'send';
            $this->store($payload)->assertOk();
            $quote = RequestQuotation::latest('id')->firstOrFail();
            $this->assertEquals($this->seller->id, $quote->seller_id);
            $this->assertEquals($this->seller->id, $quote->created_by);
        }
        $this->assertSame(0, $this->seller->notifications()->count());
        $this->store(Fixture::payload() + ['seller_id' => $this->admin->id])->assertUnprocessable()->assertJsonValidationErrors('seller_id');
        $this->get(route('admin.solicitudes.index'))->assertRedirect(route('admin.solicitudes.cotizacion.index'));
        $this->get(route('admin.capacitaciones.personal'))->assertRedirect(route('admin.solicitudes.cotizacion.index'));
        $this->post(route('admin.capacitaciones.personal.store'), [])->assertRedirect(route('admin.solicitudes.cotizacion.index'));
        $this->withSession(['access_context' => 'training'])->screen()->assertRedirect(route('admin.capacitaciones.index'));
    }

    public function test_drafts_and_foreign_quotes_are_not_exposed_to_a_seller(): void
    {
        RequestQuotation::whereIn('id', [1, 2])->update(['seller_id' => $this->seller->id]);
        $this->actingAs($this->seller);
        $this->screen()->assertOk()->assertViewHas('quotations', fn ($rows) => $rows->modelKeys() === [2]);
        foreach ([1, 3, 5] as $id) {
            $this->getJson(route('admin.solicitudes.cotizacion.show', $id))->assertForbidden();
            $this->get(route('admin.solicitudes.cotizacion.attachment', $id))->assertForbidden();
            $this->post(route('admin.solicitudes.cotizacion.authorize', $id))->assertForbidden();
            $this->putJson(route('admin.solicitudes.cotizacion.update', $id), Fixture::payload())->assertForbidden();
        }
        $this->getJson(route('admin.solicitudes.cotizacion.show', 2))->assertOk()->assertJsonPath('editable', false);
        $this->post(route('admin.solicitudes.cotizacion.authorize', 2))->assertForbidden();
    }

    public function test_invalid_and_inactive_assignments_are_rejected_and_existing_draft_is_preserved(): void
    {
        $payload = Fixture::payload() + ['seller_id' => $this->seller->id];
        $this->store($payload)->assertOk();
        $quote = RequestQuotation::latest('id')->firstOrFail();
        $this->seller->update(['is_active' => false]);
        foreach ([$this->admin->id, $this->seller->id, 9999] as $id) {
            $this->store(Fixture::payload() + ['seller_id' => $id])->assertUnprocessable()->assertJsonValidationErrors('seller_id');
        }
        $this->screen()->assertOk()->assertViewHas('sellers', fn ($sellers) => $sellers->isEmpty());
        $url = route('admin.solicitudes.cotizacion.update', $quote);
        $this->putJson($url, $payload)->assertOk();
        $payload['action'] = 'send';
        $this->putJson($url, $payload)->assertUnprocessable()->assertJsonValidationErrors('seller_id');
        $payload['seller_id'] = null;
        $this->putJson($url, $payload)->assertOk();
        $this->assertNull($quote->fresh()->seller_id);
        $this->assertSame(0, DB::table('notifications')->count());
    }

    public function test_hospital_can_assign_a_seller_without_seeing_other_hospitals(): void
    {
        $this->admin->syncRoles(Role::findOrCreate('Cliente', 'web'));
        $this->store(Fixture::payload() + ['seller_id' => $this->seller->id])->assertOk();
        $this->screen()->assertOk()->assertDontSee('Paciente ajeno');
        $this->getJson(route('admin.solicitudes.cotizacion.show', 5))->assertForbidden();
    }
}
