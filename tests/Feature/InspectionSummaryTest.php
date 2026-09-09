<?php

namespace Tests\Feature;

use App\Livewire\Oncologicos\InspeccionMezcla;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;
use Tests\Fixtures\InspectionSummary;
use Tests\TestCase;

class InspectionSummaryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        InspectionSummary::seed();
    }

    public function test_it_loads_the_selected_patient_and_all_medications_without_writing_data(): void
    {
        DB::enableQueryLog();
        $modal = Livewire::test(InspeccionMezcla::class)->call('abrirModalInspeccion', [1])
            ->assertSet('inspectionSummary.patient.name', 'Paciente de prueba con nombre y apellidos extensos')
            ->assertSet('inspectionSummary.patient.record', 'EXP-12345')
            ->assertSet('inspectionSummary.patient.sex', 'Femenino')
            ->assertSet('inspectionSummary.patient.age', '42 años')
            ->assertSet('inspectionSummary.patient.weight', '64.50 kg')
            ->assertSet('inspectionSummary.patient.service', 'Servicio de prueba')
            ->assertSet('inspectionSummary.patient.location', '2 / 205-B')
            ->assertSet('inspectionSummary.patient.doctor', 'Nombre del medico de prueba')
            ->assertSet('inspectionSummary.type', 'Oncológica')
            ->assertSet('inspectionSummary.medications.0.dose', '90.00 mg')
            ->assertSet('inspectionSummary.medications.0.volume', '45.00 mL')
            ->assertSet('inspectionSummary.medications.0.diluent', 'Diluyente de prueba')
            ->assertSet('inspectionSummary.medications.0.route', 'INTRAVENOSA')
            ->assertSet('inspectionSummary.medications.1.name', 'Segundo medicamento de prueba')
            ->assertSet('inspectionSummary.medications.1.volume', '12.50 mL')
            ->assertSet('inspectionSummary.medications.1.diluent', 'Diluyente de prueba')
            ->assertSet('inspectionSummary.total_volume', '250.00 mL')
            ->assertSee('Institución: Institucion de prueba | Hospital: Hospital de prueba')
            ->assertSee('MEZCLA-01')->assertSee('Datos del paciente')->assertSee('Información de la mezcla');
        $queries = DB::getQueryLog();
        DB::disableQueryLog();
        foreach ($queries as $query) {
            $this->assertDoesNotMatchRegularExpression('/^\s*(insert|update|delete|replace)\b/i', $query['query']);
        }
        $this->assertCount(2, $modal->get('inspectionSummary.medications'));
        $this->assertSame(1, substr_count($modal->html(), '250.00 mL'));
        $this->assertStringContainsString('rowspan="2"', $modal->html());
    }

    public function test_opening_another_mixture_resets_unsaved_fields_and_missing_summary_values(): void
    {
        DB::table('solicitud_oncos')->insert(['id' => 2, 'hospital_id' => 1, 'tipo_solicitud' => 'antibioticos']);
        DB::table('mezclas')->insert(['id' => 2, 'solicitud_id' => 2, 'lote' => 'OTRO-LOTE', 'estado' => 'preparada']);
        Livewire::test(InspeccionMezcla::class)->call('abrirModalInspeccion', 1)
            ->set('esta_rotulado', true)->set('observaciones', 'No trasladar')->set('peso_mezcla', 260)
            ->set('aprobo_nombre', 'gcortes')->call('abrirModalInspeccion', 2)
            ->assertSet('inspectionSummary.type', 'Antibiótica')
            ->assertSet('inspectionSummary.patient.name', '—')->assertSet('inspectionSummary.patient.weight', '—')
            ->assertSet('inspectionSummary.total_volume', '—')->assertSet('inspectionSummary.medications', [])
            ->assertSet('esta_rotulado', true)->assertSet('observaciones', 'N.A.')
            ->assertSet('peso_mezcla', null)->assertSet('aprobo_nombre', '')
            ->assertSee('OTRO-LOTE')->assertSee('No hay medicamentos registrados en esta mezcla.')
            ->assertDontSee('EXP-12345')->assertDontSee('MEZCLA-01');
    }

    public function test_unknown_volumes_are_not_inferred_or_replaced_with_zero(): void
    {
        DB::table('mezcla_medicamentos')->where('id', 1)->update(['dosis_ml' => null, 'dosis' => null]);
        DB::table('solicitud_oncos')->where('id', 1)->update(['edad' => 0, 'peso' => null]);
        Livewire::test(InspeccionMezcla::class)->call('abrirModalInspeccion', 1)
            ->assertSet('inspectionSummary.medications.0.volume', '—')
            ->assertSet('inspectionSummary.medications.0.dose', '—')
            ->assertSet('inspectionSummary.patient.age', '0 años')
            ->assertSet('inspectionSummary.patient.weight', '—');
    }

    public function test_patient_context_is_not_exposed_to_a_different_hospital(): void
    {
        $this->actingAs(User::forceCreate(['name' => 'Otro hospital', 'hospital_id' => 2]));
        Livewire::test(InspeccionMezcla::class)->call('abrirModalInspeccion', 1)->assertForbidden();
    }

    public function test_a_user_of_the_same_hospital_can_read_the_summary(): void
    {
        $this->actingAs(User::forceCreate(['name' => 'Mismo hospital', 'hospital_id' => 1]));
        Livewire::test(InspeccionMezcla::class)->call('abrirModalInspeccion', 1)->assertSee('EXP-12345');
    }

    public function test_the_readonly_summary_cannot_be_replaced_from_the_browser(): void
    {
        $this->expectException(CannotUpdateLockedPropertyException::class);
        Livewire::test(InspeccionMezcla::class)->call('abrirModalInspeccion', 1)
            ->set('inspectionSummary.patient.name', 'Otro paciente');
    }
}
