<?php

namespace Tests\Feature;

use App\Exports\RequestQuotationsExport;
use App\Models\RequestQuotation;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Spatie\Permission\Models\Role;
use Tests\Fixtures\RequestQuotationCaptureData as Fixture;
use Tests\TestCase;

class RequestQuotationCaptureTest extends TestCase
{
    private User $user;
    protected function setUp(): void
    {
        parent::setUp();
        $this->user = Fixture::seed();
        $this->actingAs($this->user);
        Storage::fake('local');
    }
    private function store(array $payload) { return $this->postJson(route('admin.solicitudes.cotizacion.store'), $payload); }
    private function catalogOptions(string $category = 'oncologicos', int $hospital = 1)
    {
        return $this->getJson(route('admin.solicitudes.cotizacion.options', ['category' => $category, 'hospital_id' => $hospital]));
    }
    public function test_buttons_modal_and_only_assigned_active_catalog_products_are_available(): void
    {
        $this->get(route('admin.solicitudes.cotizacion.index'))->assertOk()->assertSee('Nueva Cotizacion')->assertSee('Exportar a Excel')->assertSee('quotation-capture-title');
        $this->catalogOptions()->assertOk()->assertJsonCount(1, 'products')->assertJsonPath('products.0.id', 1);
        $this->catalogOptions('nutricionales')->assertOk()->assertJsonCount(1, 'products')->assertJsonCount(3, 'charges');
        $this->catalogOptions('antibioticos')->assertUnprocessable();
        $this->catalogOptions('oncologicos', 2)->assertUnprocessable()->assertJsonValidationErrors('hospital_id');
    }
    public function test_oncology_prices_are_server_calculated_and_creation_is_idempotent_without_preparation_or_stock_changes(): void
    {
        $payload = Fixture::payload() + ['total' => 1, 'status' => 'autorizada', 'authorized_by' => 2, 'price_list_id' => 999, 'created_by' => 2];
        $counts = array_map(fn ($table) => DB::table($table)->count(), ['solicitud_oncos', 'solicituds', 'mezclas', 'medicine_batch_movements']);
        $this->store($payload)->assertOk()->assertJsonPath('total', '524.00')->assertJsonPath('status', 'borrador');
        $quote = RequestQuotation::latest('id')->first();
        $this->assertSame(1, $quote->price_list_id);
        $this->assertEquals($this->user->id, $quote->created_by);
        $this->assertNull($quote->authorized_by);
        $this->assertSame('Solucion salina', $quote->clinical_data['rows'][0]['diluent_name']);
        $this->assertSame(4, $quote->pricing_snapshot['mixtures']);
        $this->assertSame($counts, array_map(fn ($table) => DB::table($table)->count(), ['solicitud_oncos', 'solicituds', 'mezclas', 'medicine_batch_movements']));
        $this->store($payload)->assertOk()->assertJsonPath('folio', $quote->folio);
        $this->assertSame(6, RequestQuotation::count());
    }
    public function test_nutrition_preserves_ml_and_overfill_and_excludes_service_and_supplies_from_components(): void
    {
        $this->store(Fixture::payload('nutricionales'))->assertOk()->assertJsonPath('total', '145.00');
        $quote = RequestQuotation::latest('id')->first();
        $this->assertEquals(50, $quote->clinical_data['volume_total_ml']);
        $this->assertEquals(60, $quote->clinical_data['components'][0]['quoted_volume_ml']);
        $this->assertCount(4, $quote->pricing_snapshot['lines']);
        DB::table('nutri_medicine_list_items')->where('nutrition_medicine_presentation_id', 1)->update(['charge_by' => 'frasco']);
        $this->store(Fixture::payload('nutricionales'))->assertOk()->assertJsonPath('total', '225.00');
    }
    public function test_oncology_bottle_and_ml_charge_modes_use_presentation_content(): void
    {
        DB::table('medicine_list_presentation')->where('medicine_presentation_id', 1)->update(['charge_by' => 'frasco']);
        $this->store(Fixture::payload())->assertOk()->assertJsonPath('total', '988.00');
        DB::table('medicine_list_presentation')->where('medicine_presentation_id', 1)->update(['charge_by' => 'ml', 'precio_ml_override' => 3]);
        $this->store(Fixture::payload())->assertOk()->assertJsonPath('total', '129.60');
    }
    public function test_unavailable_products_changed_prices_and_forged_institution_are_validated_again_on_save(): void
    {
        $data = Fixture::payload(); $data['rows'][0]['presentation_id'] = 2;
        $this->store($data)->assertUnprocessable()->assertJsonValidationErrors('rows.0.presentation_id');
        $data = Fixture::payload(); $data['institution_id'] = 2;
        $this->store($data)->assertUnprocessable()->assertJsonValidationErrors('institution_id');
        DB::table('medicine_list_presentation')->where('medicine_presentation_id', 1)->update(['precio' => 400]);
        $this->store(Fixture::payload())->assertOk()->assertJsonPath('total', '988.00');
        DB::table('medicines_catalog')->where('id', 1)->update(['state' => 0]);
        $this->store(Fixture::payload())->assertUnprocessable()->assertJsonValidationErrors('rows.0.presentation_id');
        $data = Fixture::payload('nutricionales'); $data['components'][0]['presentation_id'] = 2;
        $this->store($data)->assertUnprocessable()->assertJsonValidationErrors('components.0.presentation_id');
        DB::table('nutri_medicine_lists')->update(['is_active' => 0]);
        $this->catalogOptions('nutricionales')->assertUnprocessable();
    }
    public function test_list_inactive_products_are_hidden_and_rejected_without_changing_existing_quotes(): void
    {
        $before = RequestQuotation::findOrFail(1)->getAttributes();
        foreach (['oncologicos' => 'medicine_list_presentation', 'nutricionales' => 'nutri_medicine_list_items'] as $category => $table) {
            $key = $category === 'oncologicos' ? 'medicine_presentation_id' : 'nutrition_medicine_presentation_id';
            DB::table($table)->where($key, 1)->update(['is_active' => false]);
            $this->catalogOptions($category)->assertOk()->assertJsonCount(0, 'products');
            $this->store(Fixture::payload($category))->assertUnprocessable()
                ->assertJsonValidationErrors($category === 'oncologicos' ? 'rows.0.presentation_id' : 'components.0.presentation_id');
            $this->assertSame($before, RequestQuotation::findOrFail(1)->getAttributes());
            DB::table($table)->where($key, 1)->update(['is_active' => true]);
            $this->catalogOptions($category)->assertOk()->assertJsonCount(1, 'products');
        }
    }

    public function test_invalid_capture_is_not_saved(): void
    {
        foreach ([
            ['rows.0.dose_mg', -1], ['rows.0.boluses_per_day', 0], ['birth_date', '2099-01-01'],
            ['rows.0.deliveries', ['2026-09-21T09:00']], ['rows.0.deliveries', ['2026-09-23T09:00', '2026-09-23T10:00']],
            ['rows.0.diluent_id', 999], ['rows.0.untrusted', 'extra'], ['doctor_name', ''], ['category', 'antibioticos'],
        ] as [$key, $value]) {
            $data = Fixture::payload(); data_set($data, $key, $value);
            $this->store($data)->assertUnprocessable();
        }
        $data = Fixture::payload('nutricionales'); $data['components'] = [];
        $this->store($data)->assertUnprocessable()->assertJsonValidationErrors('components');
        $data = Fixture::payload('nutricionales'); $data['components'][] = $data['components'][0];
        $this->store($data)->assertUnprocessable();
        $this->assertSame(5, RequestQuotation::count());
    }
    public function test_draft_can_be_edited_then_sent_and_only_prodifem_can_authorize(): void
    {
        $this->store(Fixture::payload())->assertOk();
        $quote = RequestQuotation::latest('id')->first();
        $this->getJson(route('admin.solicitudes.cotizacion.show', $quote))->assertOk()->assertJsonPath('editable', true)->assertJsonMissingPath('attachment_path');
        $data = Fixture::payload(); $data['action'] = 'send'; $data['patient_name'] = 'Paciente actualizado';
        $this->putJson(route('admin.solicitudes.cotizacion.update', $quote), $data)->assertOk()->assertJsonPath('status', 'enviada');
        $this->assertNotNull($quote->refresh()->sent_at);
        $this->assertSame('Paciente actualizado', $quote->patient_name);
        $this->putJson(route('admin.solicitudes.cotizacion.update', $quote), $data)->assertForbidden();
        $this->user->syncRoles(Role::firstOrCreate(['name' => 'Cliente', 'guard_name' => 'web']));
        $this->post(route('admin.solicitudes.cotizacion.authorize', $quote))->assertForbidden();
        $this->user->syncRoles(Role::findByName('Admin'));
        $this->post(route('admin.solicitudes.cotizacion.authorize', $quote))->assertRedirect();
        $this->assertSame('autorizada', $quote->refresh()->status);
    }
    public function test_hospital_and_permission_boundaries_cover_catalog_save_detail_and_attachment(): void
    {
        $this->user->syncRoles(Role::firstOrCreate(['name' => 'Cliente', 'guard_name' => 'web']));
        $this->catalogOptions('oncologicos', 2)->assertForbidden();
        $data = Fixture::payload(); $data['hospital_id'] = 2;
        $this->store($data)->assertForbidden();
        $this->getJson(route('admin.solicitudes.cotizacion.show', 5))->assertForbidden();
        $this->getJson(route('admin.solicitudes.cotizacion.attachment', 5))->assertForbidden();
        $this->user->revokePermissionTo('oncologicos_solicitudes_create');
        $this->store(Fixture::payload())->assertForbidden();
        $this->catalogOptions()->assertForbidden();
        $this->store(Fixture::payload('nutricionales'))->assertOk();
    }
    public function test_private_attachment_is_validated_stored_and_downloaded_with_access_control(): void
    {
        $payload = Fixture::payload();
        $payload['attachment'] = UploadedFile::fake()->createWithContent('firma.pdf', "%PDF-1.4\n%%EOF");
        $this->store($payload)->assertOk();
        $quote = RequestQuotation::latest('id')->first();
        Storage::disk('local')->assertExists($quote->attachment_path);
        $this->get(route('admin.solicitudes.cotizacion.attachment', $quote))->assertOk()->assertDownload('firma-'.$quote->folio.'.pdf');
        $payload = Fixture::payload(); $payload['attachment'] = UploadedFile::fake()->create('grande.pdf', 5121, 'application/pdf');
        $this->store($payload)->assertUnprocessable()->assertJsonValidationErrors('attachment');
        $payload['attachment'] = UploadedFile::fake()->createWithContent('mal.php', '<?php echo 1;');
        $this->store($payload)->assertUnprocessable()->assertJsonValidationErrors('attachment');
    }
    public function test_excel_respects_filters_and_scope_and_preserves_numbers_and_formula_like_text(): void
    {
        $excel = Excel::getFacadeRoot();
        Excel::fake();
        $this->get(route('admin.solicitudes.cotizacion.export', ['tipo' => 'oncologicos', 'estado' => 'enviadas', 'hospital_id' => 1]))->assertOk();
        Excel::assertDownloaded('Cotizaciones-'.now()->format('Y-m-d').'.xlsx', fn (RequestQuotationsExport $export) => $export->collection()->modelKeys() === [2]);
        $this->user->syncRoles(Role::firstOrCreate(['name' => 'Cliente', 'guard_name' => 'web']));
        $this->get(route('admin.solicitudes.cotizacion.export', ['hospital_id' => 2]))->assertOk();
        Excel::assertDownloaded('Cotizaciones-'.now()->format('Y-m-d').'.xlsx', fn (RequestQuotationsExport $export) => $export->collection()->isEmpty());
        $quote = RequestQuotation::find(2); $quote->patient_name = '=1+1';
        $bytes = $excel->raw(new RequestQuotationsExport(collect([$quote])), \Maatwebsite\Excel\Excel::XLSX);
        $file = tempnam(sys_get_temp_dir(), 'quote-export');
        try {
            file_put_contents($file, $bytes);
            $sheet = IOFactory::load($file)->getActiveSheet();
            $this->assertSame('s', $sheet->getCell('F2')->getDataType());
            $this->assertSame('=1+1', $sheet->getCell('F2')->getValue());
            $this->assertSame('Vendedor', $sheet->getCell('G1')->getValue());
            $this->assertSame('Sin asignar', $sheet->getCell('G2')->getValue());
            $this->assertEquals(210, $sheet->getCell('I2')->getValue());
        } finally { unlink($file); }
    }
}
