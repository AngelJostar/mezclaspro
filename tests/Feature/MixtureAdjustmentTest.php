<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\MixtureAdjustmentController;
use App\Http\Controllers\Admin\Oncologicos\MezclaController;
use App\Models\MixtureAdjustment;
use App\Models\Nutricionales\Solicitud;
use App\Models\Oncologicos\Mezcla;
use App\Models\User;
use App\Services\MixtureAdjustmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\Fixtures\MixtureAdjustments;
use Tests\TestCase;

class MixtureAdjustmentTest extends TestCase
{
    private User $central;
    private User $hospital;
    private MixtureAdjustmentService $service;

    protected function setUp(): void
    {
        parent::setUp();
        [$this->central, $this->hospital] = MixtureAdjustments::seed();
        $this->service = app(MixtureAdjustmentService::class);
    }

    public static function kinds(): array
    {
        return [['nutricionales'], ['oncologicos'], ['antibioticos']];
    }

    private function target(string $kind)
    {
        DB::table('solicitud_oncos')->where('id', 1)->update(['tipo_solicitud' => $kind]);
        return $kind === 'nutricionales' ? Solicitud::findOrFail(1) : Mezcla::findOrFail(1);
    }

    #[DataProvider('kinds')]
    public function test_proposal_is_versioned_and_not_applied_before_hospital_authorization(string $kind): void
    {
        $target = $this->target($kind);
        $before = $this->service->fingerprint($target);
        $adjustment = $this->service->requestAdjustment($target, MixtureAdjustments::request($this->central, $kind));
        $this->assertSame('requested', $adjustment->status);
        $this->assertSame($before, $this->service->fingerprint($target->fresh()));
        $this->assertSame('pendiente', $target->fresh()->estado);
        $this->assertNotEmpty(array_filter($adjustment->review, fn ($field) => $field['changed']));
        $this->assertDatabaseHas('notifications', ['notifiable_id' => $this->hospital->id]);
        $this->assertEquals(1, $this->hospital->fresh()->notification);
        $this->assertDatabaseCount('medicine_batch_movements', 0);
        $this->assertStringNotContainsString('Nombre falso', json_encode($adjustment->review));

        $this->service->authorize($adjustment, $this->hospital, 'Autorizado por hospital');
        $this->assertSame('authorized', $adjustment->fresh()->status);
        $this->assertSame('pendiente', $target->fresh()->estado);
        $this->assertSame($before, $this->service->fingerprint($target->fresh()));
        $this->expectException(ValidationException::class);
        $this->service->assertWritable($target->fresh(), MixtureAdjustments::request($this->central, $kind));
    }

    #[DataProvider('kinds')]
    public function test_holds_block_approval_and_process_transitions_at_model_and_controller_boundaries(string $kind): void
    {
        $target = $this->target($kind);
        $this->service->requestAdjustment($target, MixtureAdjustments::request($this->central, $kind));
        foreach (['aprobada', 'dispensada', 'preparada', 'revisada', 'entregada'] as $state) {
            try {
                $target->fresh()->forceFill(['estado' => $state])->save();
                $this->fail('A pending adjustment must block '.$state);
            } catch (ValidationException $e) {
                $this->assertStringContainsString('hospital', $e->getMessage());
            }
        }
        $request = MixtureAdjustments::request($this->central, $kind);
        $request->merge(['accion' => 'aprobar', 'authorized_adjustment_id' => $target->fresh()->adjustment_id]);
        $this->expectException(ValidationException::class);
        $this->service->assertWritable($target->fresh(), $request);
    }

    public function test_hospital_cannot_authorize_another_hospital_or_approve_at_the_central(): void
    {
        $adjustment = $this->service->requestAdjustment(Mezcla::find(1), MixtureAdjustments::request($this->central));
        $this->hospital->hospital_id = 2;
        try {
            $this->service->authorize($adjustment, $this->hospital);
            $this->fail('Expected denied authorization');
        } catch (HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());
        }
        $this->assertFalse($this->service->canView($this->hospital, Mezcla::find(1)));
        $this->expectException(HttpException::class);
        $this->service->assertCentral($this->hospital, Mezcla::find(1));
    }

    public function test_stale_versions_cannot_be_authorized_and_cancellation_allows_a_new_version(): void
    {
        $target = Mezcla::find(1);
        $adjustment = $this->service->requestAdjustment($target, MixtureAdjustments::request($this->central));
        DB::table('mezclas')->where('id', 1)->update(['volumen_dilucion' => 275]);
        try {
            $this->service->authorize($adjustment, $this->hospital);
            $this->fail('Expected stale version rejection');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('cambio', $e->getMessage());
        }
        $this->service->cancel($adjustment, $this->central);
        $new = $this->service->requestAdjustment($target, MixtureAdjustments::request($this->central));
        $this->assertNotSame($new->id, $adjustment->id);
        $this->assertSame('cancelled', $adjustment->fresh()->status);
        $this->expectException(HttpException::class);
        $this->service->authorize($adjustment, $this->hospital);
    }

    public function test_declined_adjustment_keeps_hold_and_central_can_reject(): void
    {
        $adjustment = $this->service->requestAdjustment(Mezcla::find(1), MixtureAdjustments::request($this->central));
        $this->service->authorize($adjustment, $this->hospital, 'No autorizado por hospital', false);
        $this->assertSame('declined', $adjustment->fresh()->status);
        $this->assertSame('pendiente', Mezcla::find(1)->estado);
        $this->service->reject($adjustment, $this->central, 'Rechazo de la central');
        $this->assertSame('cancelada', Mezcla::find(1)->estado);
        $this->assertSame('rejected', $adjustment->fresh()->status);
        $this->assertDatabaseCount('medicine_batch_movements', 0);
    }

    public function test_final_approval_applies_only_the_authorized_payload_and_retains_audit(): void
    {
        $adjustment = $this->service->requestAdjustment(Mezcla::find(1), MixtureAdjustments::request($this->central));
        $this->service->authorize($adjustment, $this->hospital);
        $request = MixtureAdjustments::request($this->central);
        $request->merge(['paciente_nombre' => 'UNAUTHORIZED OVERRIDE', 'mezcla_json' => '{}']);
        app(MixtureAdjustmentController::class)->approve($request, $adjustment, $this->service);
        $this->assertSame('approved', $adjustment->fresh()->status, json_encode(session('errors')?->getMessages()));
        $this->assertSame('aprobada', Mezcla::find(1)->estado);
        $this->assertEquals(300, Mezcla::find(1)->volumen_dilucion);
        $this->assertDatabaseHas('mezcla_medicamentos', ['mezcla_id' => 1, 'dosis' => 100]);
        $this->assertDatabaseHas('solicitud_oncos', ['id' => 1, 'nombre_paciente' => 'Paciente de prueba']);
        $this->assertEquals($this->central->id, $adjustment->fresh()->approved_by);
        $this->assertNotNull($adjustment->fresh()->approved_at);
        $this->assertDatabaseCount('medicine_batch_movements', 0);
        $this->expectException(HttpException::class);
        app(MixtureAdjustmentController::class)->approve($request, $adjustment, $this->service);
    }

    public function test_direct_approval_and_rejection_do_not_create_adjustments(): void
    {
        $request = MixtureAdjustments::request($this->central);
        $request->merge(['accion' => 'aprobar']);
        app(MezclaController::class)->update($request, 1);
        $this->assertSame('aprobada', Mezcla::find(1)->estado, json_encode(session('errors')?->getMessages()));
        $this->assertDatabaseCount('mixture_adjustments', 0);
    }

    public function test_nutrition_controller_preserves_original_until_final_approval(): void
    {
        MixtureAdjustments::nutritionSchema();
        $request = MixtureAdjustments::request($this->central, 'nutricionales');
        app(\App\Http\Controllers\Admin\Nutricionales\SolicitudController::class)->update($request, Solicitud::find(1));
        $adjustment = MixtureAdjustment::first();
        $this->assertNotNull($adjustment, json_encode(session('errors')?->getMessages()));
        $this->assertDatabaseHas('solicitud_details', ['id' => 1, 'volumen_total' => 100]);
        $this->service->authorize($adjustment, $this->hospital);
        app(MixtureAdjustmentController::class)->approve($request, $adjustment, $this->service);
        $this->assertSame('approved', $adjustment->fresh()->status, json_encode(session('errors')?->getMessages()));
        $this->assertSame('aprobada', Solicitud::find(1)->estado);
        $this->assertDatabaseHas('solicitud_details', ['id' => 1, 'volumen_total' => 150]);
        $this->assertDatabaseHas('solicitud_inputs', ['solicitud_id' => 1, 'input_id' => 1, 'valor' => 25]);
    }

    public function test_failed_final_validation_rolls_back_formula_and_keeps_authorization(): void
    {
        $adjustment = $this->service->requestAdjustment(Mezcla::find(1), MixtureAdjustments::request($this->central));
        $before = $this->service->fingerprint(Mezcla::find(1));
        $this->service->authorize($adjustment, $this->hospital);
        DB::table('medicines_catalog')->where('id', 1)->update(['conc_min' => 10, 'conc_max' => 20]);
        app(MixtureAdjustmentController::class)->approve(MixtureAdjustments::request($this->central), $adjustment, $this->service);
        $this->assertSame('authorized', $adjustment->fresh()->status);
        $this->assertSame($before, $this->service->fingerprint(Mezcla::find(1)));
        $this->assertNull($adjustment->fresh()->approved_at);
        $this->assertDatabaseCount('medicine_batch_movements', 0);
    }

    public function test_approved_formula_cannot_be_changed_during_dispensing(): void
    {
        $adjustment = $this->service->requestAdjustment(Mezcla::find(1), MixtureAdjustments::request($this->central));
        $this->service->authorize($adjustment, $this->hospital);
        app(MixtureAdjustmentController::class)->approve(MixtureAdjustments::request($this->central), $adjustment, $this->service);
        $request = MixtureAdjustments::request($this->central);
        $request->merge(['accion' => 'dispensar', 'mezcla_json' => $adjustment->fresh()->proposal['mezcla_json']]);
        $this->service->assertWritable(Mezcla::find(1), $request);
        $mix = json_decode($request->input('mezcla_json'), true);
        $mix['medicamentos'][0]['dosis'] = 999;
        $request->merge(['mezcla_json' => json_encode($mix)]);
        $this->expectException(ValidationException::class);
        $this->service->assertWritable(Mezcla::find(1), $request);
    }

    public function test_requests_without_an_active_hospital_recipient_are_not_left_waiting(): void
    {
        $this->hospital->update(['is_active' => false]);
        try {
            $this->service->requestAdjustment(Mezcla::find(1), MixtureAdjustments::request($this->central));
            $this->fail('Expected missing recipient validation');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('usuario activo', $e->getMessage());
        }
        $this->assertDatabaseCount('mixture_adjustments', 0);
        $this->assertNull(Mezcla::find(1)->adjustment_id);
    }

    public function test_direct_rejection_keeps_no_adjustments_and_blocked_process(): void
    {
        $request = MixtureAdjustments::request($this->central);
        $request->merge(['accion' => 'rechazar']);
        app(MezclaController::class)->update($request, 1);
        $this->assertSame('cancelada', Mezcla::find(1)->estado);
        $this->assertDatabaseCount('mixture_adjustments', 0);
        $this->assertDatabaseCount('medicine_batch_movements', 0);
    }
}
