<?php

namespace Tests\Feature;

use App\Models\ExternalMixtureRequest;
use App\Models\Hospital;
use App\Models\Nutricionales\Category;
use App\Models\Nutricionales\Input;
use App\Models\Nutricionales\NutriMedicineList;
use App\Models\Nutricionales\NutriMedicineListItem;
use App\Models\Nutricionales\NutritionMedicineCatalog;
use App\Models\Nutricionales\NutritionMedicinePresentation;
use App\Models\Oncologicos\MedicineList;
use App\Models\Oncologicos\MedicinePresentation;
use App\Models\Oncologicos\MedicinesCatalog;
use App\Models\User;
use App\Services\Integrations\DrSam\ExternalMixtureMaterializer;
use App\Services\Integrations\DrSam\ExternalMixtureStatusService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ExternalMixtureMaterializerTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_materializes_an_external_npt_request_once_without_touching_inventory(): void
    {
        $category = Category::query()->create(['name' => 'Macronutrientes']);
        $input = Input::query()->create([
            'description' => 'Glucosa 50%',
            'tipo_input' => 'adulto',
            'orden_enum' => 1,
            'unidad' => 'ml',
            'mult' => 1,
            'div' => 1,
            'category_id' => $category->id,
        ]);
        $list = NutriMedicineList::query()->create(['name' => 'Lista Hospital', 'is_active' => true]);
        $catalog = NutritionMedicineCatalog::query()->create([
            'external_code' => 'GLUCOSE-50',
            'denominacion_generica' => 'Glucosa 50%',
            'category_id' => $category->id,
            'input_id' => $input->id,
            'is_active' => true,
        ]);
        $presentation = NutritionMedicinePresentation::query()->create([
            'external_code' => 'GLUCOSE-50-500ML',
            'nutrition_medicine_catalog_id' => $catalog->id,
            'denominacion_comercial' => 'Glucosa 500 ml',
            'presentacion_ml' => 500,
            'is_available' => true,
        ]);
        NutriMedicineListItem::query()->create([
            'nutri_medicine_list_id' => $list->id,
            'nutrition_medicine_presentation_id' => $presentation->id,
            'precio_ml' => 2.5,
        ]);
        $hospital = Hospital::query()->create([
            'external_code' => 'CBTA-HOSP-NPT',
            'name' => 'Hospital NPT',
            'adress' => 'Direccion',
            'nutri_medicine_list_id' => $list->id,
            'is_active' => true,
        ]);
        $user = User::query()->create([
            'name' => 'Usuario',
            'lastname' => 'Operativo',
            'username' => 'operativo.npt',
            'password' => Hash::make('secret'),
            'hospital_id' => $hospital->id,
            'is_active' => true,
        ]);
        $payload = [
            'local_external_id' => (string) Str::uuid(),
            'medical_unit_code' => 'CBTA-HOSP-NPT',
            'catalog_type' => 'npt',
            'catalog_version' => 'npt-v1',
            'patient' => ['external_id' => 'PAC-1', 'name' => 'Claudia Salinas'],
            'clinical' => [
                'service' => 'Nutricion clinica',
                'diagnosis' => 'Soporte nutricional',
                'doctor' => 'Dr. Carter',
                'format' => [
                    'registration' => 'REG-1',
                    'weight' => 65,
                    'sex' => 'Femenino',
                    'birth_date' => '1985-04-18',
                    'route' => 'Central',
                    'infusion_hours' => 24,
                    'total_volume' => 1200,
                    'npt_type' => 'Individualizada',
                    'delivery_at' => '2026-08-06 10:00:00',
                    'professional_license' => 'CED-1',
                ],
            ],
            'items' => [[
                'product_code' => 'GLUCOSE-50',
                'presentation_code' => 'GLUCOSE-50-500ML',
                'quantity' => 100,
                'unit' => 'ml',
            ]],
        ];
        $external = ExternalMixtureRequest::query()->create([
            'remote_request_id' => (string) Str::uuid(),
            'local_external_id' => $payload['local_external_id'],
            'hospital_id' => $hospital->id,
            'catalog_type' => 'npt',
            'catalog_version' => 'npt-v1',
            'status' => 'received',
            'payload_hash' => hash('sha256', json_encode($payload)),
            'payload' => $payload,
            'received_at' => now(),
        ]);

        $service = app(ExternalMixtureMaterializer::class);
        $this->assertTrue($service->materialize($external));
        $this->assertTrue($service->materialize($external->fresh()));

        $this->assertDatabaseCount('solicituds', 1);
        $this->assertDatabaseCount('solicitud_inputs', 1);
        $this->assertDatabaseHas('solicitud_inputs', [
            'nutrition_medicine_presentation_id' => $presentation->id,
            'valor_ml' => 100,
            'precio_ml' => 250,
        ]);
        $this->assertDatabaseHas('external_mixture_requests', [
            'id' => $external->id,
            'status' => 'materialized',
            'materialized_type' => 'npt',
        ]);
        $this->assertDatabaseCount('medicine_stock_movements', 0);

        \App\Models\Nutricionales\Solicitud::query()->firstOrFail()->update(['estado' => 'revisada', 'remision' => 'REM-NPT-1001']);
        $refreshed = app(ExternalMixtureStatusService::class)->refresh($external->fresh());
        $this->assertSame('ready', $refreshed->status);
        $this->assertSame('revisada', $refreshed->status_details['source_status']);
        $this->assertSame('consume_on_operational_approval', $refreshed->status_details['inventory']['policy']);
        $this->assertSame('validated', $refreshed->status_details['inventory']['stage']);
        $this->assertFalse($refreshed->status_details['inventory']['consumed']);
        $this->assertTrue($refreshed->status_details['remission']['available']);
        $this->assertSame('REM-NPT-1001', $refreshed->status_details['remission']['number']);

        $laboratory = \App\Models\Oncologicos\Laboratory::query()->create([
            'nombre' => 'Laboratorio NPT',
            'estado' => 'Activo',
            'activo' => true,
        ]);
        $stock = \App\Models\Nutricionales\MedicineLaboratoryStock::query()->create([
            'nutrition_medicine_presentation_id' => $presentation->id,
            'laboratory_id' => $laboratory->id,
            'stock_ml_inicial' => 500,
            'stock_ml_actual' => 400,
            'frascos_iniciales' => 1,
            'frascos_actuales' => 0.8,
            'lote' => 'LOT-NPT-1',
            'caducidad' => '2027-08-06',
            'is_active' => true,
        ]);
        \App\Models\Nutricionales\MedicineStockMovement::query()->create([
            'medicine_laboratory_stock_id' => $stock->id,
            'user_id' => $user->id,
            'tipo' => 'salida',
            'cantidad_ml' => 100,
            'stock_antes' => 500,
            'stock_despues' => 400,
            'reference_type' => 'Solicitud',
            'reference_id' => \App\Models\Nutricionales\Solicitud::query()->firstOrFail()->id,
        ]);
        $consumed = app(ExternalMixtureStatusService::class)->refresh($external->fresh());
        $this->assertSame('consumed', $consumed->status_details['inventory']['stage']);
        $this->assertTrue($consumed->status_details['inventory']['consumed']);
        $this->assertSame(1, $consumed->status_details['inventory']['movement_count']);
        $this->assertEquals(100.0, $consumed->status_details['inventory']['consumed_quantity']);

        Sanctum::actingAs($user, ['requests:read']);
        $this->get('/api/internal/v1/mixture-requests/'.$external->remote_request_id.'/remission')
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_it_materializes_an_external_oncology_request_once_without_touching_inventory(): void
    {
        $list = MedicineList::query()->create([
            'name' => 'Lista Oncologica Hospital',
            'active_brands' => true,
            'charge_by' => 'mg',
        ]);
        $catalog = MedicinesCatalog::query()->create([
            'external_code' => 'DOCETAXEL',
            'denominacion' => 'Docetaxel',
            'denominacion_comercial' => 'Docetaxel',
            'requires_infusor' => false,
            'state' => true,
            'conc_min' => 0.3,
            'conc_max' => 0.74,
        ]);
        $presentation = MedicinePresentation::query()->create([
            'external_code' => 'DOCETAXEL-80MG',
            'catalog_id' => $catalog->id,
            'presentacion' => 'Frasco 80 mg',
            'contenido_valor' => 80,
            'contenido_unidad' => 'mg',
            'volumen_diluyente' => 100,
            'is_available' => true,
        ]);
        $list->presentations()->attach($presentation->id, [
            'charge_by' => 'mg',
            'precio_mg_override' => 12.5,
        ]);
        $hospital = Hospital::query()->create([
            'external_code' => 'CBTA-HOSP-ONCO',
            'name' => 'Hospital Oncologico',
            'adress' => 'Direccion',
            'onco_medicine_list_id' => $list->id,
            'is_active' => true,
        ]);
        User::query()->create([
            'name' => 'Usuario',
            'lastname' => 'Oncologia',
            'username' => 'operativo.onco',
            'password' => Hash::make('secret'),
            'hospital_id' => $hospital->id,
            'is_active' => true,
        ]);
        $payload = [
            'local_external_id' => (string) Str::uuid(),
            'medical_unit_code' => 'CBTA-HOSP-ONCO',
            'catalog_type' => 'oncology',
            'catalog_version' => 'oncology-v1',
            'patient' => ['external_id' => 'PAC-ONCO-1', 'name' => 'Claudia Salinas'],
            'clinical' => [
                'service' => 'Oncologia',
                'diagnosis' => 'Cancer de mama',
                'doctor' => 'Dr. Carter',
                'required_at' => '2026-08-06 10:00:00',
                'format' => [
                    'weight' => 65,
                    'sex' => 'Femenino',
                    'birth_date' => '1985-04-18',
                    'professional_license' => 'CED-2',
                    'medications' => [[
                        'dilution_volume' => 250,
                        'infusion_minutes' => 90,
                    ]],
                ],
            ],
            'items' => [[
                'product_code' => 'DOCETAXEL',
                'presentation_code' => 'DOCETAXEL-80MG',
                'quantity' => 75,
                'unit' => 'mg',
            ]],
        ];
        $external = ExternalMixtureRequest::query()->create([
            'remote_request_id' => (string) Str::uuid(),
            'local_external_id' => $payload['local_external_id'],
            'hospital_id' => $hospital->id,
            'catalog_type' => 'oncology',
            'catalog_version' => 'oncology-v1',
            'status' => 'received',
            'payload_hash' => hash('sha256', json_encode($payload)),
            'payload' => $payload,
            'received_at' => now(),
        ]);

        $service = app(ExternalMixtureMaterializer::class);
        $this->assertTrue($service->materialize($external));
        $this->assertTrue($service->materialize($external->fresh()));

        $this->assertDatabaseCount('solicitud_oncos', 1);
        $this->assertDatabaseCount('mezclas', 1);
        $this->assertDatabaseCount('mezcla_medicamentos', 1);
        $this->assertDatabaseHas('mezcla_medicamentos', [
            'nombre_medicamento' => 'Docetaxel',
            'dosis' => 75,
            'charge_by' => 'mg',
            'precio_mg_snapshot' => 12.5,
        ]);
        $this->assertDatabaseHas('external_mixture_requests', [
            'id' => $external->id,
            'status' => 'materialized',
            'materialized_type' => 'oncology',
        ]);
        $this->assertDatabaseCount('medicine_stock_movements', 0);

        $mixture = \App\Models\Oncologicos\Mezcla::query()->firstOrFail();
        config()->set('services.dr_sam.webhook_url', 'https://dr-sam.test/api/integrations/cbta/mixture-status');
        config()->set('services.dr_sam.webhook_secret', 'webhook-secret');
        Http::fake(['https://dr-sam.test/*' => Http::response(['accepted' => true])]);
        foreach (['aprobada' => 'authorized', 'preparada' => 'preparing', 'revisada' => 'ready', 'entregada' => 'delivered'] as $source => $canonical) {
            $mixture->update(['estado' => $source]);
            $refreshed = app(ExternalMixtureStatusService::class)->refresh($external->fresh());
            $this->assertSame($canonical, $refreshed->status);
            $this->assertSame('validated', $refreshed->status_details['inventory']['stage']);
            $this->assertFalse($refreshed->status_details['inventory']['consumed']);
        }
        Http::assertSentCount(4);
    }
}
