<?php

namespace Tests\Feature;

use App\Exports\HospitalConciliationExport;
use App\Models\HospitalConciliationSubmission;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\Fixtures\HospitalToolsData;
use Tests\Fixtures\UnifiedRequestExportData;
use Tests\TestCase;

class HospitalConciliationSubmissionTest extends TestCase
{
    private User $hospital;
    private array $previews = [];

    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:', 'cache.default' => 'array']);
        DB::purge('sqlite');
        $this->hospital = UnifiedRequestExportData::seed();
        HospitalToolsData::addBilling();
        foreach (['users' => 'is_active', 'hospitals' => 'access_is_active', 'clientes' => 'is_active'] as $table => $column) {
            Schema::table($table, fn (Blueprint $schema) => $schema->boolean($column)->default(true));
        }
        (require database_path('migrations/2024_05_21_124030_create_notifications_table.php'))->up();
        (require database_path('migrations/2026_09_15_130000_create_hospital_conciliation_submissions.php'))->up();
        (require database_path('migrations/2026_09_15_150000_add_confirmation_to_hospital_conciliation_submissions.php'))->up();
        $this->mock(\App\Services\InstitutionBillingPricingService::class, function ($mock) {
            $mock->shouldReceive('priceOncoMix')->andReturn(['total_iva_included' => 200.25]);
            $mock->shouldReceive('priceNutritionRequest')->andReturn(['total_iva_included' => 300.50]);
        });
        Schema::create('laboratory_purchase_orders', function (Blueprint $table) { $table->id(); $table->unsignedBigInteger('created_by'); });
        $this->actingAs($this->hospital->refresh());
    }

    private function send(array $data = [])
    {
        $input = array_merge([
            'submission_key' => (string) Str::uuid(), 'tab' => 'conciliacion', 'periodo' => 'dia',
            'desde' => '2026-09-01', 'hasta' => '2026-09-30',
        ], $data);
        $key = $input['submission_key'];
        if (!isset($this->previews[$key])) {
            $preview = $this->getJson(route('admin.hospital.conciliacion.resumen', $input));
            if ($preview->status() !== 200) return $preview;
            $this->previews[$key] = $preview->json();
        }
        $input['confirmation_token'] ??= $this->previews[$key]['confirmation_token'];
        $input['reasons'] ??= collect($this->previews[$key]['summary']['non_conciliable'])->mapWithKeys(fn ($row) => [$row['key'] => 'Revisar registro de prueba'])->all();
        return $this->postJson(route('admin.hospital.conciliacion.enviar'), $input);
    }

    private function internalUser(): User
    {
        $user = User::findOrFail(2);
        Role::findOrCreate('Super Admin', 'web');
        $user->syncRoles(['Super Admin']);
        $user->hospital_id = null;
        $user->save();
        return $user->refresh();
    }

    public function test_submission_contains_every_filtered_page_and_preserves_yes_no_without_billing_changes(): void
    {
        for ($id = 20; $id < 38; $id++) DB::table('mezclas')->insert(['id' => $id, 'solicitud_id' => 1, 'estado' => 'aprobada']);
        DB::table('institution_billings')->where('origen_tipo', 'oncologica_mezcla')->where('origen_id', 1)->update(['conciliable' => 'No']);
        $billing = DB::table('institution_billings')->orderBy('id')->get()->toJson();
        $mixes = DB::table('mezclas')->orderBy('id')->get()->toJson();
        $this->send(['page' => 2, 'orden' => 'id', 'direccion' => 'desc', 'columnas' => ['type' => ['Oncologica']],
            'snapshot' => [['patient' => 'FORGED']], 'hospital_id' => 2])->assertCreated()->assertJsonPath('folio', 'CON-000001');
        $submission = HospitalConciliationSubmission::sole();
        $this->assertSame((int) $this->hospital->hospital_id, $submission->hospital_id);
        $this->assertSame($this->hospital->id, $submission->submitted_by);
        $this->assertSame(20, $submission->mixture_count);
        $this->assertSame(19, $submission->conciliable_count);
        $this->assertSame(37, $submission->snapshot[0]['id']);
        $this->assertSame('No', collect($submission->snapshot)->firstWhere('id', 1)['cells']['conciliable']);
        $this->assertNotContains(3, array_column($submission->snapshot, 'id'));
        $this->assertStringNotContainsString('FORGED', json_encode($submission->snapshot));
        $this->assertSame($billing, DB::table('institution_billings')->orderBy('id')->get()->toJson());
        $this->assertSame($mixes, DB::table('mezclas')->orderBy('id')->get()->toJson());
        $this->assertSame(0, DB::table('institution_billing_movements')->count());
    }

    public function test_submission_retries_are_idempotent_and_cannot_change_scope(): void
    {
        $key = (string) Str::uuid();
        $this->send(['submission_key' => $key])->assertCreated();
        $before = HospitalConciliationSubmission::sole()->snapshot;
        DB::table('institution_billings')->where('origen_id', 1)->update(['conciliable' => 'No']);
        $this->send(['submission_key' => $key])->assertOk()->assertJsonPath('folio', 'CON-000001');
        $this->assertSame($before, HospitalConciliationSubmission::sole()->snapshot);
        $this->send(['submission_key' => $key, 'desde' => ''])->assertConflict();
        $other = User::findOrFail(2);
        $other->syncRoles($this->hospital->roles);
        $other->givePermissionTo($this->hospital->getAllPermissions());
        $this->actingAs($other);
        $this->send(['submission_key' => $key])->assertConflict();
        $this->assertSame(1, HospitalConciliationSubmission::count());
    }

    public function test_submission_honors_the_conciliation_quick_filter(): void
    {
        DB::table('institution_billings')->where('origen_tipo', 'oncologica_mezcla')->where('origen_id', 1)->update(['conciliable' => 'No']);
        $this->send(['conciliacion_estado' => 'no_conciliables'])->assertCreated();
        $submission = HospitalConciliationSubmission::sole();
        $this->assertSame(1, $submission->mixture_count);
        $this->assertSame(0, $submission->conciliable_count);
        $this->assertSame('no_conciliables', $submission->filters['conciliacion_estado']);
        $this->assertSame('No', $submission->snapshot[0]['cells']['conciliable']);
    }

    public function test_dates_empty_selection_validation_and_permissions_are_enforced(): void
    {
        $this->send(['desde' => '2026-09-09'])->assertUnprocessable();
        $this->send(['desde' => '2026-10-01'])->assertUnprocessable();
        $this->send(['submission_key' => 'bad'])->assertUnprocessable();
        $this->send(['columnas' => ['patient' => ['AJENO']]])->assertUnprocessable();
        $this->send(['columnas' => ['invalid' => ['value']]])->assertUnprocessable();
        $this->assertSame(0, HospitalConciliationSubmission::count());
        $this->send(['periodo' => 'mes', 'desde' => '2026-09-15', 'hasta' => '2026-09-15'])->assertCreated();
        $this->assertSame('01/09/2026 - 30/09/2026', HospitalConciliationSubmission::sole()->periodLabel());
        $this->hospital->revokePermissionTo('oncologicos_solicitudes_index');
        $this->send()->assertCreated();
        $this->assertSame(['nutricionales'], array_column(HospitalConciliationSubmission::latest('id')->first()->snapshot, 'kind'));
        $this->hospital->revokePermissionTo('nutricionales_solicitudes_index');
        $this->send()->assertForbidden();
        $this->actingAs($this->internalUser());
        $this->send()->assertForbidden();
    }

    public function test_internal_inbox_detail_and_download_use_the_original_snapshot(): void
    {
        $this->send()->assertCreated();
        $submission = HospitalConciliationSubmission::sole();
        DB::table('institution_billings')->where('origen_id', 1)->update(['conciliable' => 'No']);
        DB::table('solicitud_oncos')->where('id', 1)->update(['nombre_paciente' => 'PATIENT CHANGED']);
        $this->actingAs($this->internalUser());
        $this->get(route('admin.instituciones.reportes', ['seccion' => 'conciliacion']))->assertOk()
            ->assertSee('Panel Administrativo')->assertSee('CON-000001')->assertSee('Hospital de prueba')
            ->assertViewHas('submissions', fn ($rows) => $rows->total() === 1);
        $this->get(route('admin.instituciones.reportes', ['seccion' => 'conciliacion', 'search' => 'missing']))
            ->assertOk()->assertSee('No hay solicitudes')->assertDontSee('CON-000001');
        $this->get(route('admin.instituciones.conciliaciones.show', $submission))->assertOk()
            ->assertSee('Paciente 1')->assertDontSee('PATIENT CHANGED')->assertDontSee('Paciente 2 Nutricion');
        Excel::fake();
        $this->get(route('admin.instituciones.conciliaciones.download', $submission))->assertOk();
        Excel::assertDownloaded('CON-000001.xlsx', function (HospitalConciliationExport $export) use ($submission) {
            $this->assertSame($submission->snapshot, $export->collection()->all());
            $this->assertContains('Sí', $export->map($export->collection()->first()));
            $this->assertCount(15, $export->map($export->collection()->first()));
            return true;
        });
    }

    public function test_hospitals_and_unauthorized_internal_users_cannot_read_submissions(): void
    {
        $this->send()->assertCreated();
        $submission = HospitalConciliationSubmission::sole();
        Permission::findOrCreate('menu.administracion.reports', 'web');
        $this->hospital->givePermissionTo('menu.administracion.reports');
        $urls = [route('admin.instituciones.reportes', ['seccion' => 'conciliacion']),
            route('admin.instituciones.conciliaciones.show', $submission), route('admin.instituciones.conciliaciones.download', $submission)];
        foreach ($urls as $url) $this->get($url)->assertForbidden();
        $user = $this->internalUser();
        $user->syncRoles(Role::findOrCreate('Usuario general', 'web')); $user->syncPermissions([]);
        $this->actingAs($user->refresh());
        foreach ($urls as $url) $this->get($url)->assertForbidden();
        $user->givePermissionTo('menu.administracion.reports');
        $this->actingAs($user->refresh());
        Excel::fake();
        foreach ($urls as $url) $this->get($url)->assertOk();
    }

    public function test_inbox_filters_by_institution_and_hospital_before_pagination(): void
    {
        $this->send()->assertCreated();
        $original = HospitalConciliationSubmission::sole();
        DB::table('clientes')->insert(['id' => 2, 'nombre' => 'Otra institucion']);
        DB::table('hospitals')->insert(['id' => 3, 'name' => 'Segundo hospital']);
        DB::table('cliente_hospital')->insert([
            ['cliente_id' => 2, 'hospital_id' => 2], ['cliente_id' => 1, 'hospital_id' => 3],
        ]);
        foreach ([...array_fill(0, 15, 1), 2, 3] as $hospitalId) {
            $copy = $original->replicate();
            $copy->submission_key = (string) Str::uuid();
            $copy->hospital_id = $hospitalId;
            $copy->hospital_name = $hospitalId === 1 ? 'Hospital de prueba' : 'Hospital '.$hospitalId;
            $copy->sender_name = $hospitalId === 3 ? 'Remitente distinto' : 'Remitente de prueba';
            $copy->save();
        }
        $this->actingAs($this->internalUser());
        $url = fn ($query = []) => route('admin.instituciones.reportes', ['seccion' => 'conciliacion'] + $query);
        $response = $this->get($url(['institucion_id' => 1]))->assertOk()->assertSee('Todas las instituciones')->assertSee('Todos los hospitales')
            ->assertViewHas('institutionId', 1)->assertViewHas('submissions', fn ($rows) => $rows->total() === 17 && $rows->count() === 15);
        parse_str(parse_url($response->viewData('submissions')->nextPageUrl(), PHP_URL_QUERY), $next);
        $this->assertSame('1', $next['institucion_id']);
        $this->assertSame('conciliacion', $next['seccion']);
        $this->get($url(['institucion_id' => 1, 'hospital_id' => 1, 'page' => 2]))->assertOk()
            ->assertViewHas('hospitalId', 1)->assertViewHas('submissions', fn ($rows) => $rows->total() === 16 && $rows->count() === 1);
        $this->get($url(['hospital_id' => 3]))->assertOk()->assertViewHas('submissions', fn ($rows) => $rows->total() === 1);
        $this->get($url(['institucion_id' => 2]))->assertOk()->assertViewHas('submissions', fn ($rows) => $rows->total() === 1);
        $this->get($url(['institucion_id' => 2, 'hospital_id' => 1]))->assertOk()
            ->assertSee('para los filtros seleccionados')->assertViewHas('submissions', fn ($rows) => $rows->total() === 0);
        $this->get($url(['institucion_id' => 1, 'search' => 'Remitente distinto']))->assertOk()
            ->assertViewHas('submissions', fn ($rows) => $rows->total() === 1);
        $this->get($url())->assertOk()->assertViewHas('submissions', fn ($rows) => $rows->total() === 18);
        foreach ([['institucion_id' => 999], ['hospital_id' => 999], ['institucion_id' => 'invalid'], ['hospital_id' => [1]]] as $invalid) {
            $this->getJson($url($invalid))->assertUnprocessable();
        }
    }

    public function test_inbox_displays_institutions_before_hospital_and_handles_missing_associations(): void
    {
        $this->send()->assertCreated();
        $this->actingAs($this->internalUser());
        $url = route('admin.instituciones.reportes', ['seccion' => 'conciliacion']);
        $this->get($url)->assertOk()->assertSeeInOrder(['<th>Fecha de envío</th>', '<th>Institución</th>', '<th>Hospital</th>'], false)
            ->assertSee('<td>Institucion de prueba</td><td>Hospital de prueba</td>', false);
        DB::table('clientes')->insert(['id' => 2, 'nombre' => 'Otra institucion']);
        DB::table('cliente_hospital')->insert(['cliente_id' => 2, 'hospital_id' => 1]);
        $this->get($url)->assertOk()->assertSee('<td>Institucion de prueba, Otra institucion</td>', false);
        DB::table('cliente_hospital')->where('hospital_id', 1)->delete();
        $this->get($url)->assertOk()->assertSee('<td>Sin institución</td><td>Hospital de prueba</td>', false);
        $this->get($url.'&search=missing')->assertOk()->assertSee('colspan="10"', false);
    }

    public function test_red_count_is_only_used_for_submissions_with_non_conciliable_mixtures(): void
    {
        $this->send()->assertCreated();
        $admin = $this->internalUser();
        $this->actingAs($admin)->get(route('admin.instituciones.reportes', ['seccion' => 'conciliacion']))
            ->assertOk()->assertDontSee('class="ht-no-count"', false);
        $this->actingAs($this->hospital);
        DB::table('institution_billings')->where('origen_tipo', 'oncologica_mezcla')->where('origen_id', 1)->update(['conciliable' => 'No']);
        $this->send()->assertCreated();
        $this->actingAs($admin)->get(route('admin.instituciones.reportes', ['seccion' => 'conciliacion']))
            ->assertOk()->assertSee('<span class="ht-no-count" title="1 mezclas no conciliables">1</span>', false);
    }

    public function test_summary_shows_all_filtered_rows_without_pagination_and_remains_read_only(): void
    {
        DB::table('institution_billings')->where('origen_tipo', 'oncologica_mezcla')->where('origen_id', 1)->update(['conciliable' => 'No']);
        $this->send()->assertCreated();
        $submission = HospitalConciliationSubmission::sole();
        $snapshot = $submission->snapshot;
        $prototype = collect($snapshot)->firstWhere('conciliable', true);
        for ($id = 100; $id < 116; $id++) {
            $row = $prototype;
            $row['id'] = $id; $row['cells']['id'] = (string) $id; $row['cells']['request_id'] = 'SOL-'.$id;
            $row['cells']['patient'] = $id === 115 ? 'Paciente único' : 'Paciente de prueba '.$id;
            $snapshot[] = $row;
        }
        $submission->update(['snapshot' => $snapshot, 'mixture_count' => 20, 'conciliable_count' => 19]);
        $before = $submission->fresh()->toArray();
        $this->actingAs($this->internalUser());
        $url = fn ($query = []) => route('admin.instituciones.conciliaciones.show', [$submission] + $query);
        $this->get($url())->assertOk()->assertViewHas('rows', fn ($rows) => $rows->count() === 20)
            ->assertSee('Listado de solicitudes del hospital')->assertSee('aria-label="Solo lectura"', false)
            ->assertSee('Mostrando 20 mezclas')->assertDontSee('Paginación de la conciliación');
        $this->get($url(['page' => 3]))->assertOk()->assertViewHas('rows', fn ($rows) => $rows->count() === 20);
        $this->get($url(['estado' => 'si']))->assertOk()->assertViewHas('rows', fn ($rows) => $rows->count() === 19);
        $this->get($url(['estado' => 'no']))->assertOk()->assertViewHas('rows', fn ($rows) => $rows->count() === 1)
            ->assertSee('Revisar registro de prueba');
        $this->get($url(['search' => 'UNICO', 'page' => 99]))->assertOk()
            ->assertViewHas('rows', fn ($rows) => $rows->count() === 1);
        $this->get($url(['search' => 'SOL-112']))->assertOk()->assertViewHas('rows', fn ($rows) => $rows->count() === 1);
        $this->get($url(['search' => 'UNICO', 'estado' => 'no']))->assertOk()->assertViewHas('rows', fn ($rows) => $rows->count() === 0);
        $this->getJson($url(['estado' => 'no']))->assertOk()->assertJsonPath('title', 'Resumen de conciliación · CON-000001')
            ->assertJsonStructure(['html']);
        $this->getJson($url(['estado' => 'invalid']))->assertUnprocessable();
        $this->getJson($url(['search' => str_repeat('a', 151)]))->assertUnprocessable();
        $this->assertSame($before, $submission->fresh()->toArray());
        $this->actingAs($this->hospital)->getJson($url())->assertForbidden();
    }

    public function test_summary_handles_old_snapshots_and_escapes_their_contents(): void
    {
        $this->send()->assertCreated();
        $submission = HospitalConciliationSubmission::sole();
        $snapshot = $submission->snapshot;
        foreach ($snapshot as &$row) unset($row['conciliable'], $row['adjustment'], $row['amount_cents'], $row['reason']);
        unset($row);
        $snapshot[0]['cells']['patient'] = '<img src=x onerror=alert(1)>';
        $submission->update(['snapshot' => $snapshot]);
        $this->actingAs($this->internalUser());
        $response = $this->getJson(route('admin.instituciones.conciliaciones.show', $submission))->assertOk();
        $this->assertStringContainsString('&lt;img src=x onerror=alert(1)&gt;', $response->json('html'));
        $this->assertStringNotContainsString('<img src=x', $response->json('html'));
        $this->assertStringContainsString('Sin registrar', $response->json('html'));
    }

    public function test_preview_uses_real_prices_filters_and_does_not_write(): void
    {
        DB::table('institution_billings')->where('origen_tipo', 'oncologica_mezcla')->where('origen_id', 1)->update(['conciliable' => 'No', 'precio_total' => '$1,234.56']);
        $query = ['desde' => '2026-09-01', 'hasta' => '2026-09-30'];
        $this->getJson(route('admin.hospital.conciliacion.resumen', $query))->assertOk()
            ->assertJsonPath('summary.total.count', 4)->assertJsonPath('summary.total.amount_cents', 213506)
            ->assertJsonPath('summary.yes.count', 3)->assertJsonPath('summary.yes.amount_cents', 90050)
            ->assertJsonPath('summary.no.amount_cents', 123456)
            ->assertJsonCount(1, 'summary.non_conciliable');
        $this->getJson(route('admin.hospital.conciliacion.resumen', $query + ['conciliacion_estado' => 'no_conciliables']))
            ->assertOk()->assertJsonPath('summary.total.count', 1)->assertJsonPath('summary.yes.amount_cents', 0);
        $this->assertSame(0, HospitalConciliationSubmission::count());
        $this->assertSame(0, DB::table('institution_billing_movements')->count());
    }

    public function test_missing_prices_are_not_zero_and_explicit_zero_is_preserved(): void
    {
        $this->mock(\App\Services\InstitutionBillingPricingService::class, fn ($mock) => $mock->shouldReceive('priceOncoMix')->andReturn(['total_iva_included' => 0]));
        DB::table('institution_billings')->where('origen_tipo', 'oncologica_mezcla')->where('origen_id', 1)->update(['precio_total' => '0']);
        $this->send()->assertCreated()->assertJsonPath('summary.total.amount_cents', null)->assertJsonPath('summary.total.missing_amounts', 2);
        $row = collect(HospitalConciliationSubmission::sole()->snapshot)->first(fn ($row) => $row['kind'] === 'oncologicos' && $row['id'] === 1);
        $this->assertSame(0, $row['amount_cents']);
    }

    public function test_stale_preview_and_forged_token_cannot_send(): void
    {
        $input = ['desde' => '2026-09-01', 'hasta' => '2026-09-30', 'submission_key' => (string) Str::uuid()];
        $preview = $this->getJson(route('admin.hospital.conciliacion.resumen', $input))->assertOk()->json();
        DB::table('institution_billings')->where('origen_id', 1)->update(['precio_total' => '999']);
        $input['confirmation_token'] = $preview['confirmation_token'];
        $this->postJson(route('admin.hospital.conciliacion.enviar'), $input)->assertConflict();
        $input['confirmation_token'] = str_repeat('a', 64);
        $this->postJson(route('admin.hospital.conciliacion.enviar'), $input)->assertConflict();
        $this->assertSame(0, HospitalConciliationSubmission::count());
    }

    public function test_reasons_are_required_scoped_and_frozen_with_the_amount(): void
    {
        DB::table('institution_billings')->where('origen_tipo', 'oncologica_mezcla')->where('origen_id', 1)->update(['conciliable' => 'No']);
        $this->send(['reasons' => []])->assertUnprocessable();
        $this->send(['reasons' => ['oncologicos-1' => '  ']])->assertUnprocessable();
        $this->send(['reasons' => ['oncologicos-1' => 'Revisar', 'oncologicos-3' => 'Ajeno']])->assertUnprocessable();
        $key = (string) Str::uuid();
        $this->send(['submission_key' => $key, 'reasons' => ['oncologicos-1' => '=Motivo de prueba']])->assertCreated()
            ->assertJsonPath('summary.no.amount_cents', 50000);
        DB::table('institution_billings')->where('origen_id', 1)->update(['precio_total' => '900']);
        $this->send(['submission_key' => $key, 'reasons' => ['oncologicos-1' => '=Motivo de prueba']])->assertOk()
            ->assertJsonPath('summary.no.amount_cents', 50000);
        $this->send(['submission_key' => $key, 'reasons' => ['oncologicos-1' => 'Otro motivo']])->assertConflict();
        $this->actingAs($this->internalUser());
        $this->get(route('admin.instituciones.conciliaciones.show', HospitalConciliationSubmission::sole()))
            ->assertOk()->assertSee('=Motivo de prueba')->assertSee('$500.00 MXN');
    }
}
