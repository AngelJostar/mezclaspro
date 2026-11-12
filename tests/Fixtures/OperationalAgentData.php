<?php

namespace Tests\Fixtures;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB, Schema};

class OperationalAgentData
{
    public static function seed(): User
    {
        $user = UnifiedRequestExportData::seed();
        $user->syncRoles('Super Admin');
        HospitalToolsData::addBilling();
        foreach (['users' => ['is_active'], 'hospitals' => ['is_active', 'access_is_active'], 'clientes' => ['is_active']] as $table => $columns) {
            Schema::table($table, function (Blueprint $t) use ($columns) { foreach ($columns as $column) $t->boolean($column)->default(true); });
        }
        Schema::table('users', fn (Blueprint $t) => $t->unsignedBigInteger('institution_id')->nullable());
        Schema::table('hospitals', fn (Blueprint $t) => $t->string('email')->nullable());
        foreach ([
            '2026_09_08_000003_create_ai_agents_table.php', '2026_09_11_000001_add_is_active_to_ai_agents_table.php',
            '2026_09_14_000005_add_agent_execution.php', '2026_09_16_000001_add_integration_key_to_ai_agents.php',
            '2026_11_04_000001_create_ai_agent_access_grants.php',
            '2026_08_19_000001_add_compensation_fields_to_institution_billings_table.php',
            '2026_09_15_130000_create_hospital_conciliation_submissions.php',
            '2026_09_15_150000_add_confirmation_to_hospital_conciliation_submissions.php',
            '2026_10_06_180000_add_direction_to_hospital_conciliation_submissions.php',
            '2026_10_06_190000_create_conciliation_periods.php', '2026_10_07_120000_add_creation_batch_to_conciliation_periods.php',
            '2026_10_07_150000_create_conciliation_period_decisions.php', '2026_10_07_160100_add_request_version_to_period_decisions.php',
            '2026_11_12_000001_create_operational_agent_tables.php',
        ] as $file) (require database_path('migrations/'.$file))->up();
        DB::table('cliente_hospital')->insert(['hospital_id' => 2, 'cliente_id' => 1]);
        DB::table('mezclas')->whereIn('id', [1, 2, 3, 4])->update(['remision' => DB::raw("'REM-' || id")]);
        DB::table('solicituds')->where('id', 11)->update(['remision' => 'NPT-11']);
        (new \Database\Seeders\OperationalAgentsSeeder())->run();
        self::nutrition();
        return $user->refresh();
    }

    public static function nutrition(): void
    {
        foreach (['solicitud_patients' => ['registro', 'peso', 'fecha_nacimiento'],
            'solicitud_details' => ['volumen_total', 'npt', 'via_administracion', 'nombre_medico', 'tiempo_infusion_min']] as $table => $columns) {
            Schema::table($table, function (Blueprint $t) use ($columns) { foreach ($columns as $column) $t->string($column)->nullable(); });
        }
        Schema::create('inputs', function (Blueprint $t) { $t->id(); $t->string('description'); $t->string('unidad'); $t->integer('category_id'); });
        Schema::create('solicitud_inputs', function (Blueprint $t) { $t->id(); $t->unsignedBigInteger('solicitud_id'); $t->unsignedBigInteger('input_id'); $t->string('valor')->nullable(); $t->string('valor_ml')->nullable(); });
        DB::table('inputs')->insert([
            ['id'=>1,'description'=>'Aminoácidos','unidad'=>'g/Kg','category_id'=>1],
            ['id'=>2,'description'=>'Dextrosa','unidad'=>'g/Kg','category_id'=>2],
            ['id'=>3,'description'=>'Sodio','unidad'=>'mEq/Kg','category_id'=>4],
        ]);
        DB::table('solicitud_patients')->update(['nombre_paciente'=>'Paciente','apellidos_paciente'=>'Sintético','registro'=>'EXP-PRUEBA','peso'=>'60','fecha_nacimiento'=>'1980-03-05']);
        DB::table('solicitud_details')->update(['volumen_total'=>'1000','npt'=>'ADULT','via_administracion'=>'Central','nombre_medico'=>'Médico de prueba','tiempo_infusion_min'=>'24']);
        DB::table('solicituds')->update(['estado'=>'entregada']);
        DB::table('solicitud_patients')->insert(['id'=>3,'nombre_paciente'=>'Paciente','apellidos_paciente'=>'Sintético','registro'=>'EXP-PRUEBA','peso'=>'62','fecha_nacimiento'=>'1980-03-05']);
        DB::table('solicitud_details')->insert(['id'=>3,'fecha_hora_entrega'=>'2026-09-10 15:00:00','volumen_total'=>'2000','npt'=>'ADULT','via_administracion'=>'Central','nombre_medico'=>'Médico de prueba','tiempo_infusion_min'=>'24']);
        DB::table('solicituds')->insert(['id'=>13,'hospital_id'=>1,'user_id'=>1,'solicitud_patient_id'=>3,'solicitud_detail_id'=>3,'estado'=>'entregada','remision'=>'NPT-13']);
        foreach ([11=>[100,200,80],12=>[999,999,999],13=>[120,200,null]] as $id=>$values) {
            foreach ($values as $index=>$value) if ($value !== null) DB::table('solicitud_inputs')->insert(['solicitud_id'=>$id,'input_id'=>$index+1,'valor'=>$value,'valor_ml'=>100]);
        }
    }
}
