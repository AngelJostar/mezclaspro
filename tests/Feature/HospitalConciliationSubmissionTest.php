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
        (require database_path('migrations/2026_10_06_180000_add_direction_to_hospital_conciliation_submissions.php'))->up();
        (require database_path('migrations/2026_10_06_190000_create_conciliation_periods.php'))->up();
        $this->mock(\App\Services\InstitutionBillingPricingService::class, function ($mock) {
            $mock->shouldReceive('priceOncoMix')->andReturn(['total_iva_included' => 200.25]);
            $mock->shouldReceive('priceNutritionRequest')->andReturn(['total_iva_included' => 300.50]);
        });
        Schema::create('laboratory_purchase_orders', function (Blueprint $table) { $table->id(); $table->unsignedBigInteger('created_by'); });
        $this->actingAs($this->hospital->refresh());
    }

    private function periodScope(): array
    {
        DB::table('mezclas')->whereIn('id', [1, 2, 3, 4])->update(['remision' => DB::raw("'REM-' || id")]);
        DB::table('solicituds')->where('id', 11)->update(['remision' => 'NPT-11']);
        $this->actingAs($this->internalUser());
        return ['institucion_id' => 1, 'hospital_id' => 1, 'desde' => '2026-09-01', 'hasta' => '2026-09-30'];
    }

    private function periodDetail(array $scope): array
    {
        return $this->getJson(route('admin.instituciones.conciliacion-periodos.detail', $scope))->assertOk()->json('group');
    }

    private function createPeriodPayload(array $keys): array
    {
        $rows = collect($this->getJson(route('admin.instituciones.conciliacion-periodos.candidates'))->assertOk()->json('rows'))->keyBy('key');
        $first = $rows[$keys[0]];
        return ['creation_key' => (string) Str::uuid(), 'hospital_id' => $first['hospital_id'], 'institucion_id' => $first['institution_id'],
            'selection' => collect($keys)->mapWithKeys(fn ($key) => [$key => $rows[$key]['version']])->all()];
    }

    public function test_period_creation_persists_only_selected_remittances_and_reuses_existing_review_and_send(): void
    {
        $scope = $this->periodScope();
        DB::table('mezclas')->where('id', 2)->update(['fecha_entrega' => '2026-10-06 18:00:00']);
        $payload = $this->createPeriodPayload(['oncologicos-1', 'antibioticos-2']);
        $created = $this->postJson(route('admin.instituciones.conciliacion-periodos.store'), $payload)->assertCreated();
        $id = $created->json('period_id');
        $periodScope = array_replace($scope, ['desde' => '2026-09-09', 'hasta' => '2026-10-06', 'period_id' => $id]);
        $this->assertDatabaseHas('conciliation_periods', ['id' => $id, 'hospital_id' => 1, 'institution_id' => 1]);
        $this->assertSame('2026-09-09', \App\Models\ConciliationPeriod::findOrFail($id)->period_from->toDateString());
        $this->assertSame('2026-10-06', \App\Models\ConciliationPeriod::findOrFail($id)->period_to->toDateString());
        $this->assertDatabaseCount('conciliation_period_items', 2);
        $this->assertDatabaseCount('hospital_conciliation_submissions', 0);
        $this->postJson(route('admin.instituciones.conciliacion-periodos.store'), $payload)->assertOk()->assertJsonPath('period_id', $id);
        $this->assertDatabaseCount('conciliation_periods', 1);
        $this->get($created->json('redirect'))->assertOk()->assertViewHas('groups', function ($groups) use ($id) {
            $saved = $groups->firstWhere('period_id', $id);
            $this->assertCount(2, $saved['rows']);
            $this->assertSame(70025, $saved['total_cents']);
            $keys = $groups->flatMap(fn ($g) => array_column($g['rows'], 'key'));
            $this->assertCount($keys->count(), $keys->unique());
            return true;
        });
        $group = $this->periodDetail($periodScope);
        $review = $this->postJson(route('admin.instituciones.conciliacion-periodos.accept'), $periodScope + [
            'version' => $group['version'], 'choices' => ['oncologicos-1' => true, 'antibioticos-2' => false],
        ])->assertOk()->assertJsonPath('group.new_cents', 50000)->json('group');
        $this->postJson(route('admin.instituciones.conciliacion-periodos.send'), $periodScope + ['version' => $review['version'], 'submission_key' => $review['submission_key']])->assertOk();
        $submission = HospitalConciliationSubmission::sole();
        $this->assertSame($id, $submission->filters['period_id']);
        $this->assertSame(2, $submission->mixture_count);
        $this->assertSame(['antibioticos-2', 'oncologicos-1'], array_map(fn ($r) => $r['kind'].'-'.$r['id'], $submission->snapshot));
        $this->getJson(route('admin.instituciones.conciliacion-periodos.detail', array_replace($periodScope, ['period_id' => $id + 1])))->assertNotFound();
        $this->getJson(route('admin.instituciones.conciliacion-periodos.detail', array_diff_key($periodScope, ['period_id' => true])))->assertNotFound();
    }

    public function test_period_creation_rejects_duplicates_stale_prices_and_mixed_hospitals_atomically(): void
    {
        $this->periodScope();
        DB::table('cliente_hospital')->insertOrIgnore(['cliente_id' => 1, 'hospital_id' => 2]);
        $url = route('admin.instituciones.conciliacion-periodos.store');
        $payload = $this->createPeriodPayload(['oncologicos-1', 'nutricionales-11']);
        DB::table('institution_billings')->where('origen_tipo', 'oncologica_mezcla')->where('origen_id', 1)->update(['precio_total' => '999.99']);
        $this->postJson($url, $payload)->assertConflict();
        $this->assertDatabaseCount('conciliation_periods', 0);
        $mixed = $this->createPeriodPayload(['oncologicos-1', 'oncologicos-3']);
        $this->postJson($url, $mixed)->assertConflict();
        $this->assertDatabaseCount('conciliation_period_items', 0);
        $payload = $this->createPeriodPayload(['oncologicos-1', 'nutricionales-11']);
        $created = $this->postJson($url, $payload)->assertCreated();
        $this->postJson($url, array_replace($payload, ['creation_key' => (string) Str::uuid()]))->assertConflict();
        $this->postJson($url, array_replace($payload, ['selection' => ['oncologicos-1' => $payload['selection']['oncologicos-1']]]))->assertConflict();
        $this->assertDatabaseCount('conciliation_periods', 1);
        $this->assertDatabaseCount('conciliation_period_items', 2);
        $candidates = collect($this->getJson(route('admin.instituciones.conciliacion-periodos.candidates'))->assertOk()->json('rows'));
        $this->assertSame($created->json('period_id'), $candidates->firstWhere('key', 'oncologicos-1')['period_id']);
        $this->postJson($url, array_replace($payload, ['selection' => []]))->assertUnprocessable();
    }

    public function test_period_candidates_include_old_remittances_and_require_internal_access(): void
    {
        $this->periodScope();
        DB::table('mezclas')->where('id', 2)->update(['fecha_entrega' => '2024-01-06 18:00:00']);
        $rows = collect($this->getJson(route('admin.instituciones.conciliacion-periodos.candidates'))->assertOk()->json('rows'));
        $this->assertSame('2024-01-06 18:00', $rows->firstWhere('key', 'antibioticos-2')['date']);
        $this->actingAs($this->hospital);
        $this->getJson(route('admin.instituciones.conciliacion-periodos.candidates'))->assertForbidden();
        $this->postJson(route('admin.instituciones.conciliacion-periodos.store'), [])->assertForbidden();
    }

    public function test_period_never_sends_a_partial_selection_if_a_saved_remittance_disappears(): void
    {
        $scope = $this->periodScope();
        $payload = $this->createPeriodPayload(['oncologicos-1', 'nutricionales-11']);
        $id = $this->postJson(route('admin.instituciones.conciliacion-periodos.store'), $payload)->assertCreated()->json('period_id');
        $savedScope = array_replace($scope, ['desde' => '2026-09-09', 'hasta' => '2026-09-09', 'period_id' => $id]);
        $group = $this->periodDetail($savedScope);
        DB::table('mezclas')->where('id', 1)->update(['remision' => null]);
        $this->getJson(route('admin.instituciones.conciliacion-periodos.detail', $savedScope))->assertConflict();
        $this->postJson(route('admin.instituciones.conciliacion-periodos.send'), $savedScope + ['version' => $group['version'], 'submission_key' => $group['submission_key']])->assertConflict();
        $this->assertDatabaseCount('hospital_conciliation_submissions', 0);
    }

    public function test_period_groups_actual_remittances_by_month_and_keeps_remission_screen(): void
    {
        $scope = $this->periodScope();
        DB::table('mezclas')->where('id', 2)->update(['fecha_entrega' => '2026-10-06 23:59:59']);
        DB::table('mezclas')->where('id', 4)->update(['remision' => null]);
        $url = route('admin.instituciones.reportes', ['seccion' => 'conciliacion', 'modalidad' => 'periodo', 'desde' => '2026-09-09', 'hasta' => '2026-10-06']);
        $this->get($url)->assertOk()->assertSee('Por periodo')->assertSee('Por remisión')->assertSee('Nuevo monto de conciliación')
            ->assertViewHas('groups', function ($groups) {
                $this->assertCount(2, $groups);
                $this->assertSame('2026-09-09', $groups[0]['from']);
                $this->assertSame('2026-09-30', $groups[0]['to']);
                $this->assertSame(100000, $groups[0]['total_cents']);
                $this->assertCount(2, $groups[0]['rows']);
                $this->assertSame('2026-10-01', $groups[1]['from']);
                $this->assertSame('2026-10-06', $groups[1]['to']);
                $this->assertSame(20025, $groups[1]['new_cents']);
                return true;
            });
        $this->get(route('admin.instituciones.reportes', ['seccion' => 'conciliacion']))->assertOk()
            ->assertSee('Por remisión')->assertSee('Solicitudes de conciliación')->assertViewHas('rows');
        $this->assertSame(0, DB::table('institution_billing_movements')->count());
    }

    public function test_period_accept_saves_selections_atomically_and_updates_original_and_new_totals(): void
    {
        $scope = $this->periodScope();
        $group = $this->periodDetail($scope);
        $this->assertSame(140050, $group['total_cents']);
        $choices = array_fill_keys(array_column($group['rows'], 'key'), true);
        $choices['oncologicos-1'] = false;
        $choices['antibioticos-2'] = false;
        $response = $this->postJson(route('admin.instituciones.conciliacion-periodos.accept'), $scope + ['version' => $group['version'], 'choices' => $choices])
            ->assertOk()->assertJsonPath('group.total_cents', 140050)->assertJsonPath('group.new_cents', 70025)->assertJsonPath('group.excluded_cents', 70025);
        $this->assertNotSame($group['version'], $response->json('group.version'));
        $this->assertSame(2, DB::table('institution_billing_movements')->count());
        $this->assertDatabaseHas('institution_billings', ['origen_id' => 2, 'origen_tipo' => 'oncologica_mezcla', 'institucion_id' => 1, 'hospital_id' => 1, 'conciliable' => 'No']);
        $this->assertDatabaseHas('institution_billings', ['origen_id' => 1, 'folio_interno' => 'F-1', 'precio_total' => '500.00']);
        $this->assertSame(0, HospitalConciliationSubmission::count());
        $this->postJson(route('admin.instituciones.conciliacion-periodos.accept'), $scope + ['version' => $response->json('group.version'), 'choices' => $choices])->assertOk();
        $this->assertSame(2, DB::table('institution_billing_movements')->count());
    }

    public function test_period_status_tabs_filter_whole_groups_and_preserve_date_and_hospital_filters(): void
    {
        $scope = $this->periodScope();
        $query = $scope + ['seccion' => 'conciliacion', 'modalidad' => 'periodo'];
        $url = fn ($tab) => route('admin.instituciones.reportes', $query + ['bandeja' => $tab]);
        $counts = ['todas' => 1, 'recibidas' => 0, 'enviadas' => 0, 'pendientes' => 1];
        $this->get($url('pendientes'))->assertOk()->assertViewHas('tabCounts', $counts)
            ->assertViewHas('groups', fn ($groups) => $groups->count() === 1)
            ->assertSee('Todas')->assertSee('Recibidas')->assertSee('Enviadas')->assertSee('Pendientes')
            ->assertDontSee('Solicitudes de conciliación')
            ->assertViewHas('filterQuery', fn ($filters) => $filters['hospital_id'] === 1 && $filters['desde'] === $scope['desde']);
        $this->get($url('recibidas'))->assertOk()->assertViewHas('groups', fn ($groups) => $groups->isEmpty());

        $group = $this->periodDetail($scope);
        $this->postJson(route('admin.instituciones.conciliacion-periodos.send'), $scope + ['version' => $group['version'], 'submission_key' => $group['submission_key']])->assertOk();
        $this->get($url('enviadas'))->assertOk()->assertViewHas('tabCounts', array_replace($counts, ['enviadas' => 1, 'pendientes' => 0]))
            ->assertViewHas('groups', fn ($groups) => $groups->count() === 1 && count($groups[0]['rows']) === count($group['rows']));
        $this->get($url('pendientes'))->assertOk()->assertViewHas('groups', fn ($groups) => $groups->isEmpty());

        $received = HospitalConciliationSubmission::sole()->replicate();
        $received->submission_key = (string) Str::uuid();
        $received->direction = 'received';
        $received->save();
        $this->get($url('recibidas'))->assertOk()->assertViewHas('tabCounts', array_replace($counts, ['recibidas' => 1, 'pendientes' => 0]))
            ->assertViewHas('groups', fn ($groups) => $groups->count() === 1 && $groups[0]['total_cents'] === $group['total_cents']);
        $this->getJson($url('invalid'))->assertUnprocessable();
        $this->get(route('admin.instituciones.reportes', array_replace($query, ['desde' => '2027-01-01', 'hasta' => '2027-01-31'])))
            ->assertOk()->assertViewHas('tabCounts', ['todas' => 0, 'recibidas' => 0, 'enviadas' => 0, 'pendientes' => 0]);
    }

    public function test_period_rejects_stale_and_foreign_selections_without_partial_writes(): void
    {
        $scope = $this->periodScope();
        $group = $this->periodDetail($scope);
        $choices = array_fill_keys(array_column($group['rows'], 'key'), false);
        $url = route('admin.instituciones.conciliacion-periodos.accept');
        $this->postJson($url, $scope + ['version' => $group['version'], 'choices' => $choices + ['oncologicos-3' => false]])->assertUnprocessable();
        $incomplete = $choices; unset($incomplete['oncologicos-1']);
        $this->postJson($url, $scope + ['version' => $group['version'], 'choices' => $incomplete])->assertUnprocessable();
        DB::table('institution_billings')->where('origen_id', 1)->update(['precio_total' => '600.00']);
        $this->postJson($url, $scope + ['version' => $group['version'], 'choices' => $choices])->assertConflict();
        $this->postJson(route('admin.instituciones.conciliacion-periodos.send'), $scope + ['version' => $group['version'], 'submission_key' => (string) Str::uuid()])->assertConflict();
        $this->assertSame(0, DB::table('institution_billing_movements')->count());
        $this->assertDatabaseHas('institution_billings', ['origen_id' => 1, 'conciliable' => 'Si']);
        $this->assertSame(0, HospitalConciliationSubmission::count());
    }

    public function test_period_send_freezes_totals_is_idempotent_and_reaches_only_its_hospital(): void
    {
        $scope = $this->periodScope();
        DB::table('institution_billings')->where('origen_id', 1)->update(['conciliable' => 'No']);
        $group = $this->periodDetail($scope);
        $payload = $scope + ['version' => $group['version'], 'submission_key' => $group['submission_key']];
        $url = route('admin.instituciones.conciliacion-periodos.send');
        $this->postJson($url, $payload)->assertOk()->assertJsonPath('folio', 'CON-000001');
        $this->postJson($url, $payload)->assertOk()->assertJsonPath('folio', 'CON-000001');
        $this->postJson($url, array_replace($payload, ['submission_key' => (string) Str::uuid()]))->assertOk();
        $this->assertSame(1, HospitalConciliationSubmission::count());
        $sent = HospitalConciliationSubmission::sole();
        $this->assertSame('sent', $sent->direction);
        $this->assertSame(4, $sent->mixture_count);
        $this->assertSame(3, $sent->conciliable_count);
        $this->assertSame(140050, $sent->summary()['total']['amount_cents']);
        $this->assertSame(90050, $sent->summary()['yes']['amount_cents']);
        $this->assertSame('CON-000001', $this->periodDetail($scope)['sent_folio']);
        DB::table('institution_billings')->where('origen_id', 1)->update(['precio_total' => '999.00']);
        $this->assertSame(140050, $sent->fresh()->summary()['total']['amount_cents']);
        $this->actingAs($this->hospital)->get(route('admin.herramientas.index', ['seccion' => 'conciliacion']))->assertOk()->assertSee('CON-000001')->assertSee('Recibida');
        $this->get(route('admin.herramientas.conciliaciones.download', $sent))->assertOk();
        $this->hospital->hospital_id = 2; $this->hospital->save();
        $this->get(route('admin.herramientas.conciliaciones.download', $sent))->assertNotFound();
    }

    public function test_period_scopes_institutions_permissions_and_dates(): void
    {
        $scope = $this->periodScope();
        $this->getJson(route('admin.instituciones.conciliacion-periodos.detail', array_replace($scope, ['hospital_id' => 2])))->assertUnprocessable();
        $this->getJson(route('admin.instituciones.conciliacion-periodos.detail', array_replace($scope, ['hasta' => '2026-08-01'])))->assertUnprocessable();
        $this->getJson(route('admin.instituciones.conciliacion-periodos.detail', array_replace($scope, ['desde' => '2027-01-01', 'hasta' => '2027-01-31'])))->assertNotFound();
        DB::table('clientes')->insert(['id' => 2, 'nombre' => 'Otra institución']);
        DB::table('cliente_hospital')->insert(['hospital_id' => 1, 'cliente_id' => 2]);
        $group = $this->periodDetail($scope);
        $this->assertCount(2, $group['rows']); // Unassigned remittances must not be duplicated across institutions.
        $this->getJson(route('admin.instituciones.conciliacion-periodos.detail', array_replace($scope, ['institucion_id' => 2])))->assertNotFound();
        $this->actingAs($this->hospital);
        foreach (['detail', 'accept', 'send'] as $action) {
            $url = route('admin.instituciones.conciliacion-periodos.'.$action, $action === 'detail' ? $scope : []);
            ($action === 'detail' ? $this->getJson($url) : $this->postJson($url, $scope))->assertForbidden();
        }
    }

    public function test_period_missing_amount_is_not_zero_and_cannot_be_sent(): void
    {
        $scope = $this->periodScope();
        DB::table('institution_billings')->where('origen_id', 1)->update(['precio_total' => '0']);
        $this->mock(\App\Services\InstitutionBillingPricingService::class, fn ($mock) => $mock->shouldReceive('priceOncoMix', 'priceNutritionRequest')->andReturn(['total_iva_included' => 0]));
        $group = $this->periodDetail($scope);
        $this->assertNull($group['total_cents']);
        $this->assertSame(2, $group['missing_prices']);
        $this->assertSame(0, collect($group['rows'])->firstWhere('key', 'oncologicos-1')['amount_cents']);
        $this->postJson(route('admin.instituciones.conciliacion-periodos.send'), $scope + ['version' => $group['version'], 'submission_key' => (string) Str::uuid()])->assertUnprocessable();
    }

    public function test_client_tools_show_only_own_hospital_submissions_and_block_other_downloads(): void
    {
        $own = HospitalConciliationSubmission::create([
            'submission_key' => (string) Str::uuid(), 'hospital_id' => $this->hospital->hospital_id,
            'hospital_name' => 'Hospital propio', 'sender_name' => 'Remitente propio',
            'filters' => [], 'mixture_count' => 3, 'conciliable_count' => 2, 'snapshot' => [],
        ]);
        $other = $own->replicate();
        $other->submission_key = (string) Str::uuid();
        $other->hospital_id = 2;
        $other->hospital_name = 'Hospital ajeno';
        $other->save();

        $this->get(route('admin.herramientas.index', ['seccion' => 'conciliacion', 'hospital_id' => 2]))
            ->assertOk()->assertSee('Fecha de envío')->assertSee('Conciliables Sí')
            ->assertSee('Remitente propio')->assertDontSee('Hospital ajeno')
            ->assertViewHas('submissions', fn ($rows) => $rows->total() === 1);
        $this->get(route('admin.herramientas.conciliaciones.download', $other))->assertNotFound();
        $this->get(route('admin.herramientas.conciliaciones.download', $own))->assertOk();
    }

    public function test_client_billing_displays_requested_columns(): void
    {
        $this->mock(\App\Http\Controllers\Admin\InstitucionBillingController::class, function ($mock) {
            $mock->shouldReceive('clientRecords')->once()->andReturn(new \Illuminate\Pagination\LengthAwarePaginator([], 0, 15));
        });
        $this->get(route('admin.herramientas.index', ['seccion' => 'facturacion']))->assertOk()
            ->assertSee('No. de remisión')->assertSee('Nombre del médico')->assertSee('Nombre del paciente')
            ->assertSee('Precio unitario IVA incluido')->assertSee('Folio factura UUID')->assertSee('Fecha de factura');
    }

    public function test_internal_adjustment_log_lists_versions_and_blocks_client_access(): void
    {
        Schema::create('clinical_reviews', function (Blueprint $table) {
            $table->string('id')->primary(); $table->string('kind'); $table->unsignedBigInteger('target_id')->nullable();
            $table->string('purpose')->nullable(); $table->text('result')->nullable(); $table->text('medical_authorization')->nullable();
            $table->timestamps();
        });
        $url = route('admin.instituciones.reportes', ['seccion' => 'ajustes']);
        $this->get($url)->assertForbidden();
        $this->actingAs($this->internalUser())->get($url)->assertOk()
            ->assertSee('Bitácora de ajustes por mezcla')->assertSee('Propuesta de prueba')
            ->assertSee('AJUSTE AJENO')->assertSee('Autorización del hospital')
            ->assertSee('Sin revisión previa registrada');
        \App\Models\ClinicalReview::create([
            'id' => 'review-log-test', 'kind' => 'oncologicos', 'target_id' => 1, 'purpose' => 'approval',
            'result' => ['summary' => 'Comentario IA de prueba', 'findings' => []],
            'medical_authorization' => ['doctor_name' => 'Médico de prueba', 'doctor_license' => 'DEMO-123'],
            'created_at' => '2026-09-13 10:00:00',
        ]);
        $this->get($url)->assertOk()->assertSee('Comentario IA de prueba')->assertSee('Médico de prueba');
        DB::table('clinical_reviews')->where('id', 'review-log-test')->update(['result' => 'invalid-ciphertext']);
        $this->get($url)->assertOk()->assertSee('No se puede descifrar la revisión.');
    }

    public function test_billing_rows_separate_quantity_and_sale_unit_and_include_applicable_vat(): void
    {
        $pricing = \Mockery::mock(\App\Services\InstitutionBillingPricingService::class)->makePartial();
        $pricing->shouldReceive('priceOncoMix')->andReturn([
            'lines' => collect([
                ['description' => 'Gravado', 'quantity' => 2, 'unit_label' => 'frasco', 'unit_price' => 100, 'subtotal' => 200, 'vat' => 32, 'total_with_vat' => 232],
                ['description' => 'Exento', 'quantity' => 5, 'unit_label' => 'mg', 'unit_price' => 10, 'subtotal' => 50, 'vat' => 0, 'total_with_vat' => 50],
            ]), 'total_iva_included' => 282,
        ]);
        $controller = new \App\Http\Controllers\Admin\InstitucionBillingController($pricing, app(\App\Services\InstitutionBillingDueDateService::class));
        $mix = (new \App\Models\Oncologicos\Mezcla)->forceFill(['id' => 1]);
        $order = (new \App\Models\Oncologicos\SolicitudOnco)->forceFill(['id' => 1]);
        $order->setRelation('hospital', null);
        $mix->setRelation('solicitud', $order)->setRelation('billing', null);
        $method = new \ReflectionMethod($controller, 'transformRecord');
        $row = $method->invoke($controller, $mix, 'onco', null);
        $this->assertSame(['2', '5'], $row['quantity_lines']);
        $this->assertSame(['frasco', 'mg'], $row['sale_unit_lines']);
        $this->assertSame(['$116.00', '$10.00'], $row['unit_price_lines']);
        $this->assertSame("frasco\nmg", $row['export_row'][7]);
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
        $this->get($url.'&search=missing')->assertOk()->assertSee('colspan="8"', false);
    }

    public function test_inbox_switch_updates_each_mixture_with_audit_and_keeps_the_submitted_report(): void
    {
        $submission = $this->inboxSubmission();
        $original = $submission->refresh()->toArray();
        $this->actingAs($this->internalUser());
        $page = $this->get(route('admin.instituciones.reportes', ['seccion' => 'conciliacion']))->assertOk()
            ->assertSeeInOrder(['aria-label="Tipo"', 'aria-label="Paciente"', 'aria-label="Fecha y hora de solicitud"', 'aria-label="Ver"', 'aria-label="Conciliable"'], false)
            ->assertDontSee('<th>Folio</th>', false)->assertDontSee('<th>Conciliables Sí</th>', false)->assertDontSee('<th>Conciliables No</th>', false)
            ->assertSee('data-inbox-conciliable-choice="1"', false)
            ->assertSee('data-inbox-conciliable-choice="0"', false)
            ->assertSee('aria-label="Estatus de conciliación"', false)
            ->assertDontSee('data-open-conciliation', false);
        $rows = $page->viewData('submissionRows')->get($submission->id);
        $this->assertCount(count($original['snapshot']), $rows);
        foreach (['nutricionales', 'oncologicos', 'antibioticos'] as $kind) {
            $row = $rows->firstWhere('kind', $kind);
            $this->assertNotNull($row);
            $url = route('admin.instituciones.conciliaciones.conciliable', [$submission, $kind, $row['id']]);
            $this->patchJson($url, ['conciliable' => false, 'previous' => $row['previous_conciliable']])->assertOk()->assertJsonPath('conciliable', 'No');
            $origin = $kind === 'nutricionales' ? 'nutricional_solicitud' : 'oncologica_mezcla';
            $this->assertDatabaseHas('institution_billings', ['origen_tipo' => $origin, 'origen_id' => $row['id'], 'conciliable' => 'No']);
            $movement = \App\Models\InstitutionBillingMovement::where('origen_tipo', $origin)->where('origen_id', $row['id'])->latest('id')->firstOrFail();
            $this->assertSame('administration_conciliation', $movement->details['source']);
            $this->assertSame($submission->id, $movement->details['submission_id']);
            $reloaded = $this->get(route('admin.instituciones.reportes', ['seccion' => 'conciliacion']))->assertOk()->viewData('submissionRows')->get($submission->id);
            $this->assertFalse($reloaded->firstWhere('kind', $kind)['current_conciliable']);
            $this->patchJson($url, ['conciliable' => true, 'previous' => $row['previous_conciliable']])->assertConflict();
            $this->patchJson($url, ['conciliable' => true, 'previous' => 'No'])->assertOk()->assertJsonPath('conciliable', 'Si');
        }
        $this->assertSame($original, $submission->fresh()->toArray());
    }

    public function test_inbox_switch_rejects_unauthorized_users_foreign_mixtures_and_invalid_state(): void
    {
        $submission = $this->inboxSubmission();
        $url = fn ($kind, $id) => route('admin.instituciones.conciliaciones.conciliable', [$submission, $kind, $id]);
        $data = ['conciliable' => false, 'previous' => 'Si'];
        $this->patchJson($url('oncologicos', 1), $data)->assertForbidden();
        $admin = $this->internalUser();
        $this->actingAs($admin);
        $this->patchJson($url('oncologicos', 3), $data)->assertNotFound();
        $this->patchJson($url('antibioticos', 1), $data)->assertNotFound();
        $this->patchJson($url('oncologicos', 1), ['conciliable' => 'invalid', 'previous' => 'Si'])->assertUnprocessable();
        $this->patchJson($url('oncologicos', 1), ['conciliable' => false])->assertUnprocessable();
        // A submitted snapshot is not authorization to change a mixture belonging to another hospital.
        $copy = $submission->replicate();
        $copy->submission_key = (string) Str::uuid();
        $copy->hospital_id = 2;
        $copy->save();
        $this->patchJson(route('admin.instituciones.conciliaciones.conciliable', [$copy, 'oncologicos', 1]), $data)->assertNotFound();
        $admin->syncRoles(Role::findOrCreate('Usuario general', 'web'));
        $admin->syncPermissions([]);
        $this->actingAs($admin->refresh())->patchJson($url('oncologicos', 1), $data)->assertForbidden();
        $this->assertDatabaseHas('institution_billings', ['origen_tipo' => 'oncologica_mezcla', 'origen_id' => 1, 'conciliable' => 'Si']);
    }

    public function test_inbox_shows_all_requests_on_one_page_and_keeps_the_column_and_general_filters(): void
    {
        DB::table('solicitud_patients')->where('id', 1)->update(['nombre_paciente' => 'Paciente fuera de página', 'apellidos_paciente' => '']);
        DB::table('solicitud_patients')->where('id', 2)->update(['nombre_paciente' => 'Paciente de otro hospital', 'apellidos_paciente' => '']);
        for ($i = 20; $i < 36; $i++) {
            DB::table('solicitud_patients')->insert(['id' => $i, 'nombre_paciente' => 'Paciente '.$i]);
            DB::table('solicituds')->insert(['id' => $i, 'hospital_id' => 1, 'solicitud_patient_id' => $i,
                'estado' => 'aprobada', 'created_at' => '2026-10-01 10:00:00']);
        }

        $this->actingAs($this->internalUser());
        $query = ['seccion' => 'conciliacion', 'institucion_id' => 1, 'hospital_id' => 1, 'search' => 'Paciente', 'bandeja' => 'pendientes'];
        $url = fn ($extra = []) => route('admin.instituciones.reportes', $query + $extra);
        $page = $this->get($url())->assertOk();
        $this->assertSame(20, $page->viewData('rows')->count());
        $this->assertCount(20, $page->viewData('rows'));
        $page->assertSee('Mostrando 20 solicitudes en una sola página');
        $this->assertContains('Paciente fuera de página', $page->viewData('rows')->pluck('cells.patient'));
        $this->assertContains('Paciente fuera de página', $page->viewData('table')['options']['patient']);
        $this->assertNotContains('Paciente de otro hospital', $page->viewData('table')['options']['patient']);

        $filtered = $this->get($url(['columnas' => ['patient' => ['Paciente fuera de página']]]))->assertOk();
        $this->assertSame(1, $filtered->viewData('rows')->count());
        $this->assertSame(11, $filtered->viewData('rows')->first()['mixture']['id']);

        $query['columnas'] = ['type' => ['Nutricional', 'Oncologica']];
        $filteredPage = $this->get($url(['columnas' => $query['columnas'], 'page' => 2]))->assertOk();
        $this->assertSame(19, $filteredPage->viewData('rows')->count());
        $filteredPage->assertDontSee('aria-label="Pagination Navigation"', false);
        parse_str(parse_url($filteredPage->viewData('table')['url'], PHP_URL_QUERY), $next);
        $this->assertArrayNotHasKey('page', $next);
        $this->assertSame($query['columnas'], $next['columnas']);
        $this->assertSame('1', $next['hospital_id']);
        $this->assertSame('1', $next['institucion_id']);
        $this->assertSame('pendientes', $next['bandeja']);
        $this->assertSame($query['search'], $next['search']);
    }

    public function test_inbox_column_filters_combine_columns_and_use_current_conciliable_state(): void
    {
        $submission = $this->inboxSubmission();
        $this->actingAs($this->internalUser());
        $switch = route('admin.instituciones.conciliaciones.conciliable', [$submission, 'oncologicos', 1]);
        $this->patchJson($switch, ['conciliable' => false, 'previous' => 'Si'])->assertOk();
        $selected = ['type' => ['Oncologica'], 'patient' => ['Paciente 1'],
            'date' => ['2026-09-08 10:00'], 'view' => ['Ver'], 'conciliable' => ['No'],
            'conciliation_status' => ['Recibida']];
        $url = route('admin.instituciones.reportes', ['seccion' => 'conciliacion', 'columnas' => $selected]);
        $page = $this->get($url)->assertOk();
        $this->assertSame(1, $page->viewData('rows')->count());
        $this->assertSame('oncologicos', $page->viewData('rows')->first()['mixture']['kind']);
        $this->assertSame($selected, $page->viewData('table')['selected']);
        $this->assertSame(8, substr_count($page->getContent(), '<th data-force-column-filter'));
        $this->assertEqualsCanonicalizing(['No', 'Sí'], $page->viewData('table')['options']['conciliable']);

        $this->patchJson($switch, ['conciliable' => true, 'previous' => 'No'])->assertOk();
        $this->get($url)->assertOk()->assertViewHas('rows', fn ($rows) => $rows->count() === 0)
            ->assertSee('para los filtros seleccionados');
        $this->get(route('admin.instituciones.reportes', ['seccion' => 'conciliacion', 'columnas' => ['type' => ['']]]))
            ->assertOk()->assertViewHas('rows', fn ($rows) => $rows->count() === 0);
        $this->get(route('admin.instituciones.reportes', ['seccion' => 'conciliacion']))
            ->assertOk()->assertViewHas('rows', fn ($rows) => $rows->count() === 4);
    }

    public function test_inbox_column_filters_are_available_when_empty_and_reject_invalid_parameters(): void
    {
        $this->actingAs($this->internalUser());
        $url = route('admin.instituciones.reportes', ['seccion' => 'conciliacion', 'search' => 'sin resultados']);
        $page = $this->get($url)->assertOk()->assertSee('data-server-column-filters="conciliation-inbox-filters"', false);
        $this->assertSame(8, substr_count($page->getContent(), '<th data-force-column-filter'));
        $this->assertSame(8, substr_count($page->getContent(), 'data-table-column-trigger'));
        $this->assertSame([], $page->viewData('table')['options']['patient']);
        foreach ([['columnas' => ['invalid' => ['x']]], ['columnas' => ['patient' => 'x']],
            ['orden' => 'invalid'], ['direccion' => 'invalid'], ['page' => -1], ['bandeja' => 'invalid']] as $invalid) {
            $this->getJson($url.'&'.http_build_query($invalid))->assertUnprocessable();
        }
    }

    public function test_inbox_prices_and_remissions_use_billing_values_and_save_with_audit(): void
    {
        $submission = $this->inboxSubmission();
        $original = $submission->refresh()->toArray();
        DB::table('solicituds')->where('id', 11)->update(['remision' => 'REM-NUT-11']);
        DB::table('mezclas')->where('id', 1)->update(['remision' => 'REM-ONCO-1']);
        DB::table('mezclas')->where('id', 2)->update(['remision' => 'REM-ANT-2']);
        $this->actingAs($this->internalUser());
        $inbox = route('admin.instituciones.reportes', ['seccion' => 'conciliacion']);
        $page = $this->get($inbox)->assertOk()->assertSee('No. de remisión')->assertSee('Precio de venta total editable');
        $rows = $page->viewData('submissionRows')->get($submission->id);
        $this->assertSame('500.00', $rows->firstWhere('kind', 'nutricionales')['current_price']);
        $this->assertSame('200.25', $rows->firstWhere('kind', 'antibioticos')['current_price']);
        $this->assertSame('REM-NUT-11', $rows->firstWhere('kind', 'nutricionales')['remision']);
        $this->assertSame('REM-ONCO-1', $rows->firstWhere('kind', 'oncologicos')['remision']);
        $this->assertSame('REM-ANT-2', $rows->firstWhere('kind', 'antibioticos')['remision']);

        foreach ([['nutricionales', 11, '10.00', '500.00'], ['oncologicos', 1, '0', '500.00'], ['antibioticos', 2, '2.50', null]] as [$kind, $id, $price, $previous]) {
            $url = route('admin.instituciones.conciliaciones.price', [$submission, $kind, $id]);
            $normalized = number_format((float) $price, 2, '.', '');
            $this->patchJson($url, ['precio_total' => $price, 'previous' => $previous])->assertOk()->assertJsonPath('precio_total', $normalized);
            $origin = $kind === 'nutricionales' ? 'nutricional_solicitud' : 'oncologica_mezcla';
            $billing = \App\Models\InstitutionBilling::where('origen_tipo', $origin)->where('origen_id', $id)->firstOrFail();
            $this->assertSame($normalized, $billing->precio_total);
            if ($previous !== null) {
                $this->assertSame('Si', $billing->conciliable);
                $this->assertSame('F-'.$id, $billing->folio_interno);
            }
            $movement = $billing->movements()->latest('id')->firstOrFail();
            $this->assertSame('precio_total', $movement->details['field']);
            $this->assertSame($previous, $movement->details['before']);
            $this->assertSame($normalized, $movement->details['after']);
            $this->assertSame($movement->from_stage, $movement->to_stage);
            $this->assertSame($submission->id, $movement->details['submission_id']);
            $this->patchJson($url, ['precio_total' => '15.25', 'previous' => $previous])->assertConflict();
            $this->patchJson($url, ['precio_total' => $normalized, 'previous' => $normalized])->assertOk();
            $this->assertSame(1, $billing->movements()->count());
        }
        $sorted = $this->get($inbox.'&orden=price&direccion=asc')->assertOk()->viewData('rows');
        $this->assertSame(['0.00', '2.50', '10.00', '200.25'], $sorted->pluck('cells.price')->all());
        $selected = ['remision' => ['REM-NUT-11'], 'price' => ['10.00']];
        $this->get($inbox.'&'.http_build_query(['columnas' => $selected]))->assertOk()
            ->assertViewHas('rows', fn ($rows) => $rows->count() === 1 && $rows->first()['mixture']['id'] === 11);
        $this->assertSame($original, $submission->fresh()->toArray());
    }

    public function test_inbox_prices_reject_invalid_values_and_foreign_or_unauthorized_changes(): void
    {
        $submission = $this->inboxSubmission();
        $url = fn ($kind, $id) => route('admin.instituciones.conciliaciones.price', [$submission, $kind, $id]);
        $data = ['precio_total' => '100.50', 'previous' => '500.00'];
        $this->patchJson($url('oncologicos', 1), $data)->assertForbidden();
        $admin = $this->internalUser();
        $this->actingAs($admin);
        foreach (['', '-10', '1.234', '1e3', 'NaN', '10000000000', 'abc', ['100']] as $invalid) {
            $this->patchJson($url('oncologicos', 1), ['precio_total' => $invalid, 'previous' => '500.00'])->assertUnprocessable();
        }
        $this->patchJson($url('oncologicos', 1), ['precio_total' => '10'])->assertUnprocessable();
        $this->patchJson($url('oncologicos', 3), $data)->assertNotFound();
        $this->patchJson($url('antibioticos', 1), $data)->assertNotFound();
        $copy = $submission->replicate();
        $copy->submission_key = (string) Str::uuid();
        $copy->hospital_id = 2;
        $copy->save();
        $this->patchJson(route('admin.instituciones.conciliaciones.price', [$copy, 'oncologicos', 1]), $data)->assertNotFound();
        $admin->syncRoles(Role::findOrCreate('Usuario general', 'web'));
        $admin->syncPermissions([]);
        $this->actingAs($admin->refresh())->patchJson($url('oncologicos', 1), $data)->assertForbidden();
        $this->assertDatabaseHas('institution_billings', ['origen_tipo' => 'oncologica_mezcla', 'origen_id' => 1, 'precio_total' => '500.00']);
        $this->assertSame(0, \App\Models\InstitutionBillingMovement::count());
    }

    public function test_conciliation_lists_live_requests_without_submissions_and_filters_latest_exchange(): void
    {
        $this->actingAs($this->internalUser());
        $url = fn ($tab = 'todas') => route('admin.instituciones.reportes', ['seccion' => 'conciliacion', 'bandeja' => $tab]);
        $page = $this->get($url())->assertOk()->assertSeeInOrder(['Todas', 'Recibidas', 'Enviadas', 'Pendientes']);
        $this->assertSame(4, $page->viewData('rows')->count());
        $this->assertSame(['todas' => 4, 'recibidas' => 0, 'enviadas' => 0, 'pendientes' => 4], $page->viewData('tabCounts'));
        $this->assertSame(4, substr_count($page->getContent(), '<button type="button" data-inbox-conciliable-choice="1"'));
        $this->assertSame(8, substr_count($page->getContent(), 'data-table-column-trigger'));
        $this->assertSame(0, HospitalConciliationSubmission::count());
        $this->get($url('enviadas'))->assertOk()->assertViewHas('rows', fn ($rows) => $rows->isEmpty());

        $this->inboxSubmission();
        $received = $this->inboxSubmission();
        $this->get($url('recibidas'))->assertOk()->assertViewHas('rows', fn ($rows) => $rows->count() === 3
            && $rows->pluck('submission.id')->unique()->all() === [$received->id]);
        $sent = $this->inboxSubmission();
        $sent->update(['direction' => 'sent', 'snapshot' => [$sent->snapshot[0]], 'mixture_count' => 1, 'conciliable_count' => 1]);
        $page = $this->get($url())->assertOk();
        $this->assertSame(['todas' => 4, 'recibidas' => 2, 'enviadas' => 1, 'pendientes' => 1], $page->viewData('tabCounts'));
        $this->get($url('enviadas'))->assertOk()->assertViewHas('rows', fn ($rows) => $rows->count() === 1
            && $rows->first()['mixture']['id'] === 11 && $rows->first()['cells']['conciliation_status'] === 'Enviada');
        $this->get($url('pendientes'))->assertOk()->assertViewHas('rows', fn ($rows) => $rows->count() === 1
            && $rows->first()['mixture']['id'] === 4 && $rows->first()['cells']['conciliation_status'] === 'Pendiente');

        // A report for another hospital cannot change the local request's exchange state.
        $foreign = $this->inboxSubmission();
        $foreign->update(['hospital_id' => 2]);
        $this->get($url('enviadas'))->assertOk()->assertViewHas('rows', fn ($rows) => $rows->count() === 1
            && $rows->first()['submission']->id === $sent->id);
    }

    public function test_conciliation_can_edit_unsent_requests_with_permissions_audit_and_conflict_protection(): void
    {
        $switch = route('admin.instituciones.conciliaciones.request-conciliable', ['antibioticos', 2]);
        $price = route('admin.instituciones.conciliaciones.request-price', ['antibioticos', 2]);
        $this->patchJson($switch, ['conciliable' => false, 'previous' => null])->assertForbidden();
        $this->patchJson($price, ['precio_total' => '125.50', 'previous' => null])->assertForbidden();
        $this->actingAs($this->internalUser());
        $this->patchJson($switch, ['conciliable' => false, 'previous' => null])->assertOk()
            ->assertJsonPath('conciliable', 'No')->assertJsonPath('conciliation_status', 'Pendiente');
        $this->patchJson($price, ['precio_total' => '125.50', 'previous' => null])->assertOk()->assertJsonPath('precio_total', '125.50');
        $this->assertDatabaseHas('institution_billings', ['origen_tipo' => 'oncologica_mezcla', 'origen_id' => 2,
            'hospital_id' => 1, 'institucion_id' => 1, 'conciliable' => 'No', 'precio_total' => '125.50']);
        $this->patchJson($switch, ['conciliable' => true, 'previous' => null])->assertConflict();
        $this->patchJson($price, ['precio_total' => '50.00', 'previous' => null])->assertConflict();
        $this->patchJson($price, ['precio_total' => '-1', 'previous' => '125.50'])->assertUnprocessable();
        $this->patchJson(route('admin.instituciones.conciliaciones.request-price', ['antibioticos', 1]),
            ['precio_total' => '10', 'previous' => '500.00'])->assertNotFound();
        $this->patchJson(route('admin.instituciones.conciliaciones.request-price', ['oncologicos', 3]),
            ['precio_total' => '10', 'previous' => '500.00'])->assertNotFound();
        $movements = \App\Models\InstitutionBillingMovement::where('origen_id', 2)->get();
        $this->assertCount(2, $movements);
        foreach ($movements as $movement) {
            $this->assertSame('administration_conciliation', $movement->details['source']);
            $this->assertNull($movement->details['submission_id']);
            $this->assertSame($movement->from_stage, $movement->to_stage);
        }
        $this->assertSame(0, HospitalConciliationSubmission::count());
        $this->get(route('admin.instituciones.reportes', ['seccion' => 'conciliacion', 'bandeja' => 'pendientes',
            'columnas' => ['conciliable' => ['No']]]))->assertOk()
            ->assertViewHas('rows', fn ($rows) => $rows->count() === 1 && $rows->first()['cells']['price'] === '125.50');
    }

    public function test_billing_displays_the_same_conciliation_status_and_preserves_column_filter_alignment(): void
    {
        $this->withoutExceptionHandling();
        $received = $this->inboxSubmission();
        $received->update(['snapshot' => [$received->snapshot[0]]]);
        $sent = $this->inboxSubmission();
        $sent->update(['direction' => 'sent', 'snapshot' => [$sent->snapshot[1]]]);
        $foreign = $this->inboxSubmission();
        $foreign->update(['hospital_id' => 2]);
        \App\Models\InstitutionBilling::create(['origen_tipo' => 'oncologica_mezcla', 'origen_id' => 2,
            'hospital_id' => 1, 'institucion_id' => 1, 'conciliable' => 'No']);
        \App\Models\InstitutionBilling::create(['origen_tipo' => 'oncologica_mezcla', 'origen_id' => 4,
            'hospital_id' => 1, 'institucion_id' => 1, 'conciliable' => 'Conciliado']);
        $this->actingAs($this->internalUser());
        $expected = $this->get(route('admin.instituciones.reportes', ['seccion' => 'conciliacion']))->assertOk()
            ->viewData('rows')->mapWithKeys(fn ($row) => [
                $row['mixture']['kind'].':'.$row['mixture']['id'] => $row['cells']['conciliation_status'],
            ])->all();

        $pricing = \Mockery::mock(\App\Services\InstitutionBillingPricingService::class)->makePartial();
        $pricing->shouldReceive('priceOncoMix', 'priceNutritionRequest')->andReturn(['lines' => collect(), 'total_iva_included' => 100]);
        $controller = \Mockery::mock(\App\Http\Controllers\Admin\InstitucionBillingController::class,
            [$pricing, app(\App\Services\InstitutionBillingDueDateService::class)])->makePartial()->shouldAllowMockingProtectedMethods();
        $requests = $controller->conciliationRequests(null, null);
        $transform = new \ReflectionMethod($controller, 'transformRecord');
        $records = $requests['onco']->map(fn ($record) => $transform->invoke($controller, $record, 'onco', null))
            ->concat($requests['nutrition']->map(fn ($record) => $transform->invoke($controller, $record, 'nutri', null)));
        // Exercise the same status rendering in all billing tabs, independently of invoice stages.
        $controller->shouldReceive('buildMergedRecords')->andReturn($records);
        $this->app->instance(\App\Http\Controllers\Admin\InstitucionBillingController::class, $controller);
        foreach (['index', 'receivable', 'history'] as $section) {
            $page = $this->get(route('admin.instituciones.billing.'.$section))->assertOk();
            $actual = $page->viewData('records')->getCollection()->mapWithKeys(fn ($row) => [
                ($row['type'] === 'nutri' ? 'nutricionales' : $row['record']->solicitud->tipo_solicitud).':'.$row['record']->id => $row['conciliation_status'],
            ])->all();
            $this->assertEquals($expected, $actual);
            $this->assertEqualsCanonicalizing(['Pendiente', 'Recibida', 'Enviada', 'Conciliado'], array_values($actual));
            $dom = new \DOMDocument;
            @$dom->loadHTML('<?xml encoding="UTF-8">'.$page->getContent());
            $xpath = new \DOMXPath($dom);
            $headers = $xpath->query('//table[starts-with(@id,"billing-requests-table-")]/thead/tr/th');
            $this->assertStringContainsString('Estatus de', $headers[14]->textContent);
            $this->assertStringContainsString('conciliación', $headers[14]->textContent);
            foreach ($headers as $index => $header) {
                $button = $xpath->query('.//button[@data-column]', $header)->item(0);
                if ($button) $this->assertSame((string) $index, $button->getAttribute('data-column'));
            }
            foreach ($xpath->query('//tr[contains(@class,"js-billing-filter-row")]') as $row) {
                $cells = $xpath->query('./td', $row);
                $this->assertCount(count($headers), $cells);
                $this->assertContains($cells[14]->getAttribute('data-filter-value'), $expected);
            }
        }
        $this->postJson(route('admin.instituciones.billing.store'), [
            'institucion_id' => 1, 'hospital_id' => 1, 'origen_tipo' => 'oncologica_mezcla', 'origen_id' => 1, 'conciliable' => 'No',
        ])->assertOk()->assertJsonPath('conciliation_status', 'Enviada');
    }

    private function inboxSubmission(): HospitalConciliationSubmission
    {
        // Received report fixture: independent of the hospital's retired submission routes.
        return HospitalConciliationSubmission::create([
            'submission_key' => (string) Str::uuid(), 'hospital_id' => 1,
            'hospital_name' => 'Hospital de prueba', 'sender_name' => 'Remitente de prueba',
            'filters' => [], 'mixture_count' => 3, 'conciliable_count' => 3,
            'snapshot' => collect([['nutricionales', 11], ['oncologicos', 1], ['antibioticos', 2]])->map(fn ($item) => [
                'kind' => $item[0], 'id' => $item[1], 'conciliable' => true,
                'cells' => ['type' => $item[0], 'id' => (string) $item[1], 'request_id' => '1',
                    'institution' => 'Institucion de prueba', 'hospital' => 'Hospital de prueba',
                    'patient' => 'Paciente de prueba '.$item[1], 'date' => '2026-09-08 10:00', 'conciliable' => 'Sí'],
            ])->all(),
        ]);
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
