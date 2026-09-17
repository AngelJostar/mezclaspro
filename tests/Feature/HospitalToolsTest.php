<?php

namespace Tests\Feature;

use App\Models\InstitutionBilling;
use App\Models\InstitutionBillingMovement;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\Fixtures\HospitalToolsData;
use Tests\Fixtures\UnifiedRequestExportData;
use Tests\TestCase;

class HospitalToolsTest extends TestCase
{
    private $hospital;

    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        $this->hospital = UnifiedRequestExportData::seed();
        HospitalToolsData::addBilling();
        foreach (['users' => 'is_active', 'hospitals' => 'access_is_active', 'clientes' => 'is_active'] as $table => $column) {
            Schema::table($table, fn (Blueprint $schema) => $schema->boolean($column)->default(true));
        }
        (require database_path('migrations/2024_05_21_124030_create_notifications_table.php'))->up();
        $this->actingAs($this->hospital->refresh());
    }

    private function page(array $query = [])
    {
        return $this->get(route('admin.hospital.herramientas', array_merge(['desde' => '2026-09-01', 'hasta' => '2026-09-30'], $query)));
    }

    private function toggle(string $kind, int $id, array $data = [])
    {
        return $this->patchJson(route('admin.hospital.conciliable', ['kind' => $kind, 'target' => $id]), array_merge(['conciliable' => false, 'previous' => 'Si'], $data));
    }

    public function test_conciliation_quick_filters_match_yes_no_and_are_preserved_in_downloads(): void
    {
        DB::table('institution_billings')->where('origen_tipo', 'oncologica_mezcla')->where('origen_id', 1)->update(['conciliable' => 'No']);
        foreach (['todas' => 4, 'conciliables' => 3, 'no_conciliables' => 1] as $state => $count) {
            $response = $this->page(['conciliacion_estado' => $state])->assertOk()->assertViewHas('rows', fn ($r) => $r->total() === $count);
            $dom = new \DOMDocument;
            @$dom->loadHTML('<?xml encoding="UTF-8">'.$response->getContent());
            $xpath = new \DOMXPath($dom);
            $buttons = $xpath->query('//nav[@aria-label="Filtros de conciliación"]/a');
            $this->assertSame(['Todas', 'Conciliables', 'No conciliables'], array_map(fn ($node) => trim($node->textContent), iterator_to_array($buttons)));
            $this->assertCount(1, $xpath->query('//nav[@aria-label="Filtros de conciliación"]/following-sibling::form[@id="tools-filters"]'));
            $this->assertSame($state, $response->viewData('filterQuery')['conciliacion_estado']);
            $this->assertCount(1, $xpath->query('//input[@name="conciliacion_estado" and @value="'.$state.'"]'));
        }
        $this->page(['conciliacion_estado' => 'no_conciliables', 'columnas' => ['type' => ['Nutricional']]])->assertViewHas('rows', fn ($r) => $r->total() === 0);
        $this->page(['conciliacion_estado' => 'invalid'])->assertSessionHasErrors('conciliacion_estado');
        \Maatwebsite\Excel\Facades\Excel::fake();
        $this->get(route('admin.hospital.conciliacion.exportar', ['desde' => '', 'hasta' => '', 'conciliacion_estado' => 'no_conciliables']))->assertOk();
        \Maatwebsite\Excel\Facades\Excel::assertDownloaded('conciliacion_del_periodo.xlsx', fn ($export) => $export->collection()->count() === 1 && $export->collection()->first()['id'] === 1);
        $response = $this->page(['tab' => 'ajustes'])->assertOk();
        $response->assertDontSee('aria-label="Estado del ajuste"', false)->assertDontSee('class="ht-quick-filters"', false);
    }

    public function test_all_tabs_are_scoped_and_history_includes_past_versions(): void
    {
        $this->page()->assertOk()->assertSee('Conciliación')->assertDontSee('Hospital ajeno')
            ->assertViewHas('rows', fn ($rows) => $rows->total() === 4 && collect($rows->items())->pluck('kind')->unique()->count() === 3);
        $this->page(['tab' => 'facturacion'])->assertOk()->assertDontSee('FACTURA-AJENA')
            ->assertViewHas('rows', fn ($rows) => $rows->total() === 2);
        $this->page(['tab' => 'ajustes'])->assertOk()->assertSee('Ajuste Solicitado')->assertDontSee('AJUSTE AJENO')
            ->assertViewHas('rows', fn ($rows) => $rows->total() === 1 && $rows[0]['version']->id === 2);
        $this->get(route('admin.hospital.ajustes.historial', ['kind' => 'oncologicos', 'target' => 1]))
            ->assertOk()->assertSee('Aprobada con Ajuste')->assertSee('Ajuste Solicitado')
            ->assertViewHas('versions', fn ($versions) => $versions->count() === 2);
    }

    public function test_filters_support_day_month_year_open_ranges_and_validation(): void
    {
        $this->page(['desde' => '2026-09-08', 'hasta' => '2026-09-08'])->assertOk()->assertViewHas('rows', fn ($r) => $r->total() === 4);
        $this->page(['desde' => '2026-09-09'])->assertOk()->assertViewHas('rows', fn ($r) => $r->total() === 0);
        foreach (['mes', 'anio'] as $period) {
            $this->page(['periodo' => $period, 'desde' => '2026-09-15', 'hasta' => '2026-09-15'])->assertOk()->assertViewHas('rows', fn ($r) => $r->total() === 4);
        }
        $this->page(['desde' => '', 'hasta' => ''])->assertOk()->assertViewHas('rows', fn ($r) => $r->total() === 4);
        $this->page(['tab' => 'ajustes', 'todo_historial' => 0, 'hasta' => '2026-09-08'])->assertOk()->assertViewHas('rows', fn ($r) => $r->total() === 0);
        $this->page(['desde' => '2026-10-01'])->assertSessionHasErrors('hasta');
        $this->page(['desde' => 'invalid'])->assertSessionHasErrors('desde');
        $this->page(['tab' => 'internal'])->assertSessionHasErrors('tab');
    }

    public function test_conciliation_columns_match_request_data_and_approval_is_read_only(): void
    {
        DB::table('mezclas')->where('id', 1)->update(['fecha_entrega' => null]);
        $response = $this->page()->assertOk()->assertSee('data-server-column-filters', false);
        $rows = collect($response->viewData('rows')->items());
        $onco = $rows->firstWhere('id', 1)['cells'];
        $this->assertSame('Institucion de prueba', $onco['institution']);
        $this->assertSame('2026-09-08 10:00', $onco['date']);
        $this->assertSame('2026-09-09 15:00', $onco['delivery_date']);
        $this->assertSame('LOTE-1', $onco['lot']);
        $this->assertSame('Aprobada', $onco['approval']);
        $nutrition = $rows->firstWhere('kind', 'nutricionales')['cells'];
        $this->assertSame('00042', $nutrition['lot']);
        $this->assertSame('2026-09-09 15:00', $nutrition['delivery_date']);
        $this->assertSame('Paciente 1 Nutricion', $nutrition['patient']);
        $this->assertCount(12, $response->viewData('conciliation')['options']);
        $response->assertSee('disabled class="ht-approval ht-approval-approved"', false)
            ->assertDontSee('Aprobar mezcla')->assertDontSee('Aprobada con Ajuste');

        DB::table('mezclas')->where('id', 1)->update(['adjustment_id' => 2]);
        DB::table('mezclas')->where('id', 2)->update(['estado' => 'cancelada']);
        $rows = collect($this->page()->assertOk()->viewData('rows')->items());
        $this->assertSame('Pendiente', $rows->firstWhere('id', 1)['cells']['approval']);
        $this->assertSame('En ajuste', $rows->firstWhere('id', 1)['cells']['status']);
        $this->assertSame('Rechazada', $rows->firstWhere('id', 2)['cells']['approval']);
    }

    public function test_column_filters_and_numeric_order_apply_before_pagination_and_preserve_scope(): void
    {
        for ($id = 20; $id < 40; $id++) DB::table('mezclas')->insert(['id' => $id, 'solicitud_id' => 1, 'estado' => 'aprobada', 'lote' => 'LOTE-'.$id]);
        $query = ['orden' => 'id', 'direccion' => 'desc', 'columnas' => ['type' => ['Oncologica'], 'approval' => ['Aprobada']]];
        $response = $this->page($query)->assertOk();
        $rows = $response->viewData('rows');
        $this->assertSame(22, $rows->total());
        $this->assertSame(range(39, 25), collect($rows->items())->pluck('id')->all());
        parse_str(parse_url($rows->nextPageUrl(), PHP_URL_QUERY), $next);
        $this->assertSame($query['columnas'], $next['columnas']);
        $this->assertSame('desc', $next['direccion']);
        $this->page($query + ['page' => 2])->assertOk()->assertViewHas('rows', fn ($r) => collect($r->items())->pluck('id')->all() === [24, 23, 22, 21, 20, 4, 1]);
        $this->page(['columnas' => ['lot' => ['LOTE-39']]])->assertOk()->assertViewHas('rows', fn ($r) => $r->total() === 1 && $r[0]['id'] === 39);
        $this->page(['columnas' => ['lot' => ['LOTE-3']]])->assertOk()->assertViewHas('rows', fn ($r) => $r->total() === 0);
        $this->assertNotContains('LOTE-3', $response->viewData('conciliation')['options']['lot']);
        $this->assertContains('Nutricional', $response->viewData('conciliation')['options']['type']);
        $this->assertContains('LOTE-39', $response->viewData('conciliation')['options']['lot']);
    }

    public function test_empty_filters_validation_and_missing_institutions_remain_usable(): void
    {
        $this->page(['columnas' => ['type' => ['']]])->assertOk()
            ->assertViewHas('rows', fn ($r) => $r->total() === 0)
            ->assertSee('data-force-column-filter', false)->assertSee('colspan="13"', false);
        $this->page(['orden' => 'view'])->assertSessionHasErrors('orden');
        $this->page(['direccion' => 'other'])->assertSessionHasErrors('direccion');
        $this->page(['columnas' => ['internal' => ['test']]])->assertSessionHasErrors('columnas');
        $this->page(['columnas' => ['patient' => 'invalid']])->assertSessionHasErrors('columnas.patient');
        DB::table('cliente_hospital')->delete();
        $this->page(['columnas' => ['institution' => ['Sin institución']]])->assertOk()->assertViewHas('rows', fn ($r) => $r->total() === 4);
        DB::table('clientes')->insert(['id' => 2, 'nombre' => 'Otra institucion']);
        DB::table('cliente_hospital')->insert([['hospital_id' => 1, 'cliente_id' => 1], ['hospital_id' => 1, 'cliente_id' => 2]]);
        $this->page()->assertOk()->assertViewHas('rows', fn ($r) => collect($r->items())->every(fn ($row) => $row['cells']['institution'] === 'Institucion de prueba, Otra institucion'));
    }

    public function test_toggle_updates_only_conciliable_and_records_author_without_changing_mixture(): void
    {
        $before = DB::table('mezclas')->where('id', 1)->first();
        $this->toggle('oncologicos', 1, ['precio_total' => '0', 'hospital_id' => 2, 'folio_interno' => 'REPLACED'])->assertOk()->assertJson(['conciliable' => 'No']);
        $billing = InstitutionBilling::where('origen_id', 1)->first();
        $this->assertSame('500.00', $billing->precio_total);
        $this->assertSame('F-1', $billing->folio_interno);
        $this->assertSame(1, (int) $billing->hospital_id);
        $this->assertEquals($before, DB::table('mezclas')->where('id', 1)->first());
        $movement = InstitutionBillingMovement::first();
        $this->assertSame($this->hospital->id, (int) $movement->user_id);
        $this->assertSame(['source' => 'hospital_conciliation', 'before' => 'Si', 'after' => 'No'], $movement->details);
        $this->toggle('oncologicos', 1, ['previous' => 'No'])->assertOk();
        $this->assertSame(1, InstitutionBillingMovement::count());
    }

    public function test_conciliable_defaults_to_yes_for_missing_null_and_blank_values_without_writes(): void
    {
        InstitutionBilling::where('origen_id', 1)->update(['conciliable' => '']);
        InstitutionBilling::where('origen_id', 11)->update(['conciliable' => null]);
        $before = InstitutionBilling::orderBy('id')->get()->toArray();
        $response = $this->page()->assertOk();
        $this->assertTrue(collect($response->viewData('rows')->items())->every(fn ($row) => $row['conciliable'] && $row['cells']['conciliable'] === 'Sí'));
        $this->assertSame(['Sí'], $response->viewData('conciliation')['options']['conciliable']);
        $this->page(['columnas' => ['conciliable' => ['Sí']]])->assertOk()->assertViewHas('rows', fn ($r) => $r->total() === 4);
        $this->page(['columnas' => ['conciliable' => ['No']]])->assertOk()->assertViewHas('rows', fn ($r) => $r->total() === 0);
        $this->assertSame($before, InstitutionBilling::orderBy('id')->get()->toArray());
        $this->assertSame(0, InstitutionBillingMovement::count());
    }

    public function test_hospital_can_change_default_yes_to_no_and_reload_preserves_its_choice(): void
    {
        InstitutionBilling::where('origen_id', 1)->update(['conciliable' => '']);
        InstitutionBilling::where('origen_id', 11)->update(['conciliable' => null]);
        foreach ([['oncologicos', 1], ['antibioticos', 2], ['nutricionales', 11]] as [$kind, $id]) {
            $this->toggle($kind, $id, ['previous' => null])->assertOk()->assertJson(['conciliable' => 'No']);
            $this->page()->assertOk()->assertViewHas('rows', fn ($r) => collect($r->items())->contains(fn ($row) => $row['kind'] === $kind && $row['id'] === $id && ! $row['conciliable'] && $row['cells']['conciliable'] === 'No'));
            $this->toggle($kind, $id, ['previous' => null])->assertStatus(409);
        }
        $this->assertSame(3, InstitutionBillingMovement::count());
        $this->page(['columnas' => ['conciliable' => ['No']]])->assertOk()->assertViewHas('rows', fn ($r) => $r->total() === 3);
        $this->page(['columnas' => ['conciliable' => ['Sí']]])->assertOk()->assertViewHas('rows', fn ($r) => $r->total() === 1 && $r[0]['id'] === 4);
        $this->toggle('antibioticos', 2, ['previous' => 'No', 'conciliable' => true])->assertOk()->assertJson(['conciliable' => 'Si']);
        $this->page(['columnas' => ['conciliable' => ['Sí']]])->assertOk()->assertViewHas('rows', fn ($r) => $r->total() === 2);
    }

    public function test_missing_billing_is_created_only_for_the_hospitals_institution(): void
    {
        $this->toggle('antibioticos', 2, ['previous' => null, 'conciliable' => true])->assertOk();
        $this->assertDatabaseHas('institution_billings', ['origen_tipo' => 'oncologica_mezcla', 'origen_id' => 2, 'hospital_id' => 1, 'institucion_id' => 1, 'conciliable' => 'Si', 'precio_total' => null]);
        $this->toggle('nutricionales', 11)->assertOk();
        $this->assertDatabaseHas('institution_billings', ['origen_tipo' => 'nutricional_solicitud', 'origen_id' => 11, 'conciliable' => 'No']);
    }

    public function test_cross_hospital_wrong_kind_stale_and_invalid_updates_are_rejected(): void
    {
        $this->toggle('oncologicos', 3)->assertNotFound();
        $this->toggle('nutricionales', 12)->assertNotFound();
        $this->toggle('antibioticos', 1)->assertNotFound();
        $this->toggle('oncologicos', 1, ['previous' => 'No'])->assertStatus(409);
        $this->toggle('oncologicos', 1, ['conciliable' => 'arbitrary'])->assertUnprocessable();
        $this->assertSame(0, InstitutionBillingMovement::count());
    }

    public function test_missing_or_ambiguous_institution_cannot_create_billing(): void
    {
        DB::table('cliente_hospital')->delete();
        $this->toggle('antibioticos', 2, ['previous' => null])->assertUnprocessable();
        DB::table('clientes')->insert(['id' => 2, 'nombre' => 'Otra institucion']);
        DB::table('cliente_hospital')->insert([['hospital_id' => 1, 'cliente_id' => 1], ['hospital_id' => 1, 'cliente_id' => 2]]);
        $this->toggle('antibioticos', 2, ['previous' => null])->assertUnprocessable();
        $this->assertSame(0, InstitutionBillingMovement::count());
    }

    public function test_permissions_and_pagination_are_preserved(): void
    {
        for ($id = 20; $id < 37; $id++) DB::table('mezclas')->insert(['id' => $id, 'solicitud_id' => 1, 'estado' => 'aprobada']);
        $this->page(['page' => 2])->assertOk()->assertViewHas('rows', fn ($r) => $r->total() === 21 && $r->count() === 6 && str_contains($r->previousPageUrl(), 'desde=2026-09-01'));
        $this->hospital->revokePermissionTo('oncologicos_solicitudes_index');
        $this->page()->assertOk()->assertViewHas('rows', fn ($r) => $r->total() === 1);
        $this->toggle('oncologicos', 1)->assertForbidden();
        $this->hospital->revokePermissionTo('nutricionales_solicitudes_index');
        $this->page()->assertForbidden();
        $this->hospital->syncRoles('Super Admin');
        $this->toggle('oncologicos', 1)->assertForbidden();
    }

    public function test_adjustment_filters_use_latest_versions_and_keep_delivered_history(): void
    {
        HospitalToolsData::addLogCases();
        $this->page(['tab' => 'ajustes'])->assertOk()->assertViewHas('allHistory', true)
            ->assertViewHas('rows', fn ($rows) => $rows->total() === 7 && collect($rows->items())->contains(fn ($r) => $r['id'] === 4 && $r['status'] === 'entregada'));
        foreach (['requested' => 1, 'authorized' => 1, 'approved' => 2, 'rejected' => 2] as $status => $count) {
            $this->page(['tab' => 'ajustes', 'ajuste_estado' => $status])->assertOk()->assertViewHas('rows', fn ($r) => $r->total() === $count);
        }
        $this->page(['tab' => 'ajustes', 'todo_historial' => 0])->assertOk()->assertViewHas('rows', fn ($r) => $r->total() === 6);
        $this->page(['tab' => 'ajustes', 'ajuste_estado' => 'invalido'])->assertSessionHasErrors('ajuste_estado');
        $this->page(['tab' => 'ajustes', 'todo_historial' => 'invalido'])->assertSessionHasErrors('todo_historial');
    }

    public function test_adjustment_log_has_request_columns_messages_and_a_filtered_export(): void
    {
        HospitalToolsData::addLogCases();
        $response = $this->page(['tab' => 'ajustes'])->assertOk()->assertSee('Descargar reporte')->assertSee('Mensajería')
            ->assertSee('data-mixture-message-key="oncologicos:1"', false)
            ->assertSee('data-mixture-message-key="antibioticos:2"', false)
            ->assertSee('data-mixture-message-key="nutricionales:11"', false)
            ->assertDontSee('data-mixture-message-key="oncologicos:3"', false)
            ->assertDontSee('data-mixture-message-key="nutricionales:12"', false);
        $row = collect($response->viewData('rows')->items())->firstWhere('id', 1);
        $this->assertSame('2026-09-08 10:00', $row['cells']['date']);
        $this->assertSame('2026-09-14 10:00', $row['cells']['adjustment_date']);
        $this->assertSame('2026-09-09 15:00', $row['cells']['delivery_date']);
        $this->assertSame('Institucion de prueba', $row['cells']['institution']);
        $this->assertSame('LOTE-1', $row['cells']['lot']);
        $this->assertSame('Pendiente', $row['cells']['approval']);
        $this->assertCount(14, $response->viewData('adjustmentTable')['options']);
        $filters = ['tab' => 'ajustes', 'orden' => 'id', 'direccion' => 'asc', 'columnas' => ['lot' => ['LOTE-4'], 'approval' => ['Aprobada']]];
        $this->page($filters)->assertOk()->assertViewHas('rows', fn ($r) => $r->total() === 1 && $r[0]['id'] === 4);
        \Maatwebsite\Excel\Facades\Excel::fake();
        $this->get(route('admin.hospital.ajustes.exportar', $filters))->assertOk();
        \Maatwebsite\Excel\Facades\Excel::assertDownloaded('bitacora_de_ajustes.xlsx', function ($export) {
            $this->assertCount(1, $export->collection());
            $this->assertSame(4, $export->collection()->first()['id']);
            $this->assertContains('Institución', $export->headings());
            $this->assertContains('Aprobación', $export->headings());
            $this->assertContains('Fecha y hora programada de entrega', $export->headings());
            return true;
        });
        $this->page(['tab' => 'ajustes', 'columnas' => ['hospital' => ['Hospital ajeno']]])->assertOk()
            ->assertViewHas('rows', fn ($r) => $r->total() === 0)->assertSee('colspan="17"', false);
        $this->page(['tab' => 'ajustes', 'orden' => 'messages'])->assertSessionHasErrors('orden');
    }

    public function test_history_is_read_only_scoped_and_contains_snapshots_and_responses(): void
    {
        HospitalToolsData::addLogCases();
        $url = fn ($kind, $id) => route('admin.hospital.ajustes.historial', ['kind' => $kind, 'target' => $id, 'approval_popup' => 1]);
        $this->get($url('oncologicos', 20))->assertOk()->assertSee('Rechazado por Prodifem')->assertSee('Respuesta del hospital de prueba')
            ->assertSee('Respuesta de Prodifem de prueba')->assertSee('250')->assertSee('300')
            ->assertDontSee('method="POST"', false)->assertDontSee('id="logo-sidebar"', false);
        foreach ([['oncologicos', 3], ['nutricionales', 12], ['antibioticos', 1], ['oncologicos', 999]] as [$kind, $id]) {
            $this->get($url($kind, $id))->assertNotFound();
        }
        $this->hospital->revokePermissionTo('oncologicos_solicitudes_index');
        $this->get($url('oncologicos', 20))->assertForbidden();
        $this->hospital->syncRoles('Super Admin');
        $this->get($url('nutricionales', 11))->assertForbidden();
        $this->get(route('admin.hospital.ajustes.exportar'))->assertForbidden();
    }

    public function test_conciliation_export_uses_all_filtered_rows_in_the_selected_period(): void
    {
        for ($id = 20; $id <= 37; $id++) DB::table('mezclas')->insert(['id' => $id, 'solicitud_id' => 1, 'estado' => 'aprobada']);
        InstitutionBilling::where('origen_id', 1)->update(['conciliable' => 'No']);
        $before = InstitutionBilling::orderBy('id')->get()->toArray();
        $query = ['desde' => '2026-09-08', 'hasta' => '2026-09-08', 'columnas' => ['conciliable' => ['Sí']],
            'orden' => 'id', 'direccion' => 'desc', 'page' => 2];
        $this->page($query)->assertOk()->assertSee('Descargar reporte')->assertSee('formaction="'.route('admin.hospital.conciliacion.exportar').'"', false);
        \Maatwebsite\Excel\Facades\Excel::fake();
        $this->get(route('admin.hospital.conciliacion.exportar', $query))->assertOk();
        \Maatwebsite\Excel\Facades\Excel::assertDownloaded('conciliacion_del_periodo.xlsx', function ($export) {
            $rows = $export->collection();
            $this->assertCount(21, $rows);
            $this->assertSame(37, $rows->first()['id']);
            $this->assertTrue($rows->every(fn ($row) => $row['hospital'] === 'Hospital de prueba' && $row['cells']['conciliable'] === 'Sí'));
            $this->assertFalse($rows->contains(fn ($row) => $row['id'] === 1 || $row['hospital'] === 'Hospital ajeno'));
            $this->assertCount(12, $export->headings());
            $this->assertSame('Conciliable', $export->headings()[11]);
            return true;
        });
        $this->assertSame($before, InstitutionBilling::orderBy('id')->get()->toArray());
        $this->assertSame(0, InstitutionBillingMovement::count());
    }

    public function test_conciliation_export_respects_period_modes_empty_results_and_permissions(): void
    {
        foreach ([['dia', '2026-09-09', 0], ['mes', '2026-09-15', 4], ['anio', '2026-12-31', 4], ['dia', '', 4]] as [$period, $date, $count]) {
            \Maatwebsite\Excel\Facades\Excel::fake();
            $this->get(route('admin.hospital.conciliacion.exportar', ['periodo' => $period, 'desde' => $date, 'hasta' => $date]))->assertOk();
            \Maatwebsite\Excel\Facades\Excel::assertDownloaded('conciliacion_del_periodo.xlsx', fn ($export) => $export->collection()->count() === $count);
        }
        $this->getJson(route('admin.hospital.conciliacion.exportar', ['desde' => '2026-10-01', 'hasta' => '2026-09-01']))->assertUnprocessable();
        $this->hospital->revokePermissionTo('oncologicos_solicitudes_index');
        \Maatwebsite\Excel\Facades\Excel::fake();
        $this->get(route('admin.hospital.conciliacion.exportar', ['desde' => '', 'hasta' => '']))->assertOk();
        \Maatwebsite\Excel\Facades\Excel::assertDownloaded('conciliacion_del_periodo.xlsx', fn ($export) => $export->collection()->count() === 1 && $export->collection()->first()['kind'] === 'nutricionales');
        $this->hospital->revokePermissionTo('nutricionales_solicitudes_index');
        $this->get(route('admin.hospital.conciliacion.exportar'))->assertForbidden();
        $this->hospital->syncRoles('Super Admin');
        $this->get(route('admin.hospital.conciliacion.exportar'))->assertForbidden();
    }

    public function test_conciliation_workbook_preserves_identifiers_and_yes_no_values(): void
    {
        InstitutionBilling::where('origen_id', 1)->update(['conciliable' => 'No']);
        DB::table('solicitud_patients')->where('id', 1)->update(['nombre_paciente' => '=SUM(1,1)']);
        $rows = $this->page(['orden' => 'id', 'direccion' => 'desc'])->assertOk()->viewData('rows');
        $export = new \App\Exports\HospitalConciliationExport(collect($rows->items()));
        $bytes = \Maatwebsite\Excel\Facades\Excel::raw($export, \Maatwebsite\Excel\Excel::XLSX);
        $file = tempnam(sys_get_temp_dir(), 'conciliation-export-');
        try {
            file_put_contents($file, $bytes);
            $sheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file)->getActiveSheet();
            $this->assertSame(5, $sheet->getHighestRow());
            $this->assertSame('Conciliable', $sheet->getCell('L1')->getValue());
            $this->assertSame('00042', $sheet->getCell('I2')->getValue());
            $this->assertSame('s', $sheet->getCell('F2')->getDataType());
            $this->assertSame('=SUM(1,1) Nutricion', $sheet->getCell('F2')->getValue());
            $this->assertSame('Sí', $sheet->getCell('L2')->getValue());
            $this->assertSame('No', $sheet->getCell('L5')->getValue());
            $this->assertSame('A1:L5', $sheet->getAutoFilter()->getRange());
            $this->assertSame('A2', $sheet->getFreezePane());
        } finally { unlink($file); }
    }

    public function test_export_uses_all_filtered_rows_and_excel_treats_user_content_as_text(): void
    {
        HospitalToolsData::addLogCases();
        for ($id = 30; $id < 48; $id++) {
            DB::table('mezclas')->insert(['id' => $id, 'solicitud_id' => 1, 'estado' => 'entregada']);
            \App\Models\MixtureAdjustment::create(['kind' => 'oncologicos', 'target_id' => $id, 'hospital_id' => 1,
                'status' => 'approved', 'description' => '=HYPERLINK("test")', 'proposal' => [], 'review' => [],
                'baseline_hash' => str_repeat('c', 64), 'requested_by' => 1, 'created_at' => '2026-09-10 10:00:00']);
        }
        \Maatwebsite\Excel\Facades\Excel::fake();
        $this->get(route('admin.hospital.ajustes.exportar', ['ajuste_estado' => 'approved', 'page' => 2,
            'columnas' => ['approval' => ['Aprobada']], 'orden' => 'id', 'direccion' => 'desc']))->assertOk();
        \Maatwebsite\Excel\Facades\Excel::assertDownloaded('bitacora_de_ajustes.xlsx', function ($export) {
            $this->assertCount(20, $export->collection());
            $this->assertSame(47, $export->collection()->first()['id']);
            $this->assertTrue($export->collection()->every(fn ($row) => $row['version']->status === 'approved' && $row['hospital'] === 'Hospital de prueba'));
            $sheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $cell = $sheet->getActiveSheet()->getCell('A1');
            $export->bindValue($cell, '=HYPERLINK("test")');
            $this->assertSame('s', $cell->getDataType());
            return true;
        });
    }

    public function test_export_can_generate_an_actual_workbook(): void
    {
        HospitalToolsData::addLogCases();
        $request = \Illuminate\Http\Request::create(route('admin.hospital.herramientas'), 'GET', ['tab' => 'ajustes', 'ajuste_estado' => 'approved']);
        $request->setUserResolver(fn () => $this->hospital);
        $data = app(\App\Http\Controllers\Admin\HospitalToolsController::class)->index($request)->getData();
        $export = new \App\Exports\HospitalAdjustmentLogExport(collect($data['rows']->items()));
        $bytes = \Maatwebsite\Excel\Facades\Excel::raw($export, \Maatwebsite\Excel\Excel::XLSX);
        $file = tempnam(sys_get_temp_dir(), 'adjustment-export-');
        try {
            file_put_contents($file, $bytes);
            $sheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file)->getActiveSheet();
            $this->assertSame(3, $sheet->getHighestRow());
            $this->assertSame('Tipo', $sheet->getCell('A1')->getValue());
            $this->assertSame('Institución', $sheet->getCell('D1')->getValue());
            $this->assertSame('Aprobada con Ajuste', $sheet->getCell('N2')->getValue());
            $this->assertSame('Aprobada', $sheet->getCell('J2')->getValue());
            $this->assertSame('2026-09-08 09:30', $sheet->getCell('G2')->getValue());
            $this->assertSame('00042', $sheet->getCell('I2')->getValue());
            $this->assertSame('A1:O3', $sheet->getAutoFilter()->getRange());
            $this->assertSame('A2', $sheet->getFreezePane());
        } finally { unlink($file); }
    }
}
