<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\Oncologicos\MezclaController;
use App\Models\Oncologicos\Mezcla;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Tests\Fixtures\PreparationWorkflow;
use Tests\TestCase;

class MixturePreparationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        PreparationWorkflow::seed();
    }

    public function test_return_urls_do_not_duplicate_the_application_subdirectory(): void
    {
        URL::forceRootUrl('http://localhost/mezclaspro/public');
        $redirect = new \ReflectionMethod(MezclaController::class, 'redirectAfterMixtureAction');
        foreach ([
            'http://localhost/mezclaspro/public/admin/solicitudes?status=preparacion',
            '/mezclaspro/public/admin/solicitudes?status=preparacion',
            '/admin/solicitudes?status=preparacion',
        ] as $returnTo) {
            $request = Request::create('http://localhost/mezclaspro/public/admin/oncologicos/mezclas/1', 'PUT', [
                'accion' => 'preparada', 'return_to' => $returnTo,
            ]);
            $response = $redirect->invoke(app(MezclaController::class), Mezcla::findOrFail(1), $request);
            $this->assertSame('http://localhost/mezclaspro/public/admin/solicitudes?status=preparacion', $response->getTargetUrl());
        }
    }

    public function test_preparation_saves_once_and_returns_to_the_list_with_a_success_dialog(): void
    {
        foreach (['oncologicos', 'antibioticos'] as $category) {
            DB::table('solicitud_oncos')->where('id', 1)->update(['tipo_solicitud' => $category, 'estado' => 'dispensada']);
            DB::table('mezclas')->where('id', 1)->update(['estado' => 'dispensada']);
            $returnTo = route('admin.solicitudes.index', ['status' => 'preparacion']);
            $response = $this->post(route('admin.oncologicos.mezclas.update', 1), [
                '_method' => 'PUT', 'accion' => 'preparada', 'return_to' => $returnTo,
            ]);
            $response->assertRedirect($returnTo)->assertSessionHasNoErrors()
                ->assertSessionHas('swal.title', 'Éxito')
                ->assertSessionHas('swal.icon', 'success')
                ->assertSessionHas('swal.confirmButtonText', 'OK')
                ->assertSessionHas('swal.showCancelButton', false)
                ->assertSessionHas('swal.allowOutsideClick', false);
            $mixture = Mezcla::findOrFail(1);
            $this->assertSame('preparada', $mixture->estado);
            $this->assertSame('MEZCLA-01', $mixture->lote);
            $this->assertTrue($mixture->has_inspection_rejection);
            $this->assertSame('gcortes', $mixture->inspeccion->preparo_nombre);
            $this->assertEquals(8, DB::table('medicine_batches')->value('stock_actual'));
            $this->assertEquals(9, DB::table('diluent_presentations')->value('stock_actual'));
            $this->assertSame(1, DB::table('inspeccion_mezclas')->count());
            $this->app['session']->forget(['swal', 'success']);
            $this->from($returnTo)->post(route('admin.oncologicos.mezclas.update', 1), [
                '_method' => 'PUT', 'accion' => 'preparada', 'return_to' => $returnTo,
            ])->assertSessionHasErrors('error')->assertSessionMissing('swal');
        }
    }

    public function test_preparation_falls_back_to_the_request_list_for_an_unsafe_return_url(): void
    {
        $this->post(route('admin.oncologicos.mezclas.update', 1), [
            '_method' => 'PUT', 'accion' => 'preparada', 'return_to' => 'https://example.org/admin/solicitudes',
        ])->assertRedirect(route('admin.solicitudes.index'))->assertSessionHas('swal.icon', 'success');
    }
}
