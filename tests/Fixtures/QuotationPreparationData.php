<?php

namespace Tests\Fixtures;

use App\Models\RequestQuotation;
use App\Services\RequestQuotationCaptureService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;

class QuotationPreparationData
{
    public static function seed()
    {
        $user = RequestQuotationCaptureData::seed();
        foreach (['oncologicos_solicitudes_store', 'nutricionales_solicitudes_store'] as $permission) {
            $user->givePermissionTo(Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']));
        }
        foreach ([
            'hospitals' => ['antibiotic_medicine_list_id'],
            'medicines_catalog' => ['requires_infusor', 'conc_min', 'conc_max'],
            'medicine_presentations' => ['precio_frasco'],
            'inputs' => ['category_id', 'mult', 'div'],
            'medicine_oncos' => ['catalog_id', 'precio'],
            'administration_routes' => ['name'],
            'administration_route_medicine_catalog' => ['medicine_catalog_id', 'administration_route_id'],
            'infusors' => ['nombre_generico', 'nombre_comercial', 'is_active'],
            'solicitud_oncos' => ['servicio', 'sexo', 'edad', 'peso', 'cama', 'piso', 'registro_paciente', 'fecha_nacimiento', 'diagnostico', 'alergias', 'observaciones', 'nombre_medico', 'cedula_medico', 'remision'],
            'mezclas' => ['tiempo_infusion', 'set_infusion', 'infusor_id'],
            'mezcla_medicamentos' => ['medicamento_id', 'diluyente_id', 'via_administracion_id', 'charge_by', 'precio_mg_snapshot', 'precio_ml_snapshot', 'requires_infusor_snapshot', 'conc_min_snapshot', 'conc_max_snapshot'],
            'solicitud_inputs' => ['solicitud_id', 'input_id', 'nutrition_medicine_presentation_id', 'valor', 'valor_ml', 'valor_sobrellenado', 'precio_ml'],
            'solicitud_patients' => ['servicio', 'cama', 'piso', 'registro', 'diagnostico', 'peso', 'fecha_nacimiento', 'sexo', 'edad'],
            'solicitud_details' => ['via_administracion', 'tiempo_infusion_min', 'sobrellenado_ml', 'volumen_total', 'npt', 'observaciones', 'nombre_medico', 'cedula', 'velocidad_infusion', 'hospital_destino', 'suma_volumen', 'volumen_total_final'],
        ] as $name => $columns) {
            $exists = Schema::hasTable($name);
            $columns = array_filter($columns, fn ($column) => !$exists || !Schema::hasColumn($name, $column));
            $callback = function (Blueprint $table) use ($columns, $exists) {
                if (!$exists) { $table->id(); $table->timestamps(); }
                foreach ($columns as $column) $table->string($column)->nullable();
            };
            if ($exists) Schema::table($name, $callback); else Schema::create($name, $callback);
        }
        foreach (['hospital_id', 'request_quotation_id', 'quotation_pricing_snapshot'] as $column) {
            if (Schema::hasColumn('solicituds', $column)) Schema::table('solicituds', fn (Blueprint $table) => $table->dropColumn($column));
        }
        (require database_path('migrations/2026_09_23_000001_link_quotations_to_preparation_requests.php'))->up();
        (require database_path('migrations/2026_09_23_000002_allow_nutrition_mixtures_per_quotation.php'))->up();
        DB::table('administration_routes')->insert(['id' => 1, 'name' => 'Intravenosa']);
        DB::table('administration_route_medicine_catalog')->insert(['medicine_catalog_id' => 1, 'administration_route_id' => 1]);
        DB::table('inputs')->insert(['id' => 1, 'description' => 'Aminoacidos', 'category_id' => 1, 'mult' => 10, 'div' => 1]);
        DB::table('nutrition_medicines_catalog')->where('id', 1)->update(['input_id' => 1]);
        DB::table('medicine_lists')->insert(['id' => 2, 'name' => 'Lista antibioticos', 'catalog_category' => 'antibioticos', 'charge_by' => 'mg']);
        DB::table('medicines_catalog')->insert(['id' => 2, 'denominacion' => 'Antibiotico de prueba', 'state' => 1, 'catalog_category' => 'antibioticos']);
        DB::table('medicine_presentations')->insert(['id' => 3, 'catalog_id' => 2, 'presentacion' => 'Frasco 500 mg', 'contenido_valor' => 500, 'contenido_unidad' => 'mg', 'is_available' => 1]);
        DB::table('medicine_list_presentation')->insert(['medicine_list_id' => 2, 'medicine_presentation_id' => 3, 'precio' => 250, 'charge_by' => 'mg']);
        DB::table('hospitals')->where('id', 1)->update(['antibiotic_medicine_list_id' => 2]);
        DB::table('diluent_medicine_catalog')->insert(['medicine_catalog_id' => 2, 'diluent_id' => 1]);
        DB::table('administration_route_medicine_catalog')->insert(['medicine_catalog_id' => 2, 'administration_route_id' => 1]);
        $user->forceFill(['hospital_id' => 2])->save();
        return $user;
    }

    public static function quotation(string $category = 'oncologicos', string $unit = 'mg', int $count = 1, ?float $specialPrice = null, bool $grouped = false): RequestQuotation
    {
        if ($category === 'nutricionales') DB::table('nutri_medicine_list_items')->where('nutrition_medicine_presentation_id', 1)->update(['charge_by' => $unit]);
        else DB::table('medicine_list_presentation')->update(['charge_by' => $unit]);
        $item = ['presentation_id' => $category === 'antibioticos' ? 3 : 1,
            $unit === 'frasco' ? 'bottle_count' : 'concentration' => $unit === 'frasco' ? 2 : 50];
        if ($specialPrice !== null) $item['unit_price_override'] = $specialPrice;
        $data = ['flow' => 'commercial', 'category' => $category, 'hospital_id' => 1, 'institution_id' => 1,
            'no_commercial_relationship' => false, 'items' => array_fill(0, $count, $item),
            'patient_name' => 'Paciente', 'patient_paternal_surname' => 'Prueba', 'patient_platform_id' => 'TEST-001'];
        if ($grouped) {
            $data['mixture_count'] = $count;
            foreach ($data['items'] as $index => &$row) $row['mixture_number'] = $index + 1;
            unset($row);
        }
        $capture = app(RequestQuotationCaptureService::class)->capture(auth()->user(), $data, true);
        unset($capture['pricing_token']);
        return RequestQuotation::forceCreate($capture + ['created_by' => auth()->id(), 'status' => 'autorizada', 'authorized_at' => now(), 'authorized_by' => auth()->id()]);
    }

    public static function presentationGroups(string $category = 'oncologicos', string $unit = 'frasco'): RequestQuotation
    {
        $baseId = $category === 'antibioticos' ? 3 : 1;
        $catalogId = $category === 'antibioticos' ? 2 : 1;
        $listId = $category === 'antibioticos' ? 2 : 1;
        DB::table('medicine_presentations')->where('id', $baseId)->update(['presentacion' => 'Frasco 100 mg', 'contenido_valor' => 100]);
        DB::table('medicines_catalog')->insert(['id' => 10, 'denominacion' => 'Segundo medicamento', 'state' => 1, 'catalog_category' => $category]);
        foreach ([10 => [$catalogId, 50], 11 => [10, 25]] as $id => [$catalog, $content]) {
            DB::table('medicine_presentations')->insert(['id' => $id, 'catalog_id' => $catalog, 'presentacion' => 'Frasco '.$content.' mg',
                'contenido_valor' => $content, 'contenido_unidad' => 'mg', 'is_available' => 1]);
            DB::table('medicine_list_presentation')->insert(['medicine_list_id' => $listId, 'medicine_presentation_id' => $id,
                'precio' => 100, 'charge_by' => $unit]);
        }
        DB::table('diluent_medicine_catalog')->insert(['medicine_catalog_id' => 10, 'diluent_id' => 1]);
        DB::table('administration_route_medicine_catalog')->insert(['medicine_catalog_id' => 10, 'administration_route_id' => 1]);
        $quote = self::quotation($category, $unit, 2, null, true);
        $data = $quote->clinical_data;
        $quantity = $unit === 'frasco' ? 'bottle_count' : 'concentration';
        // Interleaved presentations ensure grouping never detaches a dose from its quoted price.
        $data['items'] = [
            ['presentation_id' => $baseId, 'mixture_number' => 1, $quantity => $unit === 'frasco' ? 2 : 40, 'unit_price_override' => 3],
            ['presentation_id' => 11, 'mixture_number' => 1, $quantity => $unit === 'frasco' ? 3 : 20, 'unit_price_override' => 7],
            ['presentation_id' => 10, 'mixture_number' => 1, $quantity => $unit === 'frasco' ? 1 : 10, 'unit_price_override' => 5],
            ['presentation_id' => 11, 'mixture_number' => 2, $quantity => $unit === 'frasco' ? 1 : 5, 'unit_price_override' => 9],
        ];
        $capture = app(RequestQuotationCaptureService::class)->capture(auth()->user(), $data, true);
        unset($capture['pricing_token']);
        $quote->forceFill($capture)->save();
        return $quote;
    }

    public static function payload(string $category = 'oncologicos', int $count = 1): array
    {
        if ($category === 'nutricionales') return [
            'nombre_paciente' => 'Paciente', 'apellidos_paciente' => 'Prueba', 'servicio' => 'Servicio de prueba',
            'fecha_nacimiento' => '1990-01-01', 'peso' => 60, 'sexo' => 'Femenino', 'via_administracion' => 'Central',
            'tiempo_infusion_min' => 60, 'npt' => 'ADULT', 'fecha_hora_entrega' => now()->addDay()->format('Y-m-d\TH:i'),
            'nombre_medico' => 'Medico de prueba', 'cedula' => 'TEST-123', 'quoted_volumes' => [50],
        ];
        return [
            'tipo_solicitud' => $category, 'paciente_nombre' => 'Paciente Prueba', 'servicio' => 'Servicio de prueba',
            'registro' => 'TEST-001', 'sexo' => 'F', 'fecha_nacimiento' => '1990-01-01', 'peso' => 60,
            'piso' => '1', 'cama' => '2', 'diagnostico' => 'Diagnostico de prueba',
            'medico_nombre' => 'Medico de prueba', 'medico_cedula' => 'TEST-123', 'cantidad_mezclas' => $count,
            'mezclas' => json_encode(array_fill(0, $count, ['volumen_dilucion' => 100, 'tiempo_infusion' => 60,
                'fechas_entrega' => [now()->addDay()->format('Y-m-d\TH:i')], 'medicamentos' => [[
                    'medicamento_id' => $category === 'antibioticos' ? 2 : 1, 'dosis' => 50, 'diluyente_id' => 1, 'via_administracion_id' => 1,
                ]]])),
        ];
    }
}
