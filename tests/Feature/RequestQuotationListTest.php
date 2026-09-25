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
                'Total MXN', 'Estado', 'Detalle', 'Enviar', 'Autorizaci&oacute;n', 'Solicitud (Foto o Archivo)', 'Enviar a preparacion'], false);
        $xpath = $this->xpath($response->getContent());
        $this->assertCount(15, $xpath->query('//table[@id="request-quotations-table"]//th'));
        $this->assertSame('Tipo', trim($xpath->query('//table[@id="request-quotations-table"]//th')->item(0)->textContent));
        $this->assertSame(['Antibiotico', 'Nutricional', 'Oncologica', 'Oncologica', 'Oncologica'], array_map(
            fn ($cell) => trim($cell->textContent), iterator_to_array($xpath->query('//tr[@data-quotation-row]/td[1]'))));
        foreach ($xpath->query('//tr[@data-quotation-row]') as $row) {
            $this->assertCount(15, $xpath->query('./td', $row));
        }
        $this->assertCount(5, $xpath->query('//table[@id="request-quotations-table"]//button[@data-quotation-send]'));
        $this->assertSame(['Todas', 'Recibidas', 'Enviadas', 'Autorizadas', 'En preparacion'], array_map(
            fn ($link) => trim($link->textContent), iterator_to_array($xpath->query('//nav[@aria-label="Estado de las cotizaciones"]/a'))));
        $this->assertCount(4, $xpath->query('//nav[@aria-label="Tipo de solicitudes"]//a'));
        $this->assertCount(1, $xpath->query('//nav[@aria-label="Tipo de solicitudes"]//a[@aria-current="page"]'));
        $this->assertCount(1, $xpath->query('//nav[@aria-label="Estado de las cotizaciones"]//a[@aria-current="page"]'));
    }

    public function test_row_states_and_actions_use_consistent_pills_without_changing_authorization_permissions(): void
    {
        $xpath = $this->xpath($this->screen()->assertOk()->getContent());
        $row = fn (int $id) => '//tr[@data-quotation-row][td[2]="COT-'.str_pad((string) $id, 6, '0', STR_PAD_LEFT).'"]';
        foreach ([1 => ['Por enviar', 'pending'], 2 => ['Enviada', 'success'],
            3 => ['Autorizada', 'success'], 4 => ['En preparacion', 'primary']] as $id => [$label, $tone]) {
            $state = $xpath->query($row($id).'/td[10]/span')->item(0);
            $this->assertSame($label, trim($state->textContent));
            $this->assertStringContainsString('quotation-row-pill--'.$tone, $state->getAttribute('class'));
        }
        foreach ([1, 2, 5] as $id) {
            $pending = $xpath->query($row($id).'/td[13]/button[@disabled]')->item(0);
            $this->assertNotNull($pending);
            $this->assertSame('Pendiente', trim($pending->textContent));
            $this->assertStringContainsString('quotation-row-pill--pending', $pending->getAttribute('class'));
        }
        foreach ([3, 4] as $id) {
            $authorized = $xpath->query($row($id).'/td[13]/span')->item(0);
            $this->assertSame('Autorizado', trim($authorized->textContent));
            $this->assertStringContainsString('quotation-row-pill--success', $authorized->getAttribute('class'));
            $this->assertStringContainsString(RequestQuotation::findOrFail($id)->authorized_at->format('d/m/Y H:i'), $authorized->getAttribute('title'));
        }
        foreach ($xpath->query('//tr[@data-quotation-row]//button | //tr[@data-quotation-row]//a') as $control) {
            $this->assertStringContainsString('quotation-row-pill', $control->getAttribute('class'));
        }

        $this->user->givePermissionTo(Permission::create(['name' => 'oncologicos_solicitudes_update', 'guard_name' => 'web']));
        $xpath = $this->xpath($this->screen()->assertOk()->getContent());
        $form = $xpath->query($row(2).'/td[13]/form')->item(0);
        $this->assertSame(route('admin.solicitudes.cotizacion.authorize', 2), $form->getAttribute('action'));
        $this->assertFalse($form->hasAttribute('onsubmit'));
        $this->assertSame('POST', $form->getAttribute('method'));
        $this->assertCount(1, $xpath->query('./input[@name="_token"]', $form));
        $this->assertSame(['folio' => 'COT-000002', 'date' => '21/09/2026', 'hospital' => 'Hospital de prueba',
            'amount' => '$210.00', 'can_authorize' => true], json_decode($form->getAttribute('data-quotation-authorization'), true));
        $this->assertCount(1, $xpath->query('//dialog[@data-quotation-authorize-dialog]'));
        $button = $xpath->query('./button', $form)->item(0);
        $this->assertSame('Pendiente', trim($button->textContent));
        $this->assertSame('button', $button->getAttribute('type'));
        $this->assertTrue($button->hasAttribute('data-quotation-authorize'));
        $this->assertFalse($button->hasAttribute('disabled'));
        $this->assertCount(1, $xpath->query($row(1).'/td[13]/button[@disabled]'));
    }

    public function test_client_and_institution_tables_omit_internal_columns_and_preserve_row_alignment(): void
    {
        foreach (['Cliente', 'Institucion'] as $role) {
            $this->user->syncRoles(Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']));
            foreach ([[], ['estado' => 'enviadas'], ['buscar' => 'NO-EXISTE']] as $filters) {
                $response = $this->screen($filters)->assertOk()->assertViewHas('isHospitalView', true);
                $xpath = $this->xpath($response->getContent());
                $this->assertSame(['Tipo', 'Folio', 'Fecha', 'Paciente', 'Total MXN', 'Detalle', 'Enviar',
                    'Autorización', 'Solicitud (Foto o Archivo)', 'Enviar a preparacion'], array_map(
                        fn ($header) => trim($header->textContent), iterator_to_array($xpath->query('//table[@id="request-quotations-table"]//th'))));
                foreach ($xpath->query('//tr[@data-quotation-row]') as $row) {
                    $this->assertCount(10, $xpath->query('./td', $row));
                    $this->assertStringStartsWith('Paciente de prueba ', trim($xpath->query('./td[4]', $row)->item(0)->textContent));
                    $this->assertStringStartsWith('$', trim($xpath->query('./td[5]', $row)->item(0)->textContent));
                    $this->assertCount(1, $xpath->query('./td[7]/button[@data-quotation-send]', $row));
                    $this->assertCount(1, $xpath->query('./td[9]/button[@data-quotation-documents]', $row));
                    $this->assertCount(1, $xpath->query('./td[6]//button[starts-with(@aria-label,"Ver COT-")]', $row));
                    $this->assertStringNotContainsString('Lista del hospital', $row->textContent);
                    $this->assertStringNotContainsString('Sin asignar', $row->textContent);
                }
            }
        }
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

    public function test_hospital_status_tabs_omit_received_and_sent_and_normalize_old_links(): void
    {
        foreach (['Cliente', 'Institucion'] as $role) {
            $this->user->syncRoles(Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']));
            foreach (['todas', 'recibidas', 'enviadas'] as $status) {
                $response = $this->screen(['estado' => $status])->assertOk()
                    ->assertViewHas('statusFilter', 'todas')
                    ->assertViewHas('quotations', fn ($rows) => $rows->modelKeys() === [4, 3, 2, 1]);
                $xpath = $this->xpath($response->getContent());
                $this->assertSame(['Todas', 'Autorizadas', 'En preparacion'], array_map(
                    fn ($link) => trim($link->textContent), iterator_to_array($xpath->query('//nav[@aria-label="Estado de las cotizaciones"]/a'))));
                $this->assertSame('Todas', trim($xpath->query('//nav[@aria-label="Estado de las cotizaciones"]/a[@aria-current="page"]')->item(0)->textContent));
                $this->assertSame('todas', $xpath->query('//input[@name="estado"]')->item(0)->getAttribute('value'));
                $this->assertStringNotContainsString('estado=recibidas', $response->getContent());
                $this->assertStringNotContainsString('estado=enviadas', $response->getContent());
            }
            foreach (['autorizadas' => [3], 'preparacion' => [4]] as $status => $ids) {
                $this->screen(['estado' => $status])->assertOk()->assertViewHas('statusFilter', $status)
                    ->assertViewHas('quotations', fn ($rows) => $rows->modelKeys() === $ids);
            }
            $this->screen(['estado' => 'enviadas', 'tipo' => 'oncologicos', 'buscar' => 'COT-000002'])
                ->assertOk()->assertViewHas('statusFilter', 'todas')
                ->assertViewHas('quotations', fn ($rows) => $rows->modelKeys() === [2]);
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
                ->assertViewHas('statusFilter', 'todas')
                ->assertViewHas('quotations', fn ($rows) => $rows->modelKeys() === [4, 3, 2, 1]);
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

    public function test_authorization_dialog_escapes_hospital_names_and_disallows_missing_amounts(): void
    {
        $this->user->givePermissionTo(Permission::findOrCreate('oncologicos_solicitudes_update', 'web'));
        $quote = RequestQuotation::findOrFail(2);
        $name = 'Hospital <img src=x onerror=alert(1)> & sucursal';
        $quote->hospital->update(['name' => $name]);
        foreach ([null, -1] as $total) {
            $quote->update(['total' => $total]);
            $response = $this->screen()->assertOk()->assertDontSee('<img src=x onerror=alert(1)>', false);
            $xpath = $this->xpath($response->getContent());
            $form = $xpath->query('//form[@data-quotation-authorization][@action="'.route('admin.solicitudes.cotizacion.authorize', $quote).'"]')->item(0);
            $data = json_decode($form->getAttribute('data-quotation-authorization'), true);
            $this->assertSame($name, $data['hospital']);
            $this->assertFalse($data['can_authorize']);
            $this->assertSame('enviada', $quote->refresh()->status);
        }
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
