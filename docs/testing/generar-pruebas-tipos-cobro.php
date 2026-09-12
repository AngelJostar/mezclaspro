<?php

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\Nutricionales\Solicitud as NutritionRequest;
use App\Models\Oncologicos\Mezcla;
use App\Services\InstitutionBillingPricingService;

require dirname(__DIR__, 2).'/vendor/autoload.php';

$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$now = now();
$suffix = '20260912';

$upsertId = function (string $table, array $identity, array $values) use ($now): int {
    $query = DB::table($table);

    foreach ($identity as $column => $value) {
        $query->where($column, $value);
    }

    $id = $query->value('id');
    $payload = array_merge($values, ['updated_at' => $now]);

    if ($id) {
        DB::table($table)->where('id', $id)->update($payload);

        return (int) $id;
    }

    return (int) DB::table($table)->insertGetId(array_merge($identity, $payload, [
        'created_at' => $now,
    ]));
};

$result = DB::transaction(function () use ($now, $suffix, $upsertId): array {
    $oncoSource = DB::table('medicine_list_presentation as mlp')
        ->join('medicine_presentations as mp', 'mp.id', '=', 'mlp.medicine_presentation_id')
        ->join('medicine_oncos as mo', 'mo.catalog_id', '=', 'mp.catalog_id')
        ->join('medicines_catalog as mc', 'mc.id', '=', 'mp.catalog_id')
        ->where('mlp.medicine_list_id', 1)
        ->where('mp.is_available', true)
        ->where('mp.contenido_unidad', 'mg')
        ->where('mp.contenido_valor', '>', 0)
        ->orderByDesc('mlp.precio')
        ->limit(2)
        ->get([
            'mo.id as medicine_onco_id',
            'mp.id as presentation_id',
            'mp.catalog_id',
            'mc.denominacion',
            'mp.presentacion',
            'mp.contenido_valor',
            'mlp.precio',
        ]);

    if ($oncoSource->count() < 2) {
        throw new RuntimeException('No hay dos presentaciones oncológicas válidas para generar las pruebas.');
    }

    $nutritionSource = DB::table('nutri_medicine_list_items as nli')
        ->join('nutrition_medicine_presentations as nmp', 'nmp.id', '=', 'nli.nutrition_medicine_presentation_id')
        ->join('nutrition_medicines_catalog as nmc', 'nmc.id', '=', 'nmp.nutrition_medicine_catalog_id')
        ->where('nli.nutri_medicine_list_id', 2)
        ->whereNotNull('nmc.input_id')
        ->where('nmp.presentacion_ml', '>', 1)
        ->whereNotIn('nmc.input_id', [40, 41])
        ->orderByDesc('nli.precio_ml')
        ->limit(2)
        ->get([
            'nmp.id as presentation_id',
            'nmc.input_id',
            'nmc.denominacion_generica',
            'nmp.presentacion',
            'nmp.presentacion_ml',
            'nli.precio_ml',
        ]);

    if ($nutritionSource->count() < 2) {
        throw new RuntimeException('No hay dos presentaciones nutricionales válidas para generar las pruebas.');
    }

    $nutritionServiceSource = DB::table('nutri_medicine_list_items as nli')
        ->join('nutrition_medicine_presentations as nmp', 'nmp.id', '=', 'nli.nutrition_medicine_presentation_id')
        ->join('nutrition_medicines_catalog as nmc', 'nmc.id', '=', 'nmp.nutrition_medicine_catalog_id')
        ->where('nli.nutri_medicine_list_id', 2)
        ->where('nmc.input_id', 41)
        ->first(['nmp.id as presentation_id']);

    if (! $nutritionServiceSource) {
        throw new RuntimeException('No existe la presentación del servicio de mezclado nutricional.');
    }

    $diluentId = DB::table('diluents')
        ->where('denominacion_generica', 'GLUCOSA 5%')
        ->value('id') ?? DB::table('diluents')->orderBy('id')->value('id');

    if (! $diluentId) {
        throw new RuntimeException('No existe un diluyente para las pruebas oncológicas.');
    }

    $oncoListId = $upsertId('medicine_lists', ['name' => "PRUEBAS COBRO ONCO {$suffix}"], [
        'laboratory_id' => 1,
        'warehouse_id' => 1,
        'primary_warehouse_id' => 1,
        'description' => 'Lista aislada para validar cobro por frasco y por miligramo.',
        'catalog_category' => 'oncologicos',
        'active_brands' => false,
        'charge_by' => 'mg',
        'show_label_lot_expiry' => false,
        'has_contract' => false,
        'has_mixing_service' => true,
        'mixing_service_price' => 440,
    ]);

    foreach ($oncoSource->values() as $index => $source) {
        $chargeBy = $index === 0 ? 'frasco' : 'mg';
        $pricePerMg = round((float) $source->precio / (float) $source->contenido_valor, 4);
        DB::table('medicine_list_presentation')->updateOrInsert([
            'medicine_list_id' => $oncoListId,
            'medicine_presentation_id' => $source->presentation_id,
        ], [
            'charge_by' => $chargeBy,
            'precio' => $source->precio,
            'precio_mg_override' => $pricePerMg,
            'precio_ml_override' => null,
            'iva_desglosado' => false,
            'descripcion_remision' => $source->denominacion.' '.$source->presentacion,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    $nutritionListId = $upsertId('nutri_medicine_lists', ['name' => "PRUEBAS COBRO NUTRI {$suffix}"], [
        'laboratory_id' => 1,
        'warehouse_id' => 1,
        'primary_warehouse_id' => 1,
        'description' => 'Lista aislada para validar cobro por frasco y por mililitro.',
        'is_active' => true,
        'active_brands' => false,
        'has_contract' => false,
    ]);

    foreach ($nutritionSource->values() as $index => $source) {
        DB::table('nutri_medicine_list_items')->updateOrInsert([
            'nutri_medicine_list_id' => $nutritionListId,
            'nutrition_medicine_presentation_id' => $source->presentation_id,
        ], [
            'precio_ml' => $source->precio_ml,
            'charge_by' => $index === 0 ? 'frasco' : 'ml',
            'descripcion_remision' => $source->denominacion_generica.' '.$source->presentacion,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    DB::table('nutri_medicine_list_items')->updateOrInsert([
        'nutri_medicine_list_id' => $nutritionListId,
        'nutrition_medicine_presentation_id' => $nutritionServiceSource->presentation_id,
    ], [
        'precio_ml' => 440,
        'charge_by' => 'ml',
        'descripcion_remision' => 'Servicio de mezclado',
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    $hospitalId = $upsertId('hospitals', ['internal_key' => "PRUEBAS-COBRO-{$suffix}"], [
        'name' => 'HOSPITAL PRUEBAS TIPOS DE COBRO',
        'short_name' => 'PRUEBAS COBRO',
        'country' => 'México',
        'adress' => 'Datos ficticios para validación local',
        'is_active' => true,
        'access_is_active' => true,
        'service_oncology' => true,
        'service_nutrition' => true,
        'laboratory_id' => 1,
        'onco_medicine_list_id' => $oncoListId,
        'nutri_medicine_list_id' => $nutritionListId,
    ]);

    $userId = DB::table('users')->where('username', 'pruebas.cobro')->value('id');
    if ($userId) {
        DB::table('users')->where('id', $userId)->update([
            'hospital_id' => $hospitalId,
            'is_active' => true,
            'updated_at' => $now,
        ]);
    } else {
        $userId = DB::table('users')->insertGetId([
            'name' => 'Usuario',
            'lastname' => 'Pruebas Cobro',
            'username' => 'pruebas.cobro',
            'password' => Hash::make('Pruebas2026!'),
            'hospital_id' => $hospitalId,
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    $createOncoCase = function (object $source, string $mode, string $code, float $dose) use ($hospitalId, $userId, $now, $upsertId, $diluentId): array {
        $requestId = $upsertId('solicitud_oncos', ['remision' => $code], [
            'user_id' => $userId,
            'hospital_id' => $hospitalId,
            'tipo_solicitud' => 'oncologicos',
            'servicio' => 'ONCOLOGÍA - PRUEBA DE COBRO',
            'nombre_paciente' => "PACIENTE PRUEBA ONCO {$mode}",
            'sexo' => 'F',
            'edad' => 40,
            'peso' => 65,
            'alergias' => 'SIN ALERGIAS',
            'registro_paciente' => $code,
            'fecha_nacimiento' => '1986-01-15',
            'diagnostico' => 'DATOS FICTICIOS PARA VALIDAR REMISIÓN',
            'fecha_entrega' => $now->copy()->addDay(),
            'observaciones' => "PRUEBA CONTROLADA: COBRO POR ".strtoupper($mode),
            'nombre_medico' => 'MÉDICO DE PRUEBA',
            'cedula_medico' => 'TEST-2026',
            'estado' => 'aprobada',
        ]);

        $mixtureId = $upsertId('mezclas', ['remision' => $code], [
            'solicitud_id' => $requestId,
            'estado' => 'revisada',
            'lote' => 'LOTE-'.$code,
            'volumen_dilucion' => 250,
            'tiempo_infusion' => '60',
            'set_infusion' => false,
            'fecha_entrega' => $now->copy()->addDay(),
        ]);

        $pricePerMg = round((float) $source->precio / (float) $source->contenido_valor, 4);
        $upsertId('mezcla_medicamentos', [
            'mezcla_id' => $mixtureId,
            'denominacion_snapshot' => $source->denominacion,
        ], [
            'medicamento_id' => $source->medicine_onco_id,
            'nombre_medicamento' => $source->denominacion,
            'requires_infusor_snapshot' => false,
            'dosis' => $dose,
            'diluyente_id' => $diluentId,
            'charge_by' => $mode,
            'precio_mg_snapshot' => $mode === 'mg' ? $pricePerMg : null,
            'precio_ml_snapshot' => null,
        ]);

        return ['solicitud_id' => $requestId, 'mezcla_id' => $mixtureId, 'remision' => $code];
    };

    $oncoBottle = $createOncoCase($oncoSource[0], 'frasco', "PR-ONCO-F-{$suffix}", (float) $oncoSource[0]->contenido_valor * 1.5);
    $oncoMg = $createOncoCase($oncoSource[1], 'mg', "PR-ONCO-MG-{$suffix}", (float) $oncoSource[1]->contenido_valor / 2);

    $createNutritionCase = function (object $source, string $mode, string $code, float $volume) use ($userId, $now, $upsertId): array {
        $patientId = $upsertId('solicitud_patients', ['registro' => $code], [
            'nombre_paciente' => 'PACIENTE PRUEBA',
            'apellidos_paciente' => "NUTRI {$mode}",
            'servicio' => 'NUTRICIÓN - PRUEBA DE COBRO',
            'diagnostico' => 'DATOS FICTICIOS PARA VALIDAR REMISIÓN',
            'fecha_nacimiento' => '1990-02-20',
            'edad' => '36',
            'peso' => 60,
            'sexo' => 'Femenino',
        ]);

        $existingRequest = DB::table('solicituds')->where('remision', $code)->first();
        $detailId = $existingRequest?->solicitud_detail_id;
        if ($detailId) {
            DB::table('solicitud_details')->where('id', $detailId)->update([
                'volumen_total' => $volume,
                'suma_volumen' => $volume,
                'volumen_total_final' => $volume,
                'observaciones' => "PRUEBA CONTROLADA: COBRO POR ".strtoupper($mode),
                'updated_at' => $now,
            ]);
        } else {
            $detailId = DB::table('solicitud_details')->insertGetId([
                'via_administracion' => 'Central',
                'tiempo_infusion_min' => 60,
                'volumen_total' => $volume,
                'suma_volumen' => $volume,
                'volumen_total_final' => $volume,
                'npt' => 'ADULT',
                'nombre_medico' => 'MÉDICO DE PRUEBA',
                'cedula' => 'TEST-2026',
                'fecha_hora_entrega' => $now->copy()->addDay(),
                'observaciones' => "PRUEBA CONTROLADA: COBRO POR ".strtoupper($mode),
                'suma_volumen_sobrellenado' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $requestId = $upsertId('solicituds', ['remision' => $code], [
            'user_id' => $userId,
            'solicitud_detail_id' => $detailId,
            'solicitud_patient_id' => $patientId,
            'is_active' => true,
            'estado' => 'revisada',
            'fecha_hora_preparacion' => $now,
            'lote' => 'LOTE-'.$code,
        ]);

        $upsertId('solicitud_inputs', [
            'solicitud_id' => $requestId,
            'input_id' => $source->input_id,
        ], [
            'nutrition_medicine_presentation_id' => $source->presentation_id,
            'lote' => 'LOTE-'.$code,
            'caducidad' => $now->copy()->addYear()->toDateString(),
            'valor' => $volume,
            'valor_ml' => $volume,
            'precio_ml' => $source->precio_ml,
        ]);

        return ['solicitud_id' => $requestId, 'remision' => $code];
    };

    $nutritionBottle = $createNutritionCase(
        $nutritionSource[0],
        'frasco',
        "PR-NUTRI-F-{$suffix}",
        (float) $nutritionSource[0]->presentacion_ml * 2.4
    );
    $nutritionMl = $createNutritionCase(
        $nutritionSource[1],
        'ml',
        "PR-NUTRI-ML-{$suffix}",
        (float) $nutritionSource[1]->presentacion_ml * 0.6
    );

    return compact(
        'hospitalId',
        'userId',
        'oncoListId',
        'nutritionListId',
        'oncoBottle',
        'oncoMg',
        'nutritionBottle',
        'nutritionMl'
    );
});

$pricing = app(InstitutionBillingPricingService::class);
$result['verification'] = collect([
    'onco_frasco' => $pricing->priceOncoMix(Mezcla::findOrFail($result['oncoBottle']['mezcla_id'])),
    'onco_mg' => $pricing->priceOncoMix(Mezcla::findOrFail($result['oncoMg']['mezcla_id'])),
    'nutri_frasco' => $pricing->priceNutritionRequest(NutritionRequest::findOrFail($result['nutritionBottle']['solicitud_id'])),
    'nutri_ml' => $pricing->priceNutritionRequest(NutritionRequest::findOrFail($result['nutritionMl']['solicitud_id'])),
])->map(fn (array $summary) => [
    'line' => $summary['lines']->first(),
    'service_total' => $summary['service_total'],
    'total_iva_included' => $summary['total_iva_included'],
])->all();

echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE).PHP_EOL;
