<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\Oncologicos\MezclaController;
use App\Livewire\Oncologicos\InspeccionMezcla;
use App\Models\InspectionWaste;
use App\Models\Oncologicos\Mezcla;
use App\Services\MedicineRemainderService;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\Fixtures\InspectionWorkflow;
use Tests\TestCase;

class InspectionRejectionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        InspectionWorkflow::seed();
    }

    private function modal()
    {
        return Livewire::test(InspeccionMezcla::class)->call('abrirModalInspeccion', 1);
    }

    public function test_rejection_archives_the_attempt_and_restarts_without_returning_inventory(): void
    {
        foreach (['oncologicos', 'antibioticos'] as $category) {
            DB::table('solicitud_oncos')->where('id', 1)->update(['tipo_solicitud' => $category]);
            DB::table('mezclas')->where('id', 1)->update(['estado' => 'preparada']);
            $rejectedLot = Mezcla::findOrFail(1)->lote;
            $modal = $this->modal()->assertSee('Rechazada')->assertSee('Aprobada')->assertDontSee('Guardar Inspeccion');
            $modal->call('rechazarInspeccion')->set('motivoRechazo', 'Fuga detectada')->call('guardarRechazo')->assertHasNoErrors()
                ->assertSet('mostrarModalInspeccion', false)->assertSet('rechazoGuardado', true)
                ->assertNotDispatched('mezcla-inspeccionada')->assertNoRedirect();
            $mixture = Mezcla::findOrFail(1);
            $this->assertSame('aprobada', $mixture->estado);
            $this->assertTrue($mixture->requires_redispensing);
            $this->assertSame('aprobada', $mixture->solicitud->estado);
            $waste = InspectionWaste::latest('id')->firstOrFail();
            $this->assertSame('Fuga detectada', $waste->reason);
            $this->assertSame($rejectedLot, $waste->snapshot['mixture']['lote']);
            $this->assertNotSame($rejectedLot, $mixture->lote);
            $this->assertSame($category, $waste->snapshot['category']);
            $this->assertSame('gcortes', $waste->snapshot['reviewer']);
            $this->assertSame('Central de prueba', $waste->snapshot['laboratory']);
            $this->assertEquals(8, DB::table('medicine_batches')->value('stock_actual'));
            $this->assertEquals(90, $waste->snapshot['medications'][0]['dosis']);
            $this->assertFalse($waste->snapshot['inspection']['mezcla_aprobada']);
            $this->assertNull($mixture->diluent_presentation_id);
            $this->assertSame(0, DB::table('mezcla_medicamento_presentaciones')->count());
            $this->assertEquals(90, $mixture->medicamentos->first()->dosis);
            $this->assertEmpty($mixture->inspeccion->preparo_nombre);
            $this->assertSame('Validador original', $mixture->inspeccion->valido_nombre);

            // Duplicate requests cannot record another waste or approve this attempt.
            $modal->call('guardarRechazo')->assertHasErrors('mezclaId');
            $this->assertSame($mixture->production_attempt - 1, InspectionWaste::count());

            // The normal edit rollback must no longer touch rejected material.
            $frozen = DB::table('medicine_remainders')->get()->toJson();
            DB::transaction(fn () => app(MedicineRemainderService::class)->rollbackReference('mezcla', 1, 1));
            $this->assertSame($frozen, DB::table('medicine_remainders')->get()->toJson());
            $this->assertEquals(5, DB::table('medicine_remainders')->value('current_ml'));
        }
        $this->assertSame(2, InspectionWaste::count());
        $this->assertSame('MED-01', InspectionWaste::first()->snapshot['medications'][0]['presentaciones_usadas'][0]['lote_usado']);
        $this->assertSame('inspection_waste', DB::table('medicine_batch_movements')->value('reference_type'));
        $this->assertSame('salida', DB::table('medicine_batch_movements')->value('movement_type'));
    }

    public function test_redispensing_consumes_a_new_diluent_without_reusing_the_rejected_charge(): void
    {
        $this->modal()->set('motivoRechazo', 'Contenedor roto')->call('guardarRechazo')->assertHasNoErrors();
        $consume = new \ReflectionMethod(MezclaController::class, 'consumeDiluentPresentationForMix');
        DB::transaction(fn () => $consume->invoke(app(MezclaController::class), 1, 1, 1, 1));
        $this->assertEquals(8, DB::table('diluent_presentations')->value('stock_actual'));
        $this->assertSame(1, DB::table('diluent_stock_movements')->where('reference_type', 'inspection_waste')->count());
        $this->assertSame(1, DB::table('diluent_stock_movements')->where('reference_type', 'mezcla')->count());
        DB::transaction(fn () => $consume->invoke(app(MezclaController::class), 1, 1, 1, 1));
        $this->assertEquals(8, DB::table('diluent_presentations')->value('stock_actual'));
    }

    public function test_all_three_next_process_actions_are_red_for_a_previously_rejected_mixture(): void
    {
        foreach (['oncologicos', 'antibioticos'] as $category) {
            DB::table('solicitud_oncos')->where('id', 1)->update(['tipo_solicitud' => $category]);
            foreach (['aprobada' => 'Dispensar', 'dispensada' => 'Preparar', 'preparada' => 'Inspeccionar'] as $status => $action) {
                foreach ([1 => false, 2 => true] as $attempt => $red) {
                    DB::table('mezclas')->where('id', 1)->update(['estado' => $status, 'production_attempt' => $attempt]);
                    DB::table('solicitud_oncos')->where('id', 1)->update(['estado' => $status]);
                    $html = view('livewire.oncologicos.solicitudes-table', [
                        'mezclas' => Mezcla::with('solicitud.hospital.instituciones')->paginate(10),
                        'sortField' => 'id', 'sortDirection' => 'desc',
                    ])->render();
                    $document = new \DOMDocument();
                    @$document->loadHTML('<?xml encoding="UTF-8">'.$html);
                    $selector = $status === 'aprobada' ? '//a[@data-dispensing-popup]' : '//button[normalize-space(.)="'.$action.'"]';
                    $link = (new \DOMXPath($document))->query($selector)->item(0);
                    $this->assertNotNull($link);
                    $this->assertSame($action, trim($link->textContent));
                    $this->assertSame($red, str_contains($link->getAttribute('class'), 'bg-red-600'));
                    $this->assertSame(! $red, str_contains($link->getAttribute('class'), 'bg-yellow-400'));
                    $this->assertStringContainsString($red || $action !== 'Preparar' ? 'text-white' : 'text-gray-900', $link->getAttribute('class'));
                    if ($status === 'aprobada') {
                        $this->assertStringContainsString('modo=dispensacion', $link->getAttribute('href'));
                    } elseif ($status === 'preparada') {
                        $this->assertStringContainsString('abrir-modal-inspeccion', $link->getAttribute('onclick'));
                    } else {
                        $this->assertSame('preparada', (new \DOMXPath($document))->query('//input[@name="accion"]')->item(0)->getAttribute('value'));
                    }
                }
            }
        }
    }

    public function test_the_unified_list_loads_the_rejection_flag_through_all_three_stages(): void
    {
        $user = \App\Models\User::forceCreate(['name' => 'Administrador', 'username' => 'admin-list']);
        $user->assignRole(\Spatie\Permission\Models\Role::create(['name' => 'Admin', 'guard_name' => 'web']));
        $user->givePermissionTo(\Spatie\Permission\Models\Permission::create(['name' => 'oncologicos_solicitudes_index', 'guard_name' => 'web']));
        $this->actingAs($user);
        $request = \Illuminate\Http\Request::create(route('admin.solicitudes.index'));
        $request->setUserResolver(fn () => $user);

        foreach (['oncologicos', 'antibioticos'] as $category) {
            DB::table('solicitud_oncos')->where('id', 1)->update(['tipo_solicitud' => $category]);
            foreach (['aprobada', 'dispensada', 'preparada'] as $status) {
                foreach ([1 => false, 2 => true] as $attempt => $rejected) {
                    DB::table('mezclas')->where('id', 1)->update(['estado' => $status, 'production_attempt' => $attempt]);
                    $data = app(\App\Http\Controllers\Admin\UnifiedSolicitudController::class)->index($request)->getData();
                    $row = $data['requests']->firstWhere('id', 1);
                    $this->assertNotNull($row);
                    $this->assertSame($status, $row['status']);
                    $this->assertSame($category, $row['type']);
                    $this->assertSame($attempt, $row['mixture']->production_attempt);
                    $this->assertSame($rejected, $row['mixture']->has_inspection_rejection);
                }
            }
        }
    }

    public function test_approval_explicitly_approves_and_keeps_existing_validation(): void
    {
        $modal = $this->modal()->call('guardarInspeccion')->assertHasErrors(['dosis_volumen', 'peso_mezcla', 'aprobo_nombre']);
        $this->assertSame('preparada', Mezcla::find(1)->estado);
        $modal->set('dosis_volumen', 250)->set('peso_mezcla', 260)->set('aprobo_nombre', 'gcortes')
            ->call('guardarInspeccion')->assertHasNoErrors()->assertDispatched('mezcla-inspeccionada');
        $this->assertSame('revisada', Mezcla::find(1)->estado);
        $this->assertTrue(Mezcla::find(1)->inspeccion->mezcla_aprobada);
        $this->assertSame(0, InspectionWaste::count());
    }

    public function test_rejection_requires_a_reason_and_a_prepared_mixture(): void
    {
        $modal = $this->modal();
        foreach (['', '   ', 'N.A.', 'n/a', str_repeat('a', 2001)] as $reason) {
            $modal->set('motivoRechazo', $reason)->call('guardarRechazo')->assertHasErrors('motivoRechazo');
        }
        foreach (['pendiente', 'aprobada', 'dispensada', 'revisada', 'entregada', 'cancelada'] as $status) {
            DB::table('mezclas')->where('id', 1)->update(['estado' => $status]);
            $modal->set('motivoRechazo', 'Fuga')->call('guardarRechazo')->assertHasErrors('mezclaId');
            $this->assertSame($status, Mezcla::find(1)->estado);
        }
        $this->assertSame(0, InspectionWaste::count());
        $this->assertSame(1, DB::table('mezcla_medicamento_presentaciones')->count());
    }

    public function test_a_stale_modal_cannot_reject_or_approve_a_later_preparation(): void
    {
        $stale = $this->modal()->set('motivoRechazo', 'Fuga');
        $this->modal()->set('motivoRechazo', 'Rotura')->call('guardarRechazo')->assertHasNoErrors();
        DB::table('mezclas')->where('id', 1)->update(['estado' => 'preparada']);
        $stale->call('guardarRechazo')->assertHasErrors('mezclaId');
        $stale->set('dosis_volumen', 250)->set('peso_mezcla', 260)->set('aprobo_nombre', 'gcortes')
            ->call('guardarInspeccion')->assertHasErrors('mezclaId');
        $this->assertSame(1, InspectionWaste::count());
        $this->assertSame('preparada', Mezcla::find(1)->estado);
        $this->modal()->set('dosis_volumen', 250)->set('peso_mezcla', 260)->set('aprobo_nombre', 'gcortes')
            ->call('guardarInspeccion')->assertHasNoErrors();
        $this->assertSame('revisada', Mezcla::find(1)->estado);
        $this->assertFalse(Mezcla::find(1)->requires_redispensing);
        $this->assertSame(1, InspectionWaste::count());
    }

    public function test_rejection_rolls_back_if_the_audit_cannot_be_recorded(): void
    {
        InspectionWaste::creating(fn () => throw new \RuntimeException('Simulated audit failure'));
        try {
            $this->modal()->set('motivoRechazo', 'Fuga')->call('guardarRechazo');
            $this->fail('Expected audit failure.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Simulated audit failure', $exception->getMessage());
        } finally {
            InspectionWaste::flushEventListeners();
        }
        $this->assertSame('preparada', Mezcla::find(1)->estado);
        $this->assertSame(1, Mezcla::find(1)->production_attempt);
        $this->assertSame('', Mezcla::find(1)->inspeccion->reviso_nombre);
        $this->assertSame(1, DB::table('mezcla_medicamento_presentaciones')->count());
        $this->assertSame('mezcla', DB::table('diluent_stock_movements')->value('reference_type'));
    }

    public function test_a_user_without_workflow_permission_cannot_submit_an_inspection(): void
    {
        $unauthorized = \App\Models\User::forceCreate(['name' => 'Sin permiso', 'username' => 'no-permission', 'hospital_id' => 1]);
        $this->actingAs($unauthorized);
        $this->modal()->set('motivoRechazo', 'Fuga')->call('guardarRechazo')->assertForbidden();
        $this->assertSame(0, InspectionWaste::count());
        $this->assertSame('preparada', Mezcla::find(1)->estado);
    }

    public function test_cancel_returns_to_inspection_without_saving_or_losing_form_values(): void
    {
        $this->modal()->set('observaciones', 'Observacion de la inspeccion')
            ->set('tipo_contenedor', 'Bolsa')->set('presenta_fugas', true)->set('peso_mezcla', 260)
            ->call('rechazarInspeccion')->assertSet('mostrarModalRechazo', true)
            ->assertSee('Motivo del rechazo')->assertSee('MEZCLA-01')->assertSee('Guardar')
            ->assertDontSee('wire:confirm', false)->assertNoRedirect()
            ->set('motivoRechazo', 'Motivo sin guardar')->call('cancelarRechazo')
            ->assertSet('mostrarModalRechazo', false)->assertSet('mostrarModalInspeccion', true)
            ->assertSet('rechazoGuardado', false)->assertSet('observaciones', 'Observacion de la inspeccion')
            ->assertSet('tipo_contenedor', 'Bolsa')->assertSet('presenta_fugas', true)->assertSet('peso_mezcla', 260)
            ->assertSee('Aprobada')->assertNoRedirect()->assertNotDispatched('mezcla-inspeccionada');
        $this->assertSame(0, InspectionWaste::count());
        $this->assertSame('MEZCLA-01', Mezcla::find(1)->lote);
        $this->assertSame('preparada', Mezcla::find(1)->estado);
        $this->assertSame(1, Mezcla::find(1)->production_attempt);
    }

    public function test_saved_reason_and_lot_are_reported_and_ok_returns_to_the_request_list(): void
    {
        $modal = $this->modal()->set('observaciones', 'Observaciones originales')
            ->call('rechazarInspeccion')->set('motivoRechazo', '  Fuga en el contenedor  ')
            ->call('guardarRechazo')->assertHasNoErrors()->assertSet('mostrarModalRechazo', false)
            ->assertSet('rechazoGuardado', true)->assertSee('Éxito')->assertSee('OK')
            ->assertDontSee('Motivo del rechazo')->assertNoRedirect()->assertNotDispatched('mezcla-inspeccionada');
        $waste = InspectionWaste::firstOrFail();
        $this->assertSame('Fuga en el contenedor', $waste->reason);
        $this->assertSame('Observaciones originales', $waste->snapshot['inspection']['observaciones']);
        $this->assertSame('MEZCLA-01', $waste->snapshot['mixture']['lote']);
        $map = new \ReflectionMethod(\App\Http\Controllers\Admin\SuperAdministratorController::class, 'mapInspectionWaste');
        $record = $map->invoke(app(\App\Http\Controllers\Admin\SuperAdministratorController::class), $waste);
        $this->assertSame('MEZCLA-01', $record['lot']);
        $this->assertSame('Fuga en el contenedor', $record['reason']);
        $this->assertSame('inspeccion', $record['type']);
        $modal->call('volverASolicitudes')->assertRedirect(route('admin.solicitudes.index'));
    }

    public function test_lot_failure_rolls_back_rejection_and_inventory_together(): void
    {
        $this->mock(\App\Services\MixtureLotService::class)->shouldReceive('next')->once()
            ->andThrow(new \RuntimeException('Lot allocation failed'));
        try {
            $this->modal()->set('motivoRechazo', 'Fuga')->call('guardarRechazo');
            $this->fail('Expected lot failure.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Lot allocation failed', $exception->getMessage());
        }
        $this->assertSame(0, InspectionWaste::count());
        $this->assertSame('MEZCLA-01', Mezcla::find(1)->lote);
        $this->assertSame(1, Mezcla::find(1)->production_attempt);
        $this->assertSame('preparada', Mezcla::find(1)->estado);
        $this->assertSame('Preparador original', Mezcla::find(1)->inspeccion->preparo_nombre);
        $this->assertSame(1, DB::table('mezcla_medicamento_presentaciones')->count());
        $this->assertSame('mezcla', DB::table('diluent_stock_movements')->value('reference_type'));
    }
}
