<?php

namespace Tests\Feature;

use App\Models\RequestQuotation;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Tests\Fixtures\RequestQuotationData;
use Tests\TestCase;

class RequestQuotationListTest extends TestCase
{
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = RequestQuotationData::seed();
        $this->actingAs($this->user);
    }

    private function screen(array $query = [])
    {
        return $this->get(route('admin.solicitudes.cotizacion.index', $query));
    }

    public function test_list_has_reference_columns_and_shared_category_carousel(): void
    {
        $response = $this->screen()->assertOk()->assertSee('Lista de Cotizaciones')
            ->assertViewHas('quotations', fn ($rows) => $rows->count() === 5)
            ->assertSeeInOrder(['Tipo', 'Folio', 'Fecha', 'Instituci&oacute;n', 'Hospital', 'Paciente', 'Vendedor', 'Lista de precios',
                'Total MXN', 'Estado', 'Enviar', 'Autorizaci&oacute;n', 'Detalle', 'Enviar a preparacion'], false);
        $xpath = $this->xpath($response->getContent());
        $this->assertCount(14, $xpath->query('//table[@id="request-quotations-table"]//th'));
        $this->assertSame('Tipo', trim($xpath->query('//table[@id="request-quotations-table"]//th')->item(0)->textContent));
        $this->assertSame(['Antibiotico', 'Nutricional', 'Oncologica', 'Oncologica', 'Oncologica'], array_map(
            fn ($cell) => trim($cell->textContent), iterator_to_array($xpath->query('//tr[@data-quotation-row]/td[1]'))));
        foreach ($xpath->query('//tr[@data-quotation-row]') as $row) {
            $this->assertCount(14, $xpath->query('./td', $row));
        }
        $this->assertCount(5, $xpath->query('//table[@id="request-quotations-table"]//button[@data-quotation-send]'));
        $this->assertSame(['Todas', 'Recibidas', 'Enviadas', 'Autorizadas', 'En preparacion'], array_map(
            fn ($link) => trim($link->textContent), iterator_to_array($xpath->query('//nav[@aria-label="Estado de las cotizaciones"]/a'))));
        $this->assertCount(4, $xpath->query('//nav[@aria-label="Tipo de solicitudes"]//a'));
        $this->assertCount(1, $xpath->query('//nav[@aria-label="Tipo de solicitudes"]//a[@aria-current="page"]'));
        $this->assertCount(1, $xpath->query('//nav[@aria-label="Estado de las cotizaciones"]//a[@aria-current="page"]'));
    }

    public function test_category_and_status_filters_combine_without_hiding_drafts_from_all(): void
    {
        foreach ([
            [[], [4, 3, 5, 2, 1]],
            [['tipo' => 'oncologicos'], [5, 2, 1]],
            [['tipo' => 'nutricionales'], [3]],
            [['tipo' => 'antibioticos'], [4]],
            [['estado' => 'enviadas'], [5, 2]],
            [['estado' => 'recibidas'], [5]],
            [['estado' => 'autorizadas'], [3]],
            [['estado' => 'preparacion'], [4]],
            [['tipo' => 'oncologicos', 'estado' => 'autorizadas'], []],
        ] as [$filters, $ids]) {
            $this->screen($filters)->assertOk()->assertViewHas('quotations', fn ($rows) => $rows->modelKeys() === $ids);
        }
    }

    public function test_received_quotes_exclude_own_drafts_and_authorized_quotes_and_preserve_hospital_scope(): void
    {
        RequestQuotation::whereIn('id', [1, 2, 3, 4])->update(['created_by' => 2]);
        $this->screen(['estado' => 'recibidas'])->assertOk()->assertViewHas('statusFilter', 'recibidas')
            ->assertViewHas('quotations', fn ($rows) => $rows->modelKeys() === [5, 2]);
        $this->screen(['estado' => 'recibidas', 'tipo' => 'nutricionales'])->assertOk()
            ->assertViewHas('quotations', fn ($rows) => $rows->isEmpty());
        $this->screen(['estado' => 'recibidas', 'hospital_id' => 1, 'buscar' => 'COT-000002'])->assertOk()
            ->assertViewHas('quotations', fn ($rows) => $rows->modelKeys() === [2]);
        foreach (['Cliente', 'Institucion'] as $role) {
            $this->user->syncRoles(Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']));
            $this->screen(['estado' => 'recibidas'])->assertOk()
                ->assertViewHas('quotations', fn ($rows) => $rows->modelKeys() === [2]);
            $this->screen(['estado' => 'recibidas', 'hospital_id' => 2])->assertOk()
                ->assertViewHas('quotations', fn ($rows) => $rows->isEmpty());
        }
    }

    public function test_institution_hospital_dates_and_search_are_applied_together(): void
    {
        $filters = ['institucion_id' => 1, 'hospital_id' => 1, 'desde' => '2026-09-21', 'hasta' => '2026-09-21'];
        $this->screen($filters)->assertOk()->assertViewHas('quotations', fn ($rows) => $rows->modelKeys() === [3, 2]);
        $this->screen($filters + ['buscar' => 'COT-000003'])->assertOk()
            ->assertViewHas('quotations', fn ($rows) => $rows->modelKeys() === [3]);
        $this->screen($filters + ['buscar' => 'prueba 2'])->assertOk()
            ->assertViewHas('quotations', fn ($rows) => $rows->modelKeys() === [2]);
        $this->screen(['institucion_id' => 1, 'hospital_id' => 2])->assertOk()
            ->assertViewHas('quotations', fn ($rows) => $rows->isEmpty());
    }

    public function test_carousel_and_status_links_preserve_other_filters(): void
    {
        $filters = ['tipo' => 'oncologicos', 'estado' => 'enviadas', 'institucion_id' => 1,
            'hospital_id' => 1, 'buscar' => 'prueba', 'desde' => '2026-09-20', 'hasta' => '2026-09-22'];
        $html = $this->screen($filters)->assertOk()->getContent();
        $xpath = $this->xpath($html);
        foreach ($xpath->query('//nav[@aria-label="Tipo de solicitudes"]//a | //nav[@aria-label="Estado de las cotizaciones"]//a') as $link) {
            parse_str(parse_url($link->getAttribute('href'), PHP_URL_QUERY), $query);
            $this->assertStringStartsWith(route('admin.solicitudes.cotizacion.index'), $link->getAttribute('href'));
            foreach (['institucion_id', 'hospital_id', 'buscar', 'desde', 'hasta'] as $key) {
                $this->assertSame((string) $filters[$key], $query[$key]);
            }
        }
    }

    public function test_hospital_accounts_never_see_other_hospital_quotes_or_filter_options(): void
    {
        foreach (['Cliente', 'Institucion'] as $role) {
            $this->user->syncRoles(Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']));
            $this->screen()->assertOk()->assertViewHas('quotations', fn ($rows) => $rows->modelKeys() === [4, 3, 2, 1])
                ->assertDontSee('Paciente ajeno')->assertDontSee('Hospital ajeno')->assertDontSee('Otra institucion');
            $this->screen(['hospital_id' => 2])->assertOk()->assertViewHas('quotations', fn ($rows) => $rows->isEmpty());
        }
        $this->user->update(['hospital_id' => null]);
        $this->screen()->assertOk()->assertViewHas('quotations', fn ($rows) => $rows->isEmpty())
            ->assertViewHas('hospitals', fn ($rows) => $rows->isEmpty());
    }

    public function test_unauthorized_categories_cannot_be_requested_through_query_parameters(): void
    {
        $this->user->syncPermissions(['nutricionales_solicitudes_index']);
        $this->screen()->assertOk()->assertViewHas('quotations', fn ($rows) => $rows->modelKeys() === [3]);
        $this->screen(['tipo' => 'oncologicos'])->assertForbidden();
        $this->screen(['tipo' => 'antibioticos'])->assertForbidden();
        $this->user->syncPermissions([]);
        $this->screen()->assertForbidden();
    }

    public function test_sort_invalid_filters_and_empty_state(): void
    {
        $this->screen(['orden' => 'total', 'direccion' => 'asc'])->assertOk()
            ->assertViewHas('quotations', fn ($rows) => $rows->modelKeys() === [3, 1, 5, 2, 4]);
        $this->screen(['tipo' => ['oncologicos'], 'estado' => ['autorizadas'], 'orden' => ['total']])->assertOk()
            ->assertViewHas('selectedType', 'todas')->assertViewHas('statusFilter', 'todas');
        $this->screen(['desde' => '2026-09-22', 'hasta' => '2026-09-20'])->assertSessionHasErrors('hasta');
        $this->screen(['desde' => '2026-99-99'])->assertSessionHasErrors('desde');
        $this->screen(['buscar' => ['test']])->assertSessionHasErrors('buscar');
        $this->screen(['institucion_id' => "1 OR 1=1"])->assertSessionHasErrors('institucion_id');
        RequestQuotation::query()->delete();
        $this->screen()->assertOk()->assertSee('No hay cotizaciones para los filtros seleccionados.')
            ->assertViewHas('quotations', fn ($rows) => $rows->isEmpty());
    }

    public function test_user_supplied_text_is_escaped_in_table_and_detail(): void
    {
        RequestQuotation::findOrFail(1)->update(['patient_name' => '<img src=x onerror=alert(1)>']);
        $html = $this->screen()->assertOk()->getContent();
        $this->assertStringNotContainsString('<img src=x onerror=alert(1)>', $html);
        $this->assertStringContainsString('&lt;img src=x onerror=alert(1)&gt;', $html);
    }

    public function test_prodifem_staff_authorizes_sent_quotes_and_records_the_actor_once(): void
    {
        $this->user->givePermissionTo(Permission::create(['name' => 'oncologicos_solicitudes_update', 'guard_name' => 'web']));
        $url = route('admin.solicitudes.cotizacion.authorize', 2);
        $this->screen()->assertSee($url, false);
        $this->from(route('admin.solicitudes.cotizacion.index'))->post($url)->assertRedirect()
            ->assertSessionHas('quotation_status');
        $quote = RequestQuotation::findOrFail(2);
        $this->assertSame('autorizada', $quote->status);
        $this->assertSame($this->user->id, $quote->authorized_by);
        $time = $quote->authorized_at->toDateTimeString();
        $this->post($url)->assertRedirect();
        $this->assertSame($time, $quote->fresh()->authorized_at->toDateTimeString());
        $this->screen(['estado' => 'autorizadas'])->assertViewHas('quotations', fn ($rows) => $rows->modelKeys() === [3, 2]);
    }

    public function test_hospitals_cannot_authorize_even_with_legacy_update_permission(): void
    {
        $this->user->givePermissionTo(Permission::create(['name' => 'oncologicos_solicitudes_update', 'guard_name' => 'web']));
        foreach (['Cliente', 'Institucion'] as $role) {
            $this->user->syncRoles(Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']));
            $url = route('admin.solicitudes.cotizacion.authorize', 2);
            $this->screen()->assertDontSee($url, false);
            $this->post($url)->assertForbidden();
        }
        $this->assertSame('enviada', RequestQuotation::findOrFail(2)->status);
    }

    public function test_read_only_staff_cannot_authorize_and_authorization_requires_a_sent_quote(): void
    {
        $this->post(route('admin.solicitudes.cotizacion.authorize', 2))->assertForbidden();
        $this->user->givePermissionTo(Permission::create(['name' => 'oncologicos_solicitudes_update', 'guard_name' => 'web']));
        foreach ([1, 4] as $id) {
            $this->post(route('admin.solicitudes.cotizacion.authorize', $id))->assertSessionHasErrors('quotation');
        }
        RequestQuotation::findOrFail(2)->update(['total' => null]);
        $this->post(route('admin.solicitudes.cotizacion.authorize', 2))->assertSessionHasErrors('quotation');
        $this->assertSame('enviada', RequestQuotation::findOrFail(2)->status);
        $this->post(route('admin.solicitudes.cotizacion.authorize', 3))->assertForbidden();
    }

    private function xpath(string $html): \DOMXPath
    {
        $dom = new \DOMDocument;
        @$dom->loadHTML('<?xml encoding="UTF-8">'.$html);
        return new \DOMXPath($dom);
    }
}
