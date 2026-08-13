<?php

namespace Database\Seeders;

use App\Models\ExternalMixtureRequest;
use App\Models\Hospital;
use App\Models\Nutricionales\Category;
use App\Models\Nutricionales\Input;
use App\Models\Nutricionales\MedicineLaboratoryStock;
use App\Models\Nutricionales\NutriMedicineList;
use App\Models\Nutricionales\NutriMedicineListItem;
use App\Models\Nutricionales\NutritionMedicineCatalog;
use App\Models\Nutricionales\NutritionMedicinePresentation;
use App\Models\Nutricionales\Solicitud;
use App\Models\Oncologicos\Laboratory;
use App\Models\Oncologicos\MedicineBatch;
use App\Models\Oncologicos\MedicineList;
use App\Models\Oncologicos\MedicinePresentation;
use App\Models\Oncologicos\MedicinesCatalog;
use App\Models\Oncologicos\SolicitudOnco;
use App\Models\User;
use App\Services\Integrations\DrSam\ExternalMixtureMaterializer;
use App\Services\Integrations\DrSam\ExternalMixtureStatusService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class MixturesDemoSeeder extends Seeder
{
    public function run(): void
    {
        $laboratory = Laboratory::query()->updateOrCreate(
            ['nombre' => 'Central de Mezclas Demo'],
            ['estado' => 'Ciudad de Mexico', 'direccion' => 'Av. Salud 100', 'activo' => true]
        );

        $nptList = NutriMedicineList::query()->updateOrCreate(
            ['name' => 'Lista NPT Demostracion'],
            ['description' => 'Catalogo de demostracion para integracion Dr. Sam', 'is_active' => true, 'active_brands' => true]
        );
        $oncoList = MedicineList::query()->updateOrCreate(
            ['name' => 'Lista Oncologica Demostracion'],
            ['description' => 'Catalogo oncologico de demostracion', 'active_brands' => true, 'charge_by' => 'mg', 'show_label_lot_expiry' => true]
        );
        $hospital = Hospital::query()->updateOrCreate(
            ['external_code' => 'DRSAM-DEMO'],
            [
                'name' => 'Hospital General Demo Dr. Sam', 'adress' => 'Ciudad de Mexico',
                'laboratory_id' => $laboratory->id, 'nutri_medicine_list_id' => $nptList->id,
                'onco_medicine_list_id' => $oncoList->id, 'is_active' => true,
            ]
        );
        $operator = User::query()->updateOrCreate(
            ['username' => 'hospital.demo'],
            [
                'name' => 'Operador', 'lastname' => 'Hospital Demo', 'hospital_id' => $hospital->id,
                'password' => Hash::make('Mezclas2026!'), 'is_active' => true,
            ]
        );
        $role = Role::query()->firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);
        $operator->syncRoles([$role]);

        $nptItems = $this->nptCatalog($nptList, $laboratory);
        $this->nptOperationalSupplies($nptList, $laboratory);
        $oncoItems = $this->oncologyCatalog($oncoList, $laboratory);

        $requests = [
            $this->request($hospital, 1, 'npt', 'Claudia Beatriz Salinas Vega', 'PAC-100000001', $nptItems[0], 'Soporte nutricional postoperatorio'),
            $this->request($hospital, 2, 'npt', 'Guillermo Guerrero', 'PAC-100000002', $nptItems[1], 'Desnutricion moderada'),
            $this->request($hospital, 3, 'npt', 'Arturo Hernandez', 'PAC-100000003', $nptItems[0], 'Nutricion parenteral prolongada'),
            $this->request($hospital, 4, 'oncology', 'Mariana Torres Pineda', 'PAC-100000004', $oncoItems[0], 'Tratamiento oncologico ambulatorio'),
            $this->request($hospital, 5, 'oncology', 'Roberto Castillo Vega', 'PAC-100000005', $oncoItems[1], 'Protocolo de quimioterapia'),
        ];

        $materializer = app(ExternalMixtureMaterializer::class);
        foreach ($requests as $request) {
            $materializer->materialize($request);
        }

        $this->setNptState($requests[0], 'pendiente');
        $this->setNptState($requests[1], 'aprobada');
        $this->setNptState($requests[2], 'revisada', 'REM-NPT-DEMO-001');
        $this->setOncologyState($requests[3], 'aprobada');
        $this->setOncologyState($requests[4], 'revisada', 'REM-ONCO-DEMO-001');

        foreach ($requests as $request) {
            app(ExternalMixtureStatusService::class)->refresh($request->fresh());
        }
    }

    private function nptCatalog(NutriMedicineList $list, Laboratory $laboratory): array
    {
        $definitions = [
            ['GLUCOSE-50', 'Glucosa al 50%', 'GLUCOSE-50-500ML', 'Glucosa 50% 500 ml', 2.50],
            ['AMINO-10', 'Aminoacidos al 10%', 'AMINO-10-500ML', 'Aminoacidos 10% 500 ml', 3.20],
            ['LIPIDS-20', 'Lipidos al 20%', 'LIPIDS-20-250ML', 'Lipidos 20% 250 ml', 4.10],
        ];
        $items = [];
        foreach ($definitions as $index => [$code, $name, $presentationCode, $presentationName, $price]) {
            $category = Category::query()->firstOrCreate(['name' => $index === 2 ? 'Lipidos' : 'Macronutrientes']);
            $input = Input::query()->updateOrCreate(
                ['description' => $name, 'tipo_input' => 'adulto'],
                ['unidad' => 'ml', 'orden_enum' => $index + 1, 'category_id' => $category->id, 'mult' => 1, 'div' => 1, 'is_active' => true]
            );
            $catalog = NutritionMedicineCatalog::query()->updateOrCreate(
                ['external_code' => $code],
                ['denominacion_generica' => $name, 'category_id' => $category->id, 'input_id' => $input->id, 'is_active' => true]
            );
            $presentation = NutritionMedicinePresentation::query()->updateOrCreate(
                ['external_code' => $presentationCode],
                [
                    'nutrition_medicine_catalog_id' => $catalog->id, 'denominacion_comercial' => $presentationName,
                    'fabricante' => 'Laboratorio Demo', 'presentacion' => 'Frasco',
                    'presentacion_ml' => $index === 2 ? 250 : 500, 'is_available' => true,
                ]
            );
            NutriMedicineListItem::query()->updateOrCreate(
                ['nutri_medicine_list_id' => $list->id, 'nutrition_medicine_presentation_id' => $presentation->id],
                ['precio_ml' => $price]
            );
            MedicineLaboratoryStock::query()->updateOrCreate(
                [
                    'nutrition_medicine_presentation_id' => $presentation->id,
                    'laboratory_id' => $laboratory->id,
                    'lote' => 'NPT-DEMO-'.($index + 1),
                ],
                [
                    'frascos_iniciales' => 50, 'frascos_actuales' => 42 - $index,
                    'stock_ml_inicial' => 25000, 'stock_ml_actual' => 21000 - ($index * 1000),
                    'caducidad' => now()->addMonths(8 + $index)->toDateString(),
                    'fecha_ingreso' => now()->subMonth()->toDateString(), 'numero_factura' => 'FAC-NPT-DEMO', 'is_active' => true,
                ]
            );
            $items[] = ['product_code' => $code, 'presentation_code' => $presentationCode, 'quantity' => 100 + ($index * 50), 'unit' => 'ml'];
        }

        $packagingCategory = Category::query()->updateOrCreate(
            ['id' => 6],
            ['name' => 'Material de empaque']
        );
        $bagInput = Input::query()->updateOrCreate(
            ['description' => 'Bolsa EVA 2000 ml', 'tipo_input' => 'adulto'],
            [
                'unidad' => 'pieza', 'orden_enum' => 90, 'category_id' => $packagingCategory->id,
                'mult' => 1, 'div' => 1, 'is_active' => true,
            ]
        );
        $bagCatalog = NutritionMedicineCatalog::query()->updateOrCreate(
            ['external_code' => 'BOLSA-EVA-2000ML'],
            [
                'denominacion_generica' => 'Bolsa EVA 2000 ml', 'category_id' => $packagingCategory->id,
                'input_id' => $bagInput->id, 'is_active' => true,
            ]
        );
        $bagPresentation = NutritionMedicinePresentation::query()->updateOrCreate(
            ['external_code' => 'BOLSA-EVA-2000ML-STD'],
            [
                'nutrition_medicine_catalog_id' => $bagCatalog->id,
                'denominacion_comercial' => 'Bolsa EVA 2000 ml', 'fabricante' => 'Laboratorio Demo',
                'presentacion' => 'Pieza', 'presentacion_ml' => 2000, 'is_available' => true,
            ]
        );
        NutriMedicineListItem::query()->updateOrCreate(
            ['nutri_medicine_list_id' => $list->id, 'nutrition_medicine_presentation_id' => $bagPresentation->id],
            ['precio_ml' => 35]
        );
        MedicineLaboratoryStock::query()->updateOrCreate(
            [
                'nutrition_medicine_presentation_id' => $bagPresentation->id,
                'laboratory_id' => $laboratory->id,
                'lote' => 'EVA-DEMO-001',
            ],
            [
                'frascos_iniciales' => 100, 'frascos_actuales' => 95,
                'stock_ml_inicial' => 100, 'stock_ml_actual' => 95,
                'caducidad' => now()->addYear()->toDateString(),
                'fecha_ingreso' => now()->subMonth()->toDateString(),
                'numero_factura' => 'FAC-EVA-DEMO', 'is_active' => true,
            ]
        );

        return $items;
    }

    private function oncologyCatalog(MedicineList $list, Laboratory $laboratory): array
    {
        $definitions = [
            ['DOCETAXEL', 'Docetaxel', 'DOCETAXEL-80MG', 80, 12.50],
            ['PACLITAXEL', 'Paclitaxel', 'PACLITAXEL-100MG', 100, 10.80],
        ];
        $items = [];
        foreach ($definitions as $index => [$code, $name, $presentationCode, $content, $price]) {
            $catalog = MedicinesCatalog::query()->updateOrCreate(
                ['external_code' => $code],
                ['denominacion' => $name, 'requires_infusor' => false, 'state' => true, 'conc_min' => 0.1, 'conc_max' => 1.0]
            );
            $presentation = MedicinePresentation::query()->updateOrCreate(
                ['external_code' => $presentationCode],
                [
                    'catalog_id' => $catalog->id, 'presentacion' => "Frasco {$content} mg", 'contenido_valor' => $content,
                    'contenido_unidad' => 'mg', 'marca' => 'Marca Demo', 'fabricante' => 'Onco Demo',
                    'volumen_diluyente' => 100, 'is_available' => true, 'virtual_stock' => 30, 'precio_frasco' => $content * $price,
                ]
            );
            $list->presentations()->syncWithoutDetaching([$presentation->id => [
                'charge_by' => 'mg', 'precio' => $content * $price, 'precio_mg_override' => $price,
            ]]);
            MedicineBatch::query()->updateOrCreate(
                ['laboratory_id' => $laboratory->id, 'medicine_presentation_id' => $presentation->id, 'lote' => 'ONCO-DEMO-'.($index + 1)],
                [
                    'caducidad' => now()->addMonths(10 + $index)->toDateString(), 'fecha_ingreso' => now()->subWeeks(2)->toDateString(),
                    'stock_inicial' => 30, 'stock_actual' => 24 - $index, 'stock_reservado' => 2,
                    'costo_unitario' => $content * $price, 'is_current' => true, 'is_active' => true,
                ]
            );
            $items[] = ['product_code' => $code, 'presentation_code' => $presentationCode, 'quantity' => 75 + ($index * 25), 'unit' => 'mg'];
        }

        return $items;
    }

    private function nptOperationalSupplies(NutriMedicineList $list, Laboratory $laboratory): void
    {
        $category = Category::query()->firstOrCreate(['name' => 'Material complementario']);

        $definitions = [
            [37, 'Agua inyectable', 'AGUA-INYECTABLE', 'AGUA-INYECTABLE-1000ML', 'Agua inyectable 1000 ml', 1000, 'ml', 0.05],
            [40, 'Equipo de infusion', 'EQUIPO-INFUSION', 'EQUIPO-INFUSION-STD', 'Equipo de infusion', 1, 'pieza', 25],
        ];

        foreach ($definitions as [$id, $name, $code, $presentationCode, $commercialName, $capacity, $unit, $price]) {
            $input = Input::query()->find($id);
            if (! $input) {
                $input = Input::unguarded(fn () => Input::query()->create([
                    'id' => $id, 'description' => $name, 'tipo_input' => 'adulto',
                    'unidad' => $unit, 'orden_enum' => $id, 'category_id' => $category->id,
                    'mult' => 1, 'div' => 1, 'is_active' => true,
                ]));
            } else {
                $input->update([
                    'description' => $name, 'tipo_input' => 'adulto', 'unidad' => $unit,
                    'orden_enum' => $id, 'category_id' => $category->id,
                    'mult' => 1, 'div' => 1, 'is_active' => true,
                ]);
            }

            $catalog = NutritionMedicineCatalog::query()->updateOrCreate(
                ['external_code' => $code],
                ['denominacion_generica' => $name, 'category_id' => $category->id, 'input_id' => $input->id, 'is_active' => true]
            );
            $presentation = NutritionMedicinePresentation::query()->updateOrCreate(
                ['external_code' => $presentationCode],
                [
                    'nutrition_medicine_catalog_id' => $catalog->id, 'denominacion_comercial' => $commercialName,
                    'fabricante' => 'Laboratorio Demo', 'presentacion' => $unit === 'ml' ? 'Frasco' : 'Pieza',
                    'presentacion_ml' => $capacity, 'is_available' => true,
                ]
            );
            NutriMedicineListItem::query()->updateOrCreate(
                ['nutri_medicine_list_id' => $list->id, 'nutrition_medicine_presentation_id' => $presentation->id],
                ['precio_ml' => $price]
            );
            MedicineLaboratoryStock::query()->updateOrCreate(
                [
                    'nutrition_medicine_presentation_id' => $presentation->id,
                    'laboratory_id' => $laboratory->id,
                    'lote' => $id === 37 ? 'AGUA-DEMO-001' : 'EQUIPO-DEMO-001',
                ],
                [
                    'frascos_iniciales' => 100, 'frascos_actuales' => 95,
                    'stock_ml_inicial' => $id === 37 ? 100000 : 100,
                    'stock_ml_actual' => $id === 37 ? 95000 : 95,
                    'caducidad' => now()->addYear()->toDateString(),
                    'fecha_ingreso' => now()->subMonth()->toDateString(),
                    'numero_factura' => 'FAC-OPERATIVOS-DEMO', 'is_active' => true,
                ]
            );
        }
    }

    private function request(Hospital $hospital, int $sequence, string $type, string $patient, string $patientCode, array $item, string $diagnosis): ExternalMixtureRequest
    {
        $payload = [
            'local_external_id' => sprintf('10000000-0000-4000-8000-%012d', $sequence),
            'medical_unit_code' => $hospital->external_code, 'catalog_type' => $type,
            'catalog_version' => 'demo-2026-08',
            'patient' => ['external_id' => $patientCode, 'name' => $patient],
            'clinical' => [
                'service' => $type === 'npt' ? 'Nutricion clinica' : 'Oncologia', 'diagnosis' => $diagnosis,
                'doctor' => 'Dr. Carter Jimmy', 'required_at' => now()->addDays($sequence)->toIso8601String(),
                'notes' => 'Solicitud de demostracion para validar el flujo integral.',
                'format' => [
                    'registration' => 'REG-DEMO-'.str_pad((string) $sequence, 3, '0', STR_PAD_LEFT),
                    'weight' => 60 + $sequence, 'sex' => $sequence % 2 ? 'Femenino' : 'Masculino',
                    'birth_date' => '1985-04-18', 'route' => 'Central', 'infusion_hours' => 24,
                    'total_volume' => 1200, 'npt_type' => 'Individualizada',
                    'delivery_at' => now()->addDays($sequence)->setTime(10, 0)->toIso8601String(),
                    'destination_hospital' => $hospital->name, 'doctor_name' => 'Dr. Carter Jimmy',
                    'professional_license' => 'CED-DEMO-2026',
                    'medications' => [['medication' => $item['product_code'], 'dilution_volume' => 100, 'infusion_minutes' => 60]],
                ],
            ],
            'items' => [$item],
        ];

        return ExternalMixtureRequest::query()->updateOrCreate(
            ['remote_request_id' => sprintf('20000000-0000-4000-8000-%012d', $sequence)],
            [
                'local_external_id' => $payload['local_external_id'], 'hospital_id' => $hospital->id,
                'catalog_type' => $type, 'catalog_version' => 'demo-2026-08', 'status' => 'received',
                'payload_hash' => hash('sha256', json_encode($payload)), 'payload' => $payload, 'received_at' => now()->subDays(6 - $sequence),
            ]
        );
    }

    private function setNptState(ExternalMixtureRequest $external, string $status, ?string $remission = null): void
    {
        Solicitud::query()->whereKey($external->fresh()->materialized_id)->update(['estado' => $status, 'remision' => $remission]);
    }

    private function setOncologyState(ExternalMixtureRequest $external, string $status, ?string $remission = null): void
    {
        $request = SolicitudOnco::query()->find($external->fresh()->materialized_id);
        $requestStatus = match ($status) {
            'aprobada', 'preparada', 'revisada' => 'enproceso',
            'entregada' => 'finalizada',
            default => $status,
        };
        $request?->update(['estado' => $requestStatus, 'remision' => $remission]);
        $request?->mezclas()->update(['estado' => $status, 'remision' => $remission]);
    }
}
