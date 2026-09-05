<?php

namespace Tests\Feature;

use App\Livewire\Nutricionales\InspeccionNutricional;
use App\Livewire\Oncologicos\InspeccionMezcla;
use App\Models\Hospital;
use App\Models\Institucion;
use App\Models\Nutricionales\Solicitud;
use App\Support\MixtureWorkflowContext;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

class MixtureWorkflowContextTest extends TestCase
{
    public function test_labels_include_destination_and_handle_missing_institutions(): void
    {
        $hospital = new Hospital(['name' => 'Hospital de prueba']);
        $hospital->setRelation('instituciones', collect([
            new Institucion(['nombre' => 'Institucion A']),
            new Institucion(['nombre' => 'Institucion B']),
            new Institucion(['nombre' => 'Institucion A']),
        ]));
        $this->assertSame('Mezcla #38 | Institución: Institucion A, Institucion B | Hospital: Hospital de prueba', MixtureWorkflowContext::label(38, $hospital));
        $hospital->setRelation('instituciones', collect());
        $this->assertSame('Mezcla #39 | Institución: Sin institución | Hospital: Hospital de prueba', MixtureWorkflowContext::label(39, $hospital));
        $this->assertSame('Mezcla #40 | Institución: Sin institución | Hospital: Sin hospital', MixtureWorkflowContext::label(40, null));
    }

    public function test_inspection_headers_follow_each_record_without_changing_its_state(): void
    {
        $this->assertSame('sqlite', DB::getDriverName());
        $nutritionTable = (new Solicitud())->getTable();
        $inspectionTable = (new \App\Models\Oncologicos\InspeccionMezcla())->getTable();
        foreach ([
            'hospitals' => ['name'], 'clientes' => ['nombre'], 'cliente_hospital' => ['hospital_id', 'cliente_id'],
            'users' => ['name', 'hospital_id'], 'solicitud_oncos' => ['hospital_id', 'estado'],
            'mezclas' => ['solicitud_id', 'lote', 'estado'], $nutritionTable => ['user_id', 'lote', 'estado'],
            $inspectionTable => ['mezcla_id'], 'inspeccion_nutricionales' => ['solicitud_id'],
        ] as $table => $columns) {
            Schema::create($table, function (Blueprint $schema) use ($columns) {
                $schema->id();
                foreach ($columns as $column) $schema->string($column)->nullable();
            });
        }
        foreach ([1, 2] as $id) {
            DB::table('hospitals')->insert(['id' => $id, 'name' => 'Hospital '.$id]);
            DB::table('clientes')->insert(['id' => $id, 'nombre' => 'Institucion '.$id]);
            DB::table('cliente_hospital')->insert(['hospital_id' => $id, 'cliente_id' => $id]);
            DB::table('users')->insert(['id' => $id, 'hospital_id' => $id, 'name' => 'Usuario '.$id]);
            DB::table('solicitud_oncos')->insert(['id' => $id, 'hospital_id' => $id, 'estado' => 'preparada']);
            DB::table('mezclas')->insert(['id' => $id, 'solicitud_id' => $id, 'estado' => 'preparada', 'lote' => 'L'.$id]);
            DB::table($nutritionTable)->insert(['id' => $id, 'user_id' => $id, 'estado' => 'preparada', 'lote' => 'N'.$id]);
        }
        foreach ([InspeccionMezcla::class, InspeccionNutricional::class] as $component) {
            $test = Livewire::test($component);
            foreach ([1, 2] as $id) {
                $label = 'Mezcla #'.$id.' | Institución: Institucion '.$id.' | Hospital: Hospital '.$id;
                $test->call('abrirModalInspeccion', [$id])
                    ->assertSet('mixtureContext', $label)
                    ->assertSee($label)
                    ->assertSee('Cerrar inspección', false)
                    ->set('mostrarModalInspeccion', false);
            }
        }
        $this->assertSame(['preparada'], DB::table('mezclas')->distinct()->pluck('estado')->all());
        $this->assertSame(['preparada'], DB::table($nutritionTable)->distinct()->pluck('estado')->all());
        $this->assertSame(0, DB::table($inspectionTable)->count());
        $this->assertSame(0, DB::table('inspeccion_nutricionales')->count());
    }
}
