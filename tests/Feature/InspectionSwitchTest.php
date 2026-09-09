<?php

namespace Tests\Feature;

use App\Livewire\Oncologicos\InspeccionMezcla;
use App\Models\InspectionWaste;
use App\Models\Oncologicos\InspeccionMezcla as Inspection;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\Fixtures\InspectionWorkflow;
use Tests\TestCase;

class InspectionSwitchTest extends TestCase
{
    private const ANSWERS = [
        'esta_rotulado' => 1, 'medicamento' => 1, 'volumen_medicamento' => 1,
        'sello_seguridad' => 1, 'esta_roto' => 0, 'contenido_homogeneo' => 1,
        'presenta_turbidez' => 0, 'aprueba_contenedor' => 1, 'numero_lote' => 1,
        'dosis_volumen_total' => 1, 'rubrica_preparador' => 1, 'presenta_fugas' => 0,
        'coloracion_apropiada' => 1, 'presenta_particulas' => 0, 'aprueba_contenido' => 1,
    ];

    protected function setUp(): void
    {
        parent::setUp();
        InspectionWorkflow::seed();
    }

    public function test_pending_inspection_defaults_match_the_table_without_saving_them(): void
    {
        $before = Inspection::firstOrFail()->getAttributes();
        $modal = Livewire::test(InspeccionMezcla::class)->call('abrirModalInspeccion', 1);
        foreach (self::ANSWERS as $field => $answer) $modal->assertSet($field, (bool) $answer);
        $this->assertSame(15, substr_count($modal->html(), 'role="switch"'));
        $modal->call('$set', 'mostrarModalInspeccion', false);
        $this->assertSame($before, Inspection::firstOrFail()->getAttributes());
    }

    public function test_the_same_defaults_apply_without_a_precreated_inspection(): void
    {
        Inspection::query()->delete();
        $modal = Livewire::test(InspeccionMezcla::class)->call('abrirModalInspeccion', 1);
        foreach (self::ANSWERS as $field => $answer) $modal->assertSet($field, (bool) $answer);
        $this->assertSame(0, Inspection::count());
    }

    public function test_previously_saved_answers_are_not_replaced_with_defaults(): void
    {
        $answers = array_map(fn ($value) => ! $value, self::ANSWERS);
        Inspection::firstOrFail()->update(['reviso_nombre' => 'Inspector anterior', ...$answers]);
        $modal = Livewire::test(InspeccionMezcla::class)->call('abrirModalInspeccion', 1);
        foreach ($answers as $field => $answer) $modal->assertSet($field, $answer);
    }

    public function test_user_changes_survive_validation_and_save_as_boolean_answers(): void
    {
        $answers = array_map(fn ($value) => ! $value, self::ANSWERS);
        $modal = Livewire::test(InspeccionMezcla::class)->call('abrirModalInspeccion', 1)->set($answers)
            ->call('guardarInspeccion')->assertHasErrors(['dosis_volumen', 'peso_mezcla', 'aprobo_nombre']);
        foreach ($answers as $field => $answer) $modal->assertSet($field, $answer);
        $modal->set('dosis_volumen', 250)->set('peso_mezcla', 260)->set('aprobo_nombre', 'gcortes')
            ->call('guardarInspeccion')->assertHasNoErrors();
        $saved = Inspection::firstOrFail();
        foreach ($answers as $field => $answer) $this->assertSame($answer, $saved->{$field});
        $modal->call('abrirModalInspeccion', 1);
        foreach ($answers as $field => $answer) $modal->assertSet($field, $answer);
    }

    public function test_canceling_a_rejection_keeps_the_switches_and_rejection_archives_the_answers(): void
    {
        $modal = Livewire::test(InspeccionMezcla::class)->call('abrirModalInspeccion', 1)
            ->set('esta_rotulado', false)->set('presenta_fugas', true)
            ->call('rechazarInspeccion')->call('cancelarRechazo')
            ->assertSet('esta_rotulado', false)->assertSet('presenta_fugas', true)
            ->call('rechazarInspeccion')->set('motivoRechazo', 'Fuga de prueba')
            ->call('guardarRechazo')->assertHasNoErrors();
        $snapshot = InspectionWaste::firstOrFail()->snapshot['inspection'];
        $this->assertFalse($snapshot['esta_rotulado']);
        $this->assertTrue($snapshot['presenta_fugas']);
        $this->assertSame('', Inspection::firstOrFail()->reviso_nombre);
        DB::table('mezclas')->where('id', 1)->update(['estado' => 'preparada']);
        $modal->call('abrirModalInspeccion', 1);
        foreach (self::ANSWERS as $field => $answer) $modal->assertSet($field, (bool) $answer);
    }
}
